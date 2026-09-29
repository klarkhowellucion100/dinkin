<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        /*
        |--------------------------------------------------------------------------
        | BASIC COUNTS
        |--------------------------------------------------------------------------
        */

        $totalTournaments = DB::table('tournaments')
            ->where('user_id', $userId)
            ->count();

        $totalTeams = DB::table('teams')
            ->where('user_id', $userId)
            ->count();

        $totalMatches = DB::table('matches')
            ->where('user_id', $userId)
            ->count();

        $totalPlayoffMatches = DB::table('playoff_matches_controllers')
            ->where('user_id', $userId)
            ->where('match_type', '!=', 'BYE')
            ->whereNotNull('team2_id')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | COMPLETED / PENDING ROUND ROBIN MATCHES
        |--------------------------------------------------------------------------
        */

        $completedMatches = DB::table('matches')
            ->where('user_id', $userId)
            ->whereNotNull('team1_score')
            ->whereNotNull('team2_score')
            ->count();

        $pendingMatches = DB::table('matches')
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->whereNull('team1_score')
                    ->orWhereNull('team2_score');
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | PLAYOFF COMPLETION
        |--------------------------------------------------------------------------
        */

        $completedPlayoffMatches = DB::table(
            'playoff_matches_controllers'
        )
            ->where('user_id', $userId)
            ->where('match_type', '!=', 'BYE')
            ->whereNotNull('team2_id')
            ->whereNotNull('team1_score')
            ->whereNotNull('team2_score')
            ->count();

        $pendingPlayoffMatches = DB::table(
            'playoff_matches_controllers'
        )
            ->where('user_id', $userId)
            ->where('match_type', '!=', 'BYE')
            ->whereNotNull('team2_id')
            ->where(function ($query) {
                $query->whereNull('team1_score')
                    ->orWhereNull('team2_score');
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | ALL MATCHES
        |--------------------------------------------------------------------------
        */

        $allMatches =
            $totalMatches +
            $totalPlayoffMatches;

        $allCompletedMatches =
            $completedMatches +
            $completedPlayoffMatches;

        $allPendingMatches =
            $pendingMatches +
            $pendingPlayoffMatches;

        /*
        |--------------------------------------------------------------------------
        | MATCH PROGRESS
        |--------------------------------------------------------------------------
        */

        $matchProgress = $allMatches > 0
            ? round(
                ($allCompletedMatches / $allMatches) * 100
            )
            : 0;

        /*
        |--------------------------------------------------------------------------
        | UPCOMING TOURNAMENT
        |--------------------------------------------------------------------------
        */

        $upcomingTournament = DB::table('tournaments')
            ->where('user_id', $userId)
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | LATEST TOURNAMENT
        |--------------------------------------------------------------------------
        */

        $latestTournament = DB::table('tournaments')
            ->where('user_id', $userId)
            ->orderByDesc('date')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | RECENT TOURNAMENTS
        |--------------------------------------------------------------------------
        |
        | We use separate subqueries for teams and matches.
        | This prevents a JOIN between teams and matches from
        | multiplying the rows and producing incorrect counts.
        |
        */

        $teamCounts = DB::table('teams')
            ->select(
                'tournament_id',
                DB::raw('COUNT(*) as team_count')
            )
            ->where('user_id', $userId)
            ->groupBy('tournament_id');

        $matchCounts = DB::table('matches')
            ->select(
                'tournament_id',
                DB::raw('COUNT(*) as total_matches'),
                DB::raw('
                    SUM(
                        CASE
                            WHEN team1_score IS NOT NULL
                            AND team2_score IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) as completed_matches
                ')
            )
            ->where('user_id', $userId)
            ->groupBy('tournament_id');

        $recentTournaments = DB::table('tournaments')
            ->leftJoinSub(
                $teamCounts,
                'team_counts',
                function ($join) {
                    $join->on(
                        'tournaments.id',
                        '=',
                        'team_counts.tournament_id'
                    );
                }
            )
            ->leftJoinSub(
                $matchCounts,
                'match_counts',
                function ($join) {
                    $join->on(
                        'tournaments.id',
                        '=',
                        'match_counts.tournament_id'
                    );
                }
            )
            ->where('tournaments.user_id', $userId)
            ->select([
                'tournaments.id',
                'tournaments.name',
                'tournaments.date',
                'tournaments.venue',

                DB::raw(
                    'COALESCE(team_counts.team_count, 0) as team_count'
                ),

                DB::raw(
                    'COALESCE(match_counts.total_matches, 0) as total_matches'
                ),

                DB::raw(
                    'COALESCE(match_counts.completed_matches, 0) as completed_matches'
                ),
            ])
            ->orderByDesc('tournaments.date')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | TOURNAMENT PROGRESS
        |--------------------------------------------------------------------------
        */

        $recentTournaments = $recentTournaments->map(
            function ($tournament) {

                $tournament->progress =
                    $tournament->total_matches > 0
                        ? round(
                            (
                                $tournament->completed_matches /
                                $tournament->total_matches
                            ) * 100
                        )
                        : 0;

                return $tournament;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | RECENT ROUND ROBIN MATCH RESULTS
        |--------------------------------------------------------------------------
        |
        | Join team1, team2 and winner separately.
        |
        */

        $recentMatches = DB::table('matches')
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
            ->where('matches.user_id', $userId)
            ->whereNotNull('matches.team1_score')
            ->whereNotNull('matches.team2_score')
            ->select([
                'matches.id',

                'matches.round_no',
                'matches.match_no',

                'team1.team_no as team1_no',
                'team1.team_name as team1_name',

                'team2.team_no as team2_no',
                'team2.team_name as team2_name',

                'matches.team1_score',
                'matches.team2_score',

                'matches.winner_id',

                'winner.team_no as winner_no',
                'winner.team_name as winner_name',

                'matches.updated_at',
            ])
            ->orderByDesc('matches.updated_at')
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | TEAM CATEGORY SUMMARY
        |--------------------------------------------------------------------------
        */

        $categorySummary = DB::table('teams')
            ->where('user_id', $userId)
            ->select([
                'team_category',
                DB::raw('COUNT(*) as team_count'),
            ])
            ->groupBy('team_category')
            ->orderByDesc('team_count')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | RETURN DASHBOARD
        |--------------------------------------------------------------------------
        */

        return view(
            'dashboard',
            compact(
                'totalTournaments',
                'totalTeams',
                'totalMatches',
                'totalPlayoffMatches',

                'completedMatches',
                'pendingMatches',

                'completedPlayoffMatches',
                'pendingPlayoffMatches',

                'allMatches',
                'allCompletedMatches',
                'allPendingMatches',

                'matchProgress',

                'upcomingTournament',
                'latestTournament',

                'recentTournaments',
                'recentMatches',

                'categorySummary'
            )
        );
    }
}
