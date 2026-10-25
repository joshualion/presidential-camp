@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid"><div class="card"><div class="card-body">
@include('backend.shared.update-customer-card', [
 'status' => $confirmationReview['status'] ?? $updateStatus, 'entitlement' => $confirmationReview['entitlement'] ?? null, 'issue' => $confirmationReview['issue'] ?? null,
 'automaticAction' => route('superadmin.updates.automatic'), 'confirmAction' => route('superadmin.updates.prepare'),
 'checkAction' => route('superadmin.updates.check'), 'registrationUrl' => route('superadmin.deployment.settings'),
 'manualUrl' => !empty($updateStatus['latest_version']) && preg_match('/^\d+\.\d+\.\d+$/D', $updateStatus['latest_version']) ? 'https://github.com/joshualion/Campaign-Manager/releases/tag/v'.$updateStatus['latest_version'] : 'https://github.com/joshualion/Campaign-Manager/releases',
])
</div></div></div></section>
@endsection
