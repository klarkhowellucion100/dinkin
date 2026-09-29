<?php

namespace App\Http\Controllers;

use App\Models\Matches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicTournamentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Public Tournament Landing Page
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $tournaments = DB::table('tournaments')
            ->orderBy('created_at', 'desc')
            ->get();

        return view(
            'app.tournament.public.index',
            compact('tournaments')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Get Categories and Brackets
    |--------------------------------------------------------------------------
    */

    public function filters(Request $request)
    {
        $request->validate([
            'tournament_id' => 'required|integer',
        ]);

        $tournamentId = $request->tournament_id;

        $categories = DB::table('teams')
            ->where('tournament_id', $tournamentId)
            ->select('team_category')
            ->distinct()
            ->orderBy('team_category')
            ->pluck('team_category');

        $brackets = DB::table('teams')
            ->where('tournament_id', $tournamentId)
            ->select('team_bracket')
            ->distinct()
            ->orderBy('team_bracket')
            ->pluck('team_bracket');

        return response()->json([
            'categories' => $categories,
            'brackets' => $brackets,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Public Tournament Data
    |--------------------------------------------------------------------------
    */

    public function data(Request $request)
    {
        $validated = $request->validate([
            'tournament_id' => 'required|integer',
            'team_category' => 'required|string',
            'team_bracket' => 'required|string',
        ]);

        $tournamentId = $validated['tournament_id'];
        $category = $validated['team_category'];
        $bracket = $validated['team_bracket'];

        /*
        |--------------------------------------------------------------------------
        | Tournament
        |--------------------------------------------------------------------------
        */

        $tournament = DB::table('tournaments')
            ->where('id', $tournamentId)
            ->first();

        if (! $tournament) {
            return response()->json([
                'message' => 'Tournament not found.',
            ], 404)->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        /*
        |--------------------------------------------------------------------------
        | Teams
        |--------------------------------------------------------------------------
        */

        $teams = DB::table('teams')
            ->where('tournament_id', $tournamentId)
            ->where('team_category', $category)
            ->where('team_bracket', $bracket)
            ->orderBy('team_no')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Completed Round Robin Matches
        |--------------------------------------------------------------------------
        */

        $matches = Matches::where('tournament_id', $tournamentId)
            ->where('team_category', $category)
            ->where('team_bracket', $bracket)
            ->whereNotNull('team1_score')
            ->whereNotNull('team2_score')
            ->whereNotNull('winner_id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Standings
        |--------------------------------------------------------------------------
        */

        $standings = collect();

        foreach ($teams as $team) {

            $gamesToBePlayed = max(
                $teams->count() - 1,
                0
            );

            $gamesPlayed = 0;
            $wins = 0;
            $losses = 0;
            $pointsFor = 0;
            $pointsAgainst = 0;

            foreach ($matches as $match) {

                /*
                |--------------------------------------------------------------------------
                | Team 1
                |--------------------------------------------------------------------------
                */

                if ($match->team1_id == $team->id) {

                    $gamesPlayed++;

                    if ($match->winner_id == $team->id) {

                        $wins++;

                        $pointsFor += $match->team1_score;
                        $pointsAgainst += $match->team2_score;

                    } else {

                        $losses++;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Team 2
                |--------------------------------------------------------------------------
                */

                elseif ($match->team2_id == $team->id) {

                    $gamesPlayed++;

                    if ($match->winner_id == $team->id) {

                        $wins++;

                        $pointsFor += $match->team2_score;
                        $pointsAgainst += $match->team1_score;

                    } else {

                        $losses++;
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

            $standings->push([
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
        | 1. Wins DESC
        | 2. Point Differential DESC
        | 3. Points Against ASC
        |
        */

        $standings = $standings
            ->sort(function ($a, $b) {

                if ($a['wins'] != $b['wins']) {

                    return $b['wins']
                        <=>
                        $a['wins'];
                }

                if (
                    $a['point_differential']
                    !=
                    $b['point_differential']
                ) {

                    return $b['point_differential']
                        <=>
                        $a['point_differential'];
                }

                if (
                    $a['points_against']
                    !=
                    $b['points_against']
                ) {

                    return $a['points_against']
                        <=>
                        $b['points_against'];
                }

                return strcmp(
                    $a['team_no'],
                    $b['team_no']
                );
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Assign Rank
        |--------------------------------------------------------------------------
        */

        $previousStanding = null;
        $previousRank = null;

        $standings = $standings
            ->map(function ($standing, $index) use (&$previousStanding, &$previousRank) {
                $isTiedWithPrevious = $previousStanding !== null
                    && $standing['wins'] === $previousStanding['wins']
                    && $standing['point_differential'] === $previousStanding['point_differential']
                    && $standing['points_against'] === $previousStanding['points_against'];

                $standing['rank'] = $isTiedWithPrevious
                    ? $previousRank
                    : $index + 1;

                $previousRank = $standing['rank'];
                $previousStanding = $standing;

                return $standing;
            });

        /*
        |--------------------------------------------------------------------------
        | Round Robin Match Data
        |--------------------------------------------------------------------------
        */

        $matchData = DB::table('matches')
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
                'matches.team_category',
                $category
            )
            ->where(
                'matches.team_bracket',
                $bracket
            )
            ->select([
                'matches.id',
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
            ])
            ->orderBy('matches.round_no')
            ->orderBy('matches.match_no')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Playoff Matches
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Playoff matches do NOT have team_bracket.
        |
        | They are filtered only by:
        | tournament + category
        |
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
                $category
            )

            ->where(
                'playoff_matches_controllers.match_type',
                '!=',
                'BYE'
            )

            ->select([
                'playoff_matches_controllers.id',

                'playoff_matches_controllers.match_type',
                'playoff_matches_controllers.match_no',

                'playoff_matches_controllers.team1_id',
                'team1.team_no as team1_no',
                'team1.team_name as team1_name',
                'team1.team_bracket as team1_bracket',

                'playoff_matches_controllers.team2_id',
                'team2.team_no as team2_no',
                'team2.team_name as team2_name',
                'team2.team_bracket as team2_bracket',

                'playoff_matches_controllers.team1_score',
                'playoff_matches_controllers.team2_score',
                'playoff_matches_controllers.is_finalized',

                'playoff_matches_controllers.winner_id',

                'winner.team_no as winner_no',
                'winner.team_name as winner_name',
                'winner.team_bracket as winner_bracket',
            ])

            ->orderByRaw("
                CASE
                    WHEN playoff_matches_controllers.match_type IN ('ROUND', 'SF') THEN 1
                    WHEN playoff_matches_controllers.match_type = '3RD' THEN 2
                    WHEN playoff_matches_controllers.match_type = 'FINAL' THEN 3
                    ELSE 4
                END
            ")

            ->orderBy('playoff_matches_controllers.match_no')

            ->get();

        /*
        |--------------------------------------------------------------------------
        | Podium
        |--------------------------------------------------------------------------
        */

        $podium = [
            'champion' => null,
            'runner_up' => null,
            'third_place' => null,
        ];

        $getWinnerId = static function ($match): ?int {
            if ($match->winner_id !== null) {
                return (int) $match->winner_id;
            }

            if (! $match->is_finalized || $match->team1_score === null || $match->team2_score === null || $match->team1_score == $match->team2_score) {
                return null;
            }

            return (int) ($match->team1_score > $match->team2_score
                ? $match->team1_id
                : $match->team2_id);
        };

        /*
        |--------------------------------------------------------------------------
        | Final
        |--------------------------------------------------------------------------
        */

        $final = $playoffMatches->first(function ($match) use ($getWinnerId) {
            return $match->match_type === 'FINAL'
                && $getWinnerId($match) !== null;
        });

        if ($final) {
            $finalWinnerId = $getWinnerId($final);

            /*
            | Champion
            */

            if ($finalWinnerId == $final->team1_id) {

                $podium['champion'] = [
                    'team_id' => $final->team1_id,
                    'team_no' => $final->team1_no,
                    'team_name' => $final->team1_name,
                    'bracket' => $final->team1_bracket,
                    'score' => $final->team1_score,
                ];

                $podium['runner_up'] = [
                    'team_id' => $final->team2_id,
                    'team_no' => $final->team2_no,
                    'team_name' => $final->team2_name,
                    'bracket' => $final->team2_bracket,
                    'score' => $final->team2_score,
                ];

            } else {

                $podium['champion'] = [
                    'team_id' => $final->team2_id,
                    'team_no' => $final->team2_no,
                    'team_name' => $final->team2_name,
                    'bracket' => $final->team2_bracket,
                    'score' => $final->team2_score,
                ];

                $podium['runner_up'] = [
                    'team_id' => $final->team1_id,
                    'team_no' => $final->team1_no,
                    'team_name' => $final->team1_name,
                    'bracket' => $final->team1_bracket,
                    'score' => $final->team1_score,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3rd Place
        |--------------------------------------------------------------------------
        */

        $thirdPlace = $playoffMatches->first(function ($match) use ($getWinnerId) {
            return $match->match_type === '3RD'
                && $getWinnerId($match) !== null;
        });

        if ($thirdPlace) {
            $thirdPlaceWinnerId = $getWinnerId($thirdPlace);
            $previousMatchScore = null;

            if ($thirdPlace->team2_id === null) {
                $sourceMatch = $playoffMatches->first(function ($candidate) use ($thirdPlace) {
                    $teamWasInMatch = (int) $candidate->team1_id === (int) $thirdPlace->team1_id
                        || (int) $candidate->team2_id === (int) $thirdPlace->team1_id;

                    return $candidate->id !== $thirdPlace->id
                        && $candidate->match_type !== 'BYE'
                        && $teamWasInMatch
                        && $candidate->winner_id !== null
                        && (int) $candidate->winner_id !== (int) $thirdPlace->team1_id
                        && $candidate->team1_score !== null
                        && $candidate->team2_score !== null;
                });

                if ($sourceMatch) {
                    $teamScore = (int) $sourceMatch->team1_id === (int) $thirdPlace->team1_id
                        ? $sourceMatch->team1_score
                        : $sourceMatch->team2_score;
                    $opponentScore = (int) $sourceMatch->team1_id === (int) $thirdPlace->team1_id
                        ? $sourceMatch->team2_score
                        : $sourceMatch->team1_score;
                    $previousMatchScore = "{$teamScore}–{$opponentScore}";
                }
            }

            if ($thirdPlaceWinnerId == $thirdPlace->team1_id) {

                $podium['third_place'] = [
                    'team_id' => $thirdPlace->team1_id,
                    'team_no' => $thirdPlace->team1_no,
                    'team_name' => $thirdPlace->team1_name,
                    'bracket' => $thirdPlace->team1_bracket,
                    'score' => $thirdPlace->team1_score,
                    'automatic' => $thirdPlace->team2_id === null,
                    'previous_match_score' => $previousMatchScore,
                ];

            } else {

                $podium['third_place'] = [
                    'team_id' => $thirdPlace->team2_id,
                    'team_no' => $thirdPlace->team2_no,
                    'team_name' => $thirdPlace->team2_name,
                    'bracket' => $thirdPlace->team2_bracket,
                    'score' => $thirdPlace->team2_score,
                    'automatic' => false,
                    'previous_match_score' => null,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Return JSON
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'tournament' => $tournament,
            'standings' => $standings,
            'matches' => $matchData,
            'playoffs' => $playoffMatches
                ->reject(fn ($playoffMatch) => $playoffMatch->match_type === '3RD' && $playoffMatch->team2_id === null)
                ->values(),
            'podium' => $podium,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
