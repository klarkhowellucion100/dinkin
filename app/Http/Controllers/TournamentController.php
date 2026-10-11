<?php

namespace App\Http\Controllers;

use App\Models\Teams;
use App\Models\Tournament;
use App\Services\CourtScheduleService;
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

        return view('app.tournament.view', [
            'tournamentView' => $tournamentView,
            'teamsGrouped' => $teamsGrouped,
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
            'courtSchedule' => $this->getCourtSchedule($realId),
        ]);
    }

    private function getCourtSchedule(int|string $tournamentId): Collection
    {
        return DB::table('matches')
            ->join('teams as team1', 'matches.team1_id', '=', 'team1.id')
            ->join('teams as team2', 'matches.team2_id', '=', 'team2.id')
            ->where('matches.tournament_id', $tournamentId)
            ->whereNotNull('matches.schedule_no')
            ->select([
                'matches.schedule_no',
                'matches.court_no',
                'matches.team_category',
                'matches.team_bracket',
                'matches.round_no',
                'matches.match_no',
                'matches.is_finalized',
                'team1.team_name as team1_name',
                'team2.team_name as team2_name',
            ])
            ->orderBy('matches.schedule_no')
            ->orderBy('matches.court_no')
            ->get();
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
        ]);

        Tournament::whereKey($realId)->update([
            'court_count' => $validated['court_count'],
        ]);

        $this->courtSchedule->assign($realId, (int) $validated['court_count']);

        return redirect()
            ->route('tournament.courtSchedule', Crypt::encryptString($realId))
            ->with('success', "Court schedule generated across {$validated['court_count']} court(s).");
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
