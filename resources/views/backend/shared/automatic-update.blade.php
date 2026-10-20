@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid"><div class="row"><div class="col-xl-8 col-lg-10">
<a href="{{ route('superadmin.updates') }}" class="d-inline-block mb-3">Back to Official Updates</a>
<div class="card"><div class="card-header"><h3 class="card-title">Automatic Update</h3></div><div class="card-body">
@if(session('update_error'))<div class="alert alert-warning" role="alert">{{ session('update_error') }}</div>@endif
<p>Installed version: <strong>{{ $installedVersion }}</strong>@if(!empty($status['latest_version'])) · Available version: <strong>{{ $status['latest_version'] }}</strong>@endif</p>
@if($issue)
<div class="alert alert-warning" role="alert">
<strong>Automatic updating needs attention</strong><p class="mb-0 mt-2">{{ $issue['message'] }}</p>
@if($issue['reason'] === 'registration')<a class="d-inline-block mt-2" href="{{ route('superadmin.deployment.settings') }}">Connect or confirm this installation</a>@endif
</div>
<p class="cm-help">Readiness checks do not change your database, install an update, or charge you.</p>
@elseif(!empty($entitlement['entitled']))
<div class="alert alert-success"><strong>Your automatic updates are covered.</strong>
@if(!empty($entitlement['coverage_expires_at']))<p class="mb-0">Coverage ends {{ \Illuminate\Support\Carbon::parse($entitlement['coverage_expires_at'])->format('j M Y') }}.</p>@endif
<p class="mb-0">No additional payment is needed for this update.</p></div>
<p>Campaign Manager will download and verify the package, pause the application briefly, back up the existing database and affected files, install the update, and run approved pending migrations.</p>
<form method="post" action="{{ route('superadmin.updates.prepare') }}" data-automatic-start>@csrf
<input type="hidden" name="version" value="{{ $status['latest_version'] }}">
<input type="hidden" name="sha256" value="{{ $status['manifest']['checksum_sha256'] }}">
<label class="d-flex align-items-start"><input type="checkbox" name="update_consent" value="1" required class="mt-1 mr-2"><span>I am ready for the backup and a short maintenance period.</span></label>
<button class="btn btn-primary">Back up and update to {{ $status['latest_version'] }}</button>
</form>
@elseif($entitlement)
<p>Automatic updating costs <strong>{{ $entitlement['currency'] }} {{ number_format($entitlement['price_minor']/100, 2) }}</strong> for {{ $entitlement['scope_label'] }}, {{ $entitlement['coverage_months'] ?? 12 }} months of updates for this installation. No automatic renewal.</p>
<a class="btn btn-primary" href="{{ $entitlement['checkout_url'] }}" target="_blank" rel="noopener">Continue to secure payment</a>
<p class="cm-help mt-3">After payment, return here and check coverage. The update package is downloaded only after coverage is confirmed and you start the update.</p>
@endif
<div class="cm-actions mt-3">
<a class="btn btn-outline-secondary" href="{{ route('superadmin.updates.automatic') }}">@if($entitlement && empty($entitlement['entitled']))I have paid — check coverage@else Check readiness and coverage again@endif</a>
<a href="https://github.com/joshualion/Campaign-Manager/releases" target="_blank" rel="noopener">Free manual download on GitHub</a>
</div>
@if(!empty($prepared['id']))
<details class="mt-3"><summary>Previously downloaded package</summary><p>Version {{ $prepared['version'] }} was staged earlier. It is not installed. A matching verified package can be reused after coverage is confirmed.</p>
<form method="post" action="{{ route('superadmin.updates.discard') }}">@csrf<input type="hidden" name="stage_id" value="{{ $prepared['id'] }}"><button class="btn btn-outline-secondary btn-sm">Remove prepared package</button></form></details>
@endif
</div></div></div></div></div></section>
@endsection
@push('scripts')
<script>
document.querySelector('[data-automatic-start]')?.addEventListener('submit', function () {
 const button=this.querySelector('button');button.disabled=true;button.textContent='Preparing your automatic update…';
});
</script>
@endpush
