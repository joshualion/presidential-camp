@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid"><div class="card"><div class="card-body">
@include('backend.shared.update-registration-card', [
 'registrationActions' => ['connect' => route('superadmin.deployment.register'), 'verify' => route('superadmin.deployment.resend'), 'messages' => route('superadmin.deployment.sync'), 'domain' => route('superadmin.deployment.domain-change')],
 'updatesUrl' => route('superadmin.updates'),
])
</div></div></div></section>
@endsection
