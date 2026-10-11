<?php

namespace App\Services;

use App\Models\Matches;

class CourtScheduleService
{
    public function assign(int $tournamentId, ?int $courtCount): void
    {
        $matches = Matches::query()
            ->where('tournament_id', $tournamentId)
            ->get();

        if ($courtCount === null || $courtCount < 1) {
            $matches->each(fn (Matches $match): bool => $match->update([
                'schedule_no' => null,
                'court_no' => null,
            ]));

            return;
        }

        $matches = $matches->sort(function (Matches $first, Matches $second): int {
            $matchComparison = ((int) $first->match_no <=> (int) $second->match_no)
                ?: ((int) $first->round_no <=> (int) $second->round_no);

            if ($matchComparison !== 0) {
                return $matchComparison;
            }

            $categoryComparison = $this->categoryPriority($second->team_category) <=> $this->categoryPriority($first->team_category);

            if ($categoryComparison !== 0) {
                return $categoryComparison;
            }

            return strnatcasecmp((string) $first->team_bracket, (string) $second->team_bracket)
                ?: ($first->id <=> $second->id);
        })->values();

        $matches->each(function (Matches $match, int $index) use ($courtCount): void {
            $match->update([
                'schedule_no' => intdiv($index, $courtCount) + 1,
                'court_no' => ($index % $courtCount) + 1,
            ]);
        });
    }

    private function categoryPriority(string $category): int
    {
        $normalized = strtolower($category);

        if ($normalized === 'open') {
            return 100;
        }

        $level = str_contains($normalized, 'intermediate') ? 3
            : (str_contains($normalized, 'novice') ? 2 : (str_contains($normalized, 'beginner') ? 1 : 0));
        $tier = str_contains($normalized, 'high') ? 2 : (str_contains($normalized, 'low') ? 1 : 0);

        return ($level * 10) + $tier;
    }
}
