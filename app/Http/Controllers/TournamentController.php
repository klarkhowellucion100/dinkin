<?php

namespace App\Http\Controllers;

use App\Models\Matches;
use App\Models\Teams;
use App\Models\Tournament;
use App\Models\TournamentFormat;
use App\Services\CourtScheduleService;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TournamentController extends Controller
{
    public function __construct(private readonly CourtScheduleService $courtSchedule) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allTournaments = Tournament::orderBy('date', 'desc')->paginate(10, ['*'], 'index_page');

        return view('app.tournament.index', [
            'allTournaments' => $allTournaments,
        ]);
    }

    public function search(Request $request)
    {
        $search = $request->input('search');
        $allTournaments = Tournament::where('name', 'like', '%'.$search.'%')->orderBy('date', 'desc')->paginate(10, ['*'], 'index_page');

        return view('app.tournament.index', [
            'allTournaments' => $allTournaments,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $allTournaments = Tournament::all();

        return view('app.tournament.create', [
            'allTournaments' => $allTournaments,
        ]);
    }

    public function store(Request $request)
    {
        $tournamentData = $request->validate([
            'name' => 'required|unique:tournaments,name',
            'date' => 'required|date',
            'venue' => 'required',
        ]);

        $tournamentData['user_id'] = Auth::id();
        $tournamentData['code'] = Str::random(10);

        Tournament::create($tournamentData);

        return redirect()->route('tournament.index')->with('success', 'Tournament created successfully.');
    }

    public function view(string $id)
    {
        // Only decrypt inside try-catch
        try {
            $realId = Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        // Let Laravel handle everything else
        $tournamentView = Tournament::where('id', $realId)->firstOrFail(); // Laravel handles 404 here

        $teams = Teams::where('tournament_id', $realId)
            ->orderBy('team_bracket')
            ->orderBy('team_category')
            ->orderBy('team_no')
            ->get();

        $teamsGrouped = $teams->groupBy([
            'team_bracket',
            'team_category',
        ]);

        $formats = TournamentFormat::query()
            ->where('tournament_id', $realId)
            ->get()
            ->keyBy(fn (TournamentFormat $format): string => $format->team_category.'|'.$format->team_bracket);

        return view('app.tournament.view', [
            'tournamentView' => $tournamentView,
            'teamsGrouped' => $teamsGrouped,
            'formats' => $formats,
        ]);
    }

    public function courtSchedule(string $id)
    {
        try {
            $realId = Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $tournamentView = Tournament::whereKey($realId)->firstOrFail();

        return view('app.tournament.court-schedule', [
            'tournamentView' => $tournamentView,
            'courtSchedule' => $this->getCourtSchedule($realId, $tournamentView),
            'standingsByCategory' => $this->getStandingsByCategory($realId),
        ]);
    }

    public function printCourtSchedule(string $id)
    {
        try {
            $realId = Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $tournament = Tournament::whereKey($realId)->firstOrFail();

        abort_unless(in_array($tournament->schedule_mode, [
            'timed_category',
            'timed_alternate_category',
            'timed_finish_category',
            'timed_finish_alternate_category',
            'timed_bracket',
            'timed_category_spread',
        ], true), 404);

        return view('app.tournament.timed-court-schedule', [
            'tournament' => $tournament,
            'courtSchedule' => $this->getCourtSchedule($realId, $tournament),
        ]);
    }

    private function getStandingsByCategory(int|string $tournamentId): Collection
    {
        $teams = DB::table('teams')
            ->where('tournament_id', $tournamentId)
            ->orderBy('team_category')
            ->orderBy('team_bracket')
            ->orderBy('team_no')
            ->get();

        $completedMatches = Matches::query()
            ->where('tournament_id', $tournamentId)
            ->whereNotNull('team1_score')
            ->whereNotNull('team2_score')
            ->whereNotNull('winner_id')
            ->get();

        return $teams
            ->groupBy(['team_category', 'team_bracket'])
            ->map(fn (Collection $brackets): Collection => $brackets->map(function (Collection $bracketTeams) use ($completedMatches): Collection {
                $standings = $bracketTeams->map(function (object $team) use ($completedMatches, $bracketTeams): object {
                    $gamesPlayed = 0;
                    $wins = 0;
                    $losses = 0;
                    $pointsFor = 0;
                    $pointsAgainst = 0;

                    foreach ($completedMatches as $match) {
                        if ((int) $match->team1_id !== (int) $team->id && (int) $match->team2_id !== (int) $team->id) {
                            continue;
                        }

                        $gamesPlayed++;
                        $teamIsFirst = (int) $match->team1_id === (int) $team->id;
                        $teamScore = $teamIsFirst ? $match->team1_score : $match->team2_score;
                        $opponentScore = $teamIsFirst ? $match->team2_score : $match->team1_score;

                        if ((int) $match->winner_id === (int) $team->id) {
                            $wins++;
                            $pointsFor += (int) $teamScore;
                            $pointsAgainst += (int) $opponentScore;
                        } else {
                            $losses++;
                        }
                    }

                    return (object) [
                        'team_id' => $team->id,
                        'team_no' => $team->team_no,
                        'team_name' => $team->team_name,
                        'games_to_be_played' => max($bracketTeams->count() - 1, 0),
                        'games_played' => $gamesPlayed,
                        'wins' => $wins,
                        'losses' => $losses,
                        'points_for' => $pointsFor,
                        'points_against' => $pointsAgainst,
                        'point_differential' => $pointsFor - $pointsAgainst,
                    ];
                });

                return $standings->sort(function (object $first, object $second): int {
                    return ($second->wins <=> $first->wins)
                        ?: ($second->point_differential <=> $first->point_differential)
                        ?: ($first->points_against <=> $second->points_against);
                })->values()->map(function (object $standing, int $index): object {
                    $standing->rank = $index + 1;

                    return $standing;
                });
            }));
    }

    private function getCourtSchedule(int|string $tournamentId, Tournament $tournament): Collection
    {
        $schedule = DB::table('matches')
            ->join('teams as team1', 'matches.team1_id', '=', 'team1.id')
            ->join('teams as team2', 'matches.team2_id', '=', 'team2.id')
            ->where('matches.tournament_id', $tournamentId)
            ->whereNotNull('matches.schedule_no')
            ->select([
                'matches.id',
                'matches.schedule_no',
                'matches.court_no',
                'matches.team_category',
                'matches.team_bracket',
                'matches.round_no',
                'matches.match_no',
                'matches.team1_score',
                'matches.team2_score',
                'matches.winner_id',
                'matches.is_finalized',
                'team1.team_no as team1_no',
                'team1.team_name as team1_name',
                'team2.team_no as team2_no',
                'team2.team_name as team2_name',
                'winner.team_no as winner_no',
                'winner.team_name as winner_name',
            ])
            ->leftJoin('teams as winner', 'matches.winner_id', '=', 'winner.id')
            ->orderBy('matches.schedule_no')
            ->orderBy('matches.court_no')
            ->get();

        if (! str_starts_with((string) $tournament->schedule_mode, 'timed_') || ! $tournament->schedule_start_time || ! $tournament->match_duration_minutes) {
            return $schedule;
        }

        $startTime = Carbon::createFromFormat('H:i', substr((string) $tournament->schedule_start_time, 0, 5));

        return $schedule->map(function (object $match) use ($startTime, $tournament): object {
            $matchStart = $startTime->copy()->addMinutes(((int) $match->schedule_no - 1) * (int) $tournament->match_duration_minutes);
            $matchEnd = $matchStart->copy()->addMinutes((int) $tournament->match_duration_minutes);
            $match->schedule_time = $matchStart->format('g:i A').' – '.$matchEnd->format('g:i A');

            return $match;
        });
    }

    public function updateCourtCount(Request $request, string $id)
    {
        try {
            $realId = Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $validated = $request->validate([
            'court_count' => ['required', 'integer', 'min:1', 'max:100'],
            'schedule_mode' => ['sometimes', 'string', 'in:bracket,category,timed_category,timed_alternate_category,timed_finish_category,timed_finish_alternate_category,timed_bracket,timed_category_spread'],
            'schedule_start_time' => ['required_if:schedule_mode,timed_category', 'required_if:schedule_mode,timed_alternate_category', 'required_if:schedule_mode,timed_finish_category', 'required_if:schedule_mode,timed_finish_alternate_category', 'required_if:schedule_mode,timed_bracket', 'required_if:schedule_mode,timed_category_spread', 'nullable', 'date_format:H:i'],
            'match_duration_minutes' => ['required_if:schedule_mode,timed_category', 'required_if:schedule_mode,timed_alternate_category', 'required_if:schedule_mode,timed_finish_category', 'required_if:schedule_mode,timed_finish_alternate_category', 'required_if:schedule_mode,timed_bracket', 'required_if:schedule_mode,timed_category_spread', 'nullable', 'integer', 'min:1', 'max:240'],
        ]);
        $scheduleMode = $validated['schedule_mode'] ?? 'bracket';

        $settings = [
            'court_count' => $validated['court_count'],
            'schedule_mode' => $scheduleMode,
        ];

        if (str_starts_with($scheduleMode, 'timed_')) {
            $settings['schedule_start_time'] = $validated['schedule_start_time'];
            $settings['match_duration_minutes'] = $validated['match_duration_minutes'];
        }

        Tournament::whereKey($realId)->update($settings);

        $this->courtSchedule->assign(
            $realId,
            (int) $validated['court_count'],
            $scheduleMode
        );

        return redirect()
            ->route('tournament.courtSchedule', Crypt::encryptString($realId))
            ->with('success', "Court schedule generated per {$scheduleMode} across {$validated['court_count']} court(s).");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Only decrypt inside try-catch
        try {
            $realId = Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        // Let Laravel handle everything else
        $tournamentEdit = Tournament::where('id', $realId)->firstOrFail(); // Laravel handles 404 here

        $allTournaments = Tournament::all();

        return view('app.tournament.edit', [
            'tournamentEdit' => $tournamentEdit,
            'allTournaments' => $allTournaments,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Only decrypt inside try-catch
        try {
            $realId = Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        // Validate input
        $tournamentData = $request->validate([
            'name' => 'required|unique:tournaments,name,'.$realId,
            'date' => 'required|date',
            'venue' => 'required',
        ]);

        // Update record
        Tournament::where('id', $realId)->update($tournamentData);

        return redirect()
            ->route('tournament.index')
            ->with('success', 'Tournament updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Only decrypt inside try-catch
        try {
            $realId = Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        // Delete record
        Tournament::where('id', $realId)->delete();

        return redirect()
            ->route('tournament.index')
            ->with('success', 'Tournament deleted successfully.');
    }
}
