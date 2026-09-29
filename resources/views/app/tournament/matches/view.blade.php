<x-main-layout>

    <style>
        .scoreboard-modal .modal-content {
            overflow: hidden;
            border: 0;
            border-radius: 18px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
        }

        .scoreboard-modal .modal-header {
            padding: 1.2rem 1.5rem;
            color: #fff;
            background: linear-gradient(115deg, #111827, #1e3a5f);
            border-bottom: 0;
        }

        .scoreboard-modal .modal-header .close {
            color: #fff;
            text-shadow: none;
            opacity: .85;
        }

        .scoreboard-matchup {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 12px;
            align-items: center;
            padding: 16px;
            margin-bottom: 20px;
            border-radius: 14px;
            color: #fff;
            background: #172235;
        }

        .scoreboard-matchup-team {
            font-weight: 700;
            text-align: center;
        }

        .scoreboard-matchup-vs {
            color: #9ca3af;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .12em;
        }

        .scoreboard-team {
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #f8fafc;
        }

        .scoreboard-team-label {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .scoreboard-score-input {
            height: 58px;
            font-size: 28px;
            font-weight: 800;
        }

        .scoreboard-adjust {
            min-width: 48px;
            font-size: 22px;
            font-weight: 700;
        }

        .scoreboard-modal .modal-footer {
            justify-content: space-between;
            padding: 1rem 1.5rem;
            background: #f8fafc;
        }

        @media (max-width: 575px) {
            .scoreboard-modal .modal-dialog {
                margin: .5rem;
            }

            .scoreboard-team {
                padding: 12px;
            }
        }
    </style>

    <!--begin::App Content Header-->
    <div class="app-content-header">

        <!--begin::Container-->
        <div class="container-fluid">

            <!--begin::Row-->
            <div class="row">

                <div class="col-sm-6">

                    <h3 class="mb-3">
                        Matches
                    </h3>

                </div>

            </div>
            <!--end::Row-->

        </div>
        <!--end::Container-->

    </div>
    <!--end::App Content Header-->


    <!--begin::App Content-->
    <div class="app-content">

        <!--begin::Container-->
        <div class="container-fluid">

            {{-- Page Header --}}
            <div class="card mb-4">

                <div class="card-header">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h5 class="mb-0">

                                <i class="fas fa-trophy"></i>

                                {{ $tournament->name }}

                            </h5>

                            <small class="text-muted">

                                Bracket {{ $team_bracket }}

                                -

                                {{ $team_category }}

                            </small>

                        </div>


                        <div>

                            <a href="{{ route('tournament.playoffs.index', [
                                'tournament_id' => Crypt::encryptString($tournament->id),
                                'team_category' => $team_category,
                            ]) }}"
                                class="btn btn-sm btn-warning">
                                <i class="fas fa-trophy"></i>
                                Playoffs
                            </a>

                            <a href="{{ route('tournament.view', Crypt::encryptString($tournament->id)) }}"
                                class="btn btn-sm btn-secondary">

                                <i class="fas fa-arrow-left"></i>

                                Back

                            </a>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Matches Card --}}
            <div class="card">

                <div class="card-header">

                    <strong>

                        <i class="fas fa-list"></i>

                        Round Robin Matches

                    </strong>

                    <span class="badge bg-primary ms-2">

                        {{ $matches->count() }}

                        Matches

                    </span>

                </div>


                <div class="card-body">

                    @if ($matches->count() > 0)

                        {{-- Bulk Delete Form --}}
                        <form id="bulkDeleteForm" action="{{ route('tournament.matches.bulkDestroy') }}" method="POST">

                            @csrf

                            @method('DELETE')


                            {{-- Hidden Values --}}
                            <input type="hidden" name="tournament_id"
                                value="{{ Crypt::encryptString($tournament->id) }}">

                            <input type="hidden" name="team_bracket" value="{{ $team_bracket }}">

                            <input type="hidden" name="team_category" value="{{ $team_category }}">


                            {{-- Bulk Action Bar --}}
                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <div>

                                    <span class="text-muted">

                                        Select matches to perform bulk actions.

                                    </span>

                                </div>


                                <button type="submit" id="deleteSelectedBtn" class="btn btn-sm btn-danger" disabled>

                                    <i class="fas fa-trash"></i>

                                    Delete Selected

                                    <span id="selectedCount"></span>

                                </button>

                            </div>


                            {{-- Table --}}
                            <div class="table-responsive">

                                <table class="table table-bordered table-hover">

                                    <thead>

                                        <tr>

                                            {{-- Select All --}}
                                            <th width="50" class="text-center">

                                                <input type="checkbox" id="selectAllMatches" class="form-check-input">

                                            </th>


                                            {{-- Round --}}
                                            <th width="80">
                                                Round
                                            </th>


                                            {{-- Match --}}
                                            <th width="80">
                                                Match
                                            </th>


                                            {{-- Team 1 --}}
                                            <th>
                                                Team 1
                                            </th>


                                            {{-- Team 1 Score --}}
                                            <th width="100">
                                                Score
                                            </th>


                                            {{-- Team 2 --}}
                                            <th>
                                                Team 2
                                            </th>


                                            {{-- Team 2 Score --}}
                                            <th width="100">
                                                Score
                                            </th>


                                            {{-- Winner --}}
                                            <th width="180">
                                                Winner
                                            </th>


                                            {{-- Action --}}
                                            <th width="100">
                                                Action
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        @foreach ($matches as $match)
                                            <tr id="match-row-{{ $match->id }}">

                                                {{-- Checkbox --}}
                                                <td class="text-center">

                                                    <input type="checkbox" name="match_ids[]"
                                                        value="{{ Crypt::encryptString($match->id) }}"
                                                        class="form-check-input match-checkbox">

                                                </td>


                                                {{-- Round --}}
                                                <td>

                                                    <strong>

                                                        Round
                                                        {{ $match->round_no }}

                                                    </strong>

                                                </td>


                                                {{-- Match --}}
                                                <td>

                                                    {{ $match->match_no }}

                                                </td>


                                                {{-- Team 1 --}}
                                                <td>

                                                    <strong>

                                                        {{ $match->team1_no }}

                                                    </strong>

                                                    <br>

                                                    <small class="text-muted">

                                                        {{ $match->team1_name }}

                                                    </small>

                                                </td>


                                                {{-- Team 1 Score --}}
                                                <td class="text-center team1-score-display">
                                                    <strong class="score-value {{ $match->team1_score === null ? 'text-muted' : '' }}">{{ $match->team1_score ?? '-' }}</strong>
                                                </td>


                                                {{-- Team 2 --}}
                                                <td>

                                                    <strong>

                                                        {{ $match->team2_no }}

                                                    </strong>

                                                    <br>

                                                    <small class="text-muted">

                                                        {{ $match->team2_name }}

                                                    </small>

                                                </td>


                                                {{-- Team 2 Score --}}
                                                <td class="text-center team2-score-display">
                                                    <strong class="score-value {{ $match->team2_score === null ? 'text-muted' : '' }}">{{ $match->team2_score ?? '-' }}</strong>
                                                </td>


                                                {{-- Winner --}}
                                                <td class="text-center">

                                                    <strong class="text-success match-winner-display">
                                                        <i class="fas fa-trophy winner-trophy mr-1" style="{{ $match->winner_id !== null ? '' : 'display:none;' }}"></i>
                                                        <span class="winner-label">{{ $match->winner_id !== null ? $match->winner_no.' - '.$match->winner_name : '-' }}</span>
                                                    </strong>

                                                </td>


                                                {{-- Action --}}
                                                <td class="text-center">

                                                    <button type="button" class="btn btn-sm btn-primary"
                                                        data-toggle="modal"
                                                        data-target="#scoreModal{{ $match->id }}">

                                                        <i class="fas fa-edit"></i>

                                                        Score

                                                    </button>

                                                </td>

                                            </tr>
                                        @endforeach

                                    </tbody>

                                </table>

                            </div>

                        </form>


                        {{-- ================================================= --}}
                        {{-- Score Modals --}}
                        {{-- IMPORTANT: Outside the table --}}
                        {{-- ================================================= --}}

                        @foreach ($matches as $match)
                            <div class="modal fade scoreboard-modal" id="scoreModal{{ $match->id }}" tabindex="-1"
                                aria-labelledby="scoreModalLabel{{ $match->id }}" aria-hidden="true">

                                <div class="modal-dialog">

                                    <div class="modal-content">


                                        {{-- Modal Header --}}
                                        <div class="modal-header">

                                            <h5 class="modal-title" id="scoreModalLabel{{ $match->id }}">

                                                <i class="fas fa-edit"></i>

                                                Update Match Score

                                            </h5>


                                            <button type="button" data-dismiss="modal" class="btn btn-danger"
                                                aria-label="Close"><i class="fa fa-times"></i></button>

                                        </div>


                                        {{-- Score Form --}}
                                        <form class="match-score-form"
                                            action="{{ route('tournament.matches.score', Crypt::encryptString($match->id)) }}"
                                            method="POST">

                                            @csrf

                                            @method('PUT')
                                            <input type="hidden" name="finalize_score" value="0">


                                            <div class="modal-body">


                                                {{-- Match Information --}}
                                                <div class="scoreboard-matchup">
                                                    <div class="scoreboard-matchup-team">{{ $match->team1_no }} · {{ $match->team1_name }}</div>
                                                    <div class="scoreboard-matchup-vs">VS</div>
                                                    <div class="scoreboard-matchup-team">{{ $match->team2_no }} · {{ $match->team2_name }}</div>
                                                </div>

                                                {{-- Team 1 Score --}}
                                                <div class="mb-3 scoreboard-team">

                                                    <label class="form-label scoreboard-team-label">

                                                        <strong>

                                                            {{ $match->team1_no }}

                                                        </strong>

                                                        -

                                                        {{ $match->team1_name }}

                                                    </label>

                                                    <span class="ml-2 text-muted small">Server</span>
                                                    <span class="btn-group btn-group-sm server-number-control ml-1" role="group" aria-label="Team 1 server number">
                                                        <button type="button" class="btn btn-primary server-number active" aria-pressed="true">1</button>
                                                        <button type="button" class="btn btn-outline-secondary server-number" aria-pressed="false">2</button>
                                                    </span>


                                                    <div class="input-group scoreboard-input-group">
                                                        <button type="button" class="btn btn-outline-secondary scoreboard-adjust score-adjust" data-score-field="team1_score" data-delta="-1" aria-label="Decrease team 1 score">−</button>
                                                        <input type="number" name="team1_score" class="form-control text-center scoreboard-score-input score-value"
                                                            min="0" value="{{ $match->team1_score }}" required>
                                                        <button type="button" class="btn btn-outline-primary scoreboard-adjust score-adjust" data-score-field="team1_score" data-delta="1" aria-label="Increase team 1 score">+</button>
                                                    </div>

                                                </div>


                                                {{-- Team 2 Score --}}
                                                <div class="mb-3 scoreboard-team">

                                                    <label class="form-label scoreboard-team-label">

                                                        <strong>

                                                            {{ $match->team2_no }}

                                                        </strong>

                                                        -

                                                        {{ $match->team2_name }}

                                                    </label>

                                                    <span class="ml-2 text-muted small">Server</span>
                                                    <span class="btn-group btn-group-sm server-number-control ml-1" role="group" aria-label="Team 2 server number">
                                                        <button type="button" class="btn btn-primary server-number active" aria-pressed="true">1</button>
                                                        <button type="button" class="btn btn-outline-secondary server-number" aria-pressed="false">2</button>
                                                    </span>


                                                    <div class="input-group scoreboard-input-group">
                                                        <button type="button" class="btn btn-outline-secondary scoreboard-adjust score-adjust" data-score-field="team2_score" data-delta="-1" aria-label="Decrease team 2 score">−</button>
                                                        <input type="number" name="team2_score" class="form-control text-center scoreboard-score-input score-value"
                                                            min="0" value="{{ $match->team2_score }}" required>
                                                        <button type="button" class="btn btn-outline-primary scoreboard-adjust score-adjust" data-score-field="team2_score" data-delta="1" aria-label="Increase team 2 score">+</button>
                                                    </div>

                                                </div>


                                                {{-- Information --}}
                                                <div class="small text-muted mb-2"><i class="fas fa-info-circle mr-1"></i>Scores autosave as you edit. A tied score remains in progress; finalize when the match is over.</div>

                                                <div class="score-feedback" role="status" aria-live="polite"></div>

                                            </div>


                                            {{-- Modal Footer --}}
                                            <div class="modal-footer">

                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">

                                                    Cancel

                                                </button>


                                                <button type="submit" class="btn btn-success">

                                                    <i class="fas fa-save"></i>

                                                    Finalize &amp; Save

                                                </button>

                                            </div>

                                        </form>

                                    </div>

                                </div>

                            </div>
                        @endforeach
                    @else
                        {{-- No Matches --}}
                        <div class="text-center py-5">

                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>


                            <h5>

                                No Matches Generated

                            </h5>


                            <p class="text-muted">

                                Matches have not yet been generated for

                                Bracket {{ $team_bracket }}

                                -

                                {{ $team_category }}.

                            </p>

                        </div>

                    @endif

                </div>

            </div>

        </div>
        <!--end::Container-->

    </div>
    <!--end::App Content-->


    {{-- ================================================= --}}
    {{-- Bulk Delete JavaScript --}}
    {{-- ================================================= --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            document.querySelectorAll('.match-score-form').forEach(function(scoreForm) {
                let saveTimer = null;
                let saveInFlight = false;
                let saveAgain = false;
                let finalizeAfterSave = false;

                scoreForm.addEventListener('click', function(event) {
                    const adjustmentButton = event.target.closest('.score-adjust');

                    if (adjustmentButton) {
                        const scoreInput = scoreForm.elements[adjustmentButton.dataset.scoreField];
                        const currentScore = Number.parseInt(scoreInput.value || '0', 10);
                        scoreInput.value = Math.max(0, currentScore + Number(adjustmentButton.dataset.delta));
                        scoreInput.dispatchEvent(new Event('input', { bubbles: true }));
                        return;
                    }

                    const serverButton = event.target.closest('.server-number');

                    if (serverButton) {
                        const controls = serverButton.closest('.server-number-control');
                        controls.querySelectorAll('.server-number').forEach(function(button) {
                            const active = button === serverButton;
                            button.classList.toggle('btn-primary', active);
                            button.classList.toggle('btn-outline-secondary', !active);
                            button.classList.toggle('active', active);
                            button.setAttribute('aria-pressed', active ? 'true' : 'false');
                        });
                    }
                });

                async function saveScore(finalize = false) {
                    if (saveInFlight) {
                        saveAgain = true;
                        finalizeAfterSave = finalizeAfterSave || finalize;
                        return;
                    }

                    const saveButton = scoreForm.querySelector('[type="submit"]');
                    const feedback = scoreForm.querySelector('.score-feedback');
                    const finalizeInput = scoreForm.elements.finalize_score;
                    saveInFlight = true;
                    finalizeInput.value = finalize ? '1' : '0';
                    saveButton.disabled = finalize;
                    feedback.className = 'alert alert-light score-feedback';
                    feedback.textContent = finalize ? 'Saving final result…' : 'Saving score…';

                    try {
                        const formData = new FormData(scoreForm);
                        const response = await fetch(scoreForm.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const result = await response.json();

                        if (!response.ok) {
                            const validationMessage = result.errors
                                ? Object.values(result.errors).flat()[0]
                                : null;
                            throw new Error(validationMessage || result.message || 'Unable to save this score.');
                        }

                        const matchRow = document.getElementById(`match-row-${result.match_id}`);
                        const team1Score = matchRow.querySelector('.team1-score-display .score-value');
                        const team2Score = matchRow.querySelector('.team2-score-display .score-value');
                        team1Score.textContent = result.team1_score;
                        team2Score.textContent = result.team2_score;
                        team1Score.classList.remove('text-muted');
                        team2Score.classList.remove('text-muted');

                        if (result.winner_id) {
                            matchRow.querySelector('.winner-label').textContent =
                                `${result.winner_no} - ${result.winner_name}`;
                            matchRow.querySelector('.winner-trophy').style.display = 'inline-block';
                        } else {
                            matchRow.querySelector('.winner-label').textContent = 'In progress';
                            matchRow.querySelector('.winner-trophy').style.display = 'none';
                        }

                        feedback.className = 'alert alert-success score-feedback';
                        feedback.textContent = result.message;
                        if (finalize) {
                            window.jQuery(scoreForm.closest('.modal')).modal('hide');
                        }
                    } catch (error) {
                        feedback.className = 'alert alert-danger score-feedback';
                        feedback.textContent = error.message;
                    } finally {
                        saveButton.disabled = false;
                        saveInFlight = false;

                        if (saveAgain) {
                            const shouldFinalize = finalizeAfterSave;
                            saveAgain = false;
                            finalizeAfterSave = false;
                            saveScore(shouldFinalize);
                        }
                    }
                }

                scoreForm.querySelectorAll('[name="team1_score"], [name="team2_score"]').forEach(function(scoreInput) {
                    scoreInput.addEventListener('input', function() {
                        clearTimeout(saveTimer);
                        const feedback = scoreForm.querySelector('.score-feedback');
                        feedback.className = 'alert alert-light score-feedback';
                        feedback.textContent = 'Score changed · autosaving…';
                        saveTimer = setTimeout(function() {
                            saveScore(false);
                        }, 450);
                    });
                });

                scoreForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    clearTimeout(saveTimer);
                    saveScore(true);
                });
            });

            const selectAll =
                document.getElementById('selectAllMatches');

            const checkboxes =
                document.querySelectorAll('.match-checkbox');

            const deleteButton =
                document.getElementById('deleteSelectedBtn');

            const selectedCount =
                document.getElementById('selectedCount');

            const form =
                document.getElementById('bulkDeleteForm');


            /*
            |--------------------------------------------------------------------------
            | Update Delete Button
            |--------------------------------------------------------------------------
            */

            function updateDeleteButton() {

                const selected =
                    document.querySelectorAll(
                        '.match-checkbox:checked'
                    );

                const count = selected.length;


                // Enable / disable delete button
                deleteButton.disabled = count === 0;


                // Display selected count
                if (count > 0) {

                    selectedCount.textContent =
                        `(${count})`;

                } else {

                    selectedCount.textContent = '';

                }


                // Select all checkbox state
                selectAll.checked =
                    checkboxes.length > 0 &&
                    count === checkboxes.length;


                // Indeterminate state
                selectAll.indeterminate =
                    count > 0 &&
                    count < checkboxes.length;

            }


            /*
            |--------------------------------------------------------------------------
            | Select All
            |--------------------------------------------------------------------------
            */

            selectAll.addEventListener('change', function() {

                checkboxes.forEach(function(checkbox) {

                    checkbox.checked =
                        selectAll.checked;

                });

                updateDeleteButton();

            });


            /*
            |--------------------------------------------------------------------------
            | Individual Checkbox
            |--------------------------------------------------------------------------
            */

            checkboxes.forEach(function(checkbox) {

                checkbox.addEventListener('change', function() {

                    updateDeleteButton();

                });

            });


            /*
            |--------------------------------------------------------------------------
            | Delete Confirmation
            |--------------------------------------------------------------------------
            */

            form.addEventListener('submit', function(event) {

                const selected =
                    document.querySelectorAll(
                        '.match-checkbox:checked'
                    );

                const count = selected.length;


                if (count === 0) {

                    event.preventDefault();

                    alert(
                        'Please select at least one match.'
                    );

                    return;

                }


                const confirmed = confirm(

                    `Are you sure you want to delete ${count} selected match(es)?\n\n` +

                    `This action cannot be undone.`

                );


                if (!confirmed) {

                    event.preventDefault();

                }

            });


            /*
            |--------------------------------------------------------------------------
            | Initial State
            |--------------------------------------------------------------------------
            */

            updateDeleteButton();

        });
    </script>

</x-main-layout>
