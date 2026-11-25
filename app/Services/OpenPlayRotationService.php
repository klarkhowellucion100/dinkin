<?php

namespace App\Services;

use App\Models\OpenPlayMatch;
use App\Models\OpenPlaySession;
use App\Models\Player;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OpenPlayRotationService
{
    public function stageNextMatches(OpenPlaySession $session): Collection
    {
        $stagedMatches = collect();
        $activeCourts = $session->matches()
            ->whereIn('status', ['active', 'staged'])
            ->pluck('court_no')
            ->map(fn (mixed $court): int => (int) $court)
            ->all();
        $nextRound = ((int) $session->matches()->max('round_no')) + 1;
        $usedPlayerIds = $this->activePlayerIds($session);
        $playersPerCourt = $session->format === 'singles' ? 2 : 4;

        foreach (range(1, (int) $session->court_count) as $courtNo) {
            if (in_array($courtNo, $activeCourts, true)) {
                continue;
            }

            $players = $this->choosePlayers($session, $playersPerCourt, $usedPlayerIds);

            if ($players->count() < $playersPerCourt) {
                break;
            }

            $teams = $playersPerCourt === 4
                ? $this->buildDoublesTeams($session, $players)
                : [
                    [$players[0]],
                    [$players[1]],
                ];

            $match = $session->matches()->create([
                'court_no' => $courtNo,
                'round_no' => $nextRound,
                'status' => 'staged',
                'team1_player1_id' => $teams[0][0]->id,
                'team1_player2_id' => $playersPerCourt === 4 ? $teams[0][1]->id : null,
                'team2_player1_id' => $teams[1][0]->id,
                'team2_player2_id' => $playersPerCourt === 4 ? $teams[1][1]->id : null,
            ]);

            $stagedMatches->push($match);
            $usedPlayerIds = array_merge($usedPlayerIds, $players->pluck('id')->all());
        }

        return $stagedMatches;
    }

    public function recordResult(OpenPlayMatch $match, int $winnerTeam, ?int $team1Score = null, ?int $team2Score = null): Collection
    {
        return DB::transaction(function () use ($match, $winnerTeam, $team1Score, $team2Score): Collection {
            abort_unless($match->status === 'active', 422, 'This game has already been completed.');
            abort_unless($this->matchIsFull($match), 422, 'Add a player before recording this result.');

            $match->update([
                'status' => 'completed',
                'winner_team' => $winnerTeam,
                'team1_score' => $team1Score,
                'team2_score' => $team2Score,
                'completed_at' => now(),
            ]);

            $playerIds = collect([
                $match->team1_player1_id,
                $match->team1_player2_id,
                $match->team2_player1_id,
                $match->team2_player2_id,
            ])->filter()->unique()->values();
            $nextQueuePosition = ((int) $match->session->players()->max('queue_position')) + 1;

            foreach ($playerIds as $playerId) {
                $isTeam1 = in_array($playerId, [$match->team1_player1_id, $match->team1_player2_id], true);
                $won = ($isTeam1 && $winnerTeam === 1) || (! $isTeam1 && $winnerTeam === 2);

                $match->session->players()->updateExistingPivot($playerId, [
                    'queue_position' => $nextQueuePosition++,
                    'games_played' => DB::raw('games_played + 1'),
                    'wins' => DB::raw('wins + '.($won ? 1 : 0)),
                    'losses' => DB::raw('losses + '.($won ? 0 : 1)),
                ]);
            }

            return $this->stageNextMatches($match->session->fresh());
        });
    }

    public function dispatchMatch(OpenPlayMatch $match): void
    {
        abort_unless($match->status === 'staged', 422, 'This match is no longer waiting to be sent to a court.');
        abort_unless($this->matchIsFull($match), 422, 'Add a player before sending this match to a court.');
        abort_if($match->session->matches()->where('status', 'active')->where('court_no', $match->court_no)->exists(), 422, 'That court is still occupied.');

        $match->update(['status' => 'active', 'started_at' => now()]);
    }

    public function stageManualMatch(OpenPlaySession $session): OpenPlayMatch
    {
        $playersPerCourt = $session->format === 'singles' ? 2 : 4;
        $players = $this->choosePlayers($session, $playersPerCourt, $this->activePlayerIds($session));

        abort_unless($players->count() === $playersPerCourt, 422, "At least {$playersPerCourt} available players are required to add a match.");

        $teams = $playersPerCourt === 4
            ? $this->buildDoublesTeams($session, $players)
            : [[$players[0]], [$players[1]]];
        $stagedCounts = $session->matches()
            ->where('status', 'staged')
            ->get()
            ->groupBy('court_no')
            ->map->count();
        $courtNo = collect(range(1, (int) $session->court_count))
            ->sortBy(fn (int $court): int => (int) ($stagedCounts[$court] ?? 0))
            ->first();

        return $session->matches()->create([
            'court_no' => $courtNo,
            'round_no' => ((int) $session->matches()->max('round_no')) + 1,
            'status' => 'staged',
            'team1_player1_id' => $teams[0][0]->id,
            'team1_player2_id' => $playersPerCourt === 4 ? $teams[0][1]->id : null,
            'team2_player1_id' => $teams[1][0]->id,
            'team2_player2_id' => $playersPerCourt === 4 ? $teams[1][1]->id : null,
        ]);
    }

    public function cancelPlayer(OpenPlaySession $session, int $playerId): Collection
    {
        $session->matches()
            ->whereIn('status', ['staged', 'active'])
            ->where(function ($query) use ($playerId): void {
                $query->where('team1_player1_id', $playerId)
                    ->orWhere('team1_player2_id', $playerId)
                    ->orWhere('team2_player1_id', $playerId)
                    ->orWhere('team2_player2_id', $playerId);
            })
            ->update(['status' => 'cancelled', 'completed_at' => now()]);

        return $this->stageNextMatches($session->fresh());
    }

    public function stageManualMatchWithPlayer(OpenPlaySession $session, int $playerId): OpenPlayMatch
    {
        $playersPerCourt = $session->format === 'singles' ? 2 : 4;
        $availablePlayerIds = $session->players()
            ->wherePivotNotNull('checked_in_at')
            ->wherePivotNull('checked_out_at')
            ->pluck('players.id');
        abort_unless($availablePlayerIds->contains($playerId), 404);

        $reservedPlayerIds = $this->activePlayerIds($session);
        abort_unless(! in_array($playerId, $reservedPlayerIds, true), 422, 'That player is already assigned to an active or staged match.');

        $otherPlayers = $session->players()
            ->wherePivotNotNull('checked_in_at')
            ->wherePivotNull('checked_out_at')
            ->whereNotIn('players.id', [...$reservedPlayerIds, $playerId])
            ->orderByPivot('queue_position')
            ->orderByPivot('games_played')
            ->get()
            ->take($playersPerCourt - 1)
            ->values();
        abort_unless($otherPlayers->count() === $playersPerCourt - 1, 422, "At least {$playersPerCourt} available players are required to add a match.");

        $players = collect([Player::findOrFail($playerId)])->merge($otherPlayers)->values();

        return $this->createStagedMatch($session, $players);
    }

    public function removePlayerFromMatch(OpenPlayMatch $match, int $playerId): Collection
    {
        abort_unless(in_array($playerId, [
            $match->team1_player1_id,
            $match->team1_player2_id,
            $match->team2_player1_id,
            $match->team2_player2_id,
        ], true), 404);
        abort_unless(in_array($match->status, ['staged', 'active'], true), 422, 'This match is no longer available to change.');

        $session = $match->session;
        $updates = [];

        foreach (['team1_player1_id', 'team1_player2_id', 'team2_player1_id', 'team2_player2_id'] as $column) {
            if ((int) $match->{$column} !== $playerId) {
                continue;
            }

            if (str_ends_with($column, 'player1_id')) {
                $partnerColumn = str_starts_with($column, 'team1_') ? 'team1_player2_id' : 'team2_player2_id';
                $updates[$column] = $match->{$partnerColumn};
                $updates[$partnerColumn] = null;
            } else {
                $updates[$column] = null;
            }
        }

        $match->update($updates);
        $session->players()->updateExistingPivot($playerId, [
            'queue_position' => ((int) $session->players()->max('queue_position')) + 1,
            'checked_in_at' => now(),
            'checked_out_at' => null,
        ]);

        return collect();
    }

    public function fillMatchPlayer(OpenPlayMatch $match, int $playerId): void
    {
        $session = $match->session;
        $available = $session->players()
            ->where('players.id', $playerId)
            ->wherePivotNotNull('checked_in_at')
            ->wherePivotNull('checked_out_at')
            ->exists();
        abort_unless($available, 404);

        $reservedPlayerIds = $this->activePlayerIds($session);
        abort_unless(! in_array($playerId, $reservedPlayerIds, true), 422, 'That player is already assigned to an active or staged match.');

        $slot = collect(['team1_player1_id', 'team1_player2_id', 'team2_player1_id', 'team2_player2_id'])
            ->first(fn (string $column): bool => $match->{$column} === null);
        abort_unless($slot !== null, 422, 'This match has no open player slot.');

        $match->update([$slot => $playerId]);
    }

    public function autoFillOpenSlots(OpenPlaySession $session): void
    {
        $openMatches = $session->matches()
            ->whereIn('status', ['staged', 'active'])
            ->where(function ($query): void {
                $query->whereNull('team1_player1_id')
                    ->orWhereNull('team1_player2_id')
                    ->orWhereNull('team2_player1_id')
                    ->orWhereNull('team2_player2_id');
            })
            ->get();

        foreach ($openMatches as $match) {
            $reservedPlayerIds = $this->activePlayerIds($session->fresh());
            $player = $session->players()
                ->wherePivotNotNull('checked_in_at')
                ->wherePivotNull('checked_out_at')
                ->whereNotIn('players.id', $reservedPlayerIds)
                ->orderByPivot('queue_position')
                ->first();

            if ($player === null) {
                break;
            }

            $this->fillMatchPlayer($match->fresh(), (int) $player->id);
        }
    }

    public function undoLatestResult(OpenPlaySession $session): Collection
    {
        $match = $session->matches()->where('status', 'completed')->latest('completed_at')->first();

        abort_unless($match !== null, 422, 'There is no completed result to undo.');

        $session->matches()->where('status', 'staged')->update(['status' => 'cancelled']);

        $playerIds = collect([
            $match->team1_player1_id,
            $match->team1_player2_id,
            $match->team2_player1_id,
            $match->team2_player2_id,
        ])->filter()->unique();

        foreach ($playerIds as $playerId) {
            $won = ($match->winner_team === 1 && in_array($playerId, [$match->team1_player1_id, $match->team1_player2_id], true))
                || ($match->winner_team === 2 && in_array($playerId, [$match->team2_player1_id, $match->team2_player2_id], true));

            $session->players()->updateExistingPivot($playerId, [
                'games_played' => DB::raw('MAX(games_played - 1, 0)'),
                'wins' => DB::raw('MAX(wins - '.($won ? 1 : 0).', 0)'),
                'losses' => DB::raw('MAX(losses - '.($won ? 0 : 1).', 0)'),
            ]);
        }

        $match->delete();

        return $this->stageNextMatches($session->fresh());
    }

    private function choosePlayers(OpenPlaySession $session, int $count, array $excludedIds): Collection
    {
        $players = $session->players()
            ->wherePivotNotNull('checked_in_at')
            ->wherePivotNull('checked_out_at')
            ->whereNotIn('players.id', array_unique($excludedIds))
            ->orderByPivot('queue_position')
            ->orderByPivot('games_played')
            ->get();

        if ($session->mode === 'balanced') {
            $lastMatch = $session->matches()
                ->where('status', 'completed')
                ->latest('completed_at')
                ->first();

            if ($lastMatch !== null) {
                $recentPlayerIds = collect([
                    $lastMatch->team1_player1_id,
                    $lastMatch->team1_player2_id,
                    $lastMatch->team2_player1_id,
                    $lastMatch->team2_player2_id,
                ])->filter()->all();
                $playersWhoDidNotJustPlay = $players->reject(fn (Player $player): bool => in_array($player->id, $recentPlayerIds, true));

                if ($playersWhoDidNotJustPlay->count() >= $count) {
                    $players = $playersWhoDidNotJustPlay->values();
                }
            }
        }

        if ($session->format === 'doubles') {
            return $this->chooseDoublesPlayers($session, $players, $count);
        }

        if ($session->mode === 'winners_losers') {
            return $this->chooseWinnersLosers($players, $count);
        }

        if ($session->mode === 'king_queen') {
            $players = $players->sortByDesc(fn (Player $player): array => [
                $this->winRate($player),
                (int) $player->pivot->wins,
                -((int) $player->pivot->games_played),
            ])->values();
        }

        return $this->chooseWithFixedPartners($players, $count);
    }

    private function createStagedMatch(OpenPlaySession $session, Collection $players): OpenPlayMatch
    {
        $playersPerCourt = $session->format === 'singles' ? 2 : 4;
        $teams = $playersPerCourt === 4
            ? $this->buildDoublesTeams($session, $players)
            : [[$players[0]], [$players[1]]];
        $stagedCounts = $session->matches()
            ->where('status', 'staged')
            ->get()
            ->groupBy('court_no')
            ->map->count();
        $courtNo = collect(range(1, (int) $session->court_count))
            ->sortBy(fn (int $court): int => (int) ($stagedCounts[$court] ?? 0))
            ->first();

        return $session->matches()->create([
            'court_no' => $courtNo,
            'round_no' => ((int) $session->matches()->max('round_no')) + 1,
            'status' => 'staged',
            'team1_player1_id' => $teams[0][0]->id,
            'team1_player2_id' => $playersPerCourt === 4 ? $teams[0][1]->id : null,
            'team2_player1_id' => $teams[1][0]->id,
            'team2_player2_id' => $playersPerCourt === 4 ? $teams[1][1]->id : null,
        ]);
    }

    private function matchIsFull(OpenPlayMatch $match): bool
    {
        $requiredColumns = $match->session->format === 'singles'
            ? ['team1_player1_id', 'team2_player1_id']
            : ['team1_player1_id', 'team1_player2_id', 'team2_player1_id', 'team2_player2_id'];

        return collect($requiredColumns)->every(fn (string $column): bool => $match->{$column} !== null);
    }

    private function chooseWinnersLosers(Collection $players, int $count): Collection
    {
        $ranked = $players->sortByDesc(fn (Player $player): array => [
            $this->winRate($player),
            (int) $player->pivot->wins,
        ])->values();
        $half = (int) ($count / 2);

        return $ranked->take($half)->merge($ranked->reverse()->take($half)->reverse())->unique('id')->values();
    }

    private function chooseWithFixedPartners(Collection $players, int $count): Collection
    {
        $selected = collect();

        foreach ($players as $player) {
            if ($selected->contains('id', $player->id)) {
                continue;
            }

            $selected->push($player);
            $partnerId = $player->pivot->fixed_partner_id;

            if ($partnerId !== null) {
                $partner = $players->firstWhere('id', (int) $partnerId);

                if ($partner !== null && ! $selected->contains('id', $partner->id)) {
                    $selected->push($partner);
                }
            }

            if ($selected->count() >= $count) {
                break;
            }
        }

        return $selected->take($count)->values();
    }

    private function chooseDoublesPlayers(OpenPlaySession $session, Collection $players, int $count): Collection
    {
        if ($players->count() <= $count) {
            return $players->values();
        }

        $partnerHistory = $this->partnerHistory($session);
        $opponentHistory = $this->opponentHistory($session);
        $rankedPlayers = $players->sortByDesc(fn (Player $player): array => [
            $this->winRate($player),
            (int) $player->pivot->wins,
        ])->values();
        $winnerIds = $rankedPlayers
            ->take((int) ceil($players->count() / 2))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $targetWinnerCount = intdiv($count, 2);
        $bestPlayers = null;
        $bestScore = PHP_INT_MAX;

        foreach ($this->playerCombinations($players->values()->all(), $count) as $candidate) {
            if (! $this->isValidFixedPartnerGroup($players, $candidate)) {
                continue;
            }

            $candidateCollection = collect($candidate)->values();
            $pairingScore = $this->bestDoublesPairingScore(
                $session,
                $candidateCollection,
                $partnerHistory,
                $opponentHistory,
            );
            $winnerCount = $candidateCollection->filter(fn (Player $player): bool => in_array((int) $player->id, $winnerIds, true))->count();
            $winnerBalancePenalty = abs($winnerCount - $targetWinnerCount);
            $gamesPlayedScore = $candidateCollection->sum(fn (Player $player): int => (int) $player->pivot->games_played);
            $queueScore = $candidateCollection->sum(fn (Player $player): int => (int) $player->pivot->queue_position);
            $score = ($winnerBalancePenalty * 1_000_000_000)
                + ($pairingScore * 1_000)
                + ($gamesPlayedScore * 100)
                + $queueScore;

            if ($score < $bestScore) {
                $bestScore = $score;
                $bestPlayers = $candidateCollection;
            }
        }

        return $bestPlayers ?? $players->take($count)->values();
    }

    /**
     * @param  array<int, Player>  $players
     * @return iterable<int, array<int, Player>>
     */
    private function playerCombinations(array $players, int $size, int $start = 0, array $selected = []): iterable
    {
        if (count($selected) === $size) {
            yield $selected;

            return;
        }

        $remaining = $size - count($selected);

        for ($index = $start; $index <= count($players) - $remaining; $index++) {
            yield from $this->playerCombinations(
                $players,
                $size,
                $index + 1,
                [...$selected, $players[$index]],
            );
        }
    }

    /**
     * Pair four selected doubles players while preserving fixed partners and
     * avoiding previously repeated partnerships whenever another pairing is available.
     *
     * @return array{0: array{0: Player, 1: Player}, 1: array{0: Player, 1: Player}}
     */
    private function buildDoublesTeams(OpenPlaySession $session, Collection $players): array
    {
        $pairings = [
            [[0, 1], [2, 3]],
            [[0, 2], [1, 3]],
            [[0, 3], [1, 2]],
        ];
        $partnerHistory = $this->partnerHistory($session);
        $opponentHistory = $this->opponentHistory($session);
        $bestPairing = null;
        $bestScore = PHP_INT_MAX;

        foreach ($pairings as $pairing) {
            if (! $this->isValidFixedPartnerPairing($players, $pairing)) {
                continue;
            }

            $score = $this->pairingScore($session, $players, $pairing, $partnerHistory, $opponentHistory);

            if ($score < $bestScore) {
                $bestScore = $score;
                $bestPairing = $pairing;
            }
        }

        $bestPairing ??= $pairings[0];

        return [
            [
                $players->get($bestPairing[0][0]),
                $players->get($bestPairing[0][1]),
            ],
            [
                $players->get($bestPairing[1][0]),
                $players->get($bestPairing[1][1]),
            ],
        ];
    }

    /**
     * @param  array<int, array<int, int>>  $partnerHistory
     * @param  array<int, array<int, int>>  $opponentHistory
     */
    private function bestDoublesPairingScore(
        OpenPlaySession $session,
        Collection $players,
        array $partnerHistory,
        array $opponentHistory,
    ): int {
        $pairings = [
            [[0, 1], [2, 3]],
            [[0, 2], [1, 3]],
            [[0, 3], [1, 2]],
        ];
        $bestScore = PHP_INT_MAX;

        foreach ($pairings as $pairing) {
            if (! $this->isValidFixedPartnerPairing($players, $pairing)) {
                continue;
            }

            $bestScore = min(
                $bestScore,
                $this->pairingScore($session, $players, $pairing, $partnerHistory, $opponentHistory),
            );
        }

        return $bestScore;
    }

    /**
     * @param  array<int, array<int, int>>  $partnerHistory
     * @param  array<int, array<int, int>>  $opponentHistory
     */
    private function pairingScore(
        OpenPlaySession $session,
        Collection $players,
        array $pairing,
        array $partnerHistory,
        array $opponentHistory,
    ): int {
        $partnerRepeatCount = 0;

        foreach ($pairing as [$firstIndex, $secondIndex]) {
            $firstId = (int) $players->get($firstIndex)->id;
            $secondId = (int) $players->get($secondIndex)->id;
            $partnerRepeatCount += $partnerHistory[$firstId][$secondId] ?? 0;
        }

        $opponentRepeatCount = 0;

        foreach ($pairing[0] as $team1Index) {
            foreach ($pairing[1] as $team2Index) {
                $team1Id = (int) $players->get($team1Index)->id;
                $team2Id = (int) $players->get($team2Index)->id;
                $opponentRepeatCount += $opponentHistory[$team1Id][$team2Id] ?? 0;
            }
        }

        $sameSidePenalty = $session->mode === 'winners_losers' && ! $this->isWinnersLosersPairing($pairing)
            ? 1
            : 0;

        return ($sameSidePenalty * 1_000_000)
            + ($partnerRepeatCount * 10_000)
            + ($opponentRepeatCount * 100);
    }

    /**
     * @return array<int, array<int, int>>
     */
    private function partnerHistory(OpenPlaySession $session): array
    {
        $history = [];

        foreach ($session->matches()->where('status', 'completed')->get() as $match) {
            $teams = [
                array_values(array_filter([$match->team1_player1_id, $match->team1_player2_id])),
                array_values(array_filter([$match->team2_player1_id, $match->team2_player2_id])),
            ];

            foreach ($teams as $team) {
                if (count($team) < 2) {
                    continue;
                }

                $firstId = (int) $team[0];
                $secondId = (int) $team[1];
                $history[$firstId][$secondId] = ($history[$firstId][$secondId] ?? 0) + 1;
                $history[$secondId][$firstId] = ($history[$secondId][$firstId] ?? 0) + 1;
            }
        }

        return $history;
    }

    /**
     * @return array<int, array<int, int>>
     */
    private function opponentHistory(OpenPlaySession $session): array
    {
        $history = [];

        foreach ($session->matches()->where('status', 'completed')->get() as $match) {
            $team1 = array_values(array_filter([$match->team1_player1_id, $match->team1_player2_id]));
            $team2 = array_values(array_filter([$match->team2_player1_id, $match->team2_player2_id]));

            foreach ($team1 as $team1Id) {
                foreach ($team2 as $team2Id) {
                    $firstId = (int) $team1Id;
                    $secondId = (int) $team2Id;
                    $history[$firstId][$secondId] = ($history[$firstId][$secondId] ?? 0) + 1;
                    $history[$secondId][$firstId] = ($history[$secondId][$firstId] ?? 0) + 1;
                }
            }
        }

        return $history;
    }

    /**
     * @param  array<int, Player>  $candidate
     */
    private function isValidFixedPartnerGroup(Collection $players, array $candidate): bool
    {
        $candidateIds = collect($candidate)->pluck('id')->map(fn (mixed $id): int => (int) $id);

        foreach ($candidate as $player) {
            $fixedPartnerId = (int) ($player->pivot->fixed_partner_id ?? 0);

            if ($fixedPartnerId !== 0 && $players->contains('id', $fixedPartnerId) && ! $candidateIds->contains($fixedPartnerId)) {
                return false;
            }
        }

        return true;
    }

    private function isValidFixedPartnerPairing(Collection $players, array $pairing): bool
    {
        foreach ($players as $index => $player) {
            $fixedPartnerId = (int) ($player->pivot->fixed_partner_id ?? 0);

            if ($fixedPartnerId === 0) {
                continue;
            }

            $partnerIndex = $players->search(fn (Player $candidate): bool => (int) $candidate->id === $fixedPartnerId);

            if ($partnerIndex !== false && ! $this->sameTeam($pairing, (int) $index, (int) $partnerIndex)) {
                return false;
            }
        }

        return true;
    }

    private function sameTeam(array $pairing, int $firstIndex, int $secondIndex): bool
    {
        return in_array($firstIndex, $pairing[0], true) && in_array($secondIndex, $pairing[0], true)
            || in_array($firstIndex, $pairing[1], true) && in_array($secondIndex, $pairing[1], true);
    }

    private function isWinnersLosersPairing(array $pairing): bool
    {
        foreach ($pairing as [$firstIndex, $secondIndex]) {
            if (($firstIndex < 2) === ($secondIndex < 2)) {
                return false;
            }
        }

        return true;
    }

    private function activePlayerIds(OpenPlaySession $session): array
    {
        return $session->matches()
            ->whereIn('status', ['active', 'staged'])
            ->get()
            ->flatMap(fn (OpenPlayMatch $match): array => [
                $match->team1_player1_id,
                $match->team1_player2_id,
                $match->team2_player1_id,
                $match->team2_player2_id,
            ])
            ->filter()
            ->unique()
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    private function winRate(Player $player): float
    {
        $gamesPlayed = (int) $player->pivot->games_played;

        return $gamesPlayed === 0 ? 0 : (int) $player->pivot->wins / $gamesPlayed;
    }
}
