<?php

namespace App\Http\Controllers;

use App\Models\Matches;
use App\Models\Teams;
use App\Services\RoundRobinMatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class MatchesController extends Controller
{
    public function __construct(private readonly RoundRobinMatchService $matchSchedule) {}

    public function generate(Request $request, $tournament_id)
    {
        /*
        |--------------------------------------------------------------------------
        | Decrypt Tournament ID
        |--------------------------------------------------------------------------
        */

        try {
            $tournamentId = Crypt::decryptString($tournament_id);
        } catch (\Exception $e) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'team_bracket' => 'required|string',
            'team_category' => 'required|string',
        ]);

        $bracket = $validated['team_bracket'];
        $category = $validated['team_category'];

        /*
        |--------------------------------------------------------------------------
        | Get Teams
        |--------------------------------------------------------------------------
        */

        $teams = Teams::where(
            'tournament_id',
            $tournamentId
        )
            ->where(
                'team_bracket',
                $bracket
            )
            ->where(
                'team_category',
                $category
            )
            ->orderBy('team_no')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Validate Team Count
        |--------------------------------------------------------------------------
        */

        if ($teams->count() < 2) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    "At least 2 teams are required to generate matches for Bracket {$bracket} - {$category}."
                );

        }

        $this->matchSchedule->sync(
            (int) $tournamentId,
            $bracket,
            $category,
            (int) Auth::id()
        );

        return redirect()
            ->back()
            ->with(
                'success',
                "Round robin matches successfully refreshed for Bracket {$bracket} - {$category}."
            );
    }

    public function view($tournament_id, $team_bracket, $team_category)
    {
        /*
        |--------------------------------------------------------------------------
        | Decrypt Tournament ID
        |--------------------------------------------------------------------------
        */

        try {
            $tournamentId = Crypt::decryptString($tournament_id);
        } catch (\Exception $e) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Get Tournament
        |--------------------------------------------------------------------------
        */

        $tournament = DB::table('tournaments')
            ->where('id', $tournamentId)
            ->first();

        if (! $tournament) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Get Matches with Team Information
        |--------------------------------------------------------------------------
        */

        $matches = DB::table('matches')
            ->join(
                'teams as team1',
                'matches.team1_id',
                '=',
                'team1.id'
            )
            ->join(
                'teams as team2',
                'matches.team2_id',
                '=',
                'team2.id'
            )
            ->leftJoin(
                'teams as winner',
                'matches.winner_id',
                '=',
                'winner.id'
            )
            ->where(
                'matches.tournament_id',
                $tournamentId
            )
            ->where(
                'matches.team_bracket',
                $team_bracket
            )
            ->where(
                'matches.team_category',
                $team_category
            )
            ->select([
                'matches.id',
                'matches.code',
                'matches.tournament_id',
                'matches.team_bracket',
                'matches.team_category',

                'matches.round_no',
                'matches.match_no',

                'matches.team1_id',
                'team1.team_no as team1_no',
                'team1.team_name as team1_name',

                'matches.team2_id',
                'team2.team_no as team2_no',
                'team2.team_name as team2_name',

                'matches.team1_score',
                'matches.team2_score',

                'matches.winner_id',
                'winner.team_no as winner_no',
                'winner.team_name as winner_name',

                'matches.user_id',
                'matches.created_at',
                'matches.updated_at',
            ])
            ->orderBy('matches.round_no')
            ->orderBy('matches.match_no')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */

        return view(
            'app.tournament.matches.view',
            [
                'tournament' => $tournament,
                'matches' => $matches,
                'team_bracket' => $team_bracket,
                'team_category' => $team_category,
            ]

        );
    }

    public function updateScore(
        Request $request,
        $id
    ) {

        /*
        |--------------------------------------------------------------------------
        | Decrypt Match ID
        |--------------------------------------------------------------------------
        */

        try {

            $matchId = Crypt::decryptString(
                $id
            );

        } catch (\Exception $e) {

            abort(404);

        }

        /*
        |--------------------------------------------------------------------------
        | Get Match
        |--------------------------------------------------------------------------
        */

        $match = Matches::findOrFail(
            $matchId
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Scores
        |--------------------------------------------------------------------------
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

        ]);

        $isFinal = (bool) ($request->validate([
            'finalize_score' => ['sometimes', 'boolean'],
        ])['finalize_score'] ?? false);

        if ($isFinal && $validated['team1_score'] == $validated['team2_score']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'A tied score can be saved while the match is ongoing. Enter a winning score before finalizing.',
                ], 422);
            }

            return redirect()->back()->with('error', 'A tied score can be saved while the match is ongoing. Enter a winning score before finalizing.');
        }

        /*
        |--------------------------------------------------------------------------
        | Determine Winner
        |--------------------------------------------------------------------------
        */

        if (! $isFinal) {
            $winnerId = null;
        } elseif (
            $validated['team1_score'] >
            $validated['team2_score']
        ) {

            $winnerId = $match->team1_id;

        } else {

            $winnerId = $match->team2_id;

        }

        /*
        |--------------------------------------------------------------------------
        | Update Match
        |--------------------------------------------------------------------------
        */

        $match->update([

            'team1_score' => $validated['team1_score'],

            'team2_score' => $validated['team2_score'],

            'winner_id' => $winnerId,

        ]);

        if ($request->expectsJson()) {
            $winner = $winnerId === null ? null : Teams::find($winnerId);

            return response()->json([
                'message' => $isFinal ? 'Final score successfully saved.' : 'Score saved. Match is still ongoing.',
                'match_id' => $match->id,
                'team1_score' => (int) $match->team1_score,
                'team2_score' => (int) $match->team2_score,
                'winner_id' => $winnerId === null ? null : (int) $winnerId,
                'winner_no' => $winner?->team_no,
                'winner_name' => $winner?->team_name,
                'is_final' => $isFinal,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Success Message
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->back()
            ->with(
                'success',
                'Match score successfully updated.'
            );

    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'match_ids' => 'required|array|min:1',
            'match_ids.*' => 'required|string',
            'tournament_id' => 'required|string',
            'team_bracket' => 'required|string',
            'team_category' => 'required|string',
        ]);

        try {
            $tournamentId = Crypt::decryptString(
                $validated['tournament_id']
            );
        } catch (\Exception $e) {
            abort(404);
        }

        $matchIds = [];

        foreach ($validated['match_ids'] as $encryptedId) {
            try {
                $matchIds[] = Crypt::decryptString($encryptedId);
            } catch (\Exception $e) {
                continue;
            }
        }

        if (empty($matchIds)) {
            return redirect()
                ->back()
                ->with('error', 'No valid matches were selected.');
        }

        $deletedCount = Matches::where('tournament_id', $tournamentId)
            ->where('team_bracket', $validated['team_bracket'])
            ->where('team_category', $validated['team_category'])
            ->whereIn('id', $matchIds)
            ->delete();

        return redirect()
            ->back()
            ->with(
                'success',
                "{$deletedCount} match(es) successfully deleted."
            );
    }

    public function standings(
        $tournament_id,
        $team_bracket,
        $team_category
    ) {
        /*
        |--------------------------------------------------------------------------
        | Decrypt Tournament ID
        |--------------------------------------------------------------------------
        */

        try {

            $tournamentId = Crypt::decryptString($tournament_id);

        } catch (\Exception $e) {

            abort(404);

        }

        /*
        |--------------------------------------------------------------------------
        | Get Tournament
        |--------------------------------------------------------------------------
        */

        $tournament = DB::table('tournaments')
            ->where('id', $tournamentId)
            ->first();

        if (! $tournament) {

            abort(404);

        }

        /*
        |--------------------------------------------------------------------------
        | Get Teams
        |--------------------------------------------------------------------------
        |
        | Only get teams belonging to the selected:
        | Tournament + Bracket + Category
        |
        */

        $teams = DB::table('teams')
            ->where('tournament_id', $tournamentId)
            ->where('team_bracket', $team_bracket)
            ->where('team_category', $team_category)
            ->orderBy('team_no')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Get Completed Matches
        |--------------------------------------------------------------------------
        |
        | A match is considered completed when both scores are not NULL.
        |
        */

        $matches = Matches::where('tournament_id', $tournamentId)
            ->where('team_bracket', $team_bracket)
            ->where('team_category', $team_category)
            ->whereNotNull('team1_score')
            ->whereNotNull('team2_score')
            ->whereNotNull('winner_id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Build Standings
        |--------------------------------------------------------------------------
        */

        $standings = collect();

        foreach ($teams as $team) {

            /*
            |--------------------------------------------------------------------------
            | Default Values
            |--------------------------------------------------------------------------
            */

            $gamesToBePlayed = max(
                $teams->count() - 1,
                0
            );

            $gamesPlayed = 0;

            $wins = 0;

            $losses = 0;

            $pointsFor = 0;

            $pointsAgainst = 0;

            /*
            |--------------------------------------------------------------------------
            | Process Matches
            |--------------------------------------------------------------------------
            */

            foreach ($matches as $match) {

                /*
                |--------------------------------------------------------------------------
                | Team 1
                |--------------------------------------------------------------------------
                */

                if ($match->team1_id == $team->id) {

                    $gamesPlayed++;

                    /*
                    |--------------------------------------------------------------------------
                    | Team 1 Won
                    |--------------------------------------------------------------------------
                    */

                    if ($match->winner_id == $team->id) {

                        $wins++;

                        // Points For
                        // Only count points from WON matches
                        $pointsFor += $match->team1_score;

                        // Points Against
                        // Only count opponent points from WON matches
                        $pointsAgainst += $match->team2_score;

                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Team 1 Lost
                    |--------------------------------------------------------------------------
                    */

                    else {

                        $losses++;

                        // Do NOT add points for
                        // Do NOT add points against

                    }

                }

                /*
                |--------------------------------------------------------------------------
                | Team 2
                |--------------------------------------------------------------------------
                */

                elseif ($match->team2_id == $team->id) {

                    $gamesPlayed++;

                    /*
                    |--------------------------------------------------------------------------
                    | Team 2 Won
                    |--------------------------------------------------------------------------
                    */

                    if ($match->winner_id == $team->id) {

                        $wins++;

                        // Points For
                        // Only count points from WON matches
                        $pointsFor += $match->team2_score;

                        // Points Against
                        // Only count opponent points from WON matches
                        $pointsAgainst += $match->team1_score;

                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Team 2 Lost
                    |--------------------------------------------------------------------------
                    */

                    else {

                        $losses++;

                        // Do NOT add points for
                        // Do NOT add points against

                    }

                }

            }

            /*
            |--------------------------------------------------------------------------
            | Point Differential
            |--------------------------------------------------------------------------
            */

            $pointDifferential =
                $pointsFor - $pointsAgainst;

            /*
            |--------------------------------------------------------------------------
            | Add Team to Standings
            |--------------------------------------------------------------------------
            */

            $standings->push((object) [

                'team_id' => $team->id,

                'team_no' => $team->team_no,

                'team_name' => $team->team_name,

                'games_to_be_played' => $gamesToBePlayed,

                'games_played' => $gamesPlayed,

                'wins' => $wins,

                'losses' => $losses,

                'points_for' => $pointsFor,

                'points_against' => $pointsAgainst,

                'point_differential' => $pointDifferential,

            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | Sort Standings
        |--------------------------------------------------------------------------
        |
        | Ranking priority:
        |
        | 1. Wins              DESC
        | 2. Point Differential DESC
        | 3. Points Against    ASC
        |
        */

        $standings = $standings
            ->sort(function ($a, $b) {

                /*
                |--------------------------------------------------------------------------
                | 1. Wins
                |--------------------------------------------------------------------------
                */

                if ($a->wins != $b->wins) {

                    return $b->wins <=> $a->wins;

                }

                /*
                |--------------------------------------------------------------------------
                | 2. Point Differential
                |--------------------------------------------------------------------------
                */

                if (
                    $a->point_differential !=
                    $b->point_differential
                ) {

                    return
                        $b->point_differential
                        <=>
                        $a->point_differential;

                }

                /*
                |--------------------------------------------------------------------------
                | 3. Points Against
                |--------------------------------------------------------------------------
                |
                | Lower Points Against ranks higher.
                |
                */

                if (
                    $a->points_against !=
                    $b->points_against
                ) {

                    return
                        $a->points_against
                        <=>
                        $b->points_against;

                }

                /*
                |--------------------------------------------------------------------------
                | Exact Tie
                |--------------------------------------------------------------------------
                |
                | If all three criteria are identical,
                | keep the existing order for now.
                |
                */

                return 0;

            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Assign Rank
        |--------------------------------------------------------------------------
        */

        $standings = $standings->map(
            function ($standing, $index) {

                $standing->rank =
                    $index + 1;

                return $standing;

            }
        );

        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */

        return view(
            'app.tournament.matches.standings',
            compact(
                'tournament',
                'team_bracket',
                'team_category',
                'standings'
            )
        );
    }
}
