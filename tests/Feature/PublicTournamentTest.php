<?php

use App\Models\Matches;
use App\Models\Teams;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('renders the public tournament page with court schedule tabs', function () {
    $this->get(route('public.tournaments'))
        ->assertOk()
        ->assertSee('Tournament Standings')
        ->assertSee('Court Schedule');
});

it('returns the court schedule for the selected public tournament', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'PUBLIC-COURT-TOURNAMENT',
        'name' => 'Public Court Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'court_count' => 2,
        'user_id' => $user->id,
    ]);

    $teams = collect(['Team Alpha', 'Team Bravo'])->map(fn (string $name, int $index): Teams => Teams::create([
        'code' => 'PUBLIC-COURT-TEAM-'.($index + 1),
        'team_no' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
        'team_name' => $name,
        'team_bracket' => 'A',
        'team_category' => 'Open',
        'tournament_id' => $tournament->id,
        'user_id' => $user->id,
    ]));

    Matches::create([
        'code' => 'PUBLIC-COURT-MATCH-1',
        'tournament_id' => $tournament->id,
        'team_bracket' => 'A',
        'team_category' => 'Open',
        'round_no' => '1',
        'match_no' => '1',
        'schedule_no' => 1,
        'court_no' => 1,
        'team1_id' => $teams[0]->id,
        'team2_id' => $teams[1]->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->put(route('tournament.matches.score', Crypt::encryptString(Matches::query()->latest('id')->value('id'))), [
            'team1_score' => 21,
            'team2_score' => 18,
            'finalize_score' => 1,
        ]);

    $this->get(route('public.tournaments.schedule', ['tournament_id' => $tournament->id]))
        ->assertOk()
        ->assertJsonPath('court_count', 2)
        ->assertJsonPath('court_schedule.0.team_category', 'Open')
        ->assertJsonPath('court_schedule.0.team_bracket', 'A')
        ->assertJsonPath('court_schedule.0.court_no', 1)
        ->assertJsonPath('court_schedule.0.is_finalized', true);
});

it('shows head-to-head records for tied teams in public standings without changing ranks', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'PUBLIC-H2H-TOURNAMENT',
        'name' => 'Public Head to Head Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    $teams = collect(['Team Alpha', 'Team Bravo', 'Team Charlie'])->map(fn (string $name, int $index): Teams => Teams::create([
        'code' => 'PUBLIC-H2H-TEAM-'.($index + 1),
        'team_no' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
        'team_name' => $name,
        'team_bracket' => 'A',
        'team_category' => 'Open',
        'tournament_id' => $tournament->id,
        'user_id' => $user->id,
    ]));

    foreach ([[0, 1], [1, 2], [2, 0]] as $index => [$winnerIndex, $loserIndex]) {
        Matches::create([
            'code' => 'PUBLIC-H2H-MATCH-'.($index + 1),
            'tournament_id' => $tournament->id,
            'team_bracket' => 'A',
            'team_category' => 'Open',
            'round_no' => '1',
            'match_no' => (string) ($index + 1),
            'team1_id' => $teams[$winnerIndex]->id,
            'team2_id' => $teams[$loserIndex]->id,
            'team1_score' => 11,
            'team2_score' => 7,
            'winner_id' => $teams[$winnerIndex]->id,
            'user_id' => $user->id,
        ]);
    }

    $response = $this->get(route('public.tournaments.data', [
        'tournament_id' => $tournament->id,
        'team_category' => 'Open',
        'team_bracket' => 'A',
    ]))->assertOk();

    $response->assertJsonPath('standings.0.rank', 1)
        ->assertJsonPath('standings.1.rank', 1)
        ->assertJsonPath('standings.2.rank', 1)
        ->assertJsonPath('standings.0.head_to_head.wins', 1)
        ->assertJsonPath('standings.0.head_to_head.losses', 1)
        ->assertJsonPath('standings.0.head_to_head.tied_team_count', 3);
});
