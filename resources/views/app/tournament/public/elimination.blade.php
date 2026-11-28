<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tournament->name }} · Elimination Bracket</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { min-height: 100vh; background: #f3f6fa; color: #17243a; }
        .page-shell { max-width: 1440px; }
        .hero { color: #fff; background: linear-gradient(120deg, #10294b, #176b72); border-radius: 1rem; padding: 1.5rem; }
        .hero .eyebrow { color: #b8e9e3; text-transform: uppercase; letter-spacing: .12em; font-size: .75rem; font-weight: 700; }
        .bracket-section { border: 0; border-radius: .9rem; box-shadow: 0 5px 22px rgba(25, 45, 75, .07); }
        .rounds { display: flex; align-items: stretch; gap: 1rem; overflow-x: auto; padding: .25rem .25rem 1rem; }
        .round-column { flex: 0 0 260px; }
        .round-title { color: #61708a; font-size: .8rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; margin: .25rem 0 .75rem; }
        .match-card { background: #fff; border: 1px solid #e0e7ef; border-radius: .7rem; box-shadow: 0 2px 7px rgba(25, 45, 75, .04); overflow: hidden; margin-bottom: .75rem; }
        .match-label { background: #f7f9fc; border-bottom: 1px solid #edf0f4; color: #69768a; font-size: .72rem; font-weight: 700; padding: .55rem .75rem; }
        .team-row { display: flex; justify-content: space-between; align-items: center; gap: .75rem; min-height: 42px; padding: .55rem .75rem; }
        .team-row + .team-row { border-top: 1px solid #edf0f4; }
        .team-name { overflow-wrap: anywhere; }
        .team-score { color: #536178; font-variant-numeric: tabular-nums; font-weight: 700; }
        .winner { background: #edf9f2; color: #17683b; font-weight: 700; }
        .pending { color: #8994a5; font-style: italic; }
        .match-status { padding: .55rem .75rem .7rem; }
        @media (max-width: 575.98px) { .hero { padding: 1.2rem; } .round-column { flex-basis: 230px; } }
    </style>
</head>
<body>
    <main class="container-fluid page-shell py-4 py-md-5">
        <header class="hero mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <div class="eyebrow">Tournament bracket</div>
                    <h1 class="h3 mt-2 mb-1">{{ $tournament->name }}</h1>
                    <div class="text-white-50">{{ ucfirst(str_replace('_', ' ', $matches->first()->format)) }} <span class="mx-1">·</span> {{ $team_category }} <span class="mx-1">·</span> Bracket {{ $team_bracket }}</div>
                </div>
                <a href="{{ route('public.tournaments') }}" class="btn btn-light btn-sm mt-3 mt-md-0">All tournaments</a>
            </div>
        </header>

        @foreach ($matches->groupBy('bracket_side') as $side => $sideMatches)
            <section class="card bracket-section mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h2 class="h5 mb-0">{{ ucwords(str_replace('_', ' ', $side)) }}{{ $side === 'grand_final' ? ' · Championship' : ' bracket' }}</h2>
                </div>
                <div class="card-body px-3 px-md-4">
                    <div class="rounds">
                    @foreach ($sideMatches->groupBy('round_number') as $round => $roundMatches)
                        <div class="round-column">
                            <h3 class="round-title">{{ $side === 'grand_final' ? 'Final' : 'Round '.$round }}</h3>
                            @foreach ($roundMatches as $match)
                                <article class="match-card">
                                    <div class="match-label">{{ $match->match_label }}</div>
                                    <div class="team-row {{ $match->winner_id && (int) $match->winner_id === (int) $match->team1_id ? 'winner' : '' }}">
                                        <span class="team-name {{ $match->team1 ? '' : 'pending' }}">{{ $match->team1?->team_name ?? 'Team pending' }}</span>
                                        <span class="team-score">{{ $match->team1_score ?? '–' }}</span>
                                    </div>
                                    <div class="team-row {{ $match->winner_id && (int) $match->winner_id === (int) $match->team2_id ? 'winner' : '' }}">
                                        <span class="team-name {{ $match->team2 ? '' : 'pending' }}">{{ $match->team2?->team_name ?? 'Team pending' }}</span>
                                        <span class="team-score">{{ $match->team2_score ?? '–' }}</span>
                                    </div>
                                    <div class="match-status">
                                        @if ($match->is_finalized)
                                            <span class="badge badge-success">Final</span>
                                            @if ($match->team2_id === null && $match->winner_id)
                                                <span class="small text-muted ml-1">Bye</span>
                                            @endif
                                        @else
                                            <span class="badge badge-warning">{{ $match->team1_id && $match->team2_id ? 'Ready to play' : 'Waiting for teams' }}</span>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endforeach
                    </div>
                </div>
            </section>
        @endforeach
    </main>
</body>
</html>
