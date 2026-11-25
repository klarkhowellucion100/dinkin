<?php

namespace App\Http\Controllers;

use App\Models\OpenPlayMatch;
use App\Models\OpenPlaySession;
use App\Models\Player;
use App\Services\OpenPlayRotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OpenPlaySessionController extends Controller
{
    public function __construct(private readonly OpenPlayRotationService $rotation) {}

    public function index(): View
    {
        $sessions = OpenPlaySession::query()
            ->where('user_id', Auth::id())
            ->latest('session_date')
            ->latest()
            ->paginate(10);

        return view('app.open-play.index', compact('sessions'));
    }

    public function create(): View
    {
        return view('app.open-play.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'session_date' => ['required', 'date'],
            'mode' => ['required', 'in:balanced,winners_losers,king_queen'],
            'format' => ['required', 'in:singles,doubles'],
            'court_count' => ['required', 'integer', 'min:1', 'max:64'],
        ]);

        $session = OpenPlaySession::create([...$data, 'user_id' => Auth::id()]);

        return redirect()->route('open-play.show', $session)->with('success', 'Open play session created.');
    }

    public function show(OpenPlaySession $session): View
    {
        $this->authorizeSession($session);
        $this->loadSession($session);

        return view('app.open-play.show', [
            'session' => $session,
            'standings' => $this->buildStandings($session),
            'savedPlayers' => Player::where('user_id', Auth::id())->orderBy('name')->get(),
        ]);
    }

    public function addPlayer(Request $request, OpenPlaySession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeSession($session);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'skill_level' => ['required', 'integer', 'min:1', 'max:6'],
            'gender' => ['nullable', 'string', 'max:30'],
        ]);

        $player = Player::firstOrCreate(
            ['user_id' => Auth::id(), 'name' => $data['name']],
            ['skill_level' => $data['skill_level'], 'gender' => $data['gender'] ?? null]
        );
        $queuePosition = ((int) $session->players()->max('queue_position')) + 1;
        // Adding a player is the check-in action for this staff-managed session.
        $checkedInAt = now();

        $session->players()->syncWithoutDetaching([
            $player->id => [
                'queue_position' => $queuePosition,
                'checked_in_at' => $checkedInAt,
                'checked_out_at' => null,
            ],
        ]);

        return $this->respond($request, $session, "{$player->name} added to the session.");
    }

    public function checkIn(Request $request, OpenPlaySession $session, Player $player): RedirectResponse|JsonResponse
    {
        $this->authorizeSession($session);
        abort_unless($player->user_id === Auth::id(), 404);

        $queuePosition = ((int) $session->players()->max('queue_position')) + 1;
        $session->players()->syncWithoutDetaching([
            $player->id => ['queue_position' => $queuePosition, 'checked_in_at' => now(), 'checked_out_at' => null],
        ]);

        return $this->respond($request, $session, "{$player->name} is checked in.");
    }

    public function checkOut(Request $request, OpenPlaySession $session, Player $player): RedirectResponse|JsonResponse
    {
        $this->authorizeSession($session);
        abort_unless($session->players()->whereKey($player->id)->exists(), 404);
        $session->players()->updateExistingPivot($player->id, ['checked_out_at' => now()]);
        $this->rotation->cancelPlayer($session, $player->id);

        return $this->respond($request, $session, "{$player->name} is checked out.");
    }

    public function setFixedPartner(Request $request, OpenPlaySession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeSession($session);
        $data = $request->validate([
            'player_id' => ['required', 'integer'],
            'partner_id' => ['nullable', 'integer', 'different:player_id'],
        ]);
        $playerIds = $session->players()->pluck('players.id');
        abort_unless($playerIds->contains((int) $data['player_id']), 404);
        abort_unless($data['partner_id'] === null || $playerIds->contains((int) $data['partner_id']), 404);

        $session->players()->updateExistingPivot($data['player_id'], ['fixed_partner_id' => $data['partner_id']]);

        if ($data['partner_id'] !== null) {
            $session->players()->updateExistingPivot($data['partner_id'], ['fixed_partner_id' => $data['player_id']]);
        }

        return $this->respond($request, $session, 'Fixed partner setting updated.');
    }

    public function start(Request $request, OpenPlaySession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeSession($session);
        $requiredPlayers = $session->format === 'singles' ? 2 : 4;
        $checkedInCount = $session->players()->wherePivotNotNull('checked_in_at')->wherePivotNull('checked_out_at')->count();

        if ($checkedInCount < $requiredPlayers) {
            if ($request->expectsJson()) {
                return response()->json(['message' => "At least {$requiredPlayers} checked-in players are required."], 422);
            }

            return back()->with('error', "At least {$requiredPlayers} checked-in players are required.");
        }

        $session->update(['status' => 'live', 'started_at' => now()]);
        $this->rotation->stageNextMatches($session->fresh());

        return $this->respond($request, $session, 'Session started and the first games were staged.');
    }

    public function dispatch(Request $request, OpenPlayMatch $match): RedirectResponse|JsonResponse
    {
        $match->load('session');
        $this->authorizeSession($match->session);
        $this->rotation->dispatchMatch($match);

        return $this->respond($request, $match->session, 'Match sent to the court.');
    }

    public function removePlayerFromMatch(Request $request, OpenPlayMatch $match, Player $player): RedirectResponse|JsonResponse
    {
        $match->load('session');
        $this->authorizeSession($match->session);
        $this->rotation->removePlayerFromMatch($match, $player->id);

        return $this->respond($request, $match->session, "{$player->name} was returned to the queue.");
    }

    public function addPlayerToMatch(Request $request, OpenPlaySession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeSession($session);
        $data = $request->validate(['player_id' => ['required', 'integer']]);
        $session = $session->fresh();
        $openMatch = $session->matches()
            ->whereIn('status', ['staged', 'active'])
            ->where(function ($query): void {
                $query->whereNull('team1_player1_id')
                    ->orWhereNull('team1_player2_id')
                    ->orWhereNull('team2_player1_id')
                    ->orWhereNull('team2_player2_id');
            })
            ->first();

        if ($openMatch !== null) {
            $this->rotation->fillMatchPlayer($openMatch, (int) $data['player_id']);
        } else {
            $this->rotation->stageManualMatchWithPlayer($session, (int) $data['player_id']);
        }

        return $this->respond($request, $session, 'The selected player was added to a new Up Next match.');
    }

    public function autoPick(Request $request, OpenPlaySession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeSession($session);
        $this->rotation->autoFillOpenSlots($session->fresh());
        $this->rotation->stageNextMatches($session->fresh());

        return $this->respond($request, $session, 'Up Next was refreshed with the fairest available matches.');
    }

    public function addMatch(Request $request, OpenPlaySession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeSession($session);
        $this->rotation->stageManualMatch($session->fresh());

        return $this->respond($request, $session, 'A new match was added to Up Next.');
    }

    public function addCourt(Request $request, OpenPlaySession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeSession($session);
        abort_if((int) $session->court_count >= 64, 422, 'The maximum of 64 courts has been reached.');

        $session->increment('court_count');
        $this->rotation->stageNextMatches($session->fresh());

        return $this->respond($request, $session, "Court {$session->court_count} was added and Up Next was refreshed.");
    }

    public function undo(Request $request, OpenPlaySession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeSession($session);
        $this->rotation->undoLatestResult($session);

        return $this->respond($request, $session, 'The latest result was undone.');
    }

    public function result(Request $request, OpenPlayMatch $match): RedirectResponse|JsonResponse
    {
        $match->load('session');
        $this->authorizeSession($match->session);
        $data = $request->validate([
            'team1_score' => ['required', 'integer', 'min:0'],
            'team2_score' => ['required', 'integer', 'min:0'],
        ]);
        if ((int) $data['team1_score'] === (int) $data['team2_score']) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Scores cannot be tied. Enter the final winning score.'], 422);
            }

            return back()->with('error', 'Scores cannot be tied. Enter the final winning score.');
        }

        $winnerTeam = (int) $data['team1_score'] > (int) $data['team2_score'] ? 1 : 2;

        $this->rotation->recordResult($match, $winnerTeam, (int) $data['team1_score'], (int) $data['team2_score']);

        return $this->respond($request, $match->session, 'Result recorded and the next rotation was staged.');
    }

    public function end(Request $request, OpenPlaySession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeSession($session);
        $session->update(['status' => 'ended', 'ended_at' => now()]);
        $session->matches()->where('status', 'active')->update(['status' => 'cancelled']);

        return $this->respond($request, $session, 'Open play session ended.');
    }

    public function destroy(OpenPlaySession $session): RedirectResponse
    {
        $this->authorizeSession($session);
        $session->delete();

        return redirect()->route('open-play.index')->with('success', 'Open play session and all of its records were deleted.');
    }

    private function loadSession(OpenPlaySession $session): void
    {
        $session->load([
            'players' => fn ($query) => $query->orderByPivot('queue_position'),
            'matches' => fn ($query) => $query->with(['team1Player1', 'team1Player2', 'team2Player1', 'team2Player2'])->latest(),
        ]);
    }

    private function respond(Request $request, OpenPlaySession $session, string $message): RedirectResponse|JsonResponse
    {
        if (! $request->expectsJson()) {
            return back()->with('success', $message);
        }

        $session = $session->fresh();
        $this->loadSession($session);

        return response()->json([
            'message' => $message,
            'html' => view('app.open-play.partials.session-content', [
                'session' => $session,
                'standings' => $this->buildStandings($session),
                'savedPlayers' => Player::where('user_id', Auth::id())->orderBy('name')->get(),
            ])->render(),
        ]);
    }

    private function buildStandings(OpenPlaySession $session): Collection
    {
        $completedMatches = $session->matches->where('status', 'completed');

        return $session->players->map(function (Player $player) use ($completedMatches): object {
            $gamesPlayed = 0;
            $wins = 0;
            $losses = 0;
            $pointDifferential = 0;
            $totalMatchSeconds = 0;

            foreach ($completedMatches as $match) {
                $team1Ids = collect([$match->team1_player1_id, $match->team1_player2_id])->filter();
                $team2Ids = collect([$match->team2_player1_id, $match->team2_player2_id])->filter();
                $isTeam1 = $team1Ids->contains($player->id);
                $isTeam2 = $team2Ids->contains($player->id);

                if (! $isTeam1 && ! $isTeam2) {
                    continue;
                }

                $gamesPlayed++;
                $playerTeam = $isTeam1 ? 1 : 2;
                $won = (int) $match->winner_team === $playerTeam;
                $won ? $wins++ : $losses++;
                $playerScore = $playerTeam === 1 ? $match->team1_score : $match->team2_score;
                $opponentScore = $playerTeam === 1 ? $match->team2_score : $match->team1_score;

                if ($playerScore !== null && $opponentScore !== null) {
                    $pointDifferential += (int) $playerScore - (int) $opponentScore;
                }

                if ($match->started_at !== null && $match->completed_at !== null) {
                    $totalMatchSeconds += max(0, $match->started_at->diffInSeconds($match->completed_at));
                }

            }

            return (object) [
                'player' => $player,
                'games_played' => $gamesPlayed,
                'wins' => $wins,
                'losses' => $losses,
                'win_percentage' => $gamesPlayed > 0 ? ($wins / $gamesPlayed) * 100 : 0,
                'point_differential' => $pointDifferential,
                'total_match_seconds' => $totalMatchSeconds,
            ];
        })->sort(function (object $first, object $second): int {
            return ($second->wins <=> $first->wins)
                ?: ($second->win_percentage <=> $first->win_percentage)
                ?: ($second->point_differential <=> $first->point_differential)
                ?: ($first->total_match_seconds <=> $second->total_match_seconds)
                ?: strcasecmp($first->player->name, $second->player->name);
        })->values()->map(function (object $standing, int $index): object {
            $standing->rank = $index + 1;

            return $standing;
        });
    }

    private function authorizeSession(OpenPlaySession $session): void
    {
        abort_unless((int) $session->user_id === (int) Auth::id(), 404);
    }
}
