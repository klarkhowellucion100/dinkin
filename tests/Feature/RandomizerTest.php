<?php

use App\Models\RandomizerSession;
use App\Models\User;

it('saves a randomizer draw without creating tournament data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('randomizer.store'), [
        'names' => "Alex\nSam\nJordan\nTaylor\nMorgan",
        'bracket_count' => 2,
    ]);

    $session = RandomizerSession::first();

    $response->assertRedirect(route('randomizer.index'));
    expect($session)->not->toBeNull();
    $this->assertDatabaseCount('randomizer_entries', 5);
    $this->assertDatabaseCount('tournaments', 0);
    expect($session->entries->groupBy('bracket_number'))->toHaveCount(2);
    expect($session->entries)->toHaveCount(5);
    expect($session->entries->groupBy('bracket_number')->map->count()->sort()->values()->all())->toBe([2, 3]);

    $this->actingAs($user)
        ->get(route('randomizer.index'))
        ->assertOk()
        ->assertSee('Latest result')
        ->assertSee('Bracket 1');
});

it('rejects a draw when there are not enough names for every bracket', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('randomizer.index'))
        ->post(route('randomizer.store'), [
            'names' => 'Alex',
            'bracket_count' => 2,
        ])
        ->assertRedirect(route('randomizer.index'))
        ->assertSessionHasErrors('names');

    $this->assertDatabaseCount('randomizer_sessions', 0);
});

it('can review and delete a saved draw', function () {
    $user = User::factory()->create();
    $session = RandomizerSession::create([
        'user_id' => $user->id,
        'bracket_count' => 2,
        'teams_per_bracket' => null,
        'name_count' => 4,
        'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('randomizer.show', $session->id))
        ->assertOk()
        ->assertSee('Saved draw');

    $this->actingAs($user)
        ->delete(route('randomizer.destroy', $session->id))
        ->assertRedirect(route('randomizer.index'));

    $this->assertDatabaseMissing('randomizer_sessions', ['id' => $session->id]);
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
