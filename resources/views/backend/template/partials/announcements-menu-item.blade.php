@if(app(\App\Services\AnnouncementAudienceService::class)->canPublish(auth()->user()))
@php($announcementRoutePrefix = auth()->user()->access_level.'.announcements.')
<li class="nav-item">
  <a href="{{ route($announcementRoutePrefix.'index') }}" class="nav-link {{ request()->routeIs($announcementRoutePrefix.'*') ? 'active' : '' }}">
    <i class="fas fa-bullhorn nav-icon"></i>
    <p>Announcements</p>
  </a>
</li>
@endif
