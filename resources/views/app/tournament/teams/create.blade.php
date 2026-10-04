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
                            <h3 class="card-title">Bulk Add Teams</h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="col-xl-12 mt-4 p-3">
                            <form action="{{ route('teams.store') }}" method="POST">
                                @csrf
                                @method('POST')
                                <div class="row">
                                    <input type="text" class="form-control" id="tournament_id" name="tournament_id"
                                        value="{{ $tournament->id }}" hidden>

                                    <div class="col-12">
                                        <div class="form-group mb-3">
                                            <label for="names">Team Names <span class="text-danger">*</span></label>
                                            <textarea class="form-control @error('names') is-invalid @enderror" id="names"
                                                name="names" rows="10" placeholder="One team per line&#10;Team Alpha&#10;Team Bravo&#10;Team Charlie" required>{{ old('names') }}</textarea>
                                            <small class="form-text text-muted">Paste one team name per line. Team numbers will be assigned automatically in the order entered.</small>
                                            @error('names')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

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
                                </div>
                                <div class="d-flex flex-wrap gap-3">
                                    <x-form-button btnType="submit" btnAddClass="btn-primary" fdprocessedid="swuiv"
                                        btnLabel="Generate Teams" />
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
