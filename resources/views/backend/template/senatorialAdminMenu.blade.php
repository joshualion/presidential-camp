@if(Auth::user()->access_level == 'senatorialadmin')
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column sidebar-nav" data-widget="treeview" role="menu" data-accordion="false">
        <li class="tab nav-item menu-close">
            <a href="/senatorial/dashboard" class="nav-link">
                <i class="nav-icon fas fa-home"></i>
                <p>Dashboard</p>
            </a>
        </li>
        <li class="tab nav-item menu-close">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-award"></i>
                <p>
                    Elections
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/senatorial/elections" class="nav-link">
                        <i class="fas fa-award nav-icon"></i>
                        <p>Manage Elections</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/senatorial/election/operations-center" class="nav-link">
                        <i class="fas fa-broadcast-tower nav-icon"></i>
                        <p>Situation Room</p>
                    </a>
                </li>
                @if(
                    $profileData->hasAnyRole([
                        'National ICT Director',
                        'Regional ICT Director',
                        'State ICT Director',
                        'Senatorial ICT Director',
                        'LGA ICT Director',
                        'Ward ICT Director',
                        'PU ICT Director',
                        'Pu Agent'
                    ]) || $profileData->access_level == 'superadmin' )
                <li class="nav-item">
                    <a href="/senatorial/vote/add" class="nav-link">
                        <i class="fa fa-cloud nav-icon"></i>
                        <p>Upload Polls Result</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/senatorial/incident/add" class="nav-link">
                        <i class="fa fa-book nav-icon"></i>
                        <p>Report Incidents</p>
                    </a>
                </li>
                @endif
            </ul>
        </li>
        <li class="tab nav-item menu-close">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-map"></i>
                <p>
                    Drill Downs
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/senatorial/dashboard" class="nav-link">
                        <i class="fas fa-layer-group nav-icon"></i>
                        <p>District to LGAs</p>
                    </a>
                </li>
            </ul>
        </li>
        {{-- Profile Menu --}}
        <li class="tab nav-item menu-close">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-user-shield"></i>
                <p>
                    Profile
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/senatorial/profile" class="nav-link">
                        <i class="fas fa-file nav-icon"></i>
                        <p>Manage Profile</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/senatorial/change/password" class="nav-link">
                        <i class="fas fa-lock nav-icon"></i>
                        <p>Change Password</p>
                    </a>
                </li>
            </ul>
        </li>
        {{-- End Profile Menu --}}

        {{-- Logout --}}
        <li class="nav-item">
            <a href="/senatorial/logout" class="nav-link">
                <i class="fas fa-power-off nav-icon"></i>
                <p>Logout</p>
            </a>
        </li>
        {{-- End Logout --}}
    </ul>
</nav>
@endif
