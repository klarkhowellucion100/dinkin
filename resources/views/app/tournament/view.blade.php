<x-main-layout>

    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-3">Tournament Name ({{ $tournamentView->name }})</h3>
                </div>
                {{-- <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Simple Tables
                        </li>
                    </ol>
                </div> --}}
            </div>

            <div class="row">
                <div class="col-6 w-25">
                    <a href="{{ route('teams.create', ['tournament_id' => $tournamentView->id]) }}" type="button"
                        class="btn btn-block btn-success mb-3">Add
                        Team +</a>
                </div>

            </div>

            <div class="card mb-4">

                <div class="card-header">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h5 class="mb-0">

                                <i class="fas fa-trophy"></i>

                                {{ $tournamentView->name }}

                            </h5>

                        </div>


                        <div>

                            <a href="{{ route('tournament.index') }}" class="btn btn-sm btn-secondary">

                                <i class="fas fa-arrow-left"></i>

                                Back

                            </a>

                        </div>

                    </div>

                </div>

            </div>


            {{-- <x-search-input formAction="{{ route('tournament.search') }}" /> --}}
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content Header-->

    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Teams List</h3>
                        </div>

                        @php

                            $categories = collect();

                            foreach ($teamsGrouped as $bracket => $bracketCategories) {
                                foreach ($bracketCategories as $category => $teams) {
                                    $categories->push($category);
                                }
                            }

                            $categories = $categories->unique()->values();

                        @endphp
                        @if ($categories->count() > 0)

                            <ul class="nav nav-tabs mb-4" id="categoryTabs" role="tablist">

                                @foreach ($categories as $category)
                                    @php

                                        $categoryId = 'category-' . \Illuminate\Support\Str::slug($category);

                                    @endphp

                                    <li class="nav-item" role="presentation">

                                        <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                            id="{{ $categoryId }}-tab" data-bs-toggle="tab"
                                            data-bs-target="#{{ $categoryId }}" type="button" role="tab"
                                            aria-controls="{{ $categoryId }}"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">

                                            <i class="fas fa-users"></i>

                                            {{ $category }}

                                        </button>

                                    </li>
                                @endforeach

                            </ul>

                            <div class="tab-content" id="categoryTabsContent">

                                @foreach ($categories as $category)
                                    @php

                                        $categoryId = 'category-' . \Illuminate\Support\Str::slug($category);

                                    @endphp


                                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                        id="{{ $categoryId }}" role="tabpanel"
                                        aria-labelledby="{{ $categoryId }}-tab">

                                        @foreach ($teamsGrouped as $bracket => $bracketCategories)
                                            @if (isset($bracketCategories[$category]))
                                                @php

                                                    $teams = $bracketCategories[$category];

                                                @endphp

                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <strong>
                                                                    <i class="fas fa-layer-group"></i>
                                                                    Bracket {{ $bracket }}
                                                                </strong>
                                                                <span class="badge bg-secondary ms-2">
                                                                    {{ $teams->count() }}
                                                                    Teams
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <a href="{{ route('tournament.matches.view', [
                                                                    'tournament_id' => Crypt::encryptString($tournamentView->id),
                                                                    'team_bracket' => $bracket,
                                                                    'team_category' => $category,
                                                                ]) }}"
                                                                    class="btn btn-sm btn-info">
                                                                    <i class="fas fa-eye"></i>
                                                                    View Matches
                                                                </a>
                                                                <form
                                                                    action="{{ route('tournament.matches.generate', Crypt::encryptString($tournamentView->id)) }}"
                                                                    method="POST" class="d-inline"
                                                                    onsubmit="return confirm(
                                                                        'Generate round robin matches for Bracket {{ $bracket }} - {{ $category }}?'
                                                                    );">
                                                                    @csrf

                                                                    <input type="hidden" name="team_bracket"
                                                                        value="{{ $bracket }}">

                                                                    <input type="hidden" name="team_category"
                                                                        value="{{ $category }}">

                                                                    <button type="submit"
                                                                        class="btn btn-sm btn-primary">
                                                                        <i class="fas fa-random"></i>
                                                                        Generate Matches
                                                                    </button>
                                                                </form>
                                                            </div>

                                                        </div>

                                                    </div>

                                                <div class="card-body">
                                                    @php
                                                        $bulkDeleteFormId = 'bulk-delete-teams-' . \Illuminate\Support\Str::slug($bracket . '-' . $category);
                                                    @endphp

                                                    <form id="{{ $bulkDeleteFormId }}"
                                                        action="{{ route('teams.bulkDestroy') }}" method="POST"
                                                        onsubmit="return confirm('Are you sure you want to delete the selected team(s)? This action cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="tournament_id"
                                                            value="{{ Crypt::encryptString($tournamentView->id) }}">
                                                        <input type="hidden" name="team_bracket" value="{{ $bracket }}">
                                                        <input type="hidden" name="team_category" value="{{ $category }}">
                                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                                            <span class="text-muted">Select teams to perform bulk actions.</span>
                                                            <button type="submit" class="btn btn-sm btn-danger bulk-delete-teams-button" disabled>
                                                                <i class="fas fa-trash"></i> Delete Selected
                                                                <span class="bulk-delete-teams-count"></span>
                                                            </button>
                                                        </div>
                                                    </form>

                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th width="50" class="text-center">
                                                                        <input type="checkbox" class="form-check-input select-all-teams"
                                                                            form="{{ $bulkDeleteFormId }}">
                                                                    </th>
                                                                    <th width="60">
                                                                            #
                                                                        </th>
                                                                        <th>
                                                                            Team No.
                                                                        </th>
                                                                        <th>
                                                                            Team Name
                                                                        </th>
                                                                        <th width="150">
                                                                            Action
                                                                        </th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                @forelse ($teams as $team)
                                                                        <tr>
                                                                            <td class="text-center">
                                                                                <input type="checkbox" name="team_ids[]"
                                                                                    value="{{ Crypt::encryptString($team->id) }}"
                                                                                    class="form-check-input team-checkbox"
                                                                                    form="{{ $bulkDeleteFormId }}">
                                                                            </td>
                                                                            <td>
                                                                                {{ $loop->iteration }}
                                                                            </td>
                                                                            <td>
                                                                                {{ $team->team_no }}
                                                                            </td>
                                                                            <td>
                                                                                {{ $team->team_name }}
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex align-items-center"
                                                                                    style="gap: 5px;">

                                                                                    <a href="{{ route('teams.edit', Crypt::encryptString($team->id)) }}"
                                                                                        class="btn btn-sm btn-warning">
                                                                                        <i class="fas fa-edit"
                                                                                            style="color: white;"></i>
                                                                                    </a>

                                                                                    <x-form-delete-action
                                                                                        id="delete-team-{{ $team->id }}"
                                                                                        style=""
                                                                                        action="{{ route('teams.destroy', Crypt::encryptString($team->id)) }}"
                                                                                        target="deleteTeamConfirmationModal-{{ $team->id }}"
                                                                                        idLabel="deleteTeamModalLabel-{{ $team->id }}"
                                                                                        labelBody="Team Name: {{ $team->team_name }}"
                                                                                        onClick="submitTeamDeleteForm({{ $team->id }})" />
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    @empty
                                                                        <tr>
                                                                            <td colspan="5" class="text-center">
                                                                                No teams registered.
                                                                            </td>
                                                                        </tr>
                                                                    @endforelse
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="card">

                                <div class="card-body text-center">

                                    <i class="fas fa-users fa-3x mb-3 text-muted"></i>

                                    <h5>
                                        No Teams Registered
                                    </h5>

                                    <p class="text-muted">
                                        There are currently no teams registered
                                        for this tournament.
                                    </p>


                                    {{-- <a href="{{ route('teams.create', $tournamentView->id) }}" class="btn btn-primary">

                                        <i class="fas fa-plus"></i>

                                        Add Team

                                    </a> --}}

                                </div>

                            </div>

                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-main-layout>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.card-body').forEach(function (cardBody) {
            const selectAll = cardBody.querySelector('.select-all-teams');
            const checkboxes = cardBody.querySelectorAll('.team-checkbox');
            const deleteButton = cardBody.querySelector('.bulk-delete-teams-button');
            const selectedCount = cardBody.querySelector('.bulk-delete-teams-count');

            if (!selectAll || !deleteButton) {
                return;
            }

            const updateBulkDeleteState = function () {
                const selected = cardBody.querySelectorAll('.team-checkbox:checked').length;

                deleteButton.disabled = selected === 0;
                selectedCount.textContent = selected > 0 ? '(' + selected + ')' : '';
                selectAll.checked = checkboxes.length > 0 && selected === checkboxes.length;
                selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
            };

            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
                updateBulkDeleteState();
            });

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', updateBulkDeleteState);
            });
        });
    });
</script>
