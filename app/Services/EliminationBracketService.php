<?php

namespace App\Services;

use App\Models\EliminationMatch;
use App\Models\Teams;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EliminationBracketService
{
    public function generate(int $tournamentId, string $category, string $bracket, string $format, int $userId): Collection
    {
        $teams = Teams::query()
            ->where('tournament_id', $tournamentId)
            ->where('team_category', $category)
            ->where('team_bracket', $bracket)
            ->orderBy('team_no')
            ->orderBy('id')
            ->get();

        if ($teams->count() < 2) {
            throw new \InvalidArgumentException('At least two teams are required to generate an elimination bracket.');
        }

        if ($format === 'double_elimination' && $teams->count() < 4) {
            throw new \InvalidArgumentException('Double elimination requires at least four teams.');
        }

        $existing = $this->scope($tournamentId, $category, $bracket)->get();
        if ($existing->contains(fn (EliminationMatch $match): bool => $match->is_finalized)) {
            throw new \LogicException('A bracket with finalized matches cannot be regenerated.');
        }

        return DB::transaction(function () use ($tournamentId, $category, $bracket, $format, $userId, $teams): Collection {
            $this->scope($tournamentId, $category, $bracket)->delete();

            $size = 1;
            while ($size < $teams->count()) {
                $size *= 2;
            }

            $winnersRounds = $this->createWinnersBracket($tournamentId, $category, $bracket, $format, $userId, $size, $teams);

            if ($format === 'double_elimination') {
                $this->createLosersBracket($tournamentId, $category, $bracket, $format, $userId, $winnersRounds, $size);
            }

            $this->resolvePendingMatches($tournamentId, $category, $bracket);

            return $this->scope($tournamentId, $category, $bracket)
                ->orderBy('bracket_side')
                ->orderBy('round_number')
                ->orderBy('match_number')
                ->get();
        });
    }

    public function finalize(EliminationMatch $match, int $team1Score, int $team2Score): void
    {
        if ($team1Score === $team2Score) {
            throw new \InvalidArgumentException('A finalized elimination match cannot end in a tie.');
        }

        if ($match->team1_id === null || $match->team2_id === null) {
            throw new \LogicException('Both teams must be assigned before finalizing this match.');
        }

        DB::transaction(function () use ($match, $team1Score, $team2Score): void {
            $match = EliminationMatch::query()->lockForUpdate()->findOrFail($match->id);
            $wasFinalized = $match->is_finalized;
            $previousWinnerId = $match->winner_id;
            $winnerId = $team1Score > $team2Score ? $match->team1_id : $match->team2_id;
            $loserId = $team1Score > $team2Score ? $match->team2_id : $match->team1_id;

            $match->update([
                'team1_score' => $team1Score,
                'team2_score' => $team2Score,
                'winner_id' => $winnerId,
                'loser_id' => $loserId,
                'is_finalized' => true,
            ]);

            if ($wasFinalized && (int) $previousWinnerId !== (int) $winnerId) {
                $this->clearDownstreamResults($match);
            }

            $this->resolvePendingMatches($match->tournament_id, $match->team_category, $match->team_bracket);
        });
    }

    private function clearDownstreamResults(EliminationMatch $sourceMatch): void
    {
        $pendingSourceIds = [$sourceMatch->id];

        while ($pendingSourceIds !== []) {
            $dependentMatches = EliminationMatch::query()
                ->where(function (Builder $query) use ($pendingSourceIds): void {
                    $query->whereIn('team1_source_match_id', $pendingSourceIds)
                        ->orWhereIn('team2_source_match_id', $pendingSourceIds);
                })
                ->get();

            $nextSourceIds = [];

            foreach ($dependentMatches as $dependentMatch) {
                $updates = [];

                foreach ([1, 2] as $slot) {
                    if (! in_array($dependentMatch->{'team'.$slot.'_source_match_id'}, $pendingSourceIds, true)) {
                        continue;
                    }

                    $updates['team'.$slot.'_id'] = null;
                }

                if ($dependentMatch->is_finalized || $dependentMatch->team1_score !== null || $dependentMatch->team2_score !== null) {
                    $updates['team1_score'] = null;
                    $updates['team2_score'] = null;
                    $updates['winner_id'] = null;
                    $updates['loser_id'] = null;
                    $updates['is_finalized'] = false;
                }

                $dependentMatch->update($updates);
                $nextSourceIds[] = $dependentMatch->id;
            }

            $pendingSourceIds = array_values(array_unique($nextSourceIds));
        }
    }

    public function resolvePendingMatches(int $tournamentId, string $category, string $bracket): void
    {
        do {
            $changed = false;

            foreach ($this->scope($tournamentId, $category, $bracket)->where('is_finalized', false)->get() as $match) {
                $updates = [];

                foreach ([1, 2] as $slot) {
                    $sourceId = $match->{'team'.$slot.'_source_match_id'};
                    $sourceResult = $match->{'team'.$slot.'_source_result'};

                    if ($sourceId === null) {
                        continue;
                    }

                    $source = EliminationMatch::find($sourceId);
                    if ($source?->is_finalized) {
                        $resolvedTeamId = $source->{$sourceResult.'_id'};

                        if ((int) $match->{'team'.$slot.'_id'} !== (int) $resolvedTeamId) {
                            $updates['team'.$slot.'_id'] = $resolvedTeamId;
                        }
                    }
                }

                if ($updates !== []) {
                    $match->update($updates);
                    $changed = true;
                }

                if ($match->team1_id !== null && $match->team2_id === null
                    && $this->sourcesResolved($match, 1) && $this->sourcesResolved($match, 2)) {
                    $match->update([
                        'winner_id' => $match->team1_id,
                        'is_finalized' => true,
                    ]);
                    $changed = true;
                } elseif ($match->team1_id === null && $match->team2_id !== null
                    && $this->sourcesResolved($match, 1) && $this->sourcesResolved($match, 2)) {
                    $match->update([
                        'winner_id' => $match->team2_id,
                        'is_finalized' => true,
                    ]);
                    $changed = true;
                } elseif ($match->team1_id !== null && $match->team2_id !== null && $match->is_finalized
                    && ! in_array($match->winner_id, [$match->team1_id, $match->team2_id], true)) {
                    $match->update([
                        'team1_score' => null,
                        'team2_score' => null,
                        'winner_id' => null,
                        'loser_id' => null,
                        'is_finalized' => false,
                    ]);
                    $changed = true;
                }
            }
        } while ($changed);
    }

    /** @return array<int, Collection<int, EliminationMatch>> */
    private function createWinnersBracket(int $tournamentId, string $category, string $bracket, string $format, int $userId, int $size, Collection $teams): array
    {
        $rounds = (int) log($size, 2);
        $winnersRounds = [];
        $firstRound = [];

        for ($matchNumber = 1; $matchNumber <= $size / 2; $matchNumber++) {
            $team1 = $teams->get(($matchNumber - 1) * 2);
            $team2 = $teams->get((($matchNumber - 1) * 2) + 1);
            $firstRound[] = $this->createMatch($tournamentId, $category, $bracket, $format, 'winners', 1, $matchNumber, $userId, $team1?->id, $team2?->id);
        }

        $winnersRounds[1] = collect($firstRound);

        for ($round = 2; $round <= $rounds; $round++) {
            $current = collect();
            foreach ($winnersRounds[$round - 1]->values()->chunk(2) as $index => $sourceMatches) {
                $sourceMatches = $sourceMatches->values();
                $current->push($this->createMatch(
                    $tournamentId, $category, $bracket, $format, 'winners', $round, $index + 1, $userId,
                    null, null, $sourceMatches->get(0), 'winner', $sourceMatches->get(1), 'winner'
                ));
            }
            $winnersRounds[$round] = $current;
        }

        return $winnersRounds;
    }

    /** @param array<int, Collection<int, EliminationMatch>> $winnersRounds */
    private function createLosersBracket(int $tournamentId, string $category, string $bracket, string $format, int $userId, array $winnersRounds, int $size): void
    {
        $winnersRoundCount = count($winnersRounds);
        $losersRoundNumber = 1;
        $lastLosersRound = collect();

        foreach ($winnersRounds[1]->values()->chunk(2) as $index => $sourceMatches) {
            $sourceMatches = $sourceMatches->values();
            $lastLosersRound->push($this->createMatch(
                $tournamentId, $category, $bracket, $format, 'losers', $losersRoundNumber, $index + 1, $userId,
                null, null, $sourceMatches->get(0), 'loser', $sourceMatches->get(1), 'loser'
            ));
        }

        for ($winnersRoundNumber = 2; $winnersRoundNumber <= $winnersRoundCount; $winnersRoundNumber++) {
            $roundLosers = $winnersRounds[$winnersRoundNumber];

            if ($winnersRoundNumber > 2) {
                $losersRoundNumber++;
                $lastLosersRound = $this->pairLosersRoundWinners(
                    $tournamentId,
                    $category,
                    $bracket,
                    $format,
                    $userId,
                    $lastLosersRound,
                    $losersRoundNumber
                );
            }

            $losersRoundNumber++;
            $nextLosersRound = collect();
            foreach ($roundLosers->values() as $index => $winnersMatch) {
                $incomingLosersMatch = $lastLosersRound->get($index);
                $nextLosersRound->push($this->createMatch(
                    $tournamentId, $category, $bracket, $format, 'losers', $losersRoundNumber, $index + 1, $userId,
                    null, null, $incomingLosersMatch, 'winner', $winnersMatch, 'loser'
                ));
            }
            $lastLosersRound = $nextLosersRound;
        }

        $this->createMatch(
            $tournamentId, $category, $bracket, $format, 'grand_final', $losersRoundNumber + 1, 1, $userId,
            null, null, $winnersRounds[$winnersRoundCount]->first(), 'winner', $lastLosersRound->first(), 'winner'
        );
    }

    /**
     * @param  Collection<int, EliminationMatch>  $previousRound
     * @return Collection<int, EliminationMatch>
     */
    private function pairLosersRoundWinners(int $tournamentId, string $category, string $bracket, string $format, int $userId, Collection $previousRound, int $roundNumber): Collection
    {
        $currentRound = collect();

        foreach ($previousRound->values()->chunk(2) as $index => $sourceMatches) {
            $sourceMatches = $sourceMatches->values();
            $currentRound->push($this->createMatch(
                $tournamentId, $category, $bracket, $format, 'losers', $roundNumber, $index + 1, $userId,
                null, null, $sourceMatches->get(0), 'winner', $sourceMatches->get(1), 'winner'
            ));
        }

        return $currentRound;
    }

    private function createMatch(int $tournamentId, string $category, string $bracket, string $format, string $side, int $round, int $number, int $userId, ?int $team1Id, ?int $team2Id, ?EliminationMatch $team1Source = null, ?string $team1Result = null, ?EliminationMatch $team2Source = null, ?string $team2Result = null): EliminationMatch
    {
        return EliminationMatch::create([
            'tournament_id' => $tournamentId,
            'team_category' => $category,
            'team_bracket' => $bracket,
            'format' => $format,
            'bracket_side' => $side,
            'round_number' => $round,
            'match_number' => $number,
            'match_label' => Str::upper($side).' R'.$round.'-'.$number,
            'team1_id' => $team1Id,
            'team2_id' => $team2Id,
            'team1_source_match_id' => $team1Source?->id,
            'team1_source_result' => $team1Result,
            'team2_source_match_id' => $team2Source?->id,
            'team2_source_result' => $team2Result,
            'user_id' => $userId,
        ]);
    }

    private function scope(int $tournamentId, string $category, string $bracket): Builder
    {
        return EliminationMatch::query()
            ->where('tournament_id', $tournamentId)
            ->where('team_category', $category)
            ->where('team_bracket', $bracket);
    }

    private function sourcesResolved(EliminationMatch $match, int $slot): bool
    {
        $sourceId = $match->{'team'.$slot.'_source_match_id'};

        return $sourceId === null || EliminationMatch::find($sourceId)?->is_finalized === true;
    }
}
