@if(Auth::user()->access_level == 'federaladmin')
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column sidebar-nav" data-widget="treeview" role="menu" data-accordion="false">
        <li class="tab nav-item menu-close">
            <a href="{{ url('federal/dashboard') }}" class="nav-link">
                <i class="nav-icon fas fa-home"></i>
                <p>Dashboard</p>
            </a>
        </li>
        {{-- Members --}}
        <li class="tab nav-item menu-close">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-users"></i>
                <p>
                    Members
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="{{ url('federal/members/fetch') }}/{{ $profileData->federalConstituency?->uuid ?? '' }}" class="nav-link">
                        <i class="fas fa-user nav-icon"></i>
                        <p>All Members</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('federal/members/bylga') }}/{{ $profileData->federalConstituency?->uuid ?? '' }}" class="nav-link">
                        <i class="fas fa-user nav-icon"></i>
                        <p>Members by LGA</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('federal/members/byward') }}/{{ $profileData->federalConstituency?->uuid ?? '' }}" class="nav-link">
                        <i class="fas fa-user nav-icon"></i>
                        <p>Members By Wards</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('federal/members/bypu') }}/{{ $profileData->federalConstituency?->uuid ?? '' }}" class="nav-link">
                        <i class="fas fa-user nav-icon"></i>
                        <p>Members By PU</p>
                    </a>
                </li>

            </ul>
        </li>
        {{-- / Members --}}

        @include('backend.template.partials.agentMenu')

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
                    <a href="{{ url('federal/elections') }}" class="nav-link">
                        <i class="fas fa-award nav-icon"></i>
                        <p>Manage Elections</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('federal/election/operations-center') }}" class="nav-link">
                        <i class="fas fa-broadcast-tower nav-icon"></i>
                        <p>Situation Room</p>
                    </a>
                </li>
                @if(app(\App\Services\PollingUnitResultPermissionService::class)->userHasAnyApprovedAssignment($profileData))
                <li class="nav-item">
                    <a href="{{ url('federal/vote/add') }}" class="nav-link">
                        <i class="fa fa-cloud nav-icon"></i>
                        <p>Upload Polls Result</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('federal/incident/add') }}" class="nav-link">
                        <i class="fa fa-book nav-icon"></i>
                        <p>Report Incidents</p>
                    </a>
                </li>
                @endif
            </ul>
        </li>

        {{-- Messaging --}}
        <li class="tab nav-item menu-close">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-comments"></i>
                <p>
                    Messaging
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="{{ url('messages') }}" class="nav-link">
                        <i class="fas fa-comments nav-icon"></i>
                        <p>Internal Communication</p>
                    </a>
                </li>
                @include('backend.template.partials.email-notifications-menu-item')
            </ul>
        </li>
        {{-- / Messaging --}}
        @include('backend.template.partials.sms-menu')
        @include('backend.template.partials.announcements-menu-item')

        <li class="tab nav-item menu-close">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-globe-africa"></i>
                <p>
                    Locations
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="{{ url('federal/location/localgovernments') }}" class="nav-link">
                        <i class="fas fa-map-pin nav-icon"></i>
                        <p>LGA's</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('federal/location/wards') }}" class="nav-link">
                        <i class="fas fa-map-marked-alt nav-icon"></i>
                        <p>Wards</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('federal/location/pollingunits') }}" class="nav-link">
                        <i class="fas fa-monument nav-icon"></i>
                        <p>Polling Units</p>
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
                    <a href="{{ url('federal/profile') }}" class="nav-link">
                        <i class="fas fa-file nav-icon"></i>
                        <p>Manage Profile</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('federal/change/password') }}" class="nav-link">
                        <i class="fas fa-lock nav-icon"></i>
                        <p>Change Password</p>
                    </a>
                </li>
            </ul>
        </li>
        {{-- End Profile Menu --}}

        {{-- Logout --}}
        <li class="nav-item">
            <a href="{{ url('federal/logout') }}" class="nav-link">
                <i class="fas fa-power-off nav-icon"></i>
                <p>Logout</p>
            </a>
        </li>
        {{-- End Logout --}}
    </ul>
</nav>
@endif
