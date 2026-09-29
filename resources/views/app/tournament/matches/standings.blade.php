<x-main-layout>

    <!--begin::App Content Header-->
    <div class="app-content-header">

        <div class="container-fluid">

            <div class="row">

                <div class="col-sm-6">

                    <h3 class="mb-3">
                        Standings
                    </h3>

                </div>

            </div>

        </div>

    </div>
    <!--end::App Content Header-->


    <!--begin::App Content-->
    <div class="app-content">

        <div class="container-fluid">


            {{-- Tournament Header --}}
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

                            <a href="{{ route('tournament.matches.view', [
                                'tournament_id' => Crypt::encryptString($tournament->id),
                            
                                'team_bracket' => $team_bracket,
                            
                                'team_category' => $team_category,
                            ]) }}"
                                class="btn btn-sm btn-secondary">

                                <i class="fas fa-arrow-left"></i>

                                Back to Matches

                            </a>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Standings Card --}}
            <div class="card">

                <div class="card-header">

                    <strong>

                        <i class="fas fa-ranking-star"></i>

                        Tournament Standings

                    </strong>

                    <span class="badge bg-primary ms-2">

                        {{ $standings->count() }}

                        Teams

                    </span>

                </div>


                <div class="card-body">


                    @if ($standings->count() > 0)

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover">

                                <thead>

                                    <tr>

                                        <th width="70" class="text-center">
                                            Rank
                                        </th>

                                        <th width="90">
                                            Team No
                                        </th>

                                        <th>
                                            Team Name
                                        </th>

                                        <th width="120" class="text-center">
                                            Games to be Played
                                        </th>

                                        <th width="110" class="text-center">
                                            Games Played
                                        </th>

                                        <th width="80" class="text-center">
                                            Win
                                        </th>

                                        <th width="80" class="text-center">
                                            Loss
                                        </th>

                                        <th width="100" class="text-center">
                                            Points For
                                        </th>

                                        <th width="120" class="text-center">
                                            Points Against
                                        </th>

                                        <th width="130" class="text-center">
                                            Point Differential
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach ($standings as $standing)
                                        <tr>

                                            {{-- Rank --}}
                                            <td class="text-center">

                                                @if ($standing->rank == 1)
                                                    <span class="badge bg-warning text-dark">

                                                        <i class="fas fa-trophy"></i>

                                                        1

                                                    </span>
                                                @else
                                                    <strong>

                                                        {{ $standing->rank }}

                                                    </strong>
                                                @endif

                                            </td>


                                            {{-- Team No --}}
                                            <td>

                                                <strong>

                                                    {{ $standing->team_no }}

                                                </strong>

                                            </td>


                                            {{-- Team Name --}}
                                            <td>

                                                {{ $standing->team_name }}

                                            </td>


                                            {{-- Games To Be Played --}}
                                            <td class="text-center">

                                                {{ $standing->games_to_be_played }}

                                            </td>


                                            {{-- Games Played --}}
                                            <td class="text-center">

                                                {{ $standing->games_played }}

                                            </td>


                                            {{-- Wins --}}
                                            <td class="text-center">

                                                <strong class="text-success">

                                                    {{ $standing->wins }}

                                                </strong>

                                            </td>


                                            {{-- Losses --}}
                                            <td class="text-center">

                                                <strong class="text-danger">

                                                    {{ $standing->losses }}

                                                </strong>

                                            </td>


                                            {{-- Points For --}}
                                            <td class="text-center">

                                                {{ $standing->points_for }}

                                            </td>


                                            {{-- Points Against --}}
                                            <td class="text-center">

                                                {{ $standing->points_against }}

                                            </td>


                                            {{-- Point Differential --}}
                                            <td class="text-center">

                                                @if ($standing->point_differential > 0)
                                                    <strong class="text-success">

                                                        +{{ $standing->point_differential }}

                                                    </strong>
                                                @elseif ($standing->point_differential < 0)
                                                    <strong class="text-danger">

                                                        {{ $standing->point_differential }}

                                                    </strong>
                                                @else
                                                    <strong>

                                                        0

                                                    </strong>
                                                @endif

                                            </td>

                                        </tr>
                                    @endforeach

                                </tbody>

                            </table>

                        </div>
                    @else
                        <div class="text-center py-5">

                            <i class="fas fa-ranking-star fa-3x text-muted mb-3"></i>

                            <h5>
                                No Teams
                            </h5>

                            <p class="text-muted">

                                No teams are available for

                                Bracket {{ $team_bracket }}

                                -

                                {{ $team_category }}.

                            </p>

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</x-main-layout>
