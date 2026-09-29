<?php

namespace App\Http\Controllers;

use App\Models\Teams;
use App\Models\Tournament;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TournamentController extends Controller
{
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
            'team_category'
        ]);


        return view('app.tournament.view', [
            'tournamentView' => $tournamentView,
            'teamsGrouped' => $teamsGrouped,
        ]);
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
