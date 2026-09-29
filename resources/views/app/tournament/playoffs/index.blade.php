<x-main-layout>
    <style>
        .playoff-scoreboard-modal .modal-content {
            overflow: hidden;
            border: 0;
            border-radius: 18px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
        }

        .playoff-scoreboard-modal .modal-header {
            padding: 1.2rem 1.5rem;
            color: #fff;
            background: linear-gradient(115deg, #111827, #422006);
            border-bottom: 0;
        }

        .playoff-scoreboard-modal .modal-header .close {
            color: #fff;
            text-shadow: none;
            opacity: .85;
        }

        .playoff-scoreboard-matchup {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 12px;
            align-items: center;
            padding: 16px;
            margin-bottom: 18px;
            border-radius: 14px;
            color: #fff;
            background: #172235;
        }

        .playoff-scoreboard-team {
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #f8fafc;
        }

        .playoff-scoreboard-team-label {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .playoff-scoreboard-input {
            height: 58px;
            font-size: 28px;
            font-weight: 800;
        }

        .playoff-scoreboard-adjust {
            min-width: 48px;
            font-size: 22px;
            font-weight: 700;
        }

        .playoff-scoreboard-modal .modal-footer {
            justify-content: space-between;
            padding: 1rem 1.5rem;
            background: #f8fafc;
        }
    </style>

    <div class="container-fluid">

        {{-- =========================================================
        PAGE HEADER
    ========================================================== --}}

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h3 class="mb-1">

                    <i class="fas fa-trophy"></i>

                    Playoff Matches

                </h3>

                <div class="text-muted">

                    {{ $tournament->name }}

                    |

                    {{ $team_category }}

                </div>

            </div>


            <div>

                <a href="{{ route('tournament.view', Crypt::encryptString($tournament->id)) }}"
                    class="btn btn-secondary btn-sm">

                    <i class="fas fa-arrow-left"></i>

                    Back

                </a>

            </div>

        </div>


        {{-- =========================================================
        GENERATE CROSS BRACKET PLAYOFF MATCHES
    ========================================================== --}}

        <div class="card card-warning">

            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-random"></i>
                    @if ($qualificationOptions->count() === 1)
                        Choose Top Four and Generate Playoffs
                    @else
                        Choose Qualifiers and Generate Cross Bracket Matches
                    @endif
                </h3>
            </div>

            <form action="{{ route('tournament.playoffs.generate') }}" method="POST">
                @csrf
                <input type="hidden" name="tournament_id" value="{{ Crypt::encryptString($tournament->id) }}">
                <input type="hidden" name="team_category" value="{{ $team_category }}">

                <div class="card-body">
                    @if ($qualificationOptions->count() === 1)
                        @php($bracketOptions = $qualificationOptions->first())
                        <p class="text-muted">
                            Choose the top four teams from this bracket. Semifinals cross as #1 vs #4 and #2 vs #3.
                            Tied positions keep the existing tie-selection process.
                        </p>
                        <div class="border rounded p-3 mb-3">
                            <h5>Bracket {{ $bracketOptions['bracket'] }}</h5>
                            @if (collect([1, 2, 3, 4])->contains(fn ($rank) => count($bracketOptions["rank{$rank}_options"]) > 1))
                                <p class="text-warning mb-2">Select among teams tied for each qualifying position.</p>
                            @endif
                            <div class="row">
                                @foreach ([1, 2, 3, 4] as $rank)
                                    @php($rankOptions = $bracketOptions["rank{$rank}_options"])
                                    <div class="col-md-6 mb-3">
                                        <label for="single_qualifier_rank{{ $rank }}">#{{ $rank }} Qualifier</label>
                                        <select id="single_qualifier_rank{{ $rank }}" name="top_four[rank{{ $rank }}_id]" class="form-control" required>
                                            <option value="">Select #{{ $rank }}</option>
                                            @foreach ($rankOptions as $team)
                                                <option value="{{ $team->id }}" @selected(old("top_four.rank{$rank}_id", count($rankOptions) === 1 ? $team->id : null) == $team->id)>
                                                    {{ $team->team_no }} - {{ $team->team_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-muted">
                            Select the #1 and #2 qualifiers for each bracket. Ties use the standings criteria: wins,
                            point differential, then points against. Each bracket's #1 plays the next bracket's #2 in a cycle.
                        </p>
                        @forelse ($qualificationOptions as $index => $bracketOptions)
                            <div class="border rounded p-3 mb-3">
                                <h5>Bracket {{ $bracketOptions['bracket'] }}</h5>
                                <input type="hidden" name="qualifiers[{{ $index }}][bracket]" value="{{ $bracketOptions['bracket'] }}">

                                @if (count($bracketOptions['rank1_options']) > 1 || count($bracketOptions['rank2_options']) > 1)
                                    <p class="text-warning mb-2">Tied teams need to be selected for the qualifying position.</p>
                                @endif

                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="qualifier_{{ $index }}_rank1">#1 Qualifier</label>
                                        <select id="qualifier_{{ $index }}_rank1" name="qualifiers[{{ $index }}][rank1_id]" class="form-control" required>
                                            <option value="">Select #1</option>
                                            @foreach ($bracketOptions['rank1_options'] as $team)
                                                <option value="{{ $team->id }}" @selected(old("qualifiers.{$index}.rank1_id", count($bracketOptions['rank1_options']) === 1 ? $team->id : null) == $team->id)>
                                                    {{ $team->team_no }} - {{ $team->team_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="qualifier_{{ $index }}_rank2">#2 Qualifier</label>
                                        <select id="qualifier_{{ $index }}_rank2" name="qualifiers[{{ $index }}][rank2_id]" class="form-control" required>
                                            <option value="">Select #2</option>
                                            @foreach ($bracketOptions['rank2_options'] as $team)
                                                <option value="{{ $team->id }}" @selected(old("qualifiers.{$index}.rank2_id", count($bracketOptions['rank2_options']) === 1 ? $team->id : null) == $team->id)>
                                                    {{ $team->team_no }} - {{ $team->team_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No teams are registered in this category yet.</p>
                        @endforelse
                    @endif
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-warning" @disabled($qualificationOptions->count() === 1
                        ? collect([1, 2, 3, 4])->contains(fn ($rank) => count($qualificationOptions->first()["rank{$rank}_options"]) === 0)
                        : ($qualificationOptions->count() < 2 || $qualificationOptions->contains(fn ($bracket) => count($bracket['rank1_options']) === 0 || count($bracket['rank2_options']) === 0)))>
                        <i class="fas fa-cogs"></i>
                        {{ $qualificationOptions->count() === 1 ? 'Generate Top-Four Playoffs' : 'Generate Cross Bracket Matches' }}
                    </button>
                </div>
            </form>
        </div>


        {{-- =========================================================
        PLAYOFF MATCH LIST
    ========================================================== --}}

        <div class="card card-success">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-list"></i>

                    Playoff Matches

                </h3>

            </div>


            <form id="bulkDeleteForm" action="{{ route('tournament.playoffs.bulkDestroy') }}" method="POST">

                @csrf

                @method('DELETE')


                {{-- Tournament --}}

                <input type="hidden" name="tournament_id" value="{{ Crypt::encryptString($tournament->id) }}">


                {{-- Category --}}

                <input type="hidden" name="team_category" value="{{ $team_category }}">


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover mb-0">

                            <thead class="thead-light">

                                <tr>

                                    <th width="40">

                                        <input type="checkbox" id="selectAll">

                                    </th>


                                    <th>
                                        Type
                                    </th>


                                    <th>
                                        Match No.
                                    </th>


                                    <th>
                                        Team 1
                                    </th>


                                    <th class="text-center">
                                        Score
                                    </th>


                                    <th>
                                        Team 2
                                    </th>


                                    <th>
                                        Winner
                                    </th>


                                    <th width="80">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @forelse ($playoffMatches
                                as $match)
                                    <tr id="playoff-match-row-{{ $match->id }}">

                                        {{-- CHECKBOX --}}

                                        <td>

                                            <input type="checkbox" class="match-checkbox" name="match_ids[]"
                                                value="{{ Crypt::encryptString($match->id) }}">

                                        </td>


                                        {{-- MATCH TYPE --}}

                                        <td>

                                            @if ($match->match_type === 'BYE')
                                                <span class="badge badge-secondary">BYE · {{ $match->team_category }}</span>
                                            @elseif ($match->match_type === 'ROUND')
                                                <span class="badge badge-info">Knockout Round</span>
                                            @elseif ($match->match_type === 'SF')
                                                <span class="badge badge-primary">
                                                    Semifinal
                                                </span>
                                            @elseif ($match->match_type === 'SF1')
                                                <span class="badge badge-primary">
                                                    SF1
                                                </span>
                                            @elseif ($match->match_type === 'SF2')
                                                <span class="badge badge-primary">
                                                    SF2
                                                </span>
                                            @elseif ($match->match_type === '3RD')
                                                <span class="badge badge-warning">
                                                    {{ $match->team2_id === null ? '3RD · Auto-awarded' : '3RD' }}
                                                </span>
                                            @elseif ($match->match_type === 'FINAL')
                                                <span class="badge badge-success">
                                                    FINAL
                                                </span>
                                            @endif

                                        </td>


                                        {{-- MATCH NUMBER --}}

                                        <td>

                                            {{ $match->match_no ?? '-' }}

                                        </td>


                                        {{-- TEAM 1 --}}

                                        <td>

                                            @if ($match->team1_no)
                                                <strong>

                                                    {{ $match->team1_no }}

                                                </strong>

                                                <br>

                                                <small class="text-muted">

                                                    {{ $match->team1_name }}

                                                </small>

                                                <br>

                                                <small>

                                                    Bracket
                                                    {{ $match->team1_bracket }}

                                                </small>
                                            @else
                                                -
                                            @endif

                                        </td>


                                        {{-- SCORE --}}

                                        <td class="text-center">
                                            @if ($match->match_type === '3RD' && $match->team2_id === null)
                                                <span class="text-success font-weight-bold">{{ $match->automatic_score_text ?? 'Auto-awarded' }}</span>
                                            @else
                                                <strong class="h5 playoff-score-display">
                                                    {{ !is_null($match->team1_score) && !is_null($match->team2_score) ? $match->team1_score.' - '.$match->team2_score : '-' }}
                                                </strong>
                                            @endif
                                        </td>


                                        {{-- TEAM 2 --}}

                                        <td>

                                            @if ($match->team2_no)
                                                <strong>

                                                    {{ $match->team2_no }}

                                                </strong>

                                                <br>

                                                <small class="text-muted">

                                                    {{ $match->team2_name }}

                                                </small>

                                                <br>

                                                <small>

                                                    Bracket
                                                    {{ $match->team2_bracket }}

                                                </small>
                                            @else
                                                -
                                            @endif

                                        </td>


                                        {{-- WINNER --}}

                                        <td>

                                            <span class="badge badge-success playoff-winner-display" style="{{ $match->winner_no ? '' : 'display:none;' }}">
                                                <i class="fas fa-trophy mr-1"></i>
                                                <span class="playoff-winner-name">{{ $match->winner_no ? $match->winner_no.' - '.$match->winner_name : '' }}</span>
                                            </span>
                                            <span class="text-muted playoff-pending-display" style="{{ $match->winner_no ? 'display:none;' : '' }}">
                                                {{ !is_null($match->team1_score) && !is_null($match->team2_score) ? 'In progress' : 'Pending' }}
                                            </span>

                                        </td>


                                        {{-- ACTION --}}

                                        <td>

                                            @if ($match->match_type === 'BYE')
                                                <span class="text-muted">Auto advance</span>
                                            @elseif ($match->match_type === '3RD' && $match->team2_id === null)
                                                <span class="text-success">3rd place awarded</span>
                                            @else
                                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                                    data-target="#scoreModal{{ $match->id }}">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="8" class="text-center text-muted py-4">

                                            No playoff matches generated yet.

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>


                {{-- BULK DELETE --}}

                <div class="card-footer">

                    <button type="submit" id="deleteSelectedBtn" class="btn btn-danger btn-sm" disabled>

                        <i class="fas fa-trash"></i>

                        Delete Selected

                    </button>


                    <span id="selectedCount" class="text-muted ml-2">
                        0 selected
                    </span>

                </div>

            </form>

        </div>

    </div>


    {{-- =============================================================
    SCORE MODALS
============================================================= --}}

    @foreach ($playoffMatches->filter(fn ($match) => $match->match_type !== 'BYE' && $match->team2_id !== null) as $match)
        <div class="modal fade playoff-scoreboard-modal" id="scoreModal{{ $match->id }}" tabindex="-1" role="dialog"
            aria-hidden="true">

            <div class="modal-dialog" role="document">

                <div class="modal-content">

                    <form class="playoff-score-form" action="{{ route('tournament.playoffs.score', Crypt::encryptString($match->id)) }}"
                        method="POST">

                        @csrf

                        @method('PUT')
                        <input type="hidden" name="finalize_score" value="0">


                        <div class="modal-header">

                            <h5 class="modal-title">

                                Scoreboard

                                <br>

                                <small class="text-muted">

                                    {{ $match->match_type }}{{ $match->match_no ? ' · '.$match->match_no : '' }}

                                </small>

                            </h5>


                            <button type="button" class="close" data-dismiss="modal">

                                <span>
                                    &times;
                                </span>

                            </button>

                        </div>


                        <div class="modal-body">
                            <div class="playoff-scoreboard-matchup">
                                <strong class="text-center">{{ $match->team1_no }} · {{ $match->team1_name }}</strong>
                                <span class="small text-muted font-weight-bold">VS</span>
                                <strong class="text-center">{{ $match->team2_no }} · {{ $match->team2_name }}</strong>
                            </div>

                            <div class="playoff-scoreboard-team mb-3">
                                <div class="playoff-scoreboard-team-label">
                                    <span>{{ $match->team1_no }} · {{ $match->team1_name }}</span>
                                    <span class="small text-muted">Server
                                        <span class="btn-group btn-group-sm server-number-control ml-1" role="group" aria-label="Team 1 server number">
                                            <button type="button" class="btn btn-primary server-number active" aria-pressed="true">1</button>
                                            <button type="button" class="btn btn-outline-secondary server-number" aria-pressed="false">2</button>
                                        </span>
                                    </span>
                                </div>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary playoff-scoreboard-adjust score-adjust" data-score-field="team1_score" data-delta="-1" aria-label="Decrease team 1 score">−</button>
                                    <input type="number" name="team1_score" class="form-control text-center playoff-scoreboard-input" min="0" value="{{ $match->team1_score ?? 0 }}" required>
                                    <button type="button" class="btn btn-outline-primary playoff-scoreboard-adjust score-adjust" data-score-field="team1_score" data-delta="1" aria-label="Increase team 1 score">+</button>
                                </div>
                            </div>

                            <div class="playoff-scoreboard-team">
                                <div class="playoff-scoreboard-team-label">
                                    <span>{{ $match->team2_no }} · {{ $match->team2_name }}</span>
                                    <span class="small text-muted">Server
                                        <span class="btn-group btn-group-sm server-number-control ml-1" role="group" aria-label="Team 2 server number">
                                            <button type="button" class="btn btn-primary server-number active" aria-pressed="true">1</button>
                                            <button type="button" class="btn btn-outline-secondary server-number" aria-pressed="false">2</button>
                                        </span>
                                    </span>
                                </div>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary playoff-scoreboard-adjust score-adjust" data-score-field="team2_score" data-delta="-1" aria-label="Decrease team 2 score">−</button>
                                    <input type="number" name="team2_score" class="form-control text-center playoff-scoreboard-input" min="0" value="{{ $match->team2_score ?? 0 }}" required>
                                    <button type="button" class="btn btn-outline-primary playoff-scoreboard-adjust score-adjust" data-score-field="team2_score" data-delta="1" aria-label="Increase team 2 score">+</button>
                                </div>
                            </div>

                            <div class="small text-muted mt-3"><i class="fas fa-info-circle mr-1"></i>Scores autosave while ongoing. A tied score stays in progress; finalize when the match is over.</div>
                            <div class="playoff-score-feedback mt-2" role="status" aria-live="polite"></div>
                        </div>


                        <div class="modal-footer">

                            <button type="button" class="btn btn-secondary" data-dismiss="modal">

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


    {{-- =============================================================
    JAVASCRIPT
============================================================= --}}

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {

                document.querySelectorAll('.playoff-score-form').forEach(function(scoreForm) {
                    let saveTimer = null;
                    let saveInFlight = false;
                    let saveAgain = false;
                    let finalizeAfterSave = false;

                    scoreForm.addEventListener('click', function(event) {
                        const adjustButton = event.target.closest('.score-adjust');
                        if (adjustButton) {
                            const scoreInput = scoreForm.elements[adjustButton.dataset.scoreField];
                            const score = Number.parseInt(scoreInput.value || '0', 10);
                            scoreInput.value = Math.max(0, score + Number(adjustButton.dataset.delta));
                            scoreInput.dispatchEvent(new Event('input', { bubbles: true }));
                            return;
                        }

                        const serverButton = event.target.closest('.server-number');
                        if (serverButton) {
                            serverButton.closest('.server-number-control').querySelectorAll('.server-number').forEach(function(button) {
                                const active = button === serverButton;
                                button.classList.toggle('btn-primary', active);
                                button.classList.toggle('btn-outline-secondary', !active);
                                button.classList.toggle('active', active);
                                button.setAttribute('aria-pressed', active ? 'true' : 'false');
                            });
                        }
                    });

                    async function savePlayoffScore(finalize = false) {
                        if (saveInFlight) {
                            saveAgain = true;
                            finalizeAfterSave = finalizeAfterSave || finalize;
                            return;
                        }

                        const saveButton = scoreForm.querySelector('[type="submit"]');
                        const feedback = scoreForm.querySelector('.playoff-score-feedback');
                        saveInFlight = true;
                        scoreForm.elements.finalize_score.value = finalize ? '1' : '0';
                        saveButton.disabled = finalize;
                        feedback.className = 'alert alert-light playoff-score-feedback';
                        feedback.textContent = finalize ? 'Saving final result…' : 'Saving score…';

                        try {
                            const response = await fetch(scoreForm.action, {
                                method: 'POST',
                                body: new FormData(scoreForm),
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
                                throw new Error(validationMessage || result.message || 'Unable to save this playoff score.');
                            }

                            const row = document.getElementById(`playoff-match-row-${result.match_id}`);
                            row.querySelector('.playoff-score-display').textContent = `${result.team1_score} - ${result.team2_score}`;

                            const winnerBadge = row.querySelector('.playoff-winner-display');
                            const pendingLabel = row.querySelector('.playoff-pending-display');
                            if (result.winner_id) {
                                winnerBadge.querySelector('.playoff-winner-name').textContent =
                                    `${result.winner_no} - ${result.winner_name}`;
                                winnerBadge.style.display = '';
                                pendingLabel.style.display = 'none';
                            } else {
                                winnerBadge.style.display = 'none';
                                pendingLabel.textContent = 'In progress';
                                pendingLabel.style.display = '';
                            }

                            feedback.className = 'alert alert-success playoff-score-feedback';
                            feedback.textContent = result.message;

                            if (finalize) {
                                window.jQuery(scoreForm.closest('.modal')).modal('hide');
                                if (result.new_matches_generated) {
                                    window.setTimeout(function() {
                                        window.location.reload();
                                    }, 350);
                                }
                            }
                        } catch (error) {
                            feedback.className = 'alert alert-danger playoff-score-feedback';
                            feedback.textContent = error.message;
                        } finally {
                            saveButton.disabled = false;
                            saveInFlight = false;
                            if (saveAgain) {
                                const shouldFinalize = finalizeAfterSave;
                                saveAgain = false;
                                finalizeAfterSave = false;
                                savePlayoffScore(shouldFinalize);
                            }
                        }
                    }

                    scoreForm.querySelectorAll('[name="team1_score"], [name="team2_score"]').forEach(function(scoreInput) {
                        scoreInput.addEventListener('input', function() {
                            clearTimeout(saveTimer);
                            const feedback = scoreForm.querySelector('.playoff-score-feedback');
                            feedback.className = 'alert alert-light playoff-score-feedback';
                            feedback.textContent = 'Score changed · autosaving…';
                            saveTimer = setTimeout(function() {
                                savePlayoffScore(false);
                            }, 450);
                        });
                    });

                    scoreForm.addEventListener('submit', function(event) {
                        event.preventDefault();
                        clearTimeout(saveTimer);
                        savePlayoffScore(true);
                    });
                });

                const selectAll =
                    document.getElementById(
                        'selectAll'
                    );

                const checkboxes =
                    document.querySelectorAll(
                        '.match-checkbox'
                    );

                const deleteButton =
                    document.getElementById(
                        'deleteSelectedBtn'
                    );

                const selectedCount =
                    document.getElementById(
                        'selectedCount'
                    );


                function updateSelection() {
                    const selected =
                        document.querySelectorAll(
                            '.match-checkbox:checked'
                        ).length;


                    deleteButton.disabled =
                        selected === 0;


                    selectedCount.textContent =
                        selected +
                        ' selected';
                }


                /*
                 * Select all.
                 */
                if (selectAll) {

                    selectAll.addEventListener(
                        'change',
                        function() {

                            checkboxes.forEach(
                                function(checkbox) {

                                    checkbox.checked =
                                        selectAll.checked;

                                }
                            );


                            updateSelection();

                        }
                    );

                }


                /*
                 * Individual checkboxes.
                 */
                checkboxes.forEach(
                    function(checkbox) {

                        checkbox.addEventListener(
                            'change',
                            function() {

                                updateSelection();

                            }
                        );

                    }
                );


                /*
                 * Bulk delete confirmation.
                 */
                const bulkDeleteForm =
                    document.getElementById(
                        'bulkDeleteForm'
                    );


                if (bulkDeleteForm) {

                    bulkDeleteForm.addEventListener(
                        'submit',
                        function(event) {

                            const selected =
                                document.querySelectorAll(
                                    '.match-checkbox:checked'
                                ).length;


                            if (selected === 0) {

                                event.preventDefault();

                                return;

                            }


                            const confirmed =
                                confirm(
                                    'Are you sure you want to delete ' +
                                    selected +
                                    ' selected playoff match(es)?'
                                );


                            if (!confirmed) {

                                event.preventDefault();

                            }

                        }
                    );

                }

            }
        );
    </script>
</x-main-layout>
