@php
 $directSmsAccess = app(\App\Services\Sms\Direct\DirectSmsAccess::class);
@endphp
@if($directSmsAccess->enabled($profileData) && app(\App\Services\Sms\SmsAddonAccess::class)->allowed(false))
<li class="nav-item"><a class="nav-link" href="{{ route($profileData->access_level.'.direct-sms.index') }}"><i class="nav-icon fas fa-sms"></i><p>SMS messages</p></a></li>

@endif
@if(config('direct_sms.portal_visible') && app(\App\Services\Sms\SmsAddonAccess::class)->allowed(false))
@php
    $smsAccess = app(\App\Services\Sms\SmsAccessService::class);
    $smsConfigured = \Illuminate\Support\Facades\Schema::hasColumn('system_settings', 'portal_sms_enabled')
        && (bool) optional(\App\Models\SystemSetting::first())->portal_sms_enabled;
    $smsRoutePrefix = $profileData->access_level.'.sms.';
@endphp
@if($smsConfigured && $smsAccess->allows($profileData, 'sms.view'))
    <li class="nav-item {{ request()->routeIs($smsRoutePrefix.'*') ? 'menu-open' : '' }}"><a href="#" class="nav-link"><i class="nav-icon fas fa-sms"></i><p>BulkSMS <i class="right fas fa-angle-left"></i></p></a><ul class="nav nav-treeview">
        <li class="nav-item"><a class="nav-link" href="{{ route($smsRoutePrefix.'dashboard') }}"><i class="far fa-circle nav-icon"></i><p>SMS Dashboard</p></a></li>
        @if($smsAccess->allows($profileData, 'sms.compose'))<li class="nav-item"><a class="nav-link" href="{{ route($smsRoutePrefix.'compose') }}"><i class="far fa-circle nav-icon"></i><p>Compose SMS</p></a></li>@endif
        @if($smsAccess->allows($profileData, 'sms.wallet.view'))<li class="nav-item"><a class="nav-link" href="{{ route($smsRoutePrefix.'wallet') }}"><i class="far fa-circle nav-icon"></i><p>My SMS Wallet</p></a></li>@endif
        @if($smsAccess->allows($profileData, 'sms.organization_wallet.view'))<li class="nav-item"><a class="nav-link" href="{{ route($smsRoutePrefix.'organization-wallet') }}"><i class="far fa-circle nav-icon"></i><p>Organization Wallet</p></a></li>@endif
        <li class="nav-item"><a class="nav-link" href="{{ route($smsRoutePrefix.'sender-ids') }}"><i class="far fa-circle nav-icon"></i><p>Sender IDs</p></a></li>
        @if($smsAccess->allows($profileData, 'sms.reports'))<li class="nav-item"><a class="nav-link" href="{{ route($smsRoutePrefix.'batches') }}"><i class="far fa-circle nav-icon"></i><p>SMS Reports</p></a></li>@endif
    </ul></li>

@endif

@endif
