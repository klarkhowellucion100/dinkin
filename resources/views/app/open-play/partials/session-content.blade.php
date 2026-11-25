@php
    $activeMatches = $session->matches->where('status', 'active')->sortBy('court_no');
    $upNextMatches = $session->matches->where('status', 'staged')->sortBy(fn ($match) => [$match->court_no, $match->round_no]);
    $checkedInPlayers = $session->players->filter(fn ($player) => $player->pivot->checked_in_at && ! $player->pivot->checked_out_at);
    $reservedPlayerIds = $session->matches->whereIn('status', ['active', 'staged'])->flatMap(fn ($match) => [$match->team1_player1_id, $match->team1_player2_id, $match->team2_player1_id, $match->team2_player2_id])->filter()->all();
    $availablePlayers = $checkedInPlayers->reject(fn ($player) => in_array($player->id, $reservedPlayerIds, true));
    $averageMinutes = 12;
@endphp

@foreach (['success' => 'success', 'error' => 'danger'] as $key => $type)
    @if (session($key))
        <div class="alert alert-{{ $type }}">{{ session($key) }}</div>
    @endif
@endforeach

<div class="row">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div><strong><i class="fas fa-table-tennis mr-1 text-primary"></i> Live Courts</strong><small class="text-muted ml-2">{{ $activeMatches->count() }} in play</small></div>
                <div>
                    <a href="{{ route('public.open-play.show', $session) }}" target="_blank" class="btn btn-outline-info btn-sm mr-1"><i class="fas fa-tv mr-1"></i> Live board</a>
                    @if ($session->status !== 'ended')
                        <form method="POST" action="{{ route('open-play.courts.add', $session) }}" class="d-inline" data-ajax-form>@csrf<button class="btn btn-outline-primary btn-sm mr-1"><i class="fas fa-plus mr-1"></i> Add court</button></form>
                    @endif
                    @if ($session->status === 'setup')
                        <form method="POST" action="{{ route('open-play.start', $session) }}" class="d-inline" data-ajax-form>@csrf<button class="btn btn-success btn-sm">Start session</button></form>
                    @elseif ($session->status === 'live')
                        <form method="POST" action="{{ route('open-play.end', $session) }}" class="d-inline" data-ajax-form>@csrf<button class="btn btn-danger btn-sm">End session</button></form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @forelse ($activeMatches as $match)
                    @php
                        $matchReady = $session->format === 'singles'
                            ? $match->team1Player1 && $match->team2Player1
                            : $match->team1Player1 && $match->team1Player2 && $match->team2Player1 && $match->team2Player2;
                    @endphp
                    <div class="border-left border-primary bg-light rounded p-3 mb-3" data-started-at="{{ $match->started_at?->timestamp }}">
                        <div class="d-flex justify-content-between"><strong>Court {{ $match->court_no }}</strong><small class="text-muted">Round {{ $match->round_no }} · <span data-match-timer>In progress</span></small></div>
                        <div class="row text-center align-items-center mt-3">
                            <div class="col-5"><strong>Team 1</strong><div class="mt-1">{{ $match->team1Player1?->name ?? 'Open slot' }} @if($match->team1Player1)<form method="POST" action="{{ route('open-play.matches.players.remove', [$match, $match->team1Player1]) }}" class="d-inline" data-ajax-form>@csrf<button class="btn btn-link btn-sm text-danger p-0" title="Return player to queue">Remove</button></form>@endif @if($match->team1Player2)<br>{{ $match->team1Player2->name }} <form method="POST" action="{{ route('open-play.matches.players.remove', [$match, $match->team1Player2]) }}" class="d-inline" data-ajax-form>@csrf<button class="btn btn-link btn-sm text-danger p-0" title="Return player to queue">Remove</button></form>@endif</div></div>
                            <div class="col-2 text-muted">vs</div>
                            <div class="col-5"><strong>Team 2</strong><div class="mt-1">{{ $match->team2Player1?->name ?? 'Open slot' }} @if($match->team2Player1)<form method="POST" action="{{ route('open-play.matches.players.remove', [$match, $match->team2Player1]) }}" class="d-inline" data-ajax-form>@csrf<button class="btn btn-link btn-sm text-danger p-0" title="Return player to queue">Remove</button></form>@endif @if($match->team2Player2)<br>{{ $match->team2Player2->name }} <form method="POST" action="{{ route('open-play.matches.players.remove', [$match, $match->team2Player2]) }}" class="d-inline" data-ajax-form>@csrf<button class="btn btn-link btn-sm text-danger p-0" title="Return player to queue">Remove</button></form>@endif</div></div>
                        </div>
                        @if ($matchReady)
                            <form method="POST" action="{{ route('open-play.matches.result', $match) }}" class="row mt-3" data-ajax-form>
                                @csrf
                                <div class="col-md-3"><input type="number" name="team1_score" min="0" class="form-control" placeholder="Team 1 score" required></div>
                                <div class="col-md-3"><input type="number" name="team2_score" min="0" class="form-control" placeholder="Team 2 score" required></div>
                                <div class="col-md-6 mt-2 mt-md-0"><button class="btn btn-primary btn-block">Save result and build Up Next</button></div>
                            </form>
                        @else
                            <div class="alert alert-warning mt-3 mb-0">This match has an open player slot. Add a player before recording the result.</div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-3">No courts are active. Send a staged match from Up Next when a court is ready.</div>
                @endforelse
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div><strong><i class="fas fa-forward mr-1 text-warning"></i> Up Next</strong><small class="text-muted ml-2">Review before sending players to court</small></div>
                @if ($session->status === 'live')
                    <div><button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#add-player-match-modal"><i class="fas fa-user-plus mr-1"></i> Add player</button><form method="POST" action="{{ route('open-play.matches.add', $session) }}" class="d-inline ml-1" data-ajax-form>@csrf<button class="btn btn-outline-secondary btn-sm"><i class="fas fa-plus mr-1"></i> Add match</button></form></div>
                @endif
            </div>
            <div class="card-body">
                @forelse ($upNextMatches as $match)
                    <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center">
                        <div><span class="badge badge-warning mr-2">Court {{ $match->court_no }}</span><strong>Team 1</strong> {{ $match->team1Player1?->name ?? 'Open slot' }} @if($match->team1Player1)<form method="POST" action="{{ route('open-play.matches.players.remove', [$match, $match->team1Player1]) }}" class="d-inline" data-ajax-form>@csrf<button class="btn btn-link btn-sm text-danger p-0">Remove</button></form>@endif @if($match->team1Player2), {{ $match->team1Player2->name }} <form method="POST" action="{{ route('open-play.matches.players.remove', [$match, $match->team1Player2]) }}" class="d-inline" data-ajax-form>@csrf<button class="btn btn-link btn-sm text-danger p-0">Remove</button></form>@endif <span class="text-muted mx-2">vs</span><strong>Team 2</strong> {{ $match->team2Player1?->name ?? 'Open slot' }} @if($match->team2Player1)<form method="POST" action="{{ route('open-play.matches.players.remove', [$match, $match->team2Player1]) }}" class="d-inline" data-ajax-form>@csrf<button class="btn btn-link btn-sm text-danger p-0">Remove</button></form>@endif @if($match->team2Player2), {{ $match->team2Player2->name }} <form method="POST" action="{{ route('open-play.matches.players.remove', [$match, $match->team2Player2]) }}" class="d-inline" data-ajax-form>@csrf<button class="btn btn-link btn-sm text-danger p-0">Remove</button></form>@endif<small class="d-block text-muted mt-1">Round {{ $match->round_no }} · Ready to call</small></div>
                        <form method="POST" action="{{ route('open-play.matches.dispatch', $match) }}" data-ajax-form class="ml-2">@csrf<button class="btn btn-success btn-sm">Send to court</button></form>
                    </div>
                @empty
                    <div class="text-muted">No matches staged. Use Auto-pick when enough checked-in players are waiting.</div>
                @endforelse
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header"><strong><i class="fas fa-ranking-star mr-1 text-warning"></i> Session standings</strong><small class="text-muted ml-2">Wins, win rate, point differential</small></div>
            <div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Rank</th><th>Player</th><th>Games</th><th>W-L</th><th>Win %</th><th>Diff.</th></tr></thead><tbody>
                @forelse ($standings as $standing)
                    <tr><td><strong>{{ $standing->rank }}</strong></td><td>{{ $standing->player->name }}</td><td>{{ $standing->games_played }}</td><td>{{ $standing->wins }}-{{ $standing->losses }}</td><td>{{ number_format($standing->win_percentage, 1) }}%</td><td>{{ $standing->point_differential > 0 ? '+' : '' }}{{ $standing->point_differential }}</td></tr>
                @empty <tr><td colspan="6" class="text-center text-muted">No players yet.</td></tr> @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header"><strong><i class="fas fa-user-plus mr-1 text-primary"></i> Add player</strong></div>
            <form method="POST" action="{{ route('open-play.players.add', $session) }}" data-ajax-form><div class="card-body">
                @csrf
                <input name="name" class="form-control mb-2" placeholder="Search saved player or add walk-in" list="saved-players" required>
                <datalist id="saved-players">@foreach ($savedPlayers as $savedPlayer)<option value="{{ $savedPlayer->name }}">@endforeach</datalist>
                <div class="row"><div class="col-6"><select name="skill_level" class="form-control">@for ($level = 1; $level <= 6; $level++)<option value="{{ $level }}" @selected($level === 3)>{{ str_repeat('★', $level) }} Level {{ $level }}</option>@endfor</select></div><div class="col-6"><input name="gender" class="form-control" placeholder="Gender"></div></div>
            </div><div class="card-footer"><button class="btn btn-primary btn-block">Add to queue</button></div></form>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between"><div><strong><i class="fas fa-users mr-1 text-info"></i> Queue</strong><small class="text-muted ml-2">Adding a player checks them in</small></div><span class="badge badge-info">{{ $checkedInPlayers->count() }} checked in</span></div>
            <ol class="list-group list-group-flush">
                @forelse ($session->players as $player)
                    @php $waitMinutes = max(0, intdiv($loop->index, max(1, (int) $session->court_count)) * $averageMinutes); @endphp
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div><span class="badge badge-light mr-1">{{ $loop->iteration }}</span><strong>{{ $player->name }}</strong><span class="badge badge-{{ $player->pivot->checked_in_at && ! $player->pivot->checked_out_at ? 'success' : 'secondary' }} ml-1">{{ $player->pivot->checked_in_at && ! $player->pivot->checked_out_at ? 'Checked in' : 'Not checked in' }}</span><small class="d-block text-muted ml-4">{{ $player->pivot->games_played }} games · {{ $player->pivot->wins }}W / {{ $player->pivot->losses }}L · Level {{ $player->skill_level }}</small></div>
                            <div class="text-right">
                                @if ($player->pivot->checked_in_at && ! $player->pivot->checked_out_at)
                                    <small class="text-muted">~{{ $waitMinutes }} min</small><form method="POST" action="{{ route('open-play.players.check-out', [$session, $player]) }}" data-ajax-form><button class="btn btn-link btn-sm text-danger p-0">Cancel / check out</button></form>
                                @else
                                    <form method="POST" action="{{ route('open-play.players.check-in', [$session, $player]) }}" data-ajax-form><button class="btn btn-outline-success btn-sm">Check in</button></form>
                                @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('open-play.fixed-partner', $session) }}" class="mt-2" data-ajax-form>
                            @csrf
                            <input type="hidden" name="player_id" value="{{ $player->id }}">
                            <div class="input-group input-group-sm"><select name="partner_id" class="form-control"><option value="">No fixed partner</option>@foreach ($session->players as $partner) @if ($partner->id !== $player->id)<option value="{{ $partner->id }}" @selected((int) $player->pivot->fixed_partner_id === $partner->id)>{{ $partner->name }}</option>@endif @endforeach</select><div class="input-group-append"><button class="btn btn-outline-info">Pair</button></div></div>
                        </form>
                    </li>
                @empty <li class="list-group-item text-muted">No players waiting.</li> @endforelse
            </ol>
        </div>

        <div class="card border-0 shadow-sm mb-3"><div class="card-header"><strong>Session controls</strong></div><div class="card-body"><p class="small text-muted mb-2">Players are never automatically sent to a court. Auto-pick only stages fair matches; staff controls the dispatch.</p><form method="POST" action="{{ route('open-play.undo', $session) }}" data-ajax-form>@csrf<button class="btn btn-outline-secondary btn-block">Undo latest result</button></form></div></div>
    </div>
</div>

<div class="modal fade" id="add-player-match-modal" tabindex="-1" role="dialog" aria-labelledby="add-player-match-title" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="add-player-match-title">Add player to Up Next</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
        <div class="modal-body">
            <p class="text-muted small">Choose a checked-in player who is not currently playing or reserved in Up Next. The other spots will be filled fairly.</p>
            @if ($availablePlayers->isNotEmpty())
                <form method="POST" action="{{ route('open-play.matches.add-player', $session) }}" data-ajax-form>
                    @csrf
                    <label for="replacement-player">Player</label>
                    <select id="replacement-player" name="player_id" class="form-control mb-3" required>
                        @foreach ($availablePlayers as $player)<option value="{{ $player->id }}">{{ $player->name }} · {{ $player->pivot->games_played }} games · Level {{ $player->skill_level }}</option>@endforeach
                    </select>
                    <button class="btn btn-primary btn-block">Add selected player</button>
                </form>
            @else
                <div class="alert alert-warning">No checked-in players are currently available to add.</div>
            @endif
            <form method="POST" action="{{ route('open-play.auto-pick', $session) }}" class="mt-2" data-ajax-form>@csrf<button class="btn btn-outline-warning btn-block"><i class="fas fa-wand-magic-sparkles mr-1"></i> Auto-pick a fair match</button></form>
        </div>
    </div></div>
</div>

<script>
    (() => {
        const updateTimers = () => document.querySelectorAll('[data-started-at]').forEach((match) => {
            const seconds = Math.max(0, Math.floor(Date.now() / 1000) - Number(match.dataset.startedAt));
            const minutes = Math.floor(seconds / 60);
            const target = match.querySelector('[data-match-timer]');
            if (target) target.textContent = `${minutes}m ${String(seconds % 60).padStart(2, '0')}s`;
        });
        updateTimers();
        window.setInterval(updateTimers, 1000);
    })();
</script>
