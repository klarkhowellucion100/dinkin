<x-main-layout>
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-3">Add Tournament</h3>
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
            <x-back-button route="tournament.index" />

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
                            <h3 class="card-title">Create Tournament</h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="col-xl-12 mt-4 p-3">
                            <form action="{{ route('tournament.store') }}" method="POST">
                                @csrf
                                @method('POST')
                                <div class="row">

                                    <x-form-input-list style="" divClass="col-md-12" type="text" oninput=""
                                        inputLabel="Tournament Name" forLabel="name" placeHolder="Tournament Name"
                                        id="name" readonly="" value="{{ old('name') }}" name="name"
                                        list='tournamentOptions'>
                                        @foreach ($allTournaments as $tournament)
                                            <option value="{{ $tournament->name }}">
                                                {{ $tournament->name }}</option>
                                        @endforeach
                                    </x-form-input-list>

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
