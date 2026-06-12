@php($agentsMenuOpen = request()->routeIs($profileData->access_level.'.agents.*'))
<li class="tab nav-item {{ $agentsMenuOpen ? 'menu-open' : 'menu-close' }}">
  <a href="#" class="nav-link {{ $agentsMenuOpen ? 'active' : '' }}">
    <i class="nav-icon fas fa-id-badge"></i>
    <p>
      Agents
      <i class="right fas fa-angle-left"></i>
    </p>
  </a>
  <ul class="nav nav-treeview">
    @include('backend.template.partials.agentMenuLinks')
  </ul>
</li>
