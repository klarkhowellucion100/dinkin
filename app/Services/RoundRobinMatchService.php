<?php

namespace App\Services;

use App\Models\Matches;
use App\Models\Teams;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoundRobinMatchService
{
    public function sync(int $tournamentId, string $bracket, string $category, int $userId): void
    {
        DB::transaction(function () use ($tournamentId, $bracket, $category, $userId): void {
            $teams = Teams::query()
                ->where('tournament_id', $tournamentId)
                ->where('team_bracket', $bracket)
                ->where('team_category', $category)
                ->orderBy('team_no')
                ->orderBy('id')
                ->get();

            $teamIds = $teams->modelKeys();

            $existingMatches = Matches::query()
                ->where('tournament_id', $tournamentId)
                ->where('team_bracket', $bracket)
                ->where('team_category', $category)
                ->orderBy('id')
                ->get();

            foreach ($existingMatches as $existingMatch) {
                if (! in_array($existingMatch->team1_id, $teamIds) || ! in_array($existingMatch->team2_id, $teamIds)) {
                    $existingMatch->delete();
                }
            }

            $schedule = $this->generateRoundRobin($teams);
            $existingMatches = Matches::query()
                ->where('tournament_id', $tournamentId)
                ->where('team_bracket', $bracket)
                ->where('team_category', $category)
                ->orderBy('id')
                ->get();

            $matchesByPair = [];
            foreach ($existingMatches as $existingMatch) {
                $matchesByPair[$this->pairKey($existingMatch->team1_id, $existingMatch->team2_id)][] = $existingMatch;
            }

            foreach ($schedule as $scheduledMatch) {
                $pairKey = $this->pairKey($scheduledMatch['team1_id'], $scheduledMatch['team2_id']);
                $existingPair = $matchesByPair[$pairKey] ?? [];

                if ($existingPair !== []) {
                    foreach ($existingPair as $existingMatch) {
                        $existingMatch->update([
                            'round_no' => $scheduledMatch['round_no'],
                            'match_no' => $scheduledMatch['match_no'],
                        ]);
                    }

                    continue;
                }

                Matches::create([
                    'code' => 'MATCH-'.Str::upper(Str::random(12)),
                    'tournament_id' => $tournamentId,
                    'team_bracket' => $bracket,
                    'team_category' => $category,
                    'round_no' => $scheduledMatch['round_no'],
                    'match_no' => $scheduledMatch['match_no'],
                    'team1_id' => $scheduledMatch['team1_id'],
                    'team2_id' => $scheduledMatch['team2_id'],
                    'team1_score' => null,
                    'team2_score' => null,
                    'winner_id' => null,
                    'user_id' => $userId,
                ]);
            }
        });
    }

    /**
     * @param  Collection<int, Teams>  $teams
     * @return array<int, array{round_no: int, match_no: int, team1_id: int, team2_id: int}>
     */
    private function generateRoundRobin(Collection $teams): array
    {
        $participants = $teams->values()->all();

        if (count($participants) < 2) {
            return [];
        }

        if (count($participants) % 2 !== 0) {
            $participants[] = null;
        }

        $totalTeams = count($participants);
        $rounds = $totalTeams - 1;
        $matches = [];
        $matchNo = 1;

        for ($round = 1; $round <= $rounds; $round++) {
            $matchesPerRound = (int) ($totalTeams / 2);

            for ($index = 0; $index < $matchesPerRound; $index++) {
                $team1 = $participants[$index];
                $team2 = $participants[$totalTeams - 1 - $index];

                if ($team1 === null || $team2 === null) {
                    continue;
                }

                $matches[] = [
                    'round_no' => $round,
                    'match_no' => $matchNo++,
                    'team1_id' => $team1->id,
                    'team2_id' => $team2->id,
                ];
            }

            $fixed = $participants[0];
            $rotating = array_slice($participants, 1);
            $last = array_pop($rotating);
            array_unshift($rotating, $last);
            $participants = array_merge([$fixed], $rotating);
        }

        return $matches;
    }

    private function pairKey(int|string $team1Id, int|string $team2Id): string
    {
        $teamIds = [(int) $team1Id, (int) $team2Id];
        sort($teamIds);

        return implode(':', $teamIds);
    }
}
