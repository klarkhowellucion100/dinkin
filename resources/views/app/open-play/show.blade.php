<x-main-layout>
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0">{{ $session->name }}</h1>
                <p class="text-muted mb-0">
                    {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $session->mode)) }} ·
                    {{ \Illuminate\Support\Str::title($session->format) }} ·
                    {{ $session->court_count }} court(s)
                </p>
            </div>
            <a href="{{ route('open-play.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid" id="open-play-session-content">
            @include('app.open-play.partials.session-content')
        </div>
    </section>

    <script>
        document.addEventListener('submit', async function (event) {
            const form = event.target.closest('[data-ajax-form]');

            if (!form) {
                return;
            }

            event.preventDefault();
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    },
                    body: new FormData(form),
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'The action could not be completed.');
                }

                const modal = form.closest('.modal');
                if (modal && window.jQuery) {
                    window.jQuery(modal).modal('hide');
                }

                document.getElementById('open-play-session-content').innerHTML = payload.html;
                document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (error) {
                window.alert(error.message);
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    </script>
</x-main-layout>
