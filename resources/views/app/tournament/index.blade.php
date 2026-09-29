<x-main-layout>

    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-3">Tournament</h3>
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


            <a href="{{ route('tournament.create') }}" type="button" class="btn btn-block btn-success w-25">Add
                +</a>

            <x-search-input formAction="{{ route('tournament.search') }}" />
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
                            <h3 class="card-title">Tournament List</h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tournament</th>
                                        <th>Date</th>
                                        <th>Venue</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($allTournaments as $tournament)
                                        <tr>
                                            <td>{{ $tournament->name }}</td>
                                            <td>{{ \Carbon\Carbon::parse($tournament->date)->format('M d, Y') }}</td>
                                            <td>{{ $tournament->venue }}</td>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap: 5px;">
                                                    <a href="{{ route('tournament.view', Crypt::encryptString($tournament->id)) }}"
                                                        class="btn btn-sm btn-success">
                                                        <i class="fas fa-eye" style="color: white"></i>
                                                    </a>
                                                    <a href="{{ route('tournament.edit', Crypt::encryptString($tournament->id)) }}"
                                                        class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit fa-solid fa-edit"
                                                            style="color: white"></i>
                                                    </a>
                                                    <x-form-delete-action id="delete-tournament-{{ $tournament->id }}"
                                                        style=""
                                                        action="{{ route('tournament.destroy', Crypt::encryptString($tournament->id)) }}"
                                                        target="deleteTournamentConfirmationModal-{{ $tournament->id }}"
                                                        idLabel="deleteTournamentModalLabel-{{ $tournament->id }}"
                                                        labelBody="Tournament Name: {{ $tournament->name }}"
                                                        onClick="submitTournamentDeleteForm({{ $tournament->id }})" />
                                                </div>
                                            </td>
                                            </td>
                                            <script>
                                                function submitTournamentDeleteForm(id) {
                                                    // This now correctly finds "delete-tournament-1"
                                                    const form = document.getElementById('delete-tournament-' + id);
                                                    if (form) {
                                                        form.submit();
                                                    }
                                                }
                                            </script>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center">No tournaments found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                        <div class="card-footer clearfix">
                            <ul class="pagination pagination-sm m-0 float-end">
                                <div>
                                    <div class="mt-3">
                                        {{ $allTournaments->appends(['search' => request()->input('search')])->links() }}
                                    </div>
                                </div>
                            </ul>
                        </div>
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
