<?php

namespace App\Http\Controllers;

use App\Models\Teams;
use App\Models\Tournament;
use App\Services\RoundRobinMatchService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamsController extends Controller
{
    public function __construct(private readonly RoundRobinMatchService $matchSchedule) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($tournament_id)
    {
        $allTeams = Teams::all();
        $tournament = Tournament::findOrFail($tournament_id);

        return view('app.tournament.teams.create',
            ['tournament' => $tournament,
                'allTeams' => $allTeams]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $teamData = $request->validate([
            'tournament_id' => 'required',
            'team_no' => 'required',
            'team_name' => 'required',
            'team_bracket' => 'required',
            'team_category' => 'required',
        ]);

        $teamData['user_id'] = Auth::id();
        $teamData['code'] = Str::random(10);

        DB::transaction(function () use ($teamData): void {
            Teams::create($teamData);
            $this->matchSchedule->sync(
                (int) $teamData['tournament_id'],
                $teamData['team_bracket'],
                $teamData['team_category'],
                (int) Auth::id()
            );
        });

        return redirect()->route(
            'tournament.view',
            Crypt::encryptString($teamData['tournament_id'])
        )->with('success', 'Team created successfully.');
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
        $teamsEdit = Teams::where('id', $realId)->firstOrFail(); // Laravel handles 404 here

        $allTeams = Teams::all();

        return view('app.tournament.teams.edit', [
            'teamsEdit' => $teamsEdit,
            'allTeams' => $allTeams,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {

            $realId = Crypt::decryptString($id);

        } catch (DecryptException $e) {

            abort(404);
        }

        // Find the team
        $team = Teams::findOrFail($realId);

        // Validate input
        $teamData = $request->validate([
            'team_no' => 'required',
            'team_name' => 'required|unique:teams,team_name,'.$realId,
            'team_bracket' => 'required',
            'team_category' => 'required',
        ]);

        $previousBracket = $team->team_bracket;
        $previousCategory = $team->team_category;
        $tournamentId = (int) $team->tournament_id;

        DB::transaction(function () use ($team, $teamData, $previousBracket, $previousCategory, $tournamentId): void {
            $team->update($teamData);

            $userId = (int) Auth::id();
            $this->matchSchedule->sync($tournamentId, $previousBracket, $previousCategory, $userId);
            $this->matchSchedule->sync(
                $tournamentId,
                $team->team_bracket,
                $team->team_category,
                $userId
            );
        });

        // Redirect back to tournament view
        return redirect()->route(
            'tournament.view',
            Crypt::encryptString($team->tournament_id)
        )->with(
            'success',
            'Team updated successfully.'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            $realId = Crypt::decryptString($id);

        } catch (DecryptException $e) {

            abort(404);
        }

        $team = Teams::findOrFail($realId);

        $tournamentId = (int) $team->tournament_id;
        $bracket = $team->team_bracket;
        $category = $team->team_category;

        DB::transaction(function () use ($team, $tournamentId, $bracket, $category): void {
            $team->delete();
            $this->matchSchedule->sync(
                $tournamentId,
                $bracket,
                $category,
                (int) Auth::id()
            );
        });

        // Redirect back to tournament view
        return redirect()->route(
            'tournament.view',
            Crypt::encryptString($tournamentId)
        )->with(
            'success',
            'Team deleted successfully.'
        );
    }
}
