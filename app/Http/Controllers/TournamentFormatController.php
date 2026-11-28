<?php

namespace App\Http\Controllers;

use App\Models\TournamentFormat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TournamentFormatController extends Controller
{
    public function update(Request $request, string $id): RedirectResponse
    {
        $tournamentId = Crypt::decryptString($id);

        $validated = $request->validate([
            'team_category' => ['required', 'string', 'max:100'],
            'team_bracket' => ['required', 'string', 'max:100'],
            'format' => ['required', 'in:round_robin,single_elimination,double_elimination'],
        ]);

        TournamentFormat::updateOrCreate(
            [
                'tournament_id' => $tournamentId,
                'team_category' => $validated['team_category'],
                'team_bracket' => $validated['team_bracket'],
            ],
            [
                'format' => $validated['format'],
            ]
        );

        return redirect()->back()->with('success', 'Tournament format updated.');
    }
}
