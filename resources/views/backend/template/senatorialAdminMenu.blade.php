<style>
    .nav-treeview{
        background: #ffffff !important;
    }
</style>

@php
    $district = \App\Models\SenatorialDistrict::find(Auth::user()->senatorial_district_id);
@endphp

@if(Auth::user()->access_level == 'senatorialadmin')
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column sidebar-nav" data-widget="treeview" role="menu" data-accordion="false">
        <li class="tab nav-item menu-close">
            <a href="{{ url('senatorial/dashboard') }}" class="nav-link">
                <i class="nav-icon fas fa-home"></i>
                <p>
                    Dashboard
                    <i class="right fas fa-angle-left"></i>
                </p>
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
                    <a href="{{ url('senatorial/members/fetch') }}/{{ $district?->uuid }}" class="nav-link">
                        <i class="fas fa-user nav-icon"></i>
                        <p>All Members</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ url('senatorial/members/bylga') }}/{{ $district?->uuid }}" class="nav-link">
                        <i class="fas fa-user nav-icon"></i>
                        <p>Members by LGA</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ url('senatorial/members/byward') }}/{{ $district?->uuid }}" class="nav-link">
                        <i class="fas fa-user nav-icon"></i>
                        <p>Members By Wards</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ url('senatorial/members/bypu') }}/{{ $district?->uuid }}" class="nav-link">
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
                    <a href="{{ url('senatorial/elections') }}" class="nav-link">
                        <i class="fas fa-award nav-icon"></i>
                        <p>Manage Elections</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('senatorial/election/operations-center') }}" class="nav-link">
                        <i class="fas fa-broadcast-tower nav-icon"></i>
                        <p>Situation Room</p>
                    </a>
                </li>
                @if(app(\App\Services\PollingUnitResultPermissionService::class)->userHasAnyApprovedAssignment($profileData))
                <li class="nav-item">
                    <a href="{{ url('senatorial/vote/add') }}" class="nav-link">
                        <i class="fa fa-cloud nav-icon"></i>
                        <p>Upload Polls Result</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('senatorial/incident/add') }}" class="nav-link">
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

        {{-- Locations --}}
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
                    <a href="{{ url('senatorial/location/federal-constituencies') }}" class="nav-link">
                        <i class="fas fa-landmark nav-icon"></i>
                        <p>Federal Constituencies</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('senatorial/location/localgovernments') }}" class="nav-link">
                        <i class="fas fa-map-pin nav-icon"></i>
                        <p>LGA's</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('senatorial/location/wards') }}" class="nav-link">
                        <i class="fas fa-map-marked-alt nav-icon"></i>
                        <p>Wards</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('senatorial/location/pollingunits') }}" class="nav-link">
                        <i class="fas fa-monument nav-icon"></i>
                        <p>Polling Units</p>
                    </a>
                </li>
            </ul>
        </li>
        {{-- / Locations --}}

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
                    <a href="{{ url('senatorial/profile') }}" class="nav-link">
                        <i class="fas fa-file nav-icon"></i>
                        <p>Manage Profile</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('senatorial/change/password') }}" class="nav-link">
                        <i class="fas fa-lock nav-icon"></i>
                        <p>Change Password</p>
                    </a>
                </li>
            </ul>
        </li>
        {{-- End Profile Menu --}}

        {{-- Logout --}}
        <li class="nav-item">
            <a href="{{ url('senatorial/logout') }}" class="nav-link">
                <i class="fas fa-power-off nav-icon"></i>
                <p>Logout</p>
            </a>
        </li>
        {{-- End Logout --}}
    </ul>
</nav>
@endif
