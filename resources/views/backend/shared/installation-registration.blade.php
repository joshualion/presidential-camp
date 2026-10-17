@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid">
    @if(session('update_notice'))<div class="alert alert-info" role="status">{{ session('update_notice') }}</div>@endif
    @if(session('update_error'))<div class="alert alert-danger" role="alert">{{ session('update_error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    <div class="row"><div class="col-xl-9"><div class="card">
        <div class="card-header"><h3 class="card-title">Installation registration</h3><p class="cm-help mb-0 mt-1">Manage the contact and preferences for this installation.</p></div>
        <div class="card-body cm-registration">
            @if(!empty($registration['error']))<p>{{ $registration['error'] }}</p>@endif
            @if(!empty($registration['registered']))
                <p>Registered domain: {{ $registration['domain'] }}. Contact: {{ $registration['contact_email'] }}. {{ !empty($registration['contact_verified']) ? 'Contact confirmed.' : 'Contact confirmation pending.' }}</p>
                <form class="mb-3" method="post" action="{{ route('superadmin.deployment.resend') }}">@csrf<button class="btn btn-secondary">Resend contact confirmation</button></form>
                <form class="mb-3" method="post" action="{{ route('superadmin.deployment.domain-change') }}">@csrf<button class="btn btn-secondary">Request change to {{ $registrationDomain }}</button></form>
                <form class="mb-3" method="post" action="{{ route('superadmin.deployment.preferences') }}">@csrf<input type="hidden" name="marketing_opt_in" value="0"><label><input type="checkbox" name="marketing_opt_in" value="1" @checked(!empty($registration['marketing_opt_in']))> Receive optional marketing communications</label><div class="mt-2"></div><button class="btn btn-secondary">Save preference</button></form>
                <form class="mb-3" method="post" action="{{ route('superadmin.deployment.sync') }}">@csrf<button class="btn btn-secondary">Refresh Portal messages</button></form>
                <form class="cm-danger-zone" method="post" action="{{ route('superadmin.deployment.revoke') }}">@csrf<button class="btn btn-outline-danger">Remove registration and stop reporting</button></form>
            @elseif(!empty($registration['pending_revoke']))
                <p>Reporting has stopped. Portal deletion is pending until connectivity is restored.</p>
                <form class="mb-3" method="post" action="{{ route('superadmin.deployment.revoke') }}">@csrf<button class="btn btn-secondary">Retry Portal deletion</button></form>
            @else
                <p>Registration sends a deployment identifier, domain <strong>{{ $registrationDomain }}</strong>, Super Admin email <strong>{{ $profileData->email }}</strong>, installed version and installation completion status to Campaign Manager Portal. If registered, later checks report the installed version and successful update check status. Campaign supporter records, voter information and application passwords are not included.</p>
                <p>New v1.1.0 installations require free registration under the licence/privacy terms. Existing installations can complete this transition here without a data reset or remote lockout. Marketing remains optional.</p>
                <p><a href="https://campaignmanager.ng/community/privacy" target="_blank" rel="noopener noreferrer">Read the licence and privacy notice</a> — version 2026-10-12.</p>
                <form class="mb-3" method="post" action="{{ route('superadmin.deployment.register') }}">@csrf
                    <label><input type="checkbox" name="registration_consent" value="1" required> I accept the Community licence and privacy notice, including installation registration and essential software communications.</label><br>
                    <label><input type="checkbox" name="marketing_opt_in" value="1"> I also want optional product news and marketing communications.</label><br>
                    <button class="btn btn-secondary">Register this deployment</button>
                </form>
            @endif
            @foreach($registration['messages'] ?? [] as $message)<div class="alert alert-info mt-3"><strong>{{ $message['title'] }}</strong><p style="white-space:pre-wrap">{{ $message['body'] }}</p></div>@endforeach

        </div>
    </div></div></div>
</div></section>
@endsection
