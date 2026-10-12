<x-main-layout>
    <style>
        .court-schedule-table {
            min-width: 720px;
        }

        .court-schedule-table th {
            background: #111827;
            color: #fff;
            text-align: center;
            white-space: nowrap;
        }

        .court-schedule-table td {
            min-width: 180px;
            padding: 12px;
            vertical-align: top;
        }

        .court-schedule-number {
            width: 90px;
            min-width: 90px !important;
            background: #f8fafc;
            color: #6b7280;
            font-weight: 800;
            text-align: center;
        }

        .court-match-team {
            font-weight: 800;
        }

        .court-match-meta {
            color: #6b7280;
            font-size: 11px;
            line-height: 1.5;
            margin-top: 6px;
        }

        .court-empty {
            color: #cbd5e1;
            text-align: center;
        }

        .court-score {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: .08em;
        }
    </style>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="mb-1">Court Schedule</h3>
                    <p class="text-muted mb-0">{{ $tournamentView->name }}</p>
                </div>
                <a href="{{ route('tournament.view', Crypt::encryptString($tournamentView->id)) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Tournament
                </a>
            </div>

            <div class="card border-primary">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-stopwatch"></i> Scheduled Matches</strong>
                    <span class="badge bg-primary">{{ $courtSchedule->count() }} scheduled matches</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('tournament.courtSchedule.update', Crypt::encryptString($tournamentView->id)) }}"
                        class="row g-2 align-items-end" id="courtScheduleForm">
                        @csrf
                        @method('PUT')
                        <div class="col-sm-4 col-md-3">
                            <label for="court_count" class="form-label">Number of courts</label>
                            <input type="number" min="1" max="100" required class="form-control" id="court_count"
                                name="court_count" value="{{ $tournamentView->court_count ?? '' }}" placeholder="e.g. 3">
                        </div>
                        <div class="col-sm-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-magic"></i> Generate Court Schedule
                            </button>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Matches are assigned from higher categories to lower categories, then distributed across the courts.</small>
                        </div>
                    </form>

                    @if ($tournamentView->court_count)
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                            <strong class="mr-2">Print score sheets:</strong>
                            @for ($court = 1; $court <= (int) $tournamentView->court_count; $court++)
                                <a href="{{ route('tournament.courtSchedule.scoreSheets', [
                                    'tournament_id' => Crypt::encryptString($tournamentView->id),
                                    'court_no' => $court,
                                ]) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-print"></i> Court {{ $court }}
                                </a>
                            @endfor
                        </div>
                    @endif

                    @if ($courtSchedule->isNotEmpty() && $tournamentView->court_count)
                        @php
                            $scheduleNumbers = $courtSchedule->pluck('schedule_no')->unique()->sort()->values();
                            $courts = (int) $tournamentView->court_count;
                        @endphp

                        <div class="table-responsive mt-4">
                            <table class="table table-bordered court-schedule-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="court-schedule-number">No.</th>
                                        @for ($court = 1; $court <= $courts; $court++)
                                            <th>Court {{ $court }}</th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($scheduleNumbers as $scheduleNumber)
                                        <tr>
                                            <td class="court-schedule-number">{{ $scheduleNumber }}</td>
                                            @for ($court = 1; $court <= $courts; $court++)
                                                @php
                                                    $scheduledMatch = $courtSchedule->first(fn ($match) => (int) $match->schedule_no === (int) $scheduleNumber && (int) $match->court_no === $court);
                                                @endphp
                                                @if ($scheduledMatch)
                                                    <td>
                                                        <div class="court-match-team">{{ $scheduledMatch->team1_no }} {{ $scheduledMatch->team1_name }}</div>
                                                        <div class="text-muted text-center">vs</div>
                                                        <div class="court-match-team">{{ $scheduledMatch->team2_no }} {{ $scheduledMatch->team2_name }}</div>
                                                        <div class="court-score text-center mt-2" id="court-score-{{ $scheduledMatch->id }}">
                                                            {{ $scheduledMatch->team1_score ?? '-' }} - {{ $scheduledMatch->team2_score ?? '-' }}
                                                        </div>
                                                        <div class="court-match-meta">
                                                            {{ $scheduledMatch->team_category }} · Bracket {{ $scheduledMatch->team_bracket }}<br>
                                                            Round {{ $scheduledMatch->round_no }} · Match {{ $scheduledMatch->match_no }}<br>
                                                            <span class="badge {{ $scheduledMatch->is_finalized ? 'bg-success' : 'bg-warning text-dark' }}" id="court-status-{{ $scheduledMatch->id }}">
                                                                {{ $scheduledMatch->is_finalized ? 'Done' : 'Pending' }}
                                                            </span>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-primary mt-2" data-toggle="modal" data-target="#courtScoreModal{{ $scheduledMatch->id }}">
                                                            <i class="fas fa-edit"></i> Score
                                                        </button>
                                                    </td>
                                                @else
                                                    <td class="court-empty">—</td>
                                                @endif
                                            @endfor
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif ($tournamentView->court_count)
                        <p class="text-muted mt-3 mb-0">Generate matches first, then refresh the court schedule.</p>
                    @else
                        <p class="text-muted mt-3 mb-0">Set the number of courts to generate a schedule.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($standingsByCategory->isNotEmpty())
        <div class="container-fluid mb-4">
            <div class="card border-success">
                <div class="card-header">
                    <strong><i class="fas fa-ranking-star"></i> Standings Summary</strong>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" role="tablist">
                        @foreach ($standingsByCategory as $category => $brackets)
                            <li class="nav-item">
                                <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="tab" href="#standings-category-{{ $loop->index }}" role="tab">
                                    {{ $category }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content pt-3">
                        @foreach ($standingsByCategory as $category => $brackets)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="standings-category-{{ $loop->index }}" role="tabpanel">
                                <ul class="nav nav-pills mb-3" role="tablist">
                                    @foreach ($brackets as $bracket => $standings)
                                        <li class="nav-item">
                                            <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="pill" href="#standings-{{ $loop->parent->index }}-{{ $loop->index }}" role="tab">
                                                Bracket {{ $bracket }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content">
                                    @foreach ($brackets as $bracket => $standings)
                                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="standings-{{ $loop->parent->index }}-{{ $loop->index }}" role="tabpanel">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered table-hover mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th class="text-center">Rank</th>
                                                            <th>Team</th>
                                                            <th class="text-center">Played</th>
                                                            <th class="text-center">Wins</th>
                                                            <th class="text-center">Losses</th>
                                                            <th class="text-center">Points For</th>
                                                            <th class="text-center">Points Against</th>
                                                            <th class="text-center">Diff</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($standings as $standing)
                                                            <tr>
                                                                <td class="text-center"><strong>{{ $standing->rank }}</strong></td>
                                                                <td><strong>{{ $standing->team_no }}</strong> {{ $standing->team_name }}</td>
                                                                <td class="text-center">{{ $standing->games_played }}/{{ $standing->games_to_be_played }}</td>
                                                                <td class="text-center text-success font-weight-bold">{{ $standing->wins }}</td>
                                                                <td class="text-center text-danger">{{ $standing->losses }}</td>
                                                                <td class="text-center">{{ $standing->points_for }}</td>
                                                                <td class="text-center">{{ $standing->points_against }}</td>
                                                                <td class="text-center font-weight-bold">{{ $standing->point_differential > 0 ? '+' : '' }}{{ $standing->point_differential }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="8" class="text-center text-muted">No teams in this bracket.</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    @endif

    @foreach ($courtSchedule as $scheduledMatch)
        <div class="modal fade" id="courtScoreModal{{ $scheduledMatch->id }}" tabindex="-1" aria-labelledby="courtScoreModalLabel{{ $scheduledMatch->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title" id="courtScoreModalLabel{{ $scheduledMatch->id }}">
                            <i class="fas fa-edit"></i> Score Match {{ $scheduledMatch->match_no }}
                        </h5>
                        <button type="button" data-dismiss="modal" class="btn btn-danger" aria-label="Close"><i class="fa fa-times"></i></button>
                    </div>
                    <form class="court-score-form" action="{{ route('tournament.matches.score', Crypt::encryptString($scheduledMatch->id)) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="finalize_score" value="0">
                        <div class="modal-body">
                            <div class="alert alert-light text-center">
                                <strong>{{ $scheduledMatch->team1_no }} {{ $scheduledMatch->team1_name }}</strong>
                                <span class="mx-2">vs</span>
                                <strong>{{ $scheduledMatch->team2_no }} {{ $scheduledMatch->team2_name }}</strong>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-6">
                                    <label>{{ $scheduledMatch->team1_no }} Score</label>
                                    <input type="number" name="team1_score" class="form-control form-control-lg text-center" min="0" value="{{ $scheduledMatch->team1_score }}" required>
                                </div>
                                <div class="form-group col-6">
                                    <label>{{ $scheduledMatch->team2_no }} Score</label>
                                    <input type="number" name="team2_score" class="form-control form-control-lg text-center" min="0" value="{{ $scheduledMatch->team2_score }}" required>
                                </div>
                            </div>
                            <div class="court-score-feedback" role="status" aria-live="polite"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Finalize &amp; Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.court-score-form').forEach(function (form) {
                form.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    const button = form.querySelector('[type="submit"]');
                    const feedback = form.querySelector('.court-score-feedback');
                    button.disabled = true;
                    feedback.className = 'alert alert-light court-score-feedback';
                    feedback.textContent = 'Saving final result…';
                    form.elements.finalize_score.value = '1';

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const result = await response.json();

                        if (!response.ok) {
                            throw new Error(result.message || 'Unable to save this score.');
                        }

                        document.getElementById('court-score-' + result.match_id).textContent = result.team1_score + ' - ' + result.team2_score;
                        const status = document.getElementById('court-status-' + result.match_id);
                        status.className = 'badge ' + (result.is_final ? 'bg-success' : 'bg-warning text-dark');
                        status.textContent = result.is_final ? 'Done' : 'Pending';
                        feedback.className = 'alert alert-success court-score-feedback';
                        feedback.textContent = result.message;
                        window.jQuery(form.closest('.modal')).modal('hide');
                    } catch (error) {
                        feedback.className = 'alert alert-danger court-score-feedback';
                        feedback.textContent = error.message;
                    } finally {
                        button.disabled = false;
                    }
                });
            });
        });
    </script>
</x-main-layout>
