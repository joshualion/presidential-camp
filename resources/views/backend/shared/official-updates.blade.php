@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid">
    <div class="row"><div class="col-xl-8 col-lg-10">
        @if(session('update_notice'))<div class="alert alert-info" role="status">{{ session('update_notice') }}</div>@endif
        @if(session('update_error'))<div class="alert alert-danger" role="alert">{{ session('update_error') }}</div>@endif
        <div class="alert alert-warning d-none" id="update-check-error" role="alert">We could not check for updates. Check your server connection and try again.</div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Official Updates</h3><p class="cm-help mb-0 mt-1">Keep your Campaign Manager installation up to date.</p></div>
            <div class="card-body">
                <span class="cm-muted">Installed version</span>
                <div class="cm-version">{{ $installedVersion }}</div>
                <div id="update-status" role="status" aria-live="polite">
                @if($preparedUpdate)
                    <div class="alert alert-info mt-3"><strong>Version {{ $preparedUpdate['version'] }} is ready for review.</strong><p class="mb-0 mt-1">The package has been downloaded and verified. Your installed version is still {{ $installedVersion }}.</p></div>
                    <p class="cm-help">Apply the verified package using the release installation guide after backing up your application and database. Automatic installation will be added separately.</p>
                @elseif(!empty($updateStatus['available']))
                    <div class="alert alert-info mt-3"><strong>Version {{ $updateStatus['latest_version'] }} is available.</strong></div>
                    @if(!empty($updateStatus['verified']))
                        <form method="post" action="{{ route('superadmin.updates.prepare') }}" class="mb-3" data-update-prepare>@csrf
                            <button class="btn btn-primary"><i class="fas fa-download mr-2" aria-hidden="true"></i>Download and verify {{ $updateStatus['latest_version'] }}</button>
                        </form>
                        <p class="cm-help">This safely prepares the update for review. It does not install it or change your application data.</p>
                    @else
                        <p class="cm-help">This release cannot be prepared automatically because verified update metadata is unavailable. Use the official manual download.</p>
                        <a class="btn btn-primary mb-3" href="https://github.com/joshualion/Campaign-Manager/releases" target="_blank" rel="noopener">Download from official releases</a>
                    @endif
                @elseif(!empty($updateStatus['checked_at']))
                    <div class="alert alert-success mt-3"><strong>You are up to date.</strong><p class="mb-0 mt-1">No newer official release was found during the last successful check.</p></div>
                @else
                    <p class="cm-help mt-3">Checking for the latest official release…</p>
                @endif
                </div>
                <div class="cm-actions mt-3">
                    <form method="post" action="{{ route('superadmin.updates.check') }}" id="update-check-form">@csrf<button class="btn btn-outline-secondary" id="update-check-button">Check again</button></form>
                    <a href="https://github.com/joshualion/Campaign-Manager/releases" target="_blank" rel="noopener" class="small">Release notes and installation guide <span class="sr-only">(opens in a new tab)</span></a>
                </div>
                <p class="cm-help mt-3 mb-0">Last successful check: {{ !empty($updateStatus['checked_at']) && strtotime($updateStatus['checked_at']) ? \Illuminate\Support\Carbon::parse($updateStatus['checked_at'])->timezone(config('app.timezone'))->format('j M Y, H:i T') : 'Not yet checked' }}. Official software downloads are free.</p>
            </div>
        </div>
    </div></div>
</div></section>
@endsection
@push('scripts')
<script>
(function () {
    const form = document.getElementById('update-check-form');
    const button = document.getElementById('update-check-button');
    async function check(event) {
        if (event) event.preventDefault();
        if (button.disabled) return;
        button.disabled = true;
        button.textContent = 'Checking…';
        document.getElementById('update-check-error').classList.add('d-none');
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (!response.ok) throw new Error('Check failed');
            window.location.reload();
        } catch (error) {
            document.getElementById('update-check-error').classList.remove('d-none');
            button.disabled = false;
            button.textContent = 'Try again';
        }
    }
    form.addEventListener('submit', check);
    @if($shouldCheck) check(); @endif
    const prepare = document.querySelector('[data-update-prepare]');
    if (prepare) prepare.addEventListener('submit', function () {
        const button = prepare.querySelector('button'); button.disabled = true; button.textContent = 'Downloading and verifying…';
    });
})();
</script>
@endpush
