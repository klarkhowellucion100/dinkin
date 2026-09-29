<?php

namespace App\Http\Controllers;

use App\Models\PlayoffMatches;
use App\Models\Teams;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlayoffMatchesController extends Controller
{
    /**
     * Display playoff matches.
     */
    public function index(
        $tournament_id,
        $team_category
    ) {
        /*
         * Decrypt tournament ID.
         */
        try {
            $tournamentId = Crypt::decryptString(
                $tournament_id
            );
        } catch (\Exception $e) {
            abort(404);
        }

        /*
         * Get tournament.
         */
        $tournament = DB::table('tournaments')
            ->where('id', $tournamentId)
            ->first();

        if (! $tournament) {
            abort(404);
        }

        /*
         * Get all teams belonging to this tournament
         * and category.
         *
         * IMPORTANT:
         *
         * We intentionally DO NOT filter by team_bracket.
         *
         * This allows teams from different brackets
         * to participate in the crossover.
         */
        $teams = DB::table('teams')
            ->where(
                'tournament_id',
                $tournamentId
            )
            ->where(
                'team_category',
                $team_category
            )
            ->orderBy('team_bracket')
            ->orderBy('team_no')
            ->get();

        $qualificationOptions = $this->qualificationOptions(
            (int) $tournamentId,
            $team_category
        );

        /*
         * Get playoff matches.
         *
         * Team brackets are joined only to DISPLAY
         * where the team originally came from.
         *
         * The bracket is NOT stored in playoff_matches_controllers.
         */
        $playoffMatches = DB::table('playoff_matches_controllers')

            ->leftJoin(
                'teams as team1',
                'playoff_matches_controllers.team1_id',
                '=',
                'team1.id'
            )

            ->leftJoin(
                'teams as team2',
                'playoff_matches_controllers.team2_id',
                '=',
                'team2.id'
            )

            ->leftJoin(
                'teams as winner',
                'playoff_matches_controllers.winner_id',
                '=',
                'winner.id'
            )

            ->where(
                'playoff_matches_controllers.tournament_id',
                $tournamentId
            )

            ->where(
                'playoff_matches_controllers.team_category',
                $team_category
            )

            ->select([
                'playoff_matches_controllers.id',
                'playoff_matches_controllers.code',

                'playoff_matches_controllers.tournament_id',

                'playoff_matches_controllers.team_category',

                'playoff_matches_controllers.match_type',

                'playoff_matches_controllers.match_no',

                /*
                 * Team 1
                 */
                'playoff_matches_controllers.team1_id',
                'team1.team_no as team1_no',
                'team1.team_name as team1_name',
                'team1.team_bracket as team1_bracket',

                /*
                 * Team 2
                 */
                'playoff_matches_controllers.team2_id',
                'team2.team_no as team2_no',
                'team2.team_name as team2_name',
                'team2.team_bracket as team2_bracket',

                /*
                 * Scores
                 */
                'playoff_matches_controllers.team1_score',
                'playoff_matches_controllers.team2_score',

                /*
                 * Winner
                 */
                'playoff_matches_controllers.winner_id',
                'winner.team_no as winner_no',
                'winner.team_name as winner_name',

                'playoff_matches_controllers.user_id',

                'playoff_matches_controllers.created_at',
                'playoff_matches_controllers.updated_at',
            ])

            ->orderByRaw("
                CASE
                    WHEN playoff_matches_controllers.match_type IN ('ROUND', 'SF', 'BYE') THEN 1
                    WHEN playoff_matches_controllers.match_type = '3RD' THEN 2
                    WHEN playoff_matches_controllers.match_type = 'FINAL' THEN 3
                    ELSE 4
                END
            ")

            ->orderBy(
                'playoff_matches_controllers.match_no'
            )

            ->get();

        $allPlayoffMatches = $playoffMatches;
        $playoffMatches = $playoffMatches->map(function ($playoffMatch) use ($allPlayoffMatches) {
            if ($playoffMatch->match_type !== '3RD' || $playoffMatch->team2_id !== null) {
                return $playoffMatch;
            }

            $sourceMatch = $allPlayoffMatches->first(function ($candidate) use ($playoffMatch) {
                $teamWasInMatch = (int) $candidate->team1_id === (int) $playoffMatch->team1_id
                    || (int) $candidate->team2_id === (int) $playoffMatch->team1_id;

                return $candidate->id !== $playoffMatch->id
                    && $candidate->match_type !== 'BYE'
                    && $teamWasInMatch
                    && $candidate->winner_id !== null
                    && (int) $candidate->winner_id !== (int) $playoffMatch->team1_id
                    && $candidate->team1_score !== null
                    && $candidate->team2_score !== null;
            });

            if ($sourceMatch) {
                $teamScore = (int) $sourceMatch->team1_id === (int) $playoffMatch->team1_id
                    ? $sourceMatch->team1_score
                    : $sourceMatch->team2_score;
                $opponentScore = (int) $sourceMatch->team1_id === (int) $playoffMatch->team1_id
                    ? $sourceMatch->team2_score
                    : $sourceMatch->team1_score;
                $playoffMatch->automatic_score_text = "Previous match: {$teamScore}–{$opponentScore}";
            } else {
                $playoffMatch->automatic_score_text = 'Auto-awarded';
            }

            return $playoffMatch;
        });

        return view(
            'app.tournament.playoffs.index',
            compact(
                'tournament',
                'teams',
                'qualificationOptions',
                'playoffMatches',
                'team_category'
            )
        );
    }

    public function generateCrossBracket(Request $request)
    {
        $validated = $request->validate([
            'tournament_id' => ['required', 'string'],
            'team_category' => ['required', 'string'],
        ]);

        try {
            $tournamentId = (int) Crypt::decryptString($validated['tournament_id']);
        } catch (\Exception $e) {
            abort(404);
        }

        $options = $this->qualificationOptions($tournamentId, $validated['team_category']);

        if ($options->isEmpty()) {
            return back()->withInput()->with('error', 'No brackets with teams were found for this category.');
        }

        if (PlayoffMatches::query()
            ->where('tournament_id', $tournamentId)
            ->where('team_category', $validated['team_category'])
            ->where('match_type', '!=', 'BYE')
            ->exists()) {
            return back()->withInput()->with('error', 'Playoff matches already exist for this category. Delete them before generating a new crossover.');
        }

        if ($options->count() === 1) {
            $singleValidated = $request->validate([
                'top_four.rank1_id' => ['required', 'integer'],
                'top_four.rank2_id' => ['required', 'integer'],
                'top_four.rank3_id' => ['required', 'integer'],
                'top_four.rank4_id' => ['required', 'integer'],
            ]);

            $bracketOptions = $options->first();
            $singleSelection = $singleValidated['top_four'];
            $selectedIds = [
                (int) $singleSelection['rank1_id'],
                (int) $singleSelection['rank2_id'],
                (int) $singleSelection['rank3_id'],
                (int) $singleSelection['rank4_id'],
            ];

            foreach (range(1, 4) as $rank) {
                $allowed = collect($bracketOptions["rank{$rank}_options"])
                    ->contains(fn ($team) => (int) $team->id === (int) $singleSelection["rank{$rank}_id"]);

                if (! $allowed) {
                    return back()->withInput()->with('error', "The selected #{$rank} team is outside the top four standings or their tie group.");
                }
            }

            if (count(array_unique($selectedIds)) !== 4) {
                return back()->withInput()->with('error', 'Choose four different teams for the single-bracket playoffs.');
            }

            DB::transaction(function () use ($selectedIds, $tournamentId, $validated): void {
                PlayoffMatches::query()
                    ->where('tournament_id', $tournamentId)
                    ->where('team_category', $validated['team_category'])
                    ->where('match_type', 'BYE')
                    ->delete();

                foreach ([[$selectedIds[0], $selectedIds[3]], [$selectedIds[1], $selectedIds[2]]] as $index => [$team1Id, $team2Id]) {
                    PlayoffMatches::create([
                        'code' => 'PLAYOFF-'.Str::upper(Str::random(12)),
                        'tournament_id' => $tournamentId,
                        'team_bracket' => null,
                        'team_category' => $validated['team_category'],
                        'match_type' => 'SF',
                        'match_no' => 'R1-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                        'team1_id' => $team1Id,
                        'team2_id' => $team2Id,
                        'team1_score' => null,
                        'team2_score' => null,
                        'winner_id' => null,
                        'user_id' => Auth::id(),
                    ]);
                }
            });

            return redirect()->back()->with('success', 'Single-bracket playoffs generated: #1 vs #4 and #2 vs #3. Winners advance to the final; semifinal losers play for 3rd place.');
        }

        $validated = array_merge($validated, $request->validate([
            'qualifiers' => ['required', 'array', 'min:2'],
            'qualifiers.*.bracket' => ['required', 'string'],
            'qualifiers.*.rank1_id' => ['required', 'integer'],
            'qualifiers.*.rank2_id' => ['required', 'integer'],
        ]));

        $submitted = collect($validated['qualifiers'])->keyBy('bracket');
        $selectedTeams = [];

        foreach ($options as $index => $bracketOptions) {
            $selection = $submitted->get($bracketOptions['bracket']);

            if ($selection === null) {
                return back()->withInput()->with('error', 'Choose the first and second qualifiers for every bracket.');
            }

            $rank1Id = (int) $selection['rank1_id'];
            $rank2Id = (int) $selection['rank2_id'];
            $rank1Allowed = collect($bracketOptions['rank1_options'])->contains(fn ($team) => (int) $team->id === $rank1Id);
            $rank2Allowed = collect($bracketOptions['rank2_options'])->contains(fn ($team) => (int) $team->id === $rank2Id);

            if ($rank1Id === $rank2Id || ! $rank1Allowed || ! $rank2Allowed) {
                return back()->withInput()->with('error', "The selected qualifiers for Bracket {$bracketOptions['bracket']} are invalid. Choose from the displayed top two or tied teams.");
            }

            $selectedTeams[$index] = [
                'bracket' => $bracketOptions['bracket'],
                'rank1_id' => $rank1Id,
                'rank2_id' => $rank2Id,
            ];
        }

        if ($submitted->count() !== $options->count()) {
            return back()->withInput()->with('error', 'Choose qualifiers only for the brackets shown.');
        }

        DB::transaction(function () use ($selectedTeams, $tournamentId, $validated): void {
            PlayoffMatches::query()
                ->where('tournament_id', $tournamentId)
                ->where('team_category', $validated['team_category'])
                ->where('match_type', 'BYE')
                ->delete();

            $bracketCount = count($selectedTeams);

            foreach ($selectedTeams as $index => $bracket) {
                $nextBracket = $selectedTeams[($index + 1) % $bracketCount];

                PlayoffMatches::create([
                    'code' => 'PLAYOFF-'.Str::upper(Str::random(12)),
                    'tournament_id' => $tournamentId,
                    'team_bracket' => null,
                    'team_category' => $validated['team_category'],
                    'match_type' => $bracketCount === 2 ? 'SF' : 'ROUND',
                    'match_no' => 'R1-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'team1_id' => $bracket['rank1_id'],
                    'team2_id' => $nextBracket['rank2_id'],
                    'team1_score' => null,
                    'team2_score' => null,
                    'winner_id' => null,
                    'user_id' => Auth::id(),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Cross bracket playoff matches successfully generated.');
    }

    /**
     * Get tied qualification candidates for the first and second seeds in every bracket.
     *
     * @return Collection<int, array{bracket: string, rank1_options: array, rank2_options: array}>
     */
    private function qualificationOptions(int $tournamentId, string $category): Collection
    {
        $teamsByBracket = DB::table('teams')
            ->where('tournament_id', $tournamentId)
            ->where('team_category', $category)
            ->orderBy('team_bracket')
            ->orderBy('team_no')
            ->get()
            ->groupBy('team_bracket');

        $completedMatches = DB::table('matches')
            ->where('tournament_id', $tournamentId)
            ->where('team_category', $category)
            ->whereNotNull('team1_score')
            ->whereNotNull('team2_score')
            ->whereNotNull('winner_id')
            ->get();

        return $teamsByBracket->map(function ($teams, $bracket) use ($completedMatches): array {
            $standings = $teams->map(function ($team) use ($completedMatches): object {
                $wins = 0;
                $pointsFor = 0;
                $pointsAgainst = 0;

                foreach ($completedMatches as $match) {
                    if ((int) $match->team1_id === (int) $team->id && (int) $match->winner_id === (int) $team->id) {
                        $wins++;
                        $pointsFor += (int) $match->team1_score;
                        $pointsAgainst += (int) $match->team2_score;
                    } elseif ((int) $match->team2_id === (int) $team->id && (int) $match->winner_id === (int) $team->id) {
                        $wins++;
                        $pointsFor += (int) $match->team2_score;
                        $pointsAgainst += (int) $match->team1_score;
                    }
                }

                return (object) [
                    'team' => $team,
                    'wins' => $wins,
                    'point_differential' => $pointsFor - $pointsAgainst,
                    'points_against' => $pointsAgainst,
                ];
            })->sort(function ($first, $second): int {
                return ($second->wins <=> $first->wins)
                    ?: ($second->point_differential <=> $first->point_differential)
                    ?: ($first->points_against <=> $second->points_against);
            })->values();

            $firstGroup = $standings->filter(fn ($standing) => $this->sameStanding($standing, $standings->first()))->values();
            $secondGroup = $firstGroup->count() > 1
                ? $firstGroup
                : $standings->slice(1)->filter(fn ($standing) => $this->sameStanding($standing, $standings->get(1)))->values();

            return [
                'bracket' => (string) $bracket,
                'rank1_options' => $firstGroup->map(fn ($standing) => $standing->team)->all(),
                'rank2_options' => $secondGroup->map(fn ($standing) => $standing->team)->all(),
                'rank3_options' => $standings->count() > 2
                    ? $standings->filter(fn ($standing) => $this->sameStanding($standing, $standings->get(2)))->map(fn ($standing) => $standing->team)->values()->all()
                    : [],
                'rank4_options' => $standings->count() > 3
                    ? $standings->filter(fn ($standing) => $this->sameStanding($standing, $standings->get(3)))->map(fn ($standing) => $standing->team)->values()->all()
                    : [],
            ];
        })->values();
    }

    private function sameStanding(?object $first, ?object $second): bool
    {
        return $first !== null
            && $second !== null
            && $first->wins === $second->wins
            && $first->point_differential === $second->point_differential
            && $first->points_against === $second->points_against;
    }

    /**
     * Store manually encoded playoff match.
     */
    public function store(Request $request)
    {
        /*
         * Validate submitted data.
         */
        $validated = $request->validate([

            'tournament_id' => [
                'required',
                'string',
            ],

            'team_category' => [
                'required',
                'string',
            ],

            'match_type' => [
                'required',
                'string',
                'in:SF,SF1,SF2,3RD,FINAL',
            ],

            'match_no' => [
                'required_if:match_type,SF',
                'nullable',
                'string',
                'max:50',
            ],

            'team1_id' => [
                'required',
                'integer',
            ],

            'team2_id' => [
                'required',
                'integer',
                'different:team1_id',
            ],
        ]);

        /*
         * Decrypt tournament ID.
         */
        try {
            $tournamentId = Crypt::decryptString(
                $validated['tournament_id']
            );
        } catch (\Exception $e) {
            abort(404);
        }

        /*
         * Verify that tournament exists.
         */
        $tournamentExists = DB::table('tournaments')
            ->where('id', $tournamentId)
            ->exists();

        if (! $tournamentExists) {
            abort(404);
        }

        /*
         * Verify both teams belong to:
         *
         * Tournament
         * Category
         *
         * Notice that team_bracket is NOT checked.
         *
         * This is intentional because the playoff
         * is a crossover.
         */
        $teamIds = [
            $validated['team1_id'],
            $validated['team2_id'],
        ];

        $validTeams = Teams::whereIn(
            'id',
            $teamIds
        )
            ->where(
                'tournament_id',
                $tournamentId
            )
            ->where(
                'team_category',
                $validated['team_category']
            )
            ->count();

        if ($validTeams !== 2) {

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'The selected teams do not belong to the selected tournament and category.'
                );
        }

        /*
         * Prevent duplicate match type.
         *
         * Example:
         *
         * Only one SF1
         * Only one SF2
         * Only one 3RD
         * Only one FINAL
         *
         * per tournament/category.
         */
        $existingMatchQuery = PlayoffMatches::where('tournament_id', $tournamentId)
            ->where('team_category', $validated['team_category'])
            ->where('match_type', $validated['match_type']);

        if ($validated['match_type'] === 'SF') {
            $existingMatchQuery->where('match_no', $validated['match_no']);
        }

        $existingMatch = $existingMatchQuery->exists();

        if ($existingMatch) {

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'This playoff match number has already been encoded for this category.'
                );
        }

        /*
         * Create playoff match.
         */
        PlayoffMatches::create([

            'code' => 'PLAYOFF-'.
                Str::upper(
                    Str::random(12)
                ),

            'tournament_id' => $tournamentId,

            'team_category' => $validated['team_category'],

            'match_type' => $validated['match_type'],

            'match_no' => $validated['match_no'] ?? null,

            'team1_id' => $validated['team1_id'],

            'team2_id' => $validated['team2_id'],

            'team1_score' => null,

            'team2_score' => null,

            'winner_id' => null,

            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->back()
            ->with(
                'success',
                'Playoff match successfully encoded.'
            );
    }

    /**
     * Update playoff match score.
     */
    public function updateScore(
        Request $request,
        $id
    ) {
        /*
         * Decrypt match ID.
         */
        try {
            $matchId = Crypt::decryptString(
                $id
            );
        } catch (\Exception $e) {
            abort(404);
        }

        /*
         * Get match.
         */
        $match = PlayoffMatches::findOrFail(
            $matchId
        );

        if ($match->match_type === 'BYE') {
            return redirect()->back()->with('error', 'A bye advances the team automatically and does not need a score.');
        }

        /*
         * Validate scores.
         */
        $validated = $request->validate([

            'team1_score' => [
                'required',
                'integer',
                'min:0',
            ],

            'team2_score' => [
                'required',
                'integer',
                'min:0',
            ],

            'finalize_score' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $isFinal = (bool) ($validated['finalize_score'] ?? false);

        if ($isFinal &&
            $validated['team1_score']
            ==
            $validated['team2_score']
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'A tied score can be saved while the match is ongoing. Enter a winning score before finalizing.',
                ], 422);
            }

            return redirect()->back()->with('error', 'A tied score can be saved while the match is ongoing. Enter a winning score before finalizing.');
        }

        if (! $isFinal) {
            $winnerId = null;
        } elseif (
            $validated['team1_score']
            >
            $validated['team2_score']
        ) {

            $winnerId =
                $match->team1_id;

        } else {

            $winnerId =
                $match->team2_id;
        }

        /*
         * Update result.
         */
        $match->update([

            'team1_score' => $validated['team1_score'],

            'team2_score' => $validated['team2_score'],

            'winner_id' => $winnerId,
            'is_finalized' => $isFinal,
        ]);

        $existingMatchCount = PlayoffMatches::query()
            ->where('tournament_id', $match->tournament_id)
            ->where('team_category', $match->team_category)
            ->count();

        if ($isFinal) {
            $this->advanceGeneratedBracket($match->fresh());
        }

        $newMatchesGenerated = PlayoffMatches::query()
            ->where('tournament_id', $match->tournament_id)
            ->where('team_category', $match->team_category)
            ->count() > $existingMatchCount;

        if ($request->expectsJson()) {
            $winner = $winnerId === null ? null : Teams::find($winnerId);

            return response()->json([
                'message' => $isFinal ? 'Final playoff score saved.' : 'Score saved. Playoff match is still ongoing.',
                'match_id' => $match->id,
                'team1_score' => (int) $validated['team1_score'],
                'team2_score' => (int) $validated['team2_score'],
                'winner_id' => $winnerId === null ? null : (int) $winnerId,
                'winner_no' => $winner?->team_no,
                'winner_name' => $winner?->team_name,
                'is_finalized' => $isFinal,
                'new_matches_generated' => $newMatchesGenerated,
            ]);
        }

        $successMessage = $isFinal && preg_match('/^R\d+-/', (string) $match->match_no)
            ? 'Playoff score saved. The next round and placement matches are generated after every match in the round has a winner.'
            : ($isFinal ? 'Playoff match score successfully updated.' : 'Playoff score saved as ongoing.');

        return redirect()
            ->back()
            ->with(
                'success',
                $successMessage
            );
    }

    private function advanceGeneratedBracket(PlayoffMatches $match): void
    {
        if (! preg_match('/^R(\d+)-/', (string) $match->match_no, $roundMatch)) {
            return;
        }

        $round = (int) $roundMatch[1];
        $nextRound = $round + 1;

        DB::transaction(function () use ($match, $round, $nextRound): void {
            $stageMatches = PlayoffMatches::query()
                ->where('tournament_id', $match->tournament_id)
                ->where('team_category', $match->team_category)
                ->whereIn('match_type', ['ROUND', 'SF', 'BYE'])
                ->where('match_no', 'like', "R{$round}-%")
                ->orderBy('match_no')
                ->lockForUpdate()
                ->get();

            if ($stageMatches->isEmpty() || $stageMatches->contains(fn ($stageMatch) => $stageMatch->winner_id === null)) {
                return;
            }

            $nextRoundExists = PlayoffMatches::query()
                ->where('tournament_id', $match->tournament_id)
                ->where('team_category', $match->team_category)
                ->whereIn('match_type', ['ROUND', 'SF', 'BYE'])
                ->where('match_no', 'like', "R{$nextRound}-%")
                ->exists();

            if ($nextRoundExists || PlayoffMatches::query()
                ->where('tournament_id', $match->tournament_id)
                ->where('team_category', $match->team_category)
                ->whereIn('match_type', ['3RD', 'FINAL'])
                ->exists()) {
                return;
            }

            $winners = $stageMatches->pluck('winner_id')->map(fn ($teamId) => (int) $teamId)->values();
            $losers = $stageMatches
                ->filter(fn ($stageMatch) => $stageMatch->match_type !== 'BYE')
                ->map(fn ($stageMatch) => (int) ($stageMatch->winner_id == $stageMatch->team1_id ? $stageMatch->team2_id : $stageMatch->team1_id))
                ->values();

            if ($winners->count() === 2) {
                $hasByeInThisRound = $stageMatches->contains(fn ($stageMatch) => $stageMatch->match_type === 'BYE');

                if ($hasByeInThisRound && $losers->count() === 1) {
                    $loserId = $losers->first();

                    if ($loserId !== null) {
                        $this->createGeneratedMatch($match, '3RD', '3RD', $loserId, null, $loserId);
                    }
                } else {
                    if ($losers->count() < 2) {
                        $losers = $losers->merge($this->mostRecentlyEliminatedTeams($match, $round, 2 - $losers->count()))->unique()->values();
                    }

                    $firstLoserId = $losers->get(0);
                    $secondLoserId = $losers->get(1);

                    if ($firstLoserId !== null && $secondLoserId !== null) {
                        $this->createGeneratedMatch($match, '3RD', '3RD', $firstLoserId, $secondLoserId);
                    }
                }

                $this->createGeneratedMatch($match, 'FINAL', 'FINAL', $winners->get(0), $winners->get(1));

                return;
            }

            $nextMatchType = $winners->count() === 4 ? 'SF' : 'ROUND';
            $matchNumber = 1;
            $remainingWinners = $winners;

            if ($remainingWinners->count() % 2 !== 0) {
                $byeTeamId = $this->highestDifferentialWinner($match, $remainingWinners, $stageMatches);
                $remainingWinners = $remainingWinners->reject(fn ($teamId) => $teamId === $byeTeamId)->values();
                $matchNo = "R{$nextRound}-".str_pad((string) $matchNumber, 3, '0', STR_PAD_LEFT);
                $this->createGeneratedMatch($match, 'BYE', $matchNo, $byeTeamId, null, $byeTeamId);
                $matchNumber++;
            }

            foreach ($remainingWinners->chunk(2) as $pair) {
                $team1Id = $pair->values()->get(0);
                $team2Id = $pair->values()->get(1);

                if ($team1Id === null || $team2Id === null) {
                    continue;
                }

                $this->createGeneratedMatch(
                    $match,
                    $nextMatchType,
                    "R{$nextRound}-".str_pad((string) $matchNumber, 3, '0', STR_PAD_LEFT),
                    $team1Id,
                    $team2Id
                );
                $matchNumber++;
            }
        });
    }

    /**
     * Award the bye to the advancing team with the best cumulative playoff
     * score differential. If tied, use the current round's score differential.
     * Stable team ID ordering makes any remaining tie deterministic.
     *
     * @param  Collection<int, int>  $teamIds
     * @param  Collection<int, PlayoffMatches>  $stageMatches
     */
    private function highestDifferentialWinner(PlayoffMatches $match, Collection $teamIds, Collection $stageMatches): int
    {
        $matches = PlayoffMatches::query()
            ->where('tournament_id', $match->tournament_id)
            ->where('team_category', $match->team_category)
            ->whereIn('match_type', ['ROUND', 'SF', 'SF1', 'SF2', '3RD', 'FINAL'])
            ->whereNotNull('team1_score')
            ->whereNotNull('team2_score')
            ->get();

        $differentialByTeam = [];

        foreach ($matches as $playedMatch) {
            $differentialByTeam[$playedMatch->team1_id] = ($differentialByTeam[$playedMatch->team1_id] ?? 0)
                + (int) $playedMatch->team1_score - (int) $playedMatch->team2_score;
            $differentialByTeam[$playedMatch->team2_id] = ($differentialByTeam[$playedMatch->team2_id] ?? 0)
                + (int) $playedMatch->team2_score - (int) $playedMatch->team1_score;
        }

        $currentRoundDifferential = [];

        foreach ($stageMatches as $stageMatch) {
            if ($stageMatch->match_type === 'BYE' || $stageMatch->team2_id === null) {
                continue;
            }

            $currentRoundDifferential[$stageMatch->team1_id] = (int) $stageMatch->team1_score - (int) $stageMatch->team2_score;
            $currentRoundDifferential[$stageMatch->team2_id] = (int) $stageMatch->team2_score - (int) $stageMatch->team1_score;
        }

        return $teamIds
            ->sort(function ($firstId, $secondId) use ($differentialByTeam, $currentRoundDifferential): int {
                return (($differentialByTeam[$secondId] ?? 0) <=> ($differentialByTeam[$firstId] ?? 0))
                    ?: (($currentRoundDifferential[$secondId] ?? 0) <=> ($currentRoundDifferential[$firstId] ?? 0))
                    ?: ($firstId <=> $secondId);
            })
            ->first();
    }

    /**
     * @return array<int, int>
     */
    private function mostRecentlyEliminatedTeams(PlayoffMatches $match, int $beforeRound, int $limit): array
    {
        $eliminatedTeams = [];

        for ($round = $beforeRound - 1; $round >= 1 && count($eliminatedTeams) < $limit; $round--) {
            $roundMatches = PlayoffMatches::query()
                ->where('tournament_id', $match->tournament_id)
                ->where('team_category', $match->team_category)
                ->whereIn('match_type', ['ROUND', 'SF'])
                ->where('match_no', 'like', "R{$round}-%")
                ->whereNotNull('winner_id')
                ->orderByDesc('match_no')
                ->get();

            foreach ($roundMatches as $roundMatch) {
                $loserId = (int) ($roundMatch->winner_id == $roundMatch->team1_id ? $roundMatch->team2_id : $roundMatch->team1_id);

                if (! in_array($loserId, $eliminatedTeams, true)) {
                    $eliminatedTeams[] = $loserId;
                }

                if (count($eliminatedTeams) >= $limit) {
                    break;
                }
            }
        }

        return $eliminatedTeams;
    }

    private function createGeneratedMatch(
        PlayoffMatches $sourceMatch,
        string $type,
        string $matchNo,
        int $team1Id,
        ?int $team2Id,
        ?int $winnerId = null
    ): void {
        PlayoffMatches::create([
            'code' => 'PLAYOFF-'.Str::upper(Str::random(12)),
            'tournament_id' => $sourceMatch->tournament_id,
            'team_bracket' => null,
            'team_category' => $sourceMatch->team_category,
            'match_type' => $type,
            'match_no' => $matchNo,
            'team1_id' => $team1Id,
            'team2_id' => $team2Id,
            'team1_score' => null,
            'team2_score' => null,
            'winner_id' => $winnerId,
            'is_finalized' => $type === 'BYE' || ($type === '3RD' && $team2Id === null),
            'user_id' => $sourceMatch->user_id,
        ]);
    }

    /**
     * Delete one playoff match.
     */
    public function destroy($id)
    {
        /*
         * Decrypt match ID.
         */
        try {
            $matchId = Crypt::decryptString(
                $id
            );
        } catch (\Exception $e) {
            abort(404);
        }

        /*
         * Find match.
         */
        $match = PlayoffMatches::findOrFail(
            $matchId
        );

        /*
         * Delete.
         */
        $match->delete();

        return redirect()
            ->back()
            ->with(
                'success',
                'Playoff match successfully deleted.'
            );
    }

    /**
     * Bulk delete playoff matches.
     */
    public function bulkDestroy(
        Request $request
    ) {

        /*
         * Validate.
         */
        $validated = $request->validate([

            'match_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'match_ids.*' => [
                'required',
                'string',
            ],

            'tournament_id' => [
                'required',
                'string',
            ],

            'team_category' => [
                'required',
                'string',
            ],
        ]);

        /*
         * Decrypt tournament ID.
         */
        try {

            $tournamentId =
                Crypt::decryptString(
                    $validated['tournament_id']
                );

        } catch (\Exception $e) {

            abort(404);
        }

        /*
         * Decrypt selected match IDs.
         */
        $matchIds = [];

        foreach (
            $validated['match_ids'] as $encryptedId
        ) {

            try {

                $matchIds[] =
                    Crypt::decryptString(
                        $encryptedId
                    );

            } catch (\Exception $e) {

                continue;
            }
        }

        /*
         * No valid IDs.
         */
        if (empty($matchIds)) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    'No valid playoff matches were selected.'
                );
        }

        /*
         * Delete only matches belonging to
         * the selected tournament/category.
         */
        $deletedCount =
            PlayoffMatches::where(
                'tournament_id',
                $tournamentId
            )
                ->where(
                    'team_category',
                    $validated['team_category']
                )
                ->whereIn(
                    'id',
                    $matchIds
                )
                ->delete();

        return redirect()
            ->back()
            ->with(
                'success',
                "{$deletedCount} playoff match(es) successfully deleted."
            );
    }
}
