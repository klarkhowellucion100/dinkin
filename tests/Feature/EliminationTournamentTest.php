<?php

use App\Models\EliminationMatch;
use App\Models\Teams;
use App\Models\Tournament;
use App\Models\User;
use App\Services\EliminationBracketService;
use Illuminate\Support\Facades\Crypt;

function createEliminationTournament(User $user, int $teamCount = 4): Tournament
{
    $tournament = Tournament::create([
        'code' => 'ELIM-'.fake()->unique()->numerify('####'),
        'name' => 'Elimination '.fake()->unique()->word(),
        'date' => '2026-12-01',
        'venue' => 'Arena',
        'user_id' => $user->id,
    ]);

    for ($teamNumber = 1; $teamNumber <= $teamCount; $teamNumber++) {
        Teams::create([
            'code' => 'TEAM-'.fake()->unique()->numerify('####'),
            'tournament_id' => $tournament->id,
            'team_no' => $teamNumber,
            'team_name' => 'Team '.$teamNumber,
            'team_bracket' => 'A',
            'team_category' => 'Open',
            'user_id' => $user->id,
        ]);
    }

    return $tournament;
}

it('generates and displays a single elimination bracket', function () {
    $user = User::factory()->create();
    $tournament = createEliminationTournament($user);
    $encryptedId = Crypt::encryptString($tournament->id);

    $this->actingAs($user)
        ->post(route('tournament.format.update', $encryptedId), [
            'team_category' => 'Open',
            'team_bracket' => 'A',
            'format' => 'single_elimination',
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('tournament.elimination.generate', $encryptedId), [
            'team_category' => 'Open',
            'team_bracket' => 'A',
            'format' => 'single_elimination',
        ])
        ->assertRedirect();

    expect(EliminationMatch::where('bracket_side', 'winners')->count())->toBe(3);

    $this->actingAs($user)
        ->get(route('tournament.elimination.index', [
            'id' => $encryptedId,
            'team_category' => 'Open',
            'team_bracket' => 'A',
        ]))
        ->assertOk()
        ->assertSee('Single elimination');

    $this->get(route('public.tournament.elimination', [$tournament->id, 'Open', 'A']))
        ->assertOk()
        ->assertSee($tournament->name)
        ->assertSee('Team 1');
});

it('shows elimination scores and winners on the public page without authentication', function () {
    $user = User::factory()->create();
    $tournament = createEliminationTournament($user);
    $service = app(EliminationBracketService::class);
    $service->generate($tournament->id, 'Open', 'A', 'single_elimination', $user->id);

    $firstMatch = EliminationMatch::query()
        ->where('tournament_id', $tournament->id)
        ->where('round_number', 1)
        ->where('match_number', 1)
        ->firstOrFail();
    $service->finalize($firstMatch, 11, 7);

    $this->assertGuest();
    $this->get(route('public.tournament.elimination', [$tournament->id, 'Open', 'A']))
        ->assertOk()
        ->assertSee($tournament->name)
        ->assertSee('Team 1')
        ->assertSee('11')
        ->assertSee('Final');
});

it('advances a finalized single elimination match', function () {
    $user = User::factory()->create();
    $tournament = createEliminationTournament($user);
    $encryptedId = Crypt::encryptString($tournament->id);

    $this->actingAs($user)->post(route('tournament.elimination.generate', $encryptedId), [
        'team_category' => 'Open',
        'team_bracket' => 'A',
        'format' => 'single_elimination',
    ]);

    $firstMatch = EliminationMatch::where('round_number', 1)->where('match_number', 1)->firstOrFail();

    $this->actingAs($user)
        ->put(route('tournament.elimination.score', Crypt::encryptString($firstMatch->id)), [
            'team1_score' => 11,
            'team2_score' => 7,
        ])
        ->assertRedirect();

    $firstMatch->refresh();
    $nextMatch = EliminationMatch::where('round_number', 2)->firstOrFail();

    expect($firstMatch->is_finalized)->toBeTrue()
        ->and($nextMatch->team1_id)->toBe($firstMatch->winner_id);
});

it('generates a standard double elimination structure', function () {
    $user = User::factory()->create();
    $tournament = createEliminationTournament($user, 8);
    $encryptedId = Crypt::encryptString($tournament->id);

    $this->actingAs($user)
        ->post(route('tournament.elimination.generate', $encryptedId), [
            'team_category' => 'Open',
            'team_bracket' => 'A',
            'format' => 'double_elimination',
        ])
        ->assertRedirect();

    expect(EliminationMatch::where('bracket_side', 'winners')->count())->toBe(7)
        ->and(EliminationMatch::where('bracket_side', 'losers')->count())->toBe(6)
        ->and(EliminationMatch::where('bracket_side', 'grand_final')->count())->toBe(1);
});

it('advances teams through every double elimination round to the grand final', function () {
    $user = User::factory()->create();
    $tournament = createEliminationTournament($user, 8);
    $service = app(EliminationBracketService::class);

    $service->generate($tournament->id, 'Open', 'A', 'double_elimination', $user->id);

    for ($iteration = 0; $iteration < 20; $iteration++) {
        $availableMatch = EliminationMatch::query()
            ->where('tournament_id', $tournament->id)
            ->where('is_finalized', false)
            ->whereNotNull('team1_id')
            ->whereNotNull('team2_id')
            ->orderByRaw("CASE bracket_side WHEN 'winners' THEN 1 WHEN 'losers' THEN 2 ELSE 3 END")
            ->orderBy('round_number')
            ->first();

        if ($availableMatch === null) {
            break;
        }

        $service->finalize($availableMatch, 11, 5);
    }

    $grandFinal = EliminationMatch::query()
        ->where('tournament_id', $tournament->id)
        ->where('bracket_side', 'grand_final')
        ->firstOrFail();

    expect($grandFinal->team1_id)->not->toBeNull()
        ->and($grandFinal->team2_id)->not->toBeNull()
        ->and($grandFinal->is_finalized)->toBeTrue();
});
