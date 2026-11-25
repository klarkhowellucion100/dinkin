<?php

namespace App\Http\Controllers;

use App\Models\OpenPlaySession;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PublicOpenPlayController extends Controller
{
    public function show(OpenPlaySession $session): View
    {
        $session->load([
            'players' => fn ($query) => $query
                ->wherePivotNotNull('checked_in_at')
                ->wherePivotNull('checked_out_at')
                ->orderByPivot('queue_position'),
            'matches' => fn ($query) => $query
                ->with(['team1Player1', 'team1Player2', 'team2Player1', 'team2Player2'])
                ->latest(),
        ]);

        return view('app.open-play.public.show', [
            'session' => $session,
            'standings' => $this->standings($session->players, $session->matches),
        ]);
    }

    private function standings(Collection $players, Collection $matches): Collection
    {
        $completedMatches = $matches->where('status', 'completed');

        return $players->map(function (object $player) use ($completedMatches): object {
            $gamesPlayed = (int) $player->pivot->games_played;
            $wins = (int) $player->pivot->wins;
            $pointDifferential = 0;
            $totalMatchSeconds = 0;

            foreach ($completedMatches as $match) {
                $team1Ids = collect([$match->team1_player1_id, $match->team1_player2_id])->filter();
                $team2Ids = collect([$match->team2_player1_id, $match->team2_player2_id])->filter();

                if ($team1Ids->contains($player->id)) {
                    $pointDifferential += (int) $match->team1_score - (int) $match->team2_score;
                } elseif ($team2Ids->contains($player->id)) {
                    $pointDifferential += (int) $match->team2_score - (int) $match->team1_score;
                }

                if ($match->started_at !== null && $match->completed_at !== null) {
                    $totalMatchSeconds += max(0, $match->started_at->diffInSeconds($match->completed_at));
                }
            }

            return (object) [
                'player' => $player,
                'games_played' => $gamesPlayed,
                'wins' => $wins,
                'losses' => (int) $player->pivot->losses,
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
}
