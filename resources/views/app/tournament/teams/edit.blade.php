<x-main-layout>
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-3">Edit Team</h3>
                    {{-- </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Simple Tables
                        </li>
                    </ol>
                </div> --}}
                </div>
                <!--end::Row-->
            </div>

            <div class="mb-3">
                <a href="{{ route('tournament.view', Crypt::encryptString($teamsEdit->tournament_id)) }}"
                    class="btn btn-block btn-success w-25">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!--end::Container-->
        </div>
        <!--end::App Content Header-->
    </div>

    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Edit Team</h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="col-xl-12 mt-4 p-3">
                            <form action="{{ route('teams.update', Crypt::encryptString($teamsEdit->id)) }}"
                                method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">

                                    <div class="col-md-3">
                                        <div class="form-floating mb-3">
                                            <label for="team_no">Team Number <span class="text-danger">*</span></label>
                                            <select class="form-control" id="team_no" name="team_no"
                                                aria-label="Team Number" required>

                                                <option value="{{ $teamsEdit->team_no }}" selected>
                                                    {{ $teamsEdit->team_no }}
                                                </option>

                                            </select>
                                            <script>
                                                const teamNoSelect = document.getElementById('team_no');

                                                for (let i = 1; i <= 1000; i++) {
                                                    const option = document.createElement('option');

                                                    option.value = `${String(i).padStart(2, '0')}`;
                                                    option.textContent = `${String(i).padStart(2, '0')}`;

                                                    teamNoSelect.appendChild(option);
                                                }
                                            </script>
                                        </div>
                                        <x-form-error name='team_no' />
                                    </div>

                                    <x-form-input-list t-list style="" divClass="col-md-9" type="text"
                                        oninput="" inputLabel="Team Name" forLabel="team_name"
                                        placeHolder="Team Name" id="team_name" readonly=""
                                        value="{{ $teamsEdit->team_name }}" name="team_name" list='tournamentOptions'>
                                        @foreach ($allTeams as $team)
                                            <option value="{{ $team->team_name }}">
                                                {{ $team->team_name }}</option>
                                        @endforeach
                                    </x-form-input-list>

                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <label for="bracket">
                                                Bracket <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control" id="team_bracket" name="team_bracket"
                                                aria-label="Bracket" required>

                                                <option value="{{ $teamsEdit->team_bracket }}" selected>
                                                    {{ $teamsEdit->team_bracket }}
                                                </option>

                                            </select>
                                            <script>
                                                const bracketSelect = document.getElementById('team_bracket');

                                                for (let i = 0; i < 26; i++) {
                                                    const letter = String.fromCharCode(65 + i);

                                                    const option = document.createElement('option');

                                                    option.value = letter;
                                                    option.textContent = `Bracket ${letter}`;

                                                    bracketSelect.appendChild(option);
                                                }
                                            </script>
                                        </div>
                                        <x-form-error name="team_bracket" />
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <label for="team_category">
                                                Team Category <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control" id="team_category" name="team_category"
                                                aria-label="Team Category" required>

                                                <option value="{{ $teamsEdit->team_category }}" selected>
                                                    {{ $teamsEdit->team_category }}
                                                </option>

                                                <option value="Low Beginners">
                                                    Low Beginners
                                                </option>
                                                <option value="High Beginners">
                                                    High Beginners
                                                </option>
                                                <option value="Low Novice">
                                                    Low Novice
                                                </option>
                                                <option value="High Novice">
                                                    High Novice
                                                </option>
                                                <option value="Low Intermediate">
                                                    Low Intermediate
                                                </option>
                                                <option value="High Intermediate">
                                                    High Intermediate
                                                </option>
                                                <option value="Open">
                                                    Open
                                                </option>
                                            </select>

                                        </div>
                                        <x-form-error name="team_category" />
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-3">
                                    <x-form-button btnType="submit"
                                        btnAddClass="btn-warning font-weight-bold white-text" fdprocessedid="swuiv"
                                        btnLabel="Update" />
                                </div>
                            </form>

                        </div>
                        <!-- end col -->
                    </div>
                    <!-- /.card --->
                </div>
                <!-- /.col -->
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content-->
</x-main-layout>
