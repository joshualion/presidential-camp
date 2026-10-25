@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid"><div class="card"><div class="card-body">
@php($displayStatus = $confirmationReview['status'] ?? $updateStatus)
@include('backend.shared.update-customer-card', [
 'status' => $displayStatus, 'entitlement' => $confirmationReview['entitlement'] ?? null, 'issue' => $confirmationReview['issue'] ?? null,
 'automaticAction' => route('superadmin.updates.automatic'), 'confirmAction' => route('superadmin.updates.prepare'),
 'checkAction' => route('superadmin.updates.check'), 'registrationUrl' => route('superadmin.deployment.settings'),
 'manualUrl' => !empty($displayStatus['latest_version']) && preg_match('/^\d+\.\d+\.\d+$/D', $displayStatus['latest_version']) ? 'https://github.com/joshualion/Campaign-Manager/releases/tag/v'.$displayStatus['latest_version'] : 'https://github.com/joshualion/Campaign-Manager/releases',
])
</div></div></div></section>
@endsection
