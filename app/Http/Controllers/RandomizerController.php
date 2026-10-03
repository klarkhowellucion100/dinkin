<?php

namespace App\Http\Controllers;

use App\Models\RandomizerSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RandomizerController extends Controller
{
    public function index(): View
    {
        $latestSession = RandomizerSession::query()
            ->where('user_id', Auth::id())
            ->with('entries')
            ->latest()
            ->first();

        $sessions = RandomizerSession::query()
            ->where('user_id', Auth::id())
            ->when($latestSession, fn ($query) => $query->where('id', '!=', $latestSession->id))
            ->latest()
            ->paginate(10, ['*'], 'draw_page');

        return view('app.randomizer.index', compact('latestSession', 'sessions'));
    }

    public function show(int $id): View
    {
        $randomizerSession = RandomizerSession::query()
            ->where('user_id', Auth::id())
            ->with('entries')
            ->findOrFail($id);

        return view('app.randomizer.show', compact('randomizerSession'));
    }

    public function destroy(int $id): RedirectResponse
    {
        RandomizerSession::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id)
            ->delete();

        return redirect()
            ->route('randomizer.index')
            ->with('success', 'The saved draw was deleted.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'names' => ['required', 'string'],
            'bracket_count' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $names = collect(preg_split('/\r\n|\r|\n/', $data['names']))
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->values();
        if ($names->count() !== $names->unique()->count()) {
            return back()
                ->withErrors(['names' => 'Each name must be unique.'])
                ->withInput();
        }

        if ($names->count() < $data['bracket_count']) {
            return back()
                ->withErrors([
                    'names' => "Add at least {$data['bracket_count']} names to fill every bracket.",
                ])
                ->withInput();
        }

        $session = DB::transaction(function () use ($data, $names): RandomizerSession {
            $shuffledNames = $names->shuffle()->values();
            $bracketCount = $data['bracket_count'];
            $namesPerBracket = intdiv($shuffledNames->count(), $bracketCount);
            $extraNames = $shuffledNames->count() % $bracketCount;
            $session = RandomizerSession::create([
                'user_id' => Auth::id(),
                'bracket_count' => $bracketCount,
                'teams_per_bracket' => null,
                'name_count' => $shuffledNames->count(),
                'generated_at' => Carbon::now(),
            ]);

            $entries = collect();
            $nameOffset = 0;

            for ($bracketNumber = 1; $bracketNumber <= $bracketCount; $bracketNumber++) {
                $bracketSize = $namesPerBracket + ($bracketNumber <= $extraNames ? 1 : 0);
                $bracketNames = $shuffledNames->slice($nameOffset, $bracketSize)->values();

                foreach ($bracketNames as $position => $name) {
                    $entries->push([
                        'name' => $name,
                        'bracket_number' => $bracketNumber,
                        'position' => $position + 1,
                    ]);
                }

                $nameOffset += $bracketSize;
            }

            $session->entries()->createMany($entries->all());

            return $session;
        });

        return redirect()
            ->route('randomizer.index')
            ->with('generated_session_id', $session->id)
            ->with('success', 'Your names have been randomized into brackets.');
    }
}
