<x-main-layout>
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0">Open Play Sessions</h1>
                <p class="text-muted mb-0">Run rotating games independently from tournaments.</p>
            </div>
            <a href="{{ route('open-play.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> New Session</a>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Session</th><th>Date</th><th>Mode</th><th>Format</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($sessions as $session)
                                    <tr>
                                        <td><strong>{{ $session->name }}</strong></td>
                                        <td>{{ $session->session_date->format('M d, Y') }}</td>
                                        <td>{{ str_replace('_', ' ', \Illuminate\Support\Str::title($session->mode)) }}</td>
                                        <td>{{ \Illuminate\Support\Str::title($session->format) }}</td>
                                        <td><span class="badge badge-{{ $session->status === 'live' ? 'success' : ($session->status === 'ended' ? 'secondary' : 'info') }}">{{ \Illuminate\Support\Str::title($session->status) }}</span></td>
                                        <td>
                                            <a href="{{ route('open-play.show', $session) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                            <form method="POST" action="{{ route('open-play.destroy', $session) }}" class="d-inline" onsubmit="return confirm('Delete this session and all its matches, queue records, and standings?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No open play sessions yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($sessions->hasPages()) <div class="card-footer">{{ $sessions->links() }}</div> @endif
            </div>
        </div>
    </section>
</x-main-layout>
