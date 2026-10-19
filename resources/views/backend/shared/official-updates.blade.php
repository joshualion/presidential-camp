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
                @if($activeJob)
                <div class="alert {{ in_array($activeJob['state'], ['recovery_required','failed']) ? 'alert-warning' : 'alert-info' }} mt-3" id="update-job" role="status" aria-live="polite"><strong id="update-job-message">{{ $activeJob['message'] }}</strong><div class="small mt-1">{{ $activeJob['source_version'] }} → {{ $activeJob['target_version'] }}</div></div>
                @endif
                <div id="update-status" role="status" aria-live="polite">
                @if($preparedUpdate)
                    <div class="alert alert-info mt-3"><strong>Version {{ $preparedUpdate['version'] }} is ready for review.</strong><p class="mb-0 mt-1">The package has been downloaded and verified. Your installed version is still {{ $installedVersion }}.</p></div>
                    @if($automaticError)<div class="alert alert-warning">{{ $automaticError }} <a href="{{ route('superadmin.deployment.settings') }}">Installation settings</a></div>
                    @elseif(!empty($entitlement['entitled']))
                        <form method="post" action="{{ route('superadmin.updates.apply') }}" data-update-prepare>@csrf<input type="hidden" name="stage_id" value="{{ $preparedUpdate['id'] }}">
                            <label class="d-flex align-items-start"><input type="checkbox" name="update_consent" value="1" required class="mt-1 mr-2"><span>I’m ready for a short maintenance period. Back up this installation, apply the verified files and run approved pending migrations.</span></label>
                            <button class="btn btn-primary">Update safely to {{ $preparedUpdate['version'] }}</button>
                        </form><p class="cm-help mt-2">Your automatic update is covered.@if(!empty($entitlement['coverage_expires_at'])) Coverage ends {{ \Illuminate\Support\Carbon::parse($entitlement['coverage_expires_at'])->format('j M Y') }}.@endif No additional payment is needed for this update.</p>
                    @elseif($entitlement)
                        <p>Automatic updates cost <strong>{{ $entitlement['currency'] }} {{ number_format($entitlement['price_minor']/100, 2) }}</strong> for {{ $entitlement['scope_label'] }}, {{ $entitlement['coverage_months'] ?? 12 }} months of updates for this installation.</p>
                        <a class="btn btn-primary" href="{{ $entitlement['checkout_url'] }}" target="_blank" rel="noopener">Continue to secure payment</a>
                        <p class="cm-help mt-2">After payment, return here and check again. Your update includes verified backups and approved pending migrations.</p>
                    @endif
                    @if(!empty($preparedUpdate['id']))<details class="mt-3"><summary>Prepared package options</summary><form method="post" action="{{ route('superadmin.updates.discard') }}" class="mt-2">@csrf<input type="hidden" name="stage_id" value="{{ $preparedUpdate['id'] }}"><button class="btn btn-outline-secondary btn-sm">Discard prepared package</button></form></details>@endif
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
    @if($activeJob && !in_array($activeJob['state'], ['completed','failed','recovered','recovery_required']))
    button.disabled = true;
    const poll = async function () {
        try {
            const response = await fetch(@json(url('/campaign-update-status.php').'?job='.$activeJob['id']), {credentials:'same-origin',cache:'no-store'});
            if (!response.ok) throw new Error('Status unavailable');
            const status = await response.json();
            document.getElementById('update-job-message').textContent = status.message;
            if (['completed','failed','recovered'].includes(status.state)) { window.location.reload(); return; }
            if (status.state === 'recovery_required') { document.getElementById('update-job').className='alert alert-warning mt-3'; return; }
        } catch (e) { document.getElementById('update-job-message').textContent='The updater is running. If progress does not return, ask your hosting operator to inspect the private update journal.'; }
        setTimeout(poll, 3000);
    }; poll();
    @elseif($shouldCheck) check(); @endif
    const prepare = document.querySelector('[data-update-prepare]');
    if (prepare) prepare.addEventListener('submit', function () {
        const button = prepare.querySelector('button'); button.disabled = true; button.textContent = 'Downloading and verifying…';
    });
})();
</script>
@endpush
