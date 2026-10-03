<x-main-layout>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-8">
                    <h1 class="m-0">Saved draw</h1>
                    <p class="text-muted mb-0">Generated {{ $randomizerSession->generated_at?->format('M d, Y · g:i A') }}</p>
                </div>
                <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
                    <a href="{{ route('randomizer.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Back</a>
                    <form action="{{ route('randomizer.destroy', $randomizerSession->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this saved draw? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i>Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-md-6"><div class="info-box shadow-sm"><span class="info-box-icon bg-primary"><i class="fas fa-layer-group"></i></span><div class="info-box-content"><span class="info-box-text">Brackets</span><span class="info-box-number">{{ $randomizerSession->bracket_count }}</span></div></div></div>
                <div class="col-md-6"><div class="info-box shadow-sm"><span class="info-box-icon bg-info"><i class="fas fa-users"></i></span><div class="info-box-content"><span class="info-box-text">Total names</span><span class="info-box-number">{{ $randomizerSession->name_count }}</span></div></div></div>
            </div>

            <div class="row">
                @foreach ($randomizerSession->entries->groupBy('bracket_number') as $bracketNumber => $entries)
                    <div class="col-md-6 col-xl-4 mb-4">
                        <div class="card card-primary card-outline h-100 shadow-sm">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-users mr-2"></i>Bracket {{ $bracketNumber }}</h3><span class="float-right text-muted">{{ $entries->count() }} names</span></div>
                            <div class="list-group list-group-flush">
                                @foreach ($entries->sortBy('position') as $entry)
                                    <div class="list-group-item d-flex align-items-center"><span class="badge badge-primary mr-3">{{ $entry->position }}</span>{{ $entry->name }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-main-layout>
