<x-main-layout>
    <style>
        .randomizer-hero {
            background: linear-gradient(135deg, #182848 0%, #304b72 100%);
            color: #fff;
            border-radius: .5rem;
        }

        .randomizer-hero .eyebrow {
            color: #8ed8ff;
            letter-spacing: .12em;
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .name-input {
            min-height: 300px;
            resize: vertical;
        }

        .bracket-card {
            border-top: 4px solid #3c8dbc;
        }

        .bracket-card .list-group-item {
            border-left: 0;
            border-right: 0;
        }

        .draw-overlay {
            background: rgba(13, 25, 45, .94);
            z-index: 1100;
        }

        .draw-orbit {
            animation: orbit 1.1s linear infinite;
        }

        .draw-number {
            animation: pulse-number .8s ease-in-out infinite;
        }

        @keyframes orbit {
            to { transform: rotate(360deg); }
        }

        @keyframes pulse-number {
            50% { transform: scale(1.18); opacity: .7; }
        }
    </style>

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-8">
                    <h1 class="m-0">Randomizer</h1>
                    <p class="text-muted mb-0">Shuffle names into fair, separate brackets.</p>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif

            <div class="randomizer-hero p-4 mb-4 shadow-sm">
                <div class="eyebrow mb-2">Bracket draw studio</div>
                <h2 class="font-weight-bold">Ready to mix things up?</h2>
                <p class="mb-0 text-light">Add one name per line, choose your bracket setup, then let the draw decide.</p>
            </div>

            <div class="row">
                <div class="col-lg-5">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-sliders-h mr-2"></i>Draw setup</h3>
                        </div>
                        <form id="randomizer-form" action="{{ route('randomizer.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="names">Names <span class="text-danger">*</span></label>
                                    <textarea class="form-control name-input @error('names') is-invalid @enderror" id="names" name="names" placeholder="One name per line&#10;Alex&#10;Sam&#10;Jordan">{{ old('names') }}</textarea>
                                    <small class="form-text text-muted">Use a unique name on every line.</small>
                                    @error('names') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-group">
                                    <label for="bracket_count">Number of brackets</label>
                                    <input type="number" min="1" max="100" class="form-control @error('bracket_count') is-invalid @enderror" id="bracket_count" name="bracket_count" value="{{ old('bracket_count', 2) }}" required>
                                    <small class="form-text text-muted">Names will be divided as evenly as possible. If there is an extra name, an earlier bracket will receive it.</small>
                                    @error('bracket_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="callout callout-info mb-0">
                                    <strong>Flexible draw</strong>
                                    <div class="small text-muted">Every bracket gets at least one name, and the distribution will be balanced.</div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" id="draw-button" class="btn btn-primary btn-lg btn-block">
                                    <i class="fas fa-random mr-2"></i>Start the draw
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-7 mt-4 mt-lg-0">
                    @if ($latestSession)
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i>Latest result</h3>
                                <span class="badge badge-light">{{ $latestSession->name_count }} names</span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach ($latestSession->entries->groupBy('bracket_number') as $bracketNumber => $entries)
                                        <div class="col-md-6 mb-3">
                                            <div class="card bracket-card h-100 mb-0">
                                                <div class="card-header bg-white">
                                                    <strong>Bracket {{ $bracketNumber }}</strong>
                                                    <span class="float-right text-muted small">{{ $entries->count() }} names</span>
                                                </div>
                                                <div class="list-group list-group-flush">
                                                    @foreach ($entries->sortBy('position') as $entry)
                                                        <div class="list-group-item d-flex align-items-center">
                                                            <span class="badge badge-primary mr-3">{{ $entry->position }}</span>
                                                            <span>{{ $entry->name }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="small text-muted mt-2">
                                    <i class="far fa-clock mr-1"></i>Generated {{ $latestSession->generated_at?->format('M d, Y · g:i A') }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center py-5">
                                <i class="fas fa-random fa-4x text-muted mb-3"></i>
                                <h3>No draws yet</h3>
                                <p class="text-muted mb-0">Your latest bracket result will appear here.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($sessions->total() > 0)
                <div class="card shadow-sm mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0"><i class="fas fa-history mr-2"></i>Previous draws</h3>
                        <span class="text-muted small">{{ $sessions->total() }} saved draws</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Generated</th><th>Brackets</th><th>Total names</th><th class="text-right">Actions</th></tr></thead>
                                <tbody>
                                    @foreach ($sessions as $session)
                                        <tr>
                                            <td>{{ $session->generated_at?->format('M d, Y · g:i A') }}</td>
                                            <td>{{ $session->bracket_count }}</td>
                                            <td>{{ $session->name_count }}</td>
                                            <td class="text-right text-nowrap">
                                                <a href="{{ route('randomizer.show', $session->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye mr-1"></i>View</a>
                                                <form action="{{ route('randomizer.destroy', $session->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this saved draw? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash mr-1"></i>Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if ($sessions->hasPages())
                        <div class="card-footer d-flex justify-content-center">
                            {{ $sessions->onEachSide(1)->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <div id="draw-overlay" class="draw-overlay position-fixed fixed-top w-100 h-100 d-none align-items-center justify-content-center text-center text-white">
        <div>
            <div class="position-relative d-inline-block mb-4">
                <i class="fas fa-circle-notch fa-4x text-info draw-orbit"></i>
                <span id="draw-number" class="draw-number position-absolute w-100 h-100 d-flex align-items-center justify-content-center font-weight-bold" style="top: 0; left: 0; font-size: 2.5rem;">3</span>
            </div>
            <h2 id="draw-status">Shuffling names...</h2>
            <p class="text-white-50 mb-0">Your brackets are being randomized</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('randomizer-form');
            const overlay = document.getElementById('draw-overlay');
            const number = document.getElementById('draw-number');
            const status = document.getElementById('draw-status');
            const drawButton = document.getElementById('draw-button');
            const bracketCount = document.getElementById('bracket_count');

            form.addEventListener('submit', function (event) {
                if (form.dataset.ready === 'true') return;

                event.preventDefault();
                drawButton.disabled = true;
                overlay.classList.remove('d-none');
                overlay.classList.add('d-flex');

                let countdown = 3;
                const timer = setInterval(function () {
                    countdown -= 1;
                    number.textContent = countdown > 0 ? countdown : '✓';
                    if (countdown === 1) status.textContent = 'Almost there...';
                    if (countdown === 0) {
                        clearInterval(timer);
                        status.textContent = 'Brackets locked!';
                        setTimeout(function () {
                            form.dataset.ready = 'true';
                            form.submit();
                        }, 500);
                    }
                }, 800);
            });
        });
    </script>
</x-main-layout>
