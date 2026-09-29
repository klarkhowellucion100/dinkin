<x-main-layout>

    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 font-weight-bold">
                    Tournament Dashboard
                </h1>
                <p class="text-muted mb-0">
                    Pickleball Tournament Management System
                </p>
            </div>

            <div>
                <a href="{{ route('tournament.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    New Tournament
                </a>
            </div>
        </div>


        {{-- Statistics --}}
        <div class="row">

            <div class="col-lg-3 col-md-6">
                <div class="card card-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted text-uppercase small">
                                    Tournaments
                                </div>

                                <h2 class="font-weight-bold mb-0">
                                    {{ $totalTournaments }}
                                </h2>
                            </div>

                            <div class="text-primary">
                                <i class="fas fa-trophy fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-lg-3 col-md-6">
                <div class="card card-success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted text-uppercase small">
                                    Teams Registered
                                </div>

                                <h2 class="font-weight-bold mb-0">
                                    {{ $totalTeams }}
                                </h2>
                            </div>

                            <div class="text-success">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-lg-3 col-md-6">
                <div class="card card-info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted text-uppercase small">
                                    Matches Completed
                                </div>

                                <h2 class="font-weight-bold mb-0">
                                    {{ $completedMatches }}
                                </h2>
                            </div>

                            <div class="text-info">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-lg-3 col-md-6">
                <div class="card card-warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted text-uppercase small">
                                    Matches Pending
                                </div>

                                <h2 class="font-weight-bold mb-0">
                                    {{ $pendingMatches }}
                                </h2>
                            </div>

                            <div class="text-warning">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>


        {{-- Upcoming Tournament + Match Progress --}}
        <div class="row">

            {{-- Upcoming Tournament --}}
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Upcoming Tournament
                        </h3>
                    </div>

                    <div class="card-body">

                        @if ($upcomingTournament)
                            <h4 class="font-weight-bold">
                                {{ $upcomingTournament->name }}
                            </h4>

                            <p class="text-muted mb-2">
                                <i class="fas fa-calendar mr-1"></i>
                                {{ \Carbon\Carbon::parse($upcomingTournament->date)->format('F d, Y') }}
                            </p>

                            <p class="text-muted mb-3">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                {{ $upcomingTournament->venue }}
                            </p>

                            <a href="{{ route('tournament.view', Crypt::encryptString($upcomingTournament->id)) }}"
                                class="btn btn-primary btn-sm">
                                Manage Tournament
                            </a>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>

                                <p class="text-muted mb-3">
                                    No upcoming tournaments.
                                </p>

                                <a href="{{ route('tournament.create') }}" class="btn btn-primary btn-sm">
                                    Create Tournament
                                </a>
                            </div>
                        @endif

                    </div>
                </div>
            </div>


            {{-- Match Progress --}}
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line mr-2"></i>
                            Match Progress
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="d-flex justify-content-between mb-2">
                            <span>
                                Overall Completion
                            </span>

                            <strong>
                                {{ $matchProgress }}%
                            </strong>
                        </div>

                        <div class="progress mb-4" style="height: 12px;">
                            <div class="progress-bar bg-success" role="progressbar"
                                style="width: {{ $matchProgress }}%;"></div>
                        </div>

                        <div class="row text-center">

                            <div class="col-4">
                                <h4 class="font-weight-bold text-primary">
                                    {{ $totalMatches }}
                                </h4>
                                <small class="text-muted">
                                    Round Robin
                                </small>
                            </div>

                            <div class="col-4">
                                <h4 class="font-weight-bold text-success">
                                    {{ $completedMatches }}
                                </h4>
                                <small class="text-muted">
                                    Completed
                                </small>
                            </div>

                            <div class="col-4">
                                <h4 class="font-weight-bold text-warning">
                                    {{ $pendingMatches }}
                                </h4>
                                <small class="text-muted">
                                    Pending
                                </small>
                            </div>

                        </div>

                    </div>
                </div>
            </div>

        </div>


        {{-- Recent Matches + Categories --}}
        <div class="row">

            {{-- Recent Matches --}}
            <div class="col-lg-8">

                <div class="card shadow-sm">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-table-tennis-paddle-ball mr-2"></i>
                            Recent Match Results
                        </h3>

                        <div class="card-tools">
                            <a href="{{ route('public.tournaments') }}" class="btn btn-sm btn-outline-primary"
                                target="_blank">
                                <i class="fas fa-external-link-alt mr-1"></i>
                                Public Results
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">

                        <div class="table-responsive">

                            <table class="table table-hover mb-0">

                                <thead class="thead-light">
                                    <tr>
                                        <th>Match</th>
                                        <th>Teams</th>
                                        <th>Score</th>
                                        <th>Winner</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @forelse ($recentMatches as $match)
                                        <tr>

                                            <td>
                                                <span class="badge badge-secondary">
                                                    R{{ $match->round_no }}
                                                    -
                                                    M{{ $match->match_no }}
                                                </span>
                                            </td>

                                            <td>
                                                <strong>
                                                    {{ $match->team1_no }}
                                                </strong>
                                                {{ $match->team1_name }}

                                                <br>

                                                <strong>
                                                    {{ $match->team2_no }}
                                                </strong>
                                                {{ $match->team2_name }}
                                            </td>

                                            <td>
                                                <strong>
                                                    {{ $match->team1_score }}
                                                    -
                                                    {{ $match->team2_score }}
                                                </strong>
                                            </td>

                                            <td>
                                                @if ($match->winner_name)
                                                    <span class="badge badge-success">
                                                        {{ $match->winner_no }}
                                                    </span>

                                                    {{ $match->winner_name }}
                                                @else
                                                    <span class="text-muted">
                                                        Pending
                                                    </span>
                                                @endif
                                            </td>

                                        </tr>

                                    @empty

                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                No match results available.
                                            </td>
                                        </tr>
                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>
                </div>

            </div>


            {{-- Categories --}}
            <div class="col-lg-4">

                <div class="card shadow-sm">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-layer-group mr-2"></i>
                            Team Categories
                        </h3>
                    </div>

                    <div class="card-body">

                        @forelse ($categorySummary as $category)
                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <div>
                                    <strong>
                                        {{ $category->team_category }}
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        {{ $category->team_count }} teams
                                    </small>
                                </div>

                                <span class="badge badge-primary">
                                    {{ $category->team_count }}
                                </span>

                            </div>

                        @empty

                            <p class="text-muted text-center mb-0">
                                No team categories available.
                            </p>
                        @endforelse

                    </div>

                </div>


                {{-- Quick Actions --}}
                <div class="card shadow-sm">

                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bolt mr-2"></i>
                            Quick Actions
                        </h3>
                    </div>

                    <div class="card-body">

                        <a href="{{ route('tournament.create') }}" class="btn btn-primary btn-block">
                            <i class="fas fa-plus mr-1"></i>
                            Create Tournament
                        </a>

                        <a href="{{ route('tournament.index') }}" class="btn btn-outline-secondary btn-block">
                            <i class="fas fa-list mr-1"></i>
                            Manage Tournaments
                        </a>

                        <a href="{{ route('public.tournaments') }}" class="btn btn-outline-success btn-block"
                            target="_blank">
                            <i class="fas fa-tv mr-1"></i>
                            Open Live Results
                        </a>

                    </div>

                </div>

            </div>

        </div>


        {{-- Recent Tournaments --}}
        <div class="card shadow-sm">

            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-2"></i>
                    Recent Tournaments
                </h3>
            </div>

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead class="thead-light">
                            <tr>
                                <th>Tournament</th>
                                <th>Date</th>
                                <th>Venue</th>
                                <th>Teams</th>
                                <th>Progress</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($recentTournaments as $tournament)
                                <tr>

                                    <td>
                                        <strong>
                                            {{ $tournament->name }}
                                        </strong>
                                    </td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($tournament->date)->format('M d, Y') }}
                                    </td>

                                    <td>
                                        {{ $tournament->venue }}
                                    </td>

                                    <td>
                                        <span class="badge badge-primary">
                                            {{ $tournament->team_count }}
                                        </span>
                                    </td>

                                    <td style="min-width: 150px;">

                                        <div class="d-flex justify-content-between">
                                            <small>
                                                {{ $tournament->completed_matches }}
                                                /
                                                {{ $tournament->total_matches }}
                                            </small>

                                            <small>
                                                {{ $tournament->progress }}%
                                            </small>
                                        </div>

                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-success"
                                                style="width: {{ $tournament->progress }}%;"></div>
                                        </div>

                                    </td>

                                    <td class="text-right">

                                        <a href="{{ route('tournament.view', Crypt::encryptString($tournament->id)) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No tournaments available.
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</x-main-layout>
