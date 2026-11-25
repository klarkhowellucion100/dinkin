<x-main-layout>
    <div class="content-header"><div class="container-fluid"><h1 class="m-0">New Open Play Session</h1></div></div>
    <section class="content"><div class="container-fluid"><div class="row"><div class="col-lg-7"><div class="card">
        <form method="POST" action="{{ route('open-play.store') }}">
            @csrf
            <div class="card-body">
                <div class="form-group"><label>Session name</label><input name="name" class="form-control" value="{{ old('name', 'Open Play '.now()->format('M d')) }}" required></div>
                <div class="form-group"><label>Date</label><input type="date" name="session_date" class="form-control" value="{{ old('session_date', now()->toDateString()) }}" required></div>
                <div class="form-group"><label>Rotation mode</label><select name="mode" class="form-control"><option value="balanced">Balanced (recommended)</option><option value="winners_losers">Winners / Losers</option><option value="king_queen">King / Queen of the Court</option></select></div>
                <div class="form-group"><label>Format</label><select name="format" class="form-control"><option value="doubles">Doubles</option><option value="singles">Singles</option></select></div>
                <div class="form-group"><label>Courts</label><input type="number" name="court_count" min="1" max="64" class="form-control" value="{{ old('court_count', 1) }}" required></div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Create Session</button> <a href="{{ route('open-play.index') }}" class="btn btn-secondary">Cancel</a></div>
        </form>
    </div></div></div></div></section>
</x-main-layout>
