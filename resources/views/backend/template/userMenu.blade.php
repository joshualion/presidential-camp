@php($memberCapabilities = app(\App\Services\MemberCapabilityService::class))

@if($memberCapabilities->isMember($profileData))
<nav class="mt-2">
  <ul class="nav nav-pills nav-sidebar flex-column sidebar-nav" data-widget="treeview" role="menu" data-accordion="false">
    @if($memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::DASHBOARD))
    <li class="nav-item">
      <a href="{{ route('user.dashboard') }}" class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
        <i class="nav-icon fas fa-home"></i>
        <p>Dashboard</p>
      </a>
    </li>
    @endif

    @if($memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::AGENTS))
      @include('backend.template.partials.agentMenu')
    @endif

    @if(
      $memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::COMMUNITY)
      || $memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::MESSAGING)
    )
    <li class="tab nav-item {{ request()->routeIs('timeline', $profileData->access_level.'.messages.*') ? 'menu-open' : 'menu-close' }}">
      <a href="#" class="nav-link {{ request()->routeIs('timeline', $profileData->access_level.'.messages.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-comments"></i>
        <p>
          Communication
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        @if($memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::COMMUNITY))
        <li class="nav-item">
          <a href="{{ route('timeline') }}" class="nav-link {{ request()->routeIs('timeline') ? 'active' : '' }}">
            <i class="fas fa-users nav-icon"></i>
            <p>Community Forum</p>
          </a>
        </li>
        @endif

        @if($memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::MESSAGING))
        <li class="nav-item">
          <a href="{{ route($profileData->access_level.'.messages.page') }}" class="nav-link {{ request()->routeIs($profileData->access_level.'.messages.*') ? 'active' : '' }}">
            <i class="fas fa-envelope nav-icon"></i>
            <p>Internal Communication</p>
          </a>
        </li>
        @endif
      </ul>
    </li>
    @endif

    @if(
      $memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::ELECTION_REPORTS)
      || $memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::ELECTION_SUBMISSION)
    )
    <li class="tab nav-item {{ request()->routeIs('user.elections*') ? 'menu-open' : 'menu-close' }}">
      <a href="#" class="nav-link {{ request()->routeIs('user.elections*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-vote-yea"></i>
        <p>Elections<i class="right fas fa-angle-left"></i></p>
      </a>
      <ul class="nav nav-treeview">
        @if($memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::ELECTION_REPORTS))
        <li class="nav-item">
          <a href="{{ route('user.elections') }}" class="nav-link {{ request()->routeIs('user.elections') || request()->routeIs('user.elections.results') ? 'active' : '' }}">
            <i class="fas fa-chart-bar nav-icon"></i><p>Election Results</p>
          </a>
        </li>
        @endif
        @if($memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::ELECTION_SUBMISSION))
        <li class="nav-item">
          <a href="{{ route('user.elections.workspace') }}" class="nav-link {{ request()->routeIs('user.elections.workspace', 'user.elections.result.*', 'user.elections.incident.*') ? 'active' : '' }}">
            <i class="fas fa-clipboard-check nav-icon"></i><p>Agent Workspace</p>
          </a>
        </li>
        @endif
      </ul>
    </li>
    @endif

    @if($memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::NOTICE_BOARD))
    <li class="nav-item">
      <a href="{{ route('user.notices') }}" class="nav-link {{ request()->routeIs('user.notices*') ? 'active' : '' }}">
        <i class="fas fa-bullhorn nav-icon"></i>
        <p>Notice Board</p>
      </a>
    </li>
    @endif

    @if(
      $memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::VOTING_BLOCK)
      || $memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::REFERRALS)
    )
    <li class="tab nav-item {{ request()->routeIs('user.block', 'user.referrals') ? 'menu-open' : 'menu-close' }}">
      <a href="#" class="nav-link {{ request()->routeIs('user.block', 'user.referrals') ? 'active' : '' }}">
        <i class="nav-icon fas fa-project-diagram"></i>
        <p>
          Voting Bloc
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        @if($memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::VOTING_BLOCK))
        <li class="nav-item">
          <a href="{{ route('user.block') }}" class="nav-link {{ request()->routeIs('user.block') ? 'active' : '' }}">
            <i class="fas fa-sitemap nav-icon"></i>
            <p>My Voting Bloc</p>
          </a>
        </li>
        @endif
        @if($memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::REFERRALS))
        <li class="nav-item">
          <a href="{{ route('user.referrals') }}" class="nav-link {{ request()->routeIs('user.referrals') ? 'active' : '' }}">
            <i class="fas fa-user-plus nav-icon"></i>
            <p>Direct Referrals</p>
          </a>
        </li>
        @endif
      </ul>
    </li>
    @endif

    @if(
      $memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::PROFILE)
      || $memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::ACCOUNT_SECURITY)
    )
    <li class="tab nav-item {{ request()->routeIs('user.profile', 'user.change.password') ? 'menu-open' : 'menu-close' }}">
      <a href="#" class="nav-link {{ request()->routeIs('user.profile', 'user.change.password') ? 'active' : '' }}">
        <i class="nav-icon fas fa-user-shield"></i>
        <p>
          Profile
          <i class="right fas fa-angle-left"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        @if($memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::PROFILE))
        <li class="nav-item">
          <a href="{{ route('user.profile') }}" class="nav-link {{ request()->routeIs('user.profile') ? 'active' : '' }}">
            <i class="fas fa-file nav-icon"></i>
            <p>Manage Profile</p>
          </a>
        </li>
        @endif

        @if($memberCapabilities->allows($profileData, \App\Services\MemberCapabilityService::ACCOUNT_SECURITY))
        <li class="nav-item">
          <a href="{{ route('user.change.password') }}" class="nav-link {{ request()->routeIs('user.change.password') ? 'active' : '' }}">
            <i class="fas fa-lock nav-icon"></i>
            <p>Change Password</p>
          </a>
        </li>
        @endif
      </ul>
    </li>
    @endif

    <li class="nav-item">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="nav-link btn btn-link text-left w-100">
          <i class="fas fa-power-off nav-icon"></i>
          <p>Logout</p>
        </button>
      </form>
    </li>
  </ul>
</nav>
@endif
