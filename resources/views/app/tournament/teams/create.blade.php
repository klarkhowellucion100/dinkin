<x-main-layout>
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-3">Add Teams</h3>
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
            {{-- <x-back-button route="tournament.view,{{ $tournament->id }}" /> --}}

            <div class="mb-3">
                <a href="{{ route('tournament.view', Crypt::encryptString($tournament->id)) }}"
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
                            <h3 class="card-title">Create Teams</h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="col-xl-12 mt-4 p-3">
                            <form action="{{ route('teams.store') }}" method="POST">
                                @csrf
                                @method('POST')
                                <div class="row">
                                    <input type="text" class="form-control" id="tournament_id" name="tournament_id"
                                        value="{{ $tournament->id }}" hidden>

                                    <div class="col-md-3">
                                        <div class="form-floating mb-3">
                                            <label for="team_no">Team Number <span class="text-danger">*</span></label>
                                            <select class="form-control" id="team_no" name="team_no"
                                                aria-label="Team Number" required>

                                                <option value="" selected disabled>
                                                    Select Team Number...
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
                                        value="{{ old('team_name') }}" name="team_name" list='tournamentOptions'>
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

                                                <option value="" selected disabled>
                                                    Select Bracket...
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

                                                <option value="" selected disabled>
                                                    Select Team Category...
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
                                    {{--
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <label for="date">Tournament Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="date"
                                                placeholder="Tournament Date" name="date"
                                                value="{{ old('date') }}">
                                        </div>
                                        <x-form-error name='date' />
                                    </div>


                                    <x-form-input-list style="" divClass="col-md-6" type="text" oninput=""
                                        inputLabel="Venue" forLabel="venue" placeHolder="Venue" id="venue"
                                        readonly="" value="{{ old('venue') }}" name="venue"
                                        list='tournamentOptions'>
                                        @foreach ($allTournaments as $tournament)
                                            <option value="{{ $tournament->venue }}">
                                                {{ $tournament->venue }}</option>
                                        @endforeach
                                    </x-form-input-list>

 --}}

                                </div>
                                <div class="d-flex flex-wrap gap-3">
                                    <x-form-button btnType="submit" btnAddClass="btn-primary" fdprocessedid="swuiv"
                                        btnLabel="Create" />
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
