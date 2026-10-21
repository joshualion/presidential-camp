@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid"><div class="card"><div class="card-body">
@include('backend.shared.update-customer-card', [
 'automaticAction' => route('superadmin.updates.automatic'), 'confirmAction' => route('superadmin.updates.prepare'),
 'checkAction' => route('superadmin.updates.check'), 'registrationUrl' => route('superadmin.deployment.settings'),
 'manualUrl' => !empty($status['latest_version']) && preg_match('/^\d+\.\d+\.\d+$/D', $status['latest_version']) ? 'https://github.com/joshualion/Campaign-Manager/releases/tag/v'.$status['latest_version'] : 'https://github.com/joshualion/Campaign-Manager/releases',
 'paymentReturn' => true,
])
</div></div></div></section>
@endsection
