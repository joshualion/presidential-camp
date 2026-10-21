@if(session('update_notice'))<div class="alert alert-info" role="status">{{ session('update_notice') }}</div>@endif
@if(session('update_error'))<div class="alert alert-warning" role="alert">{{ session('update_error') }}</div>@endif
@if($errors->any())<div class="alert alert-warning" role="alert">{{ $errors->first() }}</div>@endif
<h1>Official Updates</h1>
<p>Installed version: <strong>{{ $installedVersion }}</strong></p>
@if(!empty($activeJob) && !in_array($activeJob['state'], ['completed','failed','recovered']))
    <div class="alert alert-info" role="status">{{ $activeJob['state'] === 'recovery_required' ? 'The update needs assistance. Your backups have been kept. Contact support before trying again.' : 'Your update is in progress. Please wait for completion.' }}</div>
    <p><a href="{{ !empty($bootstrap) ? url('/campaign-update-bootstrap.php').'?progress=1' : route('superadmin.updates', ['progress' => 1]) }}">View Update Progress</a></p>
@elseif(!empty($status['available']))
    <p>Available version: <strong>{{ $status['latest_version'] }}</strong></p>
    @if(!empty($issue))
        <div class="alert alert-warning" role="alert">{{ $issue['message'] }}</div>
        @if($issue['reason'] === 'registration')<p><a href="{{ $registrationUrl }}">Connect Installation</a></p>@endif
    @endif
    @if(!empty($entitlement['entitled']) && empty($issue))
        <div class="alert alert-success">Your automatic-update coverage is active. No additional payment is needed.</div>
        <p>Confirm when you are ready. Campaign Manager will back up your installation and briefly pause access while installing version {{ $status['latest_version'] }}.</p>
        <form method="post" action="{{ $confirmAction }}" data-update-submit>@csrf
            @if(!empty($bootstrap))<input type="hidden" name="action" value="prepare">@endif
            <input type="hidden" name="version" value="{{ $status['latest_version'] }}">
            <input type="hidden" name="sha256" value="{{ $status['manifest']['checksum_sha256'] }}">
            <label><input type="checkbox" name="update_consent" value="1" required> I am ready to install this update and temporarily pause access.</label>
            <p><button class="btn btn-primary">Confirm and Install Update</button></p>
        </form>
    @else
        @if(!empty($paymentReturn) && empty($issue))<p role="status">Payment has not been confirmed yet. Pending bank transfers must be approved before updating.</p>@endif
        <form method="post" action="{{ $automaticAction }}" data-update-submit style="display:inline-block">@csrf
            @if(!empty($bootstrap))<input type="hidden" name="action" value="automatic">@endif
            <button class="btn btn-primary">Update Automatically</button>
        </form>
    @endif
    <a class="btn btn-outline-secondary" href="{{ $manualUrl }}" target="_blank" rel="noopener noreferrer">Manual Update</a>
    <p class="small mt-3">Manual downloads are free. <a href="https://github.com/joshualion/Campaign-Manager/blob/main/docs/upgrading.md" target="_blank" rel="noopener noreferrer">Manual upgrade instructions</a></p>
@elseif(!empty($status['checked_at']))
    <p role="status">Your installation is up to date.</p>
@else
    <p>Check for the latest Campaign Manager release.</p>
@endif
<form method="post" action="{{ $checkAction }}" data-update-submit class="mt-3">@csrf
    @if(!empty($bootstrap))<input type="hidden" name="action" value="check">@endif
    <button class="btn {{ empty($status['available']) ? 'btn-primary' : 'btn-outline-secondary' }}">Check for Updates</button>
</form>
<script>document.querySelectorAll('[data-update-submit]').forEach(form => form.addEventListener('submit', () => { const button=form.querySelector('button'); button.disabled=true; button.textContent='Please wait…'; }));</script>
