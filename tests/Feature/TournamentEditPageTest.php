<?php

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

it('loads the tournament edit page for an existing record', function () {
    $user = User::factory()->create();

    $tournament = Tournament::create([
        'code' => 'TOURNAMENT-1',
        'name' => 'Summer Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/tournament/'.Crypt::encryptString($tournament->id))
        ->assertOk()
        ->assertSee('Edit Tournament');
});

it('loads the tournament view page for an existing record', function () {
    $user = User::factory()->create();

    $tournament = Tournament::create([
        'code' => 'TOURNAMENT-2',
        'name' => 'Winter Cup',
        'date' => '2026-11-15',
        'venue' => 'Arena Two',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/tournament/view/'.Crypt::encryptString($tournament->id))
        ->assertOk()
        ->assertSee('Tournament Name (Winter Cup)');
});

it('deletes a tournament with an encrypted ID', function () {
    $user = User::factory()->create();

    $tournament = Tournament::create([
        'code' => 'TOURNAMENT-3',
        'name' => 'Spring Cup',
        'date' => '2026-12-15',
        'venue' => 'Arena Three',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->delete('/tournament/'.Crypt::encryptString($tournament->id))
        ->assertRedirect(route('tournament.index'));

    $this->assertDatabaseMissing('tournaments', [
        'id' => $tournament->id,
    ]);
});
