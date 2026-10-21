<h1>Installation Registration</h1>
@if(session('update_notice'))<div class="alert alert-info" role="status">{{ session('update_notice') }}</div>@endif
@if(session('update_error'))<div class="alert alert-warning" role="alert">{{ session('update_error') }}</div>@endif
@if($errors->any())<div class="alert alert-warning" role="alert">{{ $errors->first() }}</div>@endif
@if(!empty($registration['error']))<p role="alert">Registration could not be read. Contact support to restore the original installation credentials.</p>
@elseif(!empty($registration['registered']))
    <p><strong>Status:</strong> Registered with CampaignManager.ng</p>
    <p><strong>Registered domain:</strong> {{ $registration['domain'] }}</p>
    <p><strong>Administrator email:</strong> {{ $registration['contact_email'] }}</p>
    <p>Registration is active.</p>
    @if(!empty($registration['receipt']['registered_at']) && strtotime($registration['receipt']['registered_at']))<p class="small">Registered {{ \Illuminate\Support\Carbon::parse($registration['receipt']['registered_at'])->format('j M Y') }}</p>@endif
    @if(!empty($verificationRequired) && empty($registration['contact_verified']))
        <div class="alert alert-info">A one-time email verification is needed before purchasing automatic updates. Open the confirmation email sent to {{ $registration['contact_email'] }}, then return here. Your installation remains available.</div>
        <form method="post" action="{{ $registrationActions['verify'] }}">@csrf
            @if(!empty($bootstrap))<input type="hidden" name="action" value="verify-contact">@endif
            <button class="btn btn-primary">Send Verification Email</button>
        </form>
        <p><a href="{{ $updatesUrl }}">Continue to Official Updates</a></p>
    @endif
    <form method="post" action="{{ $registrationActions['messages'] }}">@csrf
        @if(!empty($bootstrap))<input type="hidden" name="action" value="messages">@endif
        <button class="btn btn-outline-secondary">Check for Portal Messages</button>
    </form>
    <details class="mt-3"><summary>Manage Registration Details</summary>
        <p>Automatic-update purchases belong to the verified registered administrator email. Another Super Admin can install an update with active coverage, but must use the registered contact's Portal account when purchasing it.</p>
        <p>To change the registered administrator email, contact support from the current registered email. Editing a local user account does not transfer installation ownership.</p>
        @if(empty($registration['contact_verified']) && empty($verificationRequired))
            <form method="post" action="{{ $registrationActions['verify'] }}">@csrf
                @if(!empty($bootstrap))<input type="hidden" name="action" value="verify-contact">@endif
                <button class="btn btn-outline-secondary">Verify Administrator Email</button>
            </form>
        @endif
        @if($registrationDomain !== $registration['domain'])
            <p>The configured domain is {{ $registrationDomain }}. A domain change requires approval through the verified registered email.</p>
            <form method="post" action="{{ $registrationActions['domain'] }}">@csrf
                @if(!empty($bootstrap))<input type="hidden" name="action" value="domain-change">@endif
                <button class="btn btn-outline-secondary">Request Domain Change</button>
            </form>
        @endif
        @if(empty($bootstrap))
            <form method="post" action="{{ route('superadmin.deployment.preferences') }}">@csrf<input type="hidden" name="marketing_opt_in" value="0">
                <label><input type="checkbox" name="marketing_opt_in" value="1" @checked(!empty($registration['marketing_opt_in']))> Receive optional marketing communications</label>
                <button class="btn btn-outline-secondary">Save Preference</button>
            </form>
            <form method="post" action="{{ route('superadmin.deployment.revoke') }}">@csrf
                <label><input type="checkbox" required> I understand that removing registration disconnects this installation from Portal services.</label>
                <button class="btn btn-outline-danger">Remove Registration</button>
            </form>
        @endif
    </details>
@elseif(!empty($registration['pending_revoke']))
    <p>Registration removal is waiting for the Portal connection. Contact support if this does not complete.</p>
@else
    <p>This installation needs to be connected to CampaignManager.ng before automatic updates can be used.</p>
    <form method="post" action="{{ $registrationActions['connect'] }}">@csrf
        @if(!empty($bootstrap))<input type="hidden" name="action" value="register">@endif
        <p><label><input type="checkbox" name="registration_consent" value="1" required> I accept the <a href="https://campaignmanager.ng/community/privacy" target="_blank" rel="noopener noreferrer">licence and privacy notice</a>, including installation registration and essential software communications.</label></p>
        <button class="btn btn-primary">Connect Installation</button>
    </form>
@endif
@foreach($registration['messages'] ?? [] as $message)<div class="alert alert-info mt-3"><strong>{{ $message['title'] }}</strong><p style="white-space:pre-wrap">{{ $message['body'] }}</p></div>@endforeach
