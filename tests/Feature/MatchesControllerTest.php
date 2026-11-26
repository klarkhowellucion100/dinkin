<?php

use App\Models\Matches;
use App\Models\PlayoffMatches;
use App\Models\Teams;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

it('assigns generated matches across courts in category priority order', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'COURT-TOURNAMENT',
        'name' => 'Court Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    $matchNumber = 1;
    foreach ([['High Beginners', 'A'], ['High Beginners', 'B'], ['Low Beginners', 'A'], ['Low Beginners', 'B']] as [$category, $bracket]) {
        $teams = collect([1, 2])->map(fn (int $number): Teams => Teams::create([
            'code' => "COURT-TEAM-{$matchNumber}-{$number}",
            'team_no' => str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            'team_name' => "{$category} {$bracket} Team {$number}",
            'team_bracket' => $bracket,
            'team_category' => $category,
            'tournament_id' => $tournament->id,
            'user_id' => $user->id,
        ]));

        Matches::create([
            'code' => "COURT-MATCH-{$matchNumber}",
            'tournament_id' => $tournament->id,
            'team_bracket' => $bracket,
            'team_category' => $category,
            'round_no' => '1',
            'match_no' => '1',
            'team1_id' => $teams[0]->id,
            'team2_id' => $teams[1]->id,
            'user_id' => $user->id,
        ]);

        if ($bracket === 'A' && $category === 'High Beginners') {
            $extraTeams = collect([3, 4])->map(fn (int $number): Teams => Teams::create([
                'code' => "COURT-TEAM-{$matchNumber}-{$number}",
                'team_no' => str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'team_name' => "{$category} {$bracket} Team {$number}",
                'team_bracket' => $bracket,
                'team_category' => $category,
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
            ]));

            Matches::create([
                'code' => "COURT-MATCH-{$matchNumber}-2",
                'tournament_id' => $tournament->id,
                'team_bracket' => $bracket,
                'team_category' => $category,
                'round_no' => '1',
                'match_no' => '2',
                'team1_id' => $extraTeams[0]->id,
                'team2_id' => $extraTeams[1]->id,
                'user_id' => $user->id,
            ]);
        }

        $matchNumber++;
    }

    $this->actingAs($user)
        ->put(route('tournament.courtSchedule.update', Crypt::encryptString($tournament->id)), [
            'court_count' => 3,
        ])
        ->assertRedirect();

    expect(Matches::query()->where('tournament_id', $tournament->id)->orderBy('schedule_no')->orderBy('court_no')->get()
        ->map(fn (Matches $match): array => [$match->team_category, $match->team_bracket, $match->schedule_no, $match->court_no])->all())
        ->toBe([
            ['High Beginners', 'A', 1, 1],
            ['High Beginners', 'B', 1, 2],
            ['Low Beginners', 'A', 1, 3],
            ['Low Beginners', 'B', 2, 1],
            ['High Beginners', 'A', 2, 2],
        ]);
});

it('keeps categories together and reuses courts sequentially in category mode', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'CATEGORY-COURT-TOURNAMENT',
        'name' => 'Category Court Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    foreach (['Open', 'High Intermediate', 'High Beginners', 'Low Beginners'] as $index => $category) {
        $teams = collect([1, 2])->map(fn (int $number): Teams => Teams::create([
            'code' => "CATEGORY-TEAM-{$index}-{$number}",
            'team_no' => str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            'team_name' => "{$category} Team {$number}",
            'team_bracket' => 'A',
            'team_category' => $category,
            'tournament_id' => $tournament->id,
            'user_id' => $user->id,
        ]));

        Matches::create([
            'code' => "CATEGORY-MATCH-{$index}",
            'tournament_id' => $tournament->id,
            'team_bracket' => 'A',
            'team_category' => $category,
            'round_no' => '1',
            'match_no' => '1',
            'team1_id' => $teams[0]->id,
            'team2_id' => $teams[1]->id,
            'user_id' => $user->id,
        ]);
    }

    $this->actingAs($user)
        ->put(route('tournament.courtSchedule.update', Crypt::encryptString($tournament->id)), [
            'court_count' => 2,
            'schedule_mode' => 'category',
        ])
        ->assertRedirect();

    expect(Matches::query()->where('tournament_id', $tournament->id)->get()
        ->sortBy(['court_no', 'schedule_no'])
        ->map(fn (Matches $match): array => [$match->team_category, $match->schedule_no, $match->court_no])->values()->all())
        ->toBe([
            ['Open', 1, 1],
            ['High Beginners', 2, 1],
            ['High Intermediate', 1, 2],
            ['Low Beginners', 2, 2],
        ]);

    expect($tournament->fresh()->schedule_mode)->toBe('category');
});

it('generates timed category blocks across courts', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'TIMED-CATEGORY-TOURNAMENT',
        'name' => 'Timed Category Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    $matchNumber = 1;
    foreach ([['Open', 'A'], ['Open', 'B'], ['High Beginners', 'A'], ['High Beginners', 'B'], ['High Beginners', 'C']] as [$category, $bracket]) {
        $teams = collect([1, 2])->map(fn (int $number): Teams => Teams::create([
            'code' => "TIMED-TEAM-{$matchNumber}-{$number}",
            'team_no' => str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            'team_name' => "{$category} {$bracket} Team {$number}",
            'team_bracket' => $bracket,
            'team_category' => $category,
            'tournament_id' => $tournament->id,
            'user_id' => $user->id,
        ]));

        Matches::create([
            'code' => "TIMED-MATCH-{$matchNumber}",
            'tournament_id' => $tournament->id,
            'team_bracket' => $bracket,
            'team_category' => $category,
            'round_no' => '1',
            'match_no' => (string) $matchNumber,
            'team1_id' => $teams[0]->id,
            'team2_id' => $teams[1]->id,
            'user_id' => $user->id,
        ]);

        $matchNumber++;
    }

    $this->actingAs($user)
        ->put(route('tournament.courtSchedule.update', Crypt::encryptString($tournament->id)), [
            'court_count' => 2,
            'schedule_mode' => 'timed_category',
            'schedule_start_time' => '18:00',
            'match_duration_minutes' => 12,
        ])
        ->assertRedirect();

    expect(Matches::query()->where('tournament_id', $tournament->id)->orderBy('schedule_no')->orderBy('court_no')->get()
        ->map(fn (Matches $match): array => [$match->team_category, $match->team_bracket, $match->schedule_no, $match->court_no])->all())
        ->toBe([
            ['Open', 'A', 1, 1],
            ['Open', 'B', 1, 2],
            ['High Beginners', 'A', 2, 1],
            ['High Beginners', 'B', 2, 2],
            ['High Beginners', 'C', 3, 1],
        ]);

    expect($tournament->fresh()->schedule_mode)->toBe('timed_category')
        ->and($tournament->fresh()->schedule_start_time)->toStartWith('18:00')
        ->and($tournament->fresh()->match_duration_minutes)->toBe(12);

    $this->actingAs($user)
        ->get(route('tournament.courtSchedule.print', Crypt::encryptString($tournament->id)))
        ->assertOk()
        ->assertSee('6:00 PM – 6:12 PM')
        ->assertSee('6:12 PM – 6:24 PM');
});

it('requires timed schedule settings for timed category mode', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'TIMED-VALIDATION-TOURNAMENT',
        'name' => 'Timed Validation Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->put(route('tournament.courtSchedule.update', Crypt::encryptString($tournament->id)), [
            'court_count' => 2,
            'schedule_mode' => 'timed_category',
        ])
        ->assertSessionHasErrors(['schedule_start_time', 'match_duration_minutes']);

    expect($tournament->fresh()->court_count)->toBeNull()
        ->and($tournament->fresh()->schedule_mode)->toBe('bracket');
});

it('reuses a freed court for the next category in finish mode', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'TIMED-FINISH-TOURNAMENT',
        'name' => 'Timed Finish Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    $matchNumber = 1;
    foreach ([['Open', 1], ['High Beginners', 3], ['Low Beginners', 1]] as [$category, $matchCount]) {
        for ($categoryMatch = 1; $categoryMatch <= $matchCount; $categoryMatch++) {
            $teams = collect([1, 2])->map(fn (int $number): Teams => Teams::create([
                'code' => "FINISH-TEAM-{$matchNumber}-{$number}",
                'team_no' => str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'team_name' => "{$category} Team {$matchNumber}-{$number}",
                'team_bracket' => $category === 'High Beginners' && $categoryMatch === 1 ? 'B' : 'A',
                'team_category' => $category,
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
            ]));

            Matches::create([
                'code' => "FINISH-MATCH-{$matchNumber}",
                'tournament_id' => $tournament->id,
                'team_bracket' => $category === 'High Beginners' && $categoryMatch === 1 ? 'B' : 'A',
                'team_category' => $category,
                'round_no' => '1',
                'match_no' => (string) $categoryMatch,
                'team1_id' => $teams[0]->id,
                'team2_id' => $teams[1]->id,
                'user_id' => $user->id,
            ]);

            $matchNumber++;
        }
    }

    $this->actingAs($user)
        ->put(route('tournament.courtSchedule.update', Crypt::encryptString($tournament->id)), [
            'court_count' => 2,
            'schedule_mode' => 'timed_finish_category',
            'schedule_start_time' => '18:00',
            'match_duration_minutes' => 12,
        ])
        ->assertRedirect();

    expect(Matches::query()->where('tournament_id', $tournament->id)->get()
        ->sortBy(['team_category', 'schedule_no'])
        ->map(fn (Matches $match): array => [$match->team_category, $match->team_bracket, $match->schedule_no, $match->court_no])->values()->all())
        ->toBe([
            ['High Beginners', 'A', 1, 2],
            ['High Beginners', 'A', 2, 2],
            ['High Beginners', 'B', 3, 2],
            ['Low Beginners', 'A', 2, 1],
            ['Open', 'A', 1, 1],
        ]);
});

it('finishes alternating bracket blocks before starting the next block', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'TIMED-FINISH-ALTERNATE-TOURNAMENT',
        'name' => 'Timed Finish Alternate Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    $matchNumber = 1;
    foreach (['A', 'B', 'C', 'D'] as $bracket) {
        for ($bracketMatch = 1; $bracketMatch <= 2; $bracketMatch++) {
            $teams = collect([1, 2])->map(fn (int $number): Teams => Teams::create([
                'code' => "FINISH-ALTERNATE-TEAM-{$matchNumber}-{$number}",
                'team_no' => str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'team_name' => "Bracket {$bracket} Team {$bracketMatch}-{$number}",
                'team_bracket' => $bracket,
                'team_category' => 'Open',
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
            ]));

            Matches::create([
                'code' => "FINISH-ALTERNATE-MATCH-{$matchNumber}",
                'tournament_id' => $tournament->id,
                'team_bracket' => $bracket,
                'team_category' => 'Open',
                'round_no' => '1',
                'match_no' => (string) $bracketMatch,
                'team1_id' => $teams[0]->id,
                'team2_id' => $teams[1]->id,
                'user_id' => $user->id,
            ]);

            $matchNumber++;
        }
    }

    $this->actingAs($user)
        ->put(route('tournament.courtSchedule.update', Crypt::encryptString($tournament->id)), [
            'court_count' => 2,
            'schedule_mode' => 'timed_finish_alternate_category',
            'schedule_start_time' => '18:00',
            'match_duration_minutes' => 12,
        ])
        ->assertRedirect();

    expect(Matches::query()->where('tournament_id', $tournament->id)->orderBy('schedule_no')->orderBy('court_no')->get()
        ->map(fn (Matches $match): array => [$match->team_bracket, $match->schedule_no, $match->court_no])->all())
        ->toBe([
            ['A', 1, 1],
            ['B', 2, 1],
            ['A', 3, 1],
            ['B', 4, 1],
            ['C', 5, 1],
            ['D', 6, 1],
            ['C', 7, 1],
            ['D', 8, 1],
        ]);
});

it('adds time ranges to the spread bracket and category modes', function () {
    $user = User::factory()->create();

    foreach (['timed_bracket', 'timed_category_spread'] as $modeIndex => $scheduleMode) {
        $tournament = Tournament::create([
            'code' => "TIMED-SPREAD-{$modeIndex}",
            'name' => "Timed Spread Cup {$modeIndex}",
            'date' => '2026-10-15',
            'venue' => 'Arena One',
            'user_id' => $user->id,
        ]);

        foreach (['Open', 'High Beginners'] as $matchIndex => $category) {
            $teams = collect([1, 2])->map(fn (int $number): Teams => Teams::create([
                'code' => "SPREAD-TEAM-{$modeIndex}-{$matchIndex}-{$number}",
                'team_no' => str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'team_name' => "{$category} Team {$number}",
                'team_bracket' => 'A',
                'team_category' => $category,
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
            ]));

            Matches::create([
                'code' => "SPREAD-MATCH-{$modeIndex}-{$matchIndex}",
                'tournament_id' => $tournament->id,
                'team_bracket' => 'A',
                'team_category' => $category,
                'round_no' => '1',
                'match_no' => '1',
                'team1_id' => $teams[0]->id,
                'team2_id' => $teams[1]->id,
                'user_id' => $user->id,
            ]);
        }

        $this->actingAs($user)
            ->put(route('tournament.courtSchedule.update', Crypt::encryptString($tournament->id)), [
                'court_count' => 2,
                'schedule_mode' => $scheduleMode,
                'schedule_start_time' => '18:00',
                'match_duration_minutes' => 12,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('tournament.courtSchedule', Crypt::encryptString($tournament->id)))
            ->assertOk()
            ->assertSee('6:00 PM – 6:12 PM');
    }
});

it('renders the court schedule on its own tournament page', function () {
    $user = User::factory()->create(['approval' => 1]);
    $tournament = Tournament::create([
        'code' => 'SEPARATE-SCHEDULE-TOURNAMENT',
        'name' => 'Separate Schedule Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
        'court_count' => 1,
    ]);
    $teams = collect(['Team Alpha', 'Team Bravo'])->map(fn (string $name, int $index): Teams => Teams::create([
        'code' => 'SEPARATE-SCHEDULE-TEAM-'.($index + 1),
        'team_no' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
        'team_name' => $name,
        'team_bracket' => 'A',
        'team_category' => 'Open',
        'tournament_id' => $tournament->id,
        'user_id' => $user->id,
    ]));
    Matches::create([
        'code' => 'SEPARATE-SCHEDULE-MATCH-1',
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
        ->get(route('tournament.courtSchedule', Crypt::encryptString($tournament->id)))
        ->assertOk()
        ->assertSee('Court Schedule')
        ->assertSee('Generate Court Schedule')
        ->assertSee('Standings Summary')
        ->assertDontSee('Generate Playoffs')
        ->assertSee('Bracket A')
        ->assertSee('Print score sheets:')
        ->assertSee('Court 1')
        ->assertSee('Score Match 1')
        ->assertSee('Team Alpha')
        ->assertSee('Pending');

    $this->actingAs($user)
        ->get(route('tournament.courtSchedule.scoreSheets', [
            'tournament_id' => Crypt::encryptString($tournament->id),
            'court_no' => 1,
        ]))
        ->assertOk()
        ->assertSee('Court 1')
        ->assertSee('Team Alpha')
        ->assertSee('Bracket')
        ->assertSee('Open')
        ->assertSee('Print / Save as PDF');
});

it('generates cross-bracket playoffs from the court schedule form', function () {
    $user = User::factory()->create(['approval' => 1]);
    $tournament = Tournament::create([
        'code' => 'COURT-PLAYOFF-TOURNAMENT',
        'name' => 'Court Playoff Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    foreach (['A', 'B'] as $bracket) {
        $teams = collect([1, 2])->map(fn (int $number): Teams => Teams::create([
            'code' => "COURT-PLAYOFF-TEAM-{$bracket}-{$number}",
            'team_no' => str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            'team_name' => "Bracket {$bracket} Team {$number}",
            'team_bracket' => $bracket,
            'team_category' => 'Open',
            'tournament_id' => $tournament->id,
            'user_id' => $user->id,
        ]));

        Matches::create([
            'code' => "COURT-PLAYOFF-MATCH-{$bracket}",
            'tournament_id' => $tournament->id,
            'team_bracket' => $bracket,
            'team_category' => 'Open',
            'round_no' => '1',
            'match_no' => '1',
            'team1_id' => $teams[0]->id,
            'team2_id' => $teams[1]->id,
            'team1_score' => 21,
            'team2_score' => 18,
            'winner_id' => $teams[0]->id,
            'is_finalized' => true,
            'user_id' => $user->id,
        ]);
    }

    $this->actingAs($user)
        ->post(route('tournament.playoffs.generate'), [
            'tournament_id' => Crypt::encryptString($tournament->id),
            'team_category' => 'Open',
            'qualifiers' => [
                ['bracket' => 'A', 'rank1_id' => Teams::where('team_name', 'Bracket A Team 1')->value('id'), 'rank2_id' => Teams::where('team_name', 'Bracket A Team 2')->value('id')],
                ['bracket' => 'B', 'rank1_id' => Teams::where('team_name', 'Bracket B Team 1')->value('id'), 'rank2_id' => Teams::where('team_name', 'Bracket B Team 2')->value('id')],
            ],
        ])
        ->assertRedirect();

    expect(PlayoffMatches::query()->where('tournament_id', $tournament->id)->count())->toBe(2);
});

it('exports only the selected bracket matches as an Excel file', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'EXPORT-TOURNAMENT',
        'name' => 'Summer Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);
    $teams = collect([
        ['code' => 'EXPORT-TEAM-1', 'team_no' => '01', 'team_name' => 'Team Alpha', 'team_bracket' => 'A', 'team_category' => 'Open'],
        ['code' => 'EXPORT-TEAM-2', 'team_no' => '02', 'team_name' => 'Team Bravo', 'team_bracket' => 'A', 'team_category' => 'Open'],
        ['code' => 'EXPORT-TEAM-3', 'team_no' => '03', 'team_name' => 'Other Team', 'team_bracket' => 'B', 'team_category' => 'Open'],
    ])->map(fn (array $attributes): Teams => Teams::create([
        ...$attributes,
        'tournament_id' => $tournament->id,
        'user_id' => $user->id,
    ]));
    Matches::create([
        'code' => 'EXPORT-MATCH-1',
        'tournament_id' => $tournament->id,
        'team_bracket' => 'A',
        'team_category' => 'Open',
        'round_no' => '1',
        'match_no' => '1',
        'team1_id' => $teams[0]->id,
        'team2_id' => $teams[1]->id,
        'team1_score' => 21,
        'team2_score' => 18,
        'winner_id' => $teams[0]->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('tournament.matches.export', [
        'tournament_id' => Crypt::encryptString($tournament->id),
        'team_bracket' => 'A',
        'team_category' => 'Open',
    ]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="Summer_Cup_Open_A.xls"')
        ->assertSee('Team Alpha')
        ->assertSee('Team Bravo')
        ->assertDontSee('Other Team');
});

it('renders the Excel export button on the matches page', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'BUTTON-TOURNAMENT',
        'name' => 'Button Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('tournament.matches.view', [
        'tournament_id' => Crypt::encryptString($tournament->id),
        'team_bracket' => 'A',
        'team_category' => 'Open',
    ]));

    $response->assertOk()->assertSee('Export Excel');
});

it('exports a full tournament offline workbook with score and standings formulas', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'OFFLINE-TOURNAMENT',
        'name' => 'Offline Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);
    $teams = collect(['Team Alpha', 'Team Bravo'])->map(fn (string $name, int $index): Teams => Teams::create([
        'code' => 'OFFLINE-TEAM-'.($index + 1),
        'team_no' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
        'team_name' => $name,
        'team_bracket' => 'A',
        'team_category' => 'Open',
        'tournament_id' => $tournament->id,
        'user_id' => $user->id,
    ]));
    Matches::create([
        'code' => 'OFFLINE-MATCH-1',
        'tournament_id' => $tournament->id,
        'team_bracket' => 'A',
        'team_category' => 'Open',
        'round_no' => '1',
        'match_no' => '1',
        'team1_id' => $teams[0]->id,
        'team2_id' => $teams[1]->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('tournament.offlineBackup', Crypt::encryptString($tournament->id)));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="Offline_Cup_offline_backup.xml"')
        ->assertSee('ss:Name="Matches"', false)
        ->assertSee('ss:Name="Standings"', false)
        ->assertSee('Team 1 Score')
        ->assertSee('COUNTIFS', false)
        ->assertSee('Team Alpha');
});

it('renders the offline backup button on the tournament page', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'OFFLINE-BUTTON-TOURNAMENT',
        'name' => 'Offline Button Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('tournament.view', Crypt::encryptString($tournament->id)))
        ->assertOk()
        ->assertSee('Download Offline Excel Backup');
});

it('renders the blank score sheet page', function () {
    $user = User::factory()->create(['approval' => 1]);

    $this->actingAs($user)
        ->get(route('scoreSheets.blank'))
        ->assertOk()
        ->assertSee('Blank Score Sheet')
        ->assertSee('Print at 100% scale to fit three blank score sheets per A4 page.')
        ->assertSee('Team 1 Signature')
        ->assertSee('Team 2 Signature');
});

it('renders printable score sheets for the selected category and bracket', function () {
    $user = User::factory()->create();
    $tournament = Tournament::create([
        'code' => 'SCORE-SHEET-TOURNAMENT',
        'name' => 'Score Sheet Cup',
        'date' => '2026-10-15',
        'venue' => 'Arena One',
        'user_id' => $user->id,
    ]);
    $teams = collect(['Team Alpha', 'Team Bravo'])->map(fn (string $name, int $index): Teams => Teams::create([
        'code' => 'SCORE-SHEET-TEAM-'.($index + 1),
        'team_no' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
        'team_name' => $name,
        'team_bracket' => 'A',
        'team_category' => 'Open',
        'tournament_id' => $tournament->id,
        'user_id' => $user->id,
    ]));
    Matches::create([
        'code' => 'SCORE-SHEET-MATCH-1',
        'tournament_id' => $tournament->id,
        'team_bracket' => 'A',
        'team_category' => 'Open',
        'round_no' => '1',
        'match_no' => '1',
        'team1_id' => $teams[0]->id,
        'team2_id' => $teams[1]->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('tournament.matches.scoreSheets', [
            'tournament_id' => Crypt::encryptString($tournament->id),
            'team_bracket' => 'A',
            'team_category' => 'Open',
        ]))
        ->assertOk()
        ->assertSee('DinKin App')
        ->assertSee('Team Alpha')
        ->assertSee('Team Bravo')
        ->assertSee('Print / Save as PDF')
        ->assertSee('Timeouts: ☐ ☐ ☐');
});
