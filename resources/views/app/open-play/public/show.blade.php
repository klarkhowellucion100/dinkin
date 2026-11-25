<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5">
    <title>{{ $session->name }} · Open Play</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --primary: #111827;
            --accent: #f59e0b;
            --success: #16a34a;
            --danger: #dc2626;
            --border: #e5e7eb;
            --muted: #6b7280;
        }
        * { box-sizing: border-box; }
        body {
            background: #f4f6f9;
            color: #111827;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
        }
        .sports-header {
            background: linear-gradient(135deg, #111827 0%, #1f2937 55%, #111827 100%);
            box-shadow: 0 4px 18px rgba(0, 0, 0, .18);
            color: #fff;
            padding: 28px 0;
        }
        .brand-wrapper { align-items: center; display: flex; gap: 15px; }
        .brand-icon {
            align-items: center;
            background: rgba(255, 255, 255, .1);
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 14px;
            color: var(--accent);
            display: flex;
            font-size: 24px;
            height: 52px;
            justify-content: center;
            width: 52px;
        }
        .brand-title { font-size: 25px; font-weight: 700; margin: 0; }
        .brand-subtitle { color: rgba(255, 255, 255, .65); font-size: 13px; margin-top: 2px; }
        .live-indicator {
            align-items: center;
            background: rgba(22, 163, 74, .15);
            border: 1px solid rgba(34, 197, 94, .3);
            border-radius: 30px;
            color: #86efac;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 7px;
            letter-spacing: .5px;
            padding: 7px 12px;
            text-transform: uppercase;
        }
        .live-dot {
            background: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, .15);
            height: 8px;
            width: 8px;
        }
        .dashboard { padding: 25px 0 50px; }
        .hero {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px !important;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .05);
            color: #111827;
            margin-bottom: 25px !important;
            padding: 20px 24px !important;
        }
        .hero h1 { font-size: 23px; font-weight: 750; }
        .hero .small { color: var(--muted); font-size: 12px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; }
        .hero p { color: var(--muted); font-size: 13px; }
        .hero .badge { background: #f3f4f6; color: #374151; }
        .refresh-note { color: var(--muted) !important; font-size: 11px; }
        .dashboard-card,
        .standings-card {
            border: 0;
            border-radius: 1rem;
            margin-bottom: 1.25rem;
            overflow: hidden;
        }
        .dashboard-card > .card-header,
        .standings-card .card-header {
            align-items: center;
            background: #fff;
            border-bottom: 1px solid var(--border);
            color: #111827;
            display: flex;
            min-height: 66px;
            padding: 1rem 1.25rem;
        }
        .dashboard-card > .card-body { padding: 1.25rem; }
        .card-header strong { font-size: .95rem; }
        .card-header strong i {
            align-items: center;
            background: #f3f4f6;
            border-radius: 8px;
            color: #f59e0b;
            display: inline-flex;
            height: 32px;
            justify-content: center;
            margin-right: .35rem !important;
            width: 32px;
        }
        .court-card {
            border: 1px solid #dbeafe;
            border-left: 4px solid #2563eb;
            border-radius: .75rem;
            overflow: hidden;
        }
        .court-card:last-child { margin-bottom: 0 !important; }
        .court-card .card-header {
            background: #eff6ff;
            border-bottom: 1px solid #dbeafe;
            color: #1e40af;
            font-size: .82rem;
            font-weight: 700;
            padding: .65rem .9rem;
        }
        .court-card .card-body { padding: 1.1rem; }
        .court-card h5 { color: #0f172a; font-size: 1rem; font-weight: 700; margin-bottom: 0; }
        .court-versus {
            color: #94a3b8;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .12em;
            margin: .7rem 0;
            text-transform: uppercase;
        }
        .dashboard-table { min-width: 650px; }
        .dashboard-table thead th {
            background: #f8fafc;
            border-top: 0;
            color: #64748b;
            font-size: .68rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .dashboard-table td { vertical-align: middle; }
        .dashboard-table tbody tr:hover { background: #f8fafc; }
        .score-badge {
            background: #eff6ff;
            border-radius: 999px;
            color: #1e40af;
            display: inline-block;
            font-weight: 700;
            padding: .25rem .6rem;
            white-space: nowrap;
        }
        .result-badge {
            background: #dcfce7;
            border-radius: 999px;
            color: #166534;
            display: inline-block;
            font-size: .78rem;
            font-weight: 700;
            padding: .25rem .6rem;
            white-space: nowrap;
        }
        .queue-list .list-group-item { border-color: #eef2f7; padding: .75rem 1.25rem; }
        .queue-number {
            align-items: center;
            background: #dbeafe;
            border-radius: 50%;
            color: #1d4ed8;
            display: inline-flex;
            font-size: .75rem;
            font-weight: 700;
            height: 26px;
            justify-content: center;
            margin-right: .55rem;
            width: 26px;
        }
        .standing-subtitle {
            color: var(--muted);
            font-size: .78rem;
            margin-top: .2rem;
        }
        .standings-table { min-width: 520px; }
        .standings-table thead th {
            background: #f8fafc;
            border-top: 0;
            color: #64748b;
            font-size: .68rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .standings-table td { vertical-align: middle; }
        .standings-table tbody tr { transition: background-color .15s ease; }
        .standings-table tbody tr:hover { background: #f8fafc; }
        .standings-table .podium-row { background: #fffdf5; }
        .rank-badge {
            align-items: center;
            background: #e2e8f0;
            border-radius: 50%;
            color: #334155;
            display: inline-flex;
            font-size: .8rem;
            font-weight: 700;
            height: 30px;
            justify-content: center;
            width: 30px;
        }
        .rank-badge.gold { background: #fbbf24; color: #78350f; }
        .rank-badge.silver { background: #cbd5e1; color: #334155; }
        .rank-badge.bronze { background: #fdba74; color: #7c2d12; }
        .player-cell { align-items: center; display: flex; min-width: 150px; }
        .player-avatar {
            align-items: center;
            background: #dbeafe;
            border-radius: 50%;
            color: #1d4ed8;
            display: inline-flex;
            flex: 0 0 32px;
            font-size: .85rem;
            font-weight: 700;
            height: 32px;
            justify-content: center;
            margin-right: .6rem;
            width: 32px;
        }
        .record-pill {
            background: #eff6ff;
            border-radius: 999px;
            color: #1e40af;
            display: inline-block;
            font-size: .82rem;
            font-weight: 700;
            padding: .25rem .55rem;
        }
        .win-rate { color: #0f766e; white-space: nowrap; }
        .diff-positive { color: #15803d; }
        .diff-negative { color: #dc2626; }
        @media (max-width: 575.98px) {
            .container { padding-left: .75rem; padding-right: .75rem; }
            .sports-header { padding: 20px 0; }
            .brand-title { font-size: 20px; }
            .live-indicator { margin-top: 12px; }
            .hero { padding: 1.25rem !important; }
            .hero h1 { font-size: 1.55rem; }
            .hero .badge { font-size: .65rem; }
            .dashboard-card > .card-header,
            .standings-card .card-header { padding: .85rem 1rem; }
            .dashboard-card > .card-body { padding: 1rem; }
        }
    </style>
</head>
<body>
    <header class="sports-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="brand-wrapper">
                        <div class="brand-icon">
                            <i class="fas fa-table-tennis-paddle-ball"></i>
                        </div>
                        <div>
                            <h1 class="brand-title">Open Play Results</h1>
                            <div class="brand-subtitle">Live standings, courts and match results</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    <span class="live-indicator"><span class="live-dot"></span> Live Results</span>
                </div>
            </div>
        </div>
    </header>

    @php
        $busyPlayerIds = $session->matches->whereIn('status', ['active', 'staged'])->flatMap(fn ($match) => [$match->team1_player1_id, $match->team1_player2_id, $match->team2_player1_id, $match->team2_player2_id])->filter()->all();
        $waitingPlayers = $session->players->reject(fn ($player) => in_array($player->id, $busyPlayerIds, true));
    @endphp
    <main class="dashboard">
    <div class="container">
        <div class="hero rounded shadow-sm p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="small text-uppercase">Open Play Live Board</div>
                    <h1 class="mb-1">{{ $session->name }}</h1>
                    <p class="mb-0">{{ $session->session_date->format('M d, Y') }} · {{ ucfirst($session->format) }} · {{ $session->court_count }} court(s)</p>
                </div>
                <span class="badge badge-light">{{ ucfirst($session->status) }}</span>
            </div>
            <small class="refresh-note d-block mt-3"><i class="fas fa-sync-alt mr-1"></i> This page refreshes every 5 seconds.</small>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card dashboard-card shadow-sm">
                    <div class="card-header"><strong><i class="fas fa-table-tennis mr-1"></i> Live Courts</strong></div>
                    <div class="card-body">
                        @forelse ($session->matches->where('status', 'active')->sortBy('court_no') as $match)
                            <div class="card court-card mb-3">
                                <div class="card-header">Court {{ $match->court_no }} · Round {{ $match->round_no }}</div>
                                <div class="card-body text-center">
                                    <h5>{{ $match->team1Player1?->name ?? 'Open slot' }}@if($match->team1Player2) / {{ $match->team1Player2->name }}@endif</h5>
                                    <div class="court-versus">versus</div>
                                    <h5>{{ $match->team2Player1?->name ?? 'Open slot' }}@if($match->team2Player2) / {{ $match->team2Player2->name }}@endif</h5>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No active games at the moment.</p>
                        @endforelse
                    </div>
                </div>

                <div class="card dashboard-card shadow-sm">
                    <div class="card-header"><strong><i class="fas fa-forward mr-1"></i> Up Next</strong><small class="text-muted ml-2">Players called when a court is ready</small></div>
                    <div class="card-body">
                        @forelse ($session->matches->where('status', 'staged')->sortBy('court_no') as $match)
                            <div class="card court-card mb-2"><div class="card-header">Court {{ $match->court_no }} · Ready to call</div><div class="card-body text-center"><h5>{{ $match->team1Player1?->name ?? 'Open slot' }}@if($match->team1Player2) / {{ $match->team1Player2->name }}@endif</h5><div class="court-versus">versus</div><h5>{{ $match->team2Player1?->name ?? 'Open slot' }}@if($match->team2Player2) / {{ $match->team2Player2->name }}@endif</h5></div></div>
                        @empty <p class="text-muted mb-0">No matches are waiting to be sent to a court.</p> @endforelse
                    </div>
                </div>

                <div class="card dashboard-card shadow-sm">
                    <div class="card-header"><strong><i class="fas fa-clock-rotate-left mr-1"></i> Recent Matches</strong></div>
                    <div class="table-responsive">
                        <table class="table dashboard-table table-sm table-hover mb-0">
                            <thead><tr><th>Round</th><th>Court</th><th>Team 1</th><th>Team 2</th><th>Score</th><th>Result</th></tr></thead>
                            <tbody>
                                @forelse ($session->matches->where('status', 'completed')->take(20) as $match)
                                    <tr>
                                        <td>{{ $match->round_no }}</td><td>{{ $match->court_no }}</td>
                                        <td>{{ $match->team1Player1->name }}@if($match->team1Player2) / {{ $match->team1Player2->name }}@endif</td>
                                        <td>{{ $match->team2Player1->name }}@if($match->team2Player2) / {{ $match->team2Player2->name }}@endif</td>
                                        <td><span class="score-badge">{{ $match->team1_score }} - {{ $match->team2_score }}</span></td>
                                        <td><span class="result-badge">Team {{ $match->winner_team }} wins</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">No completed matches yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card standings-card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong><i class="fas fa-ranking-star mr-1"></i> Player Standings</strong>
                            <div class="standing-subtitle">Wins → win % → point differential → total match time</div>
                        </div>
                        <span class="badge badge-light">{{ $standings->count() }} Players</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table standings-table mb-0">
                            <thead><tr><th>#</th><th>Player</th><th>W-L</th><th>Win %</th><th>Diff.</th><th>Time</th></tr></thead>
                            <tbody>
                                @forelse ($standings as $standing)
                                    @php
                                        $rankClass = match ($standing->rank) {
                                            1 => 'gold',
                                            2 => 'silver',
                                            3 => 'bronze',
                                            default => '',
                                        };
                                    @endphp
                                    <tr class="{{ $standing->rank <= 3 ? 'podium-row' : '' }}">
                                        <td><span class="rank-badge {{ $rankClass }}">{{ $standing->rank }}</span></td>
                                        <td>
                                            <div class="player-cell">
                                                <span class="player-avatar">{{ strtoupper(substr($standing->player->name, 0, 1)) }}</span>
                                                <div>
                                                    <strong>{{ $standing->player->name }}</strong>
                                                    <small class="d-block text-muted">{{ $standing->games_played }} games</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="record-pill">{{ $standing->wins }}-{{ $standing->losses }}</span></td>
                                        <td><strong class="win-rate">{{ number_format($standing->win_percentage, 1) }}%</strong></td>
                                        <td>
                                            <strong class="{{ $standing->point_differential >= 0 ? 'diff-positive' : 'diff-negative' }}">
                                                {{ $standing->point_differential > 0 ? '+' : '' }}{{ $standing->point_differential }}
                                            </strong>
                                        </td>
                                        <td class="text-nowrap">{{ intdiv($standing->total_match_seconds, 60) }}m {{ $standing->total_match_seconds % 60 }}s</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">No players checked in.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-top px-3 py-2 small text-muted">Ranked by wins, win percentage, then point differential.</div>
                </div>

                <div class="card dashboard-card shadow-sm">
                    <div class="card-header"><strong><i class="fas fa-users mr-1"></i> Checked-in Queue</strong></div>
                    <ol class="list-group list-group-flush queue-list">
                        @forelse ($waitingPlayers as $player)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><span class="queue-number">{{ $loop->iteration }}</span><strong>{{ $player->name }}</strong></span>
                                <small class="text-muted">{{ $player->pivot->games_played }} games · waiting</small>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No players checked in.</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </div>
    </div>
    </main>
</body>
</html>
