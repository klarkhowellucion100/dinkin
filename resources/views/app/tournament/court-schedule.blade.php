<x-main-layout>
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

                    @if ($courtSchedule->isNotEmpty())
                        <div class="table-responsive mt-4">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Schedule No.</th>
                                        <th>Court</th>
                                        <th>Category</th>
                                        <th>Bracket</th>
                                        <th>Match</th>
                                        <th>Teams</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($courtSchedule as $scheduledMatch)
                                        <tr>
                                            <td>{{ $scheduledMatch->schedule_no }}</td>
                                            <td><strong>Court {{ $scheduledMatch->court_no }}</strong></td>
                                            <td>{{ $scheduledMatch->team_category }}</td>
                                            <td>{{ $scheduledMatch->team_bracket }}</td>
                                            <td>Round {{ $scheduledMatch->round_no }} / Match {{ $scheduledMatch->match_no }}</td>
                                            <td>{{ $scheduledMatch->team1_name }} vs {{ $scheduledMatch->team2_name }}</td>
                                            <td>
                                                @if ($scheduledMatch->is_finalized)
                                                    <span class="badge bg-success">Done</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @endif
                                            </td>
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
</x-main-layout>
