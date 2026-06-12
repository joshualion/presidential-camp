@php($agentPolicy = app(\App\Services\PollingUnitAgentApprovalPolicyService::class))

<li class="nav-item">
  <a href="{{ route($profileData->access_level.'.agents.request') }}" class="nav-link {{ request()->routeIs($profileData->access_level.'.agents.request') ? 'active' : '' }}">
    <i class="fas fa-user-check nav-icon"></i>
    <p>Agent Request</p>
  </a>
</li>
<li class="nav-item">
  <a href="{{ route($profileData->access_level.'.agents.status') }}" class="nav-link {{ request()->routeIs($profileData->access_level.'.agents.status') ? 'active' : '' }}">
    <i class="fas fa-clipboard-check nav-icon"></i>
    <p>My Agent Status</p>
  </a>
</li>
@if($agentPolicy->canNominate($profileData))
<li class="nav-item">
  <a href="{{ route($profileData->access_level.'.agents.index') }}" class="nav-link {{ request()->routeIs($profileData->access_level.'.agents.index') || request()->routeIs($profileData->access_level.'.agents.show') ? 'active' : '' }}">
    <i class="fas fa-users-cog nav-icon"></i>
    <p>Polling Unit Agents</p>
  </a>
</li>
<li class="nav-item">
  <a href="{{ route($profileData->access_level.'.agents.nominate') }}" class="nav-link {{ request()->routeIs($profileData->access_level.'.agents.nominate') ? 'active' : '' }}">
    <i class="fas fa-user-plus nav-icon"></i>
    <p>Nominate Agent</p>
  </a>
</li>
@endif
@if($agentPolicy->canDirectAssign($profileData))
<li class="nav-item">
  <a href="{{ route($profileData->access_level.'.agents.direct-assign') }}" class="nav-link {{ request()->routeIs($profileData->access_level.'.agents.direct-assign') ? 'active' : '' }}">
    <i class="fas fa-check-circle nav-icon"></i>
    <p>Direct Agent Assignment</p>
  </a>
</li>
@endif
