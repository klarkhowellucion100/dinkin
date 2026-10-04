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
        $tournament = Tournament::findOrFail($tournament_id);

        return view('app.tournament.teams.create', compact('tournament'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $teamData = $request->validate([
            'tournament_id' => ['required', 'integer', 'exists:tournaments,id'],
            'names' => ['required', 'string'],
            'team_bracket' => ['required', 'string', 'max:255'],
            'team_category' => ['required', 'string', 'max:255'],
        ]);

        $names = collect(preg_split('/\r\n|\r|\n/', $teamData['names']))
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->values();

        if ($names->isEmpty()) {
            return back()
                ->withErrors(['names' => 'Add at least one team name.'])
                ->withInput();
        }

        if ($names->count() !== $names->unique()->count()) {
            return back()
                ->withErrors(['names' => 'Each team name must be unique.'])
                ->withInput();
        }

        $existingNames = Teams::query()
            ->where('tournament_id', $teamData['tournament_id'])
            ->whereIn('team_name', $names)
            ->pluck('team_name');

        if ($existingNames->isNotEmpty()) {
            return back()
                ->withErrors([
                    'names' => 'These teams already exist in this tournament: '.$existingNames->implode(', ').'.',
                ])
                ->withInput();
        }

        DB::transaction(function () use ($teamData, $names): void {
            $nextTeamNumber = 1;

            foreach ($names as $name) {
                while (Teams::query()
                    ->where('tournament_id', $teamData['tournament_id'])
                    ->where('team_bracket', $teamData['team_bracket'])
                    ->where('team_category', $teamData['team_category'])
                    ->where('team_no', str_pad((string) $nextTeamNumber, 2, '0', STR_PAD_LEFT))
                    ->exists()) {
                    $nextTeamNumber++;
                }

                Teams::create([
                    'tournament_id' => $teamData['tournament_id'],
                    'team_no' => str_pad((string) $nextTeamNumber, 2, '0', STR_PAD_LEFT),
                    'team_name' => $name,
                    'team_bracket' => $teamData['team_bracket'],
                    'team_category' => $teamData['team_category'],
                    'user_id' => Auth::id(),
                    'code' => Str::random(10),
                ]);

                $nextTeamNumber++;
            }

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
        )->with('success', $names->count().' teams created successfully.');
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

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'team_ids' => ['required', 'array', 'min:1'],
            'team_ids.*' => ['required', 'string'],
            'tournament_id' => ['required', 'string'],
            'team_bracket' => ['required', 'string'],
            'team_category' => ['required', 'string'],
        ]);

        try {
            $tournamentId = (int) Crypt::decryptString($validated['tournament_id']);
        } catch (DecryptException $e) {
            abort(404);
        }

        $teamIds = collect($validated['team_ids'])
            ->map(function (string $encryptedId): ?int {
                try {
                    return (int) Crypt::decryptString($encryptedId);
                } catch (DecryptException $e) {
                    return null;
                }
            })
            ->filter()
            ->values();

        if ($teamIds->isEmpty()) {
            return back()->with('error', 'No valid teams were selected.');
        }

        $deletedCount = 0;

        DB::transaction(function () use ($teamIds, $tournamentId, $validated, &$deletedCount): void {
            $deletedCount = Teams::query()
                ->where('tournament_id', $tournamentId)
                ->where('team_bracket', $validated['team_bracket'])
                ->where('team_category', $validated['team_category'])
                ->whereIn('id', $teamIds)
                ->delete();

            $this->matchSchedule->sync(
                $tournamentId,
                $validated['team_bracket'],
                $validated['team_category'],
                (int) Auth::id()
            );
        });

        return redirect()->route(
            'tournament.view',
            Crypt::encryptString($tournamentId)
        )->with('success', "{$deletedCount} team(s) successfully deleted.");
    }
}
