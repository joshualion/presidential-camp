@can('send-email-notifications')
@php($emailNotificationRoutePrefix = auth()->user()->access_level.'.email-notifications.')
<li class="nav-item">
    <a href="{{ route($emailNotificationRoutePrefix.'index') }}" class="nav-link {{ request()->routeIs($emailNotificationRoutePrefix.'*') ? 'active' : '' }}">
        <i class="fas fa-envelope nav-icon"></i>
        <p>Email Notifications</p>
    </a>
</li>
@endcan
