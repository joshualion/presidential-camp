@extends('backend.template.backend-master')
@section('content')
<div class="content-wrapper">
    <section class="content-header"><h1>Official Updates</h1></section>
    <section class="content">
        <div class="card"><div class="card-body">
            <p>Installed version: <strong>{{ $installedVersion }}</strong></p>
            @if(session('update_notice'))<div class="alert alert-info">{{ session('update_notice') }}</div>@endif
            @if(session('update_error'))<div class="alert alert-danger">{{ session('update_error') }}</div>@endif
            @if(!empty($updateStatus))
                <p>Latest checked version: <strong>{{ $updateStatus['latest_version'] ?? 'Unknown' }}</strong></p>
                <p>{{ $updateStatus['message'] ?? '' }}</p>
                <p>Last successful check: {{ $updateStatus['checked_at'] ?? 'Not yet checked' }}</p>
                @if(!empty($updateStatus['available']))
                    <div class="alert alert-info">A newer official version is available.</div>
                    <a class="btn btn-secondary" href="https://github.com/joshualion/Campaign-Manager/releases" target="_blank" rel="noopener">Free manual download and release notes</a>
                @endif
            @endif
            <form method="post" action="{{ route('superadmin.updates.check') }}" class="mt-3">@csrf<button class="btn btn-primary">Check for updates</button></form>
            @if(!empty($updateStatus['available']) && !empty($updateStatus['verified']))
                <form method="post" action="{{ route('superadmin.updates.prepare') }}" class="mt-3">@csrf<button class="btn btn-outline-primary">Download and verify for review</button></form>
            @endif
            <p class="mt-3">Community software and official release downloads remain free. Update assistance is an optional paid service. Your current installation continues to work without a purchase or registration.</p>
            <p>Update checks retrieve public release information from GitHub. They do not send your domain, email or campaign records.</p>
            <p><a href="{{ rtrim(config('campaign.portal_web_base'), '/') }}/clientarea/updates" target="_blank" rel="noopener">Optional update assistance and credit balance</a></p>
            <hr><h2>Optional deployment registration</h2>
            @if(!empty($registration['error']))<p>{{ $registration['error'] }}</p>@endif
            @if(!empty($registration['registered']))
                <p>Registered domain: {{ $registration['domain'] }}. Contact: {{ $registration['contact_email'] }}. {{ !empty($registration['contact_verified']) ? 'Contact confirmed.' : 'Contact confirmation pending.' }}</p>
                <form method="post" action="{{ route('superadmin.deployment.preferences') }}">@csrf<input type="hidden" name="marketing_opt_in" value="0"><label><input type="checkbox" name="marketing_opt_in" value="1" @checked(!empty($registration['marketing_opt_in']))> Receive optional marketing communications</label><button class="btn btn-secondary">Save preference</button></form>
                <form method="post" action="{{ route('superadmin.deployment.sync') }}" class="mt-3">@csrf<button class="btn btn-secondary">Refresh Portal messages</button></form>
                <form method="post" action="{{ route('superadmin.deployment.revoke') }}" class="mt-3">@csrf<button class="btn btn-outline-danger">Remove registration and stop reporting</button></form>
            @elseif(!empty($registration['pending_revoke']))
                <p>Reporting has stopped. Portal deletion is pending until connectivity is restored.</p>
                <form method="post" action="{{ route('superadmin.deployment.revoke') }}">@csrf<button class="btn btn-secondary">Retry Portal deletion</button></form>
            @else
                <p>Registration sends a deployment identifier, domain <strong>{{ $registrationDomain }}</strong>, Super Admin email <strong>{{ $profileData->email }}</strong>, installed version and installation completion status to Campaign Manager Portal. If registered, later checks report the installed version and successful update check status. Campaign supporter records, voter information and application passwords are not included.</p>
                <p>Registration is optional and can be revoked. Operational registration and support messages are separate from marketing. It is not activation and is not required to use Community software or download free releases.</p>
                <form method="post" action="{{ route('superadmin.deployment.register') }}">@csrf
                    <label><input type="checkbox" name="registration_consent" value="1" required> I agree to this deployment registration and its operational communications.</label><br>
                    <label><input type="checkbox" name="marketing_opt_in" value="1"> I also want optional product news and marketing communications.</label><br>
                    <button class="btn btn-secondary">Register this deployment</button>
                </form>
            @endif
            @foreach($registration['messages'] ?? [] as $message)<div class="alert alert-info mt-3"><strong>{{ $message['title'] }}</strong><p style="white-space:pre-wrap">{{ $message['body'] }}</p></div>@endforeach
        </div></div>
    </section>
</div>
@endsection
