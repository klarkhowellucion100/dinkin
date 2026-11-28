<?php

namespace App\Http\Controllers;

use App\Models\EliminationMatch;
use App\Services\EliminationBracketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class EliminationController extends Controller
{
    public function __construct(private readonly EliminationBracketService $brackets) {}

    public function generate(Request $request, string $id): RedirectResponse
    {
        $tournamentId = Crypt::decryptString($id);
        $validated = $request->validate([
            'team_category' => ['required', 'string'],
            'team_bracket' => ['required', 'string'],
            'format' => ['required', 'in:single_elimination,double_elimination'],
        ]);

        try {
            $this->brackets->generate(
                (int) $tournamentId,
                $validated['team_category'],
                $validated['team_bracket'],
                $validated['format'],
                (int) auth()->id()
            );
        } catch (\InvalidArgumentException|\LogicException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->route('tournament.elimination.index', [
            'id' => $id,
            'team_category' => $validated['team_category'],
            'team_bracket' => $validated['team_bracket'],
        ])->with('success', 'Elimination bracket generated.');
    }

    public function index(string $id, string $team_category, string $team_bracket)
    {
        $tournamentId = Crypt::decryptString($id);
        $matches = EliminationMatch::query()
            ->where('tournament_id', $tournamentId)
            ->where('team_category', $team_category)
            ->where('team_bracket', $team_bracket)
            ->with(['team1', 'team2', 'winner'])
            ->orderByRaw("CASE bracket_side WHEN 'winners' THEN 1 WHEN 'losers' THEN 2 ELSE 3 END")
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get();

        abort_if($matches->isEmpty(), 404);

        return view('app.tournament.elimination.index', compact('matches', 'team_category', 'team_bracket'));
    }

    public function score(Request $request, string $id): RedirectResponse
    {
        $match = EliminationMatch::findOrFail(Crypt::decryptString($id));
        $validated = $request->validate([
            'team1_score' => ['required', 'integer', 'min:0'],
            'team2_score' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $this->brackets->finalize($match, (int) $validated['team1_score'], (int) $validated['team2_score']);
        } catch (\InvalidArgumentException|\LogicException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', 'Elimination score saved and bracket advanced.');
    }
}
