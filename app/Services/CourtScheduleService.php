<?php

namespace App\Services;

use App\Models\Matches;
use Illuminate\Support\Collection;

class CourtScheduleService
{
    public function assign(int $tournamentId, ?int $courtCount, string $scheduleMode = 'bracket'): void
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

        if ($scheduleMode === 'category') {
            $this->assignByCategory($matches, $courtCount);

            return;
        }

        if ($scheduleMode === 'timed_category') {
            $this->assignTimedByCategory($matches, $courtCount);

            return;
        }

        if ($scheduleMode === 'timed_alternate_category') {
            $this->assignTimedCategoriesToCourts($matches, $courtCount, false, false);

            return;
        }

        if ($scheduleMode === 'timed_finish_category') {
            $this->assignTimedCategoriesToCourts($matches, $courtCount, true, true);

            return;
        }

        if ($scheduleMode === 'timed_bracket') {
            $this->assignSpread($matches, $courtCount);

            return;
        }

        if ($scheduleMode === 'timed_category_spread') {
            $this->assignByCategory($matches, $courtCount);

            return;
        }

        $this->assignSpread($matches, $courtCount);
    }

    /**
     * Spread matches across courts in the existing bracket order.
     *
     * @param  Collection<int, Matches>  $matches
     */
    private function assignSpread(Collection $matches, int $courtCount): void
    {
        $matches->each(function (Matches $match, int $index) use ($courtCount): void {
            $match->update([
                'schedule_no' => intdiv($index, $courtCount) + 1,
                'court_no' => ($index % $courtCount) + 1,
            ]);
        });
    }

    /**
     * @param  Collection<int, Matches>  $matches
     */
    private function assignByCategory(Collection $matches, int $courtCount): void
    {
        $scheduleByCourt = array_fill(1, $courtCount, 0);
        $categories = $matches->pluck('team_category')->unique()->sort(function (string $first, string $second): int {
            return ($this->categoryPriority($second) <=> $this->categoryPriority($first))
                ?: strnatcasecmp($first, $second);
        })->values();

        foreach ($categories as $categoryIndex => $category) {
            $courtNo = ($categoryIndex % $courtCount) + 1;
            $categoryMatches = $matches
                ->where('team_category', $category)
                ->sort(function (Matches $first, Matches $second): int {
                    return ((int) $first->round_no <=> (int) $second->round_no)
                        ?: ((int) $first->match_no <=> (int) $second->match_no)
                        ?: strnatcasecmp((string) $first->team_bracket, (string) $second->team_bracket)
                        ?: ($first->id <=> $second->id);
                });

            foreach ($categoryMatches as $match) {
                $scheduleByCourt[$courtNo]++;
                $match->update([
                    'schedule_no' => $scheduleByCourt[$courtNo],
                    'court_no' => $courtNo,
                ]);
            }
        }
    }

    /**
     * Assign matches in consecutive category blocks while using each court in parallel.
     *
     * @param  Collection<int, Matches>  $matches
     */
    private function assignTimedByCategory(Collection $matches, int $courtCount): void
    {
        $scheduleNumber = 1;
        $categories = $matches->pluck('team_category')->unique()->sort(function (string $first, string $second): int {
            return ($this->categoryPriority($second) <=> $this->categoryPriority($first))
                ?: strnatcasecmp($first, $second);
        })->values();

        foreach ($categories as $category) {
            $categoryMatches = $matches
                ->where('team_category', $category)
                ->sort(function (Matches $first, Matches $second): int {
                    return ((int) $first->round_no <=> (int) $second->round_no)
                        ?: ((int) $first->match_no <=> (int) $second->match_no)
                        ?: strnatcasecmp((string) $first->team_bracket, (string) $second->team_bracket)
                        ?: ($first->id <=> $second->id);
                })->values();

            foreach ($categoryMatches as $index => $match) {
                $match->update([
                    'schedule_no' => $scheduleNumber + intdiv($index, $courtCount),
                    'court_no' => ($index % $courtCount) + 1,
                ]);
            }

            $scheduleNumber += (int) ceil($categoryMatches->count() / $courtCount);
        }
    }

    /**
     * Assign each category to one court and reuse courts as category blocks finish.
     *
     * @param  Collection<int, Matches>  $matches
     */
    private function assignTimedCategoriesToCourts(Collection $matches, int $courtCount, bool $reuseFreedCourts, bool $finishEachBracket): void
    {
        $courtAvailability = array_fill(1, $courtCount, 1);
        $categories = $matches->pluck('team_category')->unique()->sort(function (string $first, string $second): int {
            return ($this->categoryPriority($second) <=> $this->categoryPriority($first))
                ?: strnatcasecmp($first, $second);
        })->values();

        foreach ($categories as $categoryIndex => $category) {
            $courtNo = $reuseFreedCourts
                ? array_search(min($courtAvailability), $courtAvailability, true)
                : (($categoryIndex % $courtCount) + 1);
            $scheduleNumber = $courtAvailability[$courtNo];
            $categoryMatches = $matches->where('team_category', $category);

            if ($finishEachBracket) {
                $categoryMatches = $categoryMatches
                    ->pluck('team_bracket')
                    ->unique()
                    ->sort(fn (string $first, string $second): int => strnatcasecmp($first, $second))
                    ->flatMap(fn (string $bracket): Collection => $this->sortMatches(
                        $matches->where('team_category', $category)->where('team_bracket', $bracket)
                    ))
                    ->values();
            } else {
                $categoryMatches = $this->sortMatches($categoryMatches);
            }

            foreach ($categoryMatches as $matchIndex => $match) {
                $match->update([
                    'schedule_no' => $scheduleNumber + $matchIndex,
                    'court_no' => $courtNo,
                ]);
            }

            $courtAvailability[$courtNo] += $categoryMatches->count();
        }
    }

    /**
     * @param  Collection<int, Matches>  $matches
     * @return Collection<int, Matches>
     */
    private function sortMatches(Collection $matches): Collection
    {
        return $matches->sort(function (Matches $first, Matches $second): int {
            return ((int) $first->round_no <=> (int) $second->round_no)
                ?: ((int) $first->match_no <=> (int) $second->match_no)
                ?: strnatcasecmp((string) $first->team_bracket, (string) $second->team_bracket)
                ?: ($first->id <=> $second->id);
        })->values();
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
