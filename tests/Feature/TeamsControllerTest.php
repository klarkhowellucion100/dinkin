<?php

use App\Models\Teams;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

it('creates multiple teams from newline-separated names', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'BULK-TOURNAMENT',
        'name' => 'Bulk Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->post(route('teams.store'), [
        'tournament_id' => $tournament->id,
        'names' => "Team Alpha\nTeam Bravo\nTeam Charlie",
        'team_bracket' => 'A',
        'team_category' => 'Open',
    ]);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/tournament/view/');
    $this->assertDatabaseCount('teams', 3);
    $this->assertDatabaseHas('teams', [
        'tournament_id' => $tournament->id,
        'team_no' => '01',
        'team_name' => 'Team Alpha',
        'team_bracket' => 'A',
        'team_category' => 'Open',
    ]);
    $this->assertDatabaseHas('teams', [
        'team_no' => '03',
        'team_name' => 'Team Charlie',
    ]);
    $this->assertDatabaseCount('matches', 3);
});

it('rejects duplicate team names without creating any teams', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'DUPLICATE-TOURNAMENT',
        'name' => 'Duplicate Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->from(route('teams.create', $tournament->id))
        ->post(route('teams.store'), [
            'tournament_id' => $tournament->id,
            'names' => "Team Alpha\nTeam Alpha",
            'team_bracket' => 'A',
            'team_category' => 'Open',
        ])
        ->assertRedirect(route('teams.create', $tournament->id))
        ->assertSessionHasErrors('names');

    $this->assertDatabaseCount('teams', 0);
});

it('deletes selected teams and refreshes the bracket matches', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'BULK-DELETE-TOURNAMENT',
        'name' => 'Bulk Delete Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)->post(route('teams.store'), [
        'tournament_id' => $tournament->id,
        'names' => "Team Alpha\nTeam Bravo\nTeam Charlie",
        'team_bracket' => 'A',
        'team_category' => 'Open',
    ]);

    $teams = Teams::query()->where('tournament_id', $tournament->id)->get();

    $response = $this->actingAs($user)->delete(route('teams.bulkDestroy'), [
        'tournament_id' => Crypt::encryptString($tournament->id),
        'team_bracket' => 'A',
        'team_category' => 'Open',
        'team_ids' => [
            Crypt::encryptString($teams[0]->id),
            Crypt::encryptString($teams[1]->id),
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('teams', 1);
    $this->assertDatabaseHas('teams', ['team_name' => 'Team Charlie']);
    $this->assertDatabaseCount('matches', 0);
});
