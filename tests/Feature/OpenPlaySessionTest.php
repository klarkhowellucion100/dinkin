<?php

use App\Models\OpenPlayMatch;
use App\Models\OpenPlaySession;
use App\Models\Player;
use App\Models\User;
use App\Services\OpenPlayRotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stages a balanced doubles game and rotates every player after a result', function () {
    $user = User::factory()->create();
    $session = OpenPlaySession::create([
        'user_id' => $user->id,
        'name' => 'Tuesday Open Play',
        'session_date' => '2026-10-17',
        'mode' => 'balanced',
        'format' => 'doubles',
        'court_count' => 1,
        'status' => 'live',
    ]);
    $players = collect(['A', 'B', 'C', 'D'])->map(fn (string $name, int $index): Player => Player::create([
        'user_id' => $user->id,
        'name' => $name,
        'skill_level' => 3,
    ]));

    foreach ($players as $index => $player) {
        $session->players()->attach($player, [
            'queue_position' => $index + 1,
            'checked_in_at' => now(),
        ]);
    }

    $service = app(OpenPlayRotationService::class);
    $matches = $service->stageNextMatches($session);

    expect($matches)->toHaveCount(1);

    $service->dispatchMatch($matches->first());
    $service->recordResult($matches->first(), 1, 11, 7);

    expect($session->matches()->where('status', 'completed')->count())->toBe(1)
        ->and($session->players()->wherePivot('games_played', 1)->count())->toBe(4)
        ->and($session->players()->wherePivot('wins', 1)->count())->toBe(2)
        ->and($session->players()->wherePivot('losses', 1)->count())->toBe(2)
        ->and($session->matches()->where('status', 'staged')->count())->toBe(1);
});

it('rotates doubles partners and pairs stronger players with weaker players when possible', function () {
    $user = User::factory()->create();
    $session = OpenPlaySession::create([
        'user_id' => $user->id,
        'name' => 'Partner Rotation',
        'session_date' => '2026-10-17',
        'mode' => 'winners_losers',
        'format' => 'doubles',
        'court_count' => 1,
        'status' => 'live',
    ]);
    $players = collect(['A', 'B', 'C', 'D', 'E', 'F'])->map(fn (string $name): Player => Player::create([
        'user_id' => $user->id,
        'name' => $name,
        'skill_level' => 3,
    ]));

    foreach ($players as $index => $player) {
        $session->players()->attach($player, [
            'queue_position' => $index + 1,
            'checked_in_at' => now(),
        ]);
    }

    $service = app(OpenPlayRotationService::class);
    $firstMatch = $service->stageNextMatches($session)->firstOrFail();
    $service->dispatchMatch($firstMatch);
    $service->recordResult($firstMatch, 1, 11, 7);
    $secondMatch = $session->matches()->where('status', 'staged')->firstOrFail();
    $firstTeam1 = [$firstMatch->team1_player1_id, $firstMatch->team1_player2_id];
    $firstTeam2 = [$firstMatch->team2_player1_id, $firstMatch->team2_player2_id];
    $secondTeam1 = [$secondMatch->team1_player1_id, $secondMatch->team1_player2_id];
    $secondTeam2 = [$secondMatch->team2_player1_id, $secondMatch->team2_player2_id];
    $partnerKey = function (array $team): string {
        sort($team);

        return implode('-', $team);
    };

    expect(array_diff(
        [...$secondTeam1, ...$secondTeam2],
        [...$firstTeam1, ...$firstTeam2],
    ))->not->toBeEmpty()
        ->and([$partnerKey($secondTeam1), $partnerKey($secondTeam2)])
        ->not->toContain($partnerKey($firstTeam1))
        ->not->toContain($partnerKey($firstTeam2));
});

it('avoids immediately replaying balanced players when enough players are waiting', function () {
    $user = User::factory()->create();
    $session = OpenPlaySession::create([
        'user_id' => $user->id,
        'name' => 'Balanced Cooldown',
        'session_date' => '2026-10-17',
        'mode' => 'balanced',
        'format' => 'doubles',
        'court_count' => 1,
        'status' => 'live',
    ]);
    $players = collect(range(1, 8))->map(fn (int $number): Player => Player::create([
        'user_id' => $user->id,
        'name' => 'Player '.$number,
        'skill_level' => 3,
    ]));

    foreach ($players as $index => $player) {
        $session->players()->attach($player, ['queue_position' => $index + 1, 'checked_in_at' => now()]);
    }

    $service = app(OpenPlayRotationService::class);
    $firstMatch = $service->stageNextMatches($session)->firstOrFail();
    $service->dispatchMatch($firstMatch);
    $service->recordResult($firstMatch, 1, 11, 7);
    $secondMatch = $session->matches()->where('status', 'staged')->firstOrFail();

    $firstPlayerIds = collect([$firstMatch->team1_player1_id, $firstMatch->team1_player2_id, $firstMatch->team2_player1_id, $firstMatch->team2_player2_id]);
    $secondPlayerIds = collect([$secondMatch->team1_player1_id, $secondMatch->team1_player2_id, $secondMatch->team2_player1_id, $secondMatch->team2_player2_id]);

    expect($firstPlayerIds->intersect($secondPlayerIds))->toHaveCount(0);
});

it('allows an organizer to create a session and add a player', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('open-play.store'), [
            'name' => 'Friday Open Play',
            'session_date' => '2026-10-17',
            'mode' => 'winners_losers',
            'format' => 'doubles',
            'court_count' => 2,
        ])
        ->assertRedirect();

    $session = OpenPlaySession::query()->firstOrFail();

    $this->actingAs($user)
        ->post(route('open-play.players.add', $session), [
            'name' => 'Player One',
            'skill_level' => 4,
        ])
        ->assertRedirect();

    expect($session->players()->count())->toBe(1)
        ->and($session->players()->wherePivotNotNull('checked_in_at')->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('open-play.show', $session))
        ->assertOk()
        ->assertSee('Friday Open Play')
        ->assertSee('Player One');
});

it('automatically checks in a player added during a live session', function () {
    $user = User::factory()->create();
    $session = OpenPlaySession::create([
        'user_id' => $user->id,
        'name' => 'Live Open Play',
        'session_date' => '2026-10-17',
        'mode' => 'balanced',
        'format' => 'doubles',
        'court_count' => 1,
        'status' => 'live',
    ]);

    $this->actingAs($user)
        ->post(route('open-play.players.add', $session), [
            'name' => 'Late Player',
            'skill_level' => 3,
        ])
        ->assertRedirect();

    $player = Player::query()->where('name', 'Late Player')->firstOrFail();

    expect($session->players()->where('players.id', $player->id)->wherePivotNotNull('checked_in_at')->exists())->toBeTrue();
});

it('calculates the winner from scores and refreshes the session over ajax', function () {
    $user = User::factory()->create();
    $session = OpenPlaySession::create([
        'user_id' => $user->id,
        'name' => 'Ajax Open Play',
        'session_date' => '2026-10-17',
        'mode' => 'balanced',
        'format' => 'doubles',
        'court_count' => 1,
        'status' => 'setup',
    ]);
    $players = collect(['A', 'B', 'C', 'D'])->map(fn (string $name): Player => Player::create([
        'user_id' => $user->id,
        'name' => $name,
        'skill_level' => 3,
    ]));

    foreach ($players as $index => $player) {
        $session->players()->attach($player, [
            'queue_position' => $index + 1,
            'checked_in_at' => now(),
        ]);
    }

    $ajaxHeaders = [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
    ];

    $this->actingAs($user)
        ->post(route('open-play.start', $session), [], $ajaxHeaders)
        ->assertOk()
        ->assertJsonStructure(['message', 'html']);

    $match = $session->matches()->where('status', 'staged')->firstOrFail();

    $this->actingAs($user)
        ->post(route('open-play.matches.dispatch', $match), [], $ajaxHeaders)
        ->assertOk()
        ->assertJsonPath('message', 'Match sent to the court.');

    $this->actingAs($user)
        ->post(route('open-play.matches.result', $match), [
            'team1_score' => 11,
            'team2_score' => 7,
        ], $ajaxHeaders)
        ->assertOk()
        ->assertJsonStructure(['message', 'html'])
        ->assertJsonPath('message', 'Result recorded and the next rotation was staged.');

    expect($match->fresh()->winner_team)->toBe(1)
        ->and($match->fresh()->team1_score)->toBe(11)
        ->and($match->fresh()->team2_score)->toBe(7);
});

it('stages matches without dispatching them and cancels a player from staged play', function () {
    $user = User::factory()->create();
    $session = OpenPlaySession::create([
        'user_id' => $user->id,
        'name' => 'Queue Controls',
        'session_date' => '2026-10-17',
        'mode' => 'balanced',
        'format' => 'doubles',
        'court_count' => 1,
        'status' => 'live',
    ]);
    $players = collect(['A', 'B', 'C', 'D'])->map(fn (string $name): Player => Player::create([
        'user_id' => $user->id,
        'name' => $name,
        'skill_level' => 3,
    ]));

    foreach ($players as $index => $player) {
        $session->players()->attach($player, ['queue_position' => $index + 1, 'checked_in_at' => now()]);
    }

    app(OpenPlayRotationService::class)->stageNextMatches($session);

    $this->actingAs($user)
        ->post(route('open-play.players.check-out', [$session, $players[0]]), [], ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['message', 'html']);

    expect($session->matches()->where('status', 'staged')->count())->toBe(0)
        ->and($session->players()->where('players.id', $players[0]->id)->wherePivotNotNull('checked_out_at')->exists())->toBeTrue();
});

it('returns a removed match player to the queue without checking them out', function () {
    $user = User::factory()->create();
    $session = OpenPlaySession::create([
        'user_id' => $user->id,
        'name' => 'Match Player Removal',
        'session_date' => '2026-10-17',
        'mode' => 'balanced',
        'format' => 'doubles',
        'court_count' => 1,
        'status' => 'live',
    ]);
    $players = collect(['A', 'B', 'C', 'D', 'E'])->map(fn (string $name): Player => Player::create([
        'user_id' => $user->id,
        'name' => $name,
        'skill_level' => 3,
    ]));

    foreach ($players as $index => $player) {
        $session->players()->attach($player, ['queue_position' => $index + 1, 'checked_in_at' => now()]);
    }

    $match = app(OpenPlayRotationService::class)->stageNextMatches($session)->firstOrFail();
    $removedPlayer = $players->firstWhere('id', $match->team1_player1_id);

    $this->actingAs($user)
        ->post(route('open-play.matches.players.remove', [$match, $removedPlayer]), [], ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('message', "{$removedPlayer->name} was returned to the queue.");

    $this->actingAs($user)
        ->post(route('open-play.matches.add-player', $session), [
            'player_id' => $removedPlayer->id,
        ], ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('message', 'The selected player was added to a new Up Next match.');

    expect($session->players()->where('players.id', $removedPlayer->id)->wherePivotNull('checked_out_at')->exists())->toBeTrue()
        ->and($match->fresh()->status)->toBe('staged')
        ->and($session->matches()->where('status', 'staged')->count())->toBe(1);
});

it('can undo the latest result and restore player records', function () {
    $user = User::factory()->create();
    $session = OpenPlaySession::create([
        'user_id' => $user->id,
        'name' => 'Undo Results',
        'session_date' => '2026-10-17',
        'mode' => 'balanced',
        'format' => 'doubles',
        'court_count' => 1,
        'status' => 'live',
    ]);
    $players = collect(['A', 'B', 'C', 'D'])->map(fn (string $name): Player => Player::create([
        'user_id' => $user->id,
        'name' => $name,
        'skill_level' => 3,
    ]));

    foreach ($players as $index => $player) {
        $session->players()->attach($player, ['queue_position' => $index + 1, 'checked_in_at' => now()]);
    }

    $match = app(OpenPlayRotationService::class)->stageNextMatches($session)->firstOrFail();
    app(OpenPlayRotationService::class)->dispatchMatch($match);
    app(OpenPlayRotationService::class)->recordResult($match, 1, 11, 7);

    $this->actingAs($user)
        ->post(route('open-play.undo', $session), [], ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('message', 'The latest result was undone.');

    expect($session->matches()->where('status', 'completed')->count())->toBe(0)
        ->and($session->players()->wherePivot('games_played', 0)->count())->toBe(4);
});

it('deletes the session and cascades its matches and queue records', function () {
    $user = User::factory()->create();
    $session = OpenPlaySession::create([
        'user_id' => $user->id,
        'name' => 'Delete Me Open Play',
        'session_date' => '2026-10-17',
        'mode' => 'balanced',
        'format' => 'doubles',
        'court_count' => 1,
        'status' => 'setup',
    ]);
    $players = collect(['A', 'B', 'C', 'D'])->map(fn (string $name): Player => Player::create([
        'user_id' => $user->id,
        'name' => $name,
        'skill_level' => 3,
    ]));
    $session->players()->attach($players->pluck('id')->all());
    $match = OpenPlayMatch::create([
        'open_play_session_id' => $session->id,
        'court_no' => 1,
        'round_no' => 1,
        'team1_player1_id' => $players[0]->id,
        'team1_player2_id' => $players[1]->id,
        'team2_player1_id' => $players[2]->id,
        'team2_player2_id' => $players[3]->id,
    ]);

    $this->actingAs($user)
        ->delete(route('open-play.destroy', $session))
        ->assertRedirect(route('open-play.index'));

    expect(OpenPlaySession::find($session->id))->toBeNull()
        ->and(OpenPlayMatch::find($match->id))->toBeNull()
        ->and($session->players()->count())->toBe(0)
        ->and(Player::query()->whereIn('id', $players->pluck('id'))->count())->toBe(4);
});

it('renders the public open play page without authentication', function () {
    $user = User::factory()->create();
    $session = OpenPlaySession::create([
        'user_id' => $user->id,
        'name' => 'Public Open Play',
        'session_date' => '2026-10-17',
        'mode' => 'balanced',
        'format' => 'doubles',
        'court_count' => 1,
        'status' => 'live',
    ]);
    $player = Player::create(['user_id' => $user->id, 'name' => 'Public Player', 'skill_level' => 3]);
    $session->players()->attach($player, ['queue_position' => 1, 'checked_in_at' => now()]);

    $this->get(route('public.open-play.show', $session))
        ->assertOk()
        ->assertSee('Public Open Play')
        ->assertSee('Public Player')
        ->assertSee('Player Standings');
});
