<x-main-layout>
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-3">{{ ucfirst(str_replace('_', ' ', $matches->first()->format)) }} · {{ $team_category }} · Bracket {{ $team_bracket }}</h3>
            <a href="{{ route('tournament.view', Crypt::encryptString($matches->first()->tournament_id)) }}" class="btn btn-secondary mb-3">Back to Tournament</a>
            <a href="{{ route('public.tournament.elimination', [$matches->first()->tournament_id, $team_category, $team_bracket]) }}" class="btn btn-outline-primary mb-3" target="_blank" rel="noopener">Open Public View</a>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            @foreach ($matches->groupBy('bracket_side') as $side => $sideMatches)
                <div class="card mb-4">
                    <div class="card-header"><h4 class="mb-0">{{ ucwords(str_replace('_', ' ', $side)) }}</h4></div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($sideMatches->groupBy('round_number') as $round => $roundMatches)
                                <div class="col-md-{{ max(3, (int) (12 / max(1, $sideMatches->max('round_number')))) }} mb-3">
                                    <h5>Round {{ $round }}</h5>
                                    @foreach ($roundMatches as $match)
                                        <div class="border rounded p-2 mb-2">
                                            <div class="small text-muted mb-2">{{ $match->match_label }}</div>
                                            <div>{{ $match->team1?->team_name ?? 'TBD' }} <span class="float-right">{{ $match->team1_score ?? '-' }}</span></div>
                                            <div>{{ $match->team2?->team_name ?? 'TBD' }} <span class="float-right">{{ $match->team2_score ?? '-' }}</span></div>
                                            @if ($match->team1_id && $match->team2_id)
                                                <form action="{{ route('tournament.elimination.score', Crypt::encryptString($match->id)) }}" method="POST" class="mt-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" min="0" name="team1_score" class="form-control" placeholder="Score 1" value="{{ $match->team1_score }}" required>
                                                        <input type="number" min="0" name="team2_score" class="form-control" placeholder="Score 2" value="{{ $match->team2_score }}" required>
                                                        <button class="btn btn-primary" type="submit">{{ $match->is_finalized ? 'Update Score' : 'Save Score' }}</button>
                                                    </div>
                                                    @if ($match->is_finalized)
                                                        <small class="text-muted d-block mt-1">Changing this result clears scores in dependent matches so they can be entered again.</small>
                                                    @endif
                                                </form>
                                            @else
                                                <span class="badge badge-secondary mt-2">Waiting for previous match</span>
                                            @endif
                                            @if ($match->is_finalized)
                                                <span class="badge badge-success mt-2">Finalized</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-main-layout>
