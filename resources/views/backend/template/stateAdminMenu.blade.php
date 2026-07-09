<style>
    .nav-treeview{
        background: #ffffff !important;
    }
</style>

@php
$state = \App\Models\State::find(Auth::user()->state_id);
@endphp

@if(Auth::user()->access_level == 'stateadmin')

<nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column sidebar-nav" data-widget="treeview" role="menu" data-accordion="false">

            <li class="tab nav-item menu-close">
                        <a href="{{ url('state/dashboard') }}" class="nav-link">
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
                            <a href="{{ url('state/members/fetch') }}/{{$state->uuid}}" class="nav-link">
                              <i class="fas fa-user nav-icon"></i>
                              <p>All Members</p>
                            </a>
                          </li>


                          <li class="nav-item">
                            <a href="{{ url('state/members/bylga') }}/{{$state->uuid}}" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Members by LGA</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="{{ url('state/members/byward') }}/{{$state->uuid}}" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Members By Wards</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="{{ url('state/members/bypu') }}/{{$state->uuid}}" class="nav-link">
                              <i class="fas fa-user nav-icon"></i>
                              <p>Members By PU</p>
                            </a>
                          </li>

                        </ul>
            </li>
            {{-- / Members --}}

            @include('backend.template.partials.agentMenu')

                   {{-- Election --}}
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
              <a href="{{ url('state/elections') }}" class="nav-link">
                <i class="fas fa-award nav-icon"></i>
                <p>Manage Elections</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ url('state/election/operations-center') }}" class="nav-link">
                <i class="fas fa-broadcast-tower nav-icon"></i>
                <p>Situation Room</p>
              </a>
            </li>
            @if(app(\App\Services\PollingUnitResultPermissionService::class)->userHasAnyApprovedAssignment($profileData))
            <li class="nav-item">
              <a href="{{ url('state/vote/add') }}" class="nav-link">
              <i class="fa fa-cloud nav-icon"></i>
                <p>Upload Polls Result</p>
              </a>
            </li>

            <li class="nav-item">
              <a href="{{ url('state/incident/add') }}" class="nav-link">
              <i class="fa fa-book nav-icon"></i>
                <p>Report  Incidents</p>
              </a>
            </li>
            @endif
          </ul>
</li>
{{-- / Election --}}



            {{-- Finances --}}
            {{-- <li class="tab nav-item menu-close">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-money-bill"></i>
                  <p>
                    Finances
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">

                <li class="nav-item">
                    <a href="{{ url('state/dashboard') }}" class="nav-link">
                      <i class="fas fa-credit-card nav-icon"></i>
                      <p>Expenses</p>
                    </a>
                  </li>



                  <li class="nav-item">
                    <a href="{{ url('state/state/dashboard') }}" class="nav-link">
                    <i class="fas fa-dollar-sign nav-icon"></i>
                      <p>Funding Request</p>
                    </a>
                  </li>

                </ul>
            </li> --}}
            {{-- / Finances --}}


            {{-- Events --}}
            {{-- <li class="tab nav-item menu-close">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-calendar"></i>
                  <p>
                    Events
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">

                <li class="nav-item">
                    <a href="{{ url('state/events') }}" class="nav-link">
                      <i class="fas fa-award nav-icon"></i>
                      <p>All Events</p>
                    </a>
                  </li>

                  <li class="nav-item">
                    <a href="{{ url('state/event/add') }}" class="nav-link">
                      <i class="fas fa-plus nav-icon"></i>
                      <p>Add Event</p>
                    </a>
                  </li>


                </ul>
           </li> --}}
            {{-- / Events --}}


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

                          <!-- <li class="nav-item">
                            <a href="#" class="nav-link">
                            <i class="fas fa-comment-alt nav-icon"></i>
                              <p>Live Chat</p>
                            </a>
                          </li> -->
                          <!-- <li class="nav-item">
                            <a href="#" class="nav-link">
                            <i class="fas fa-envelope-square nav-icon"></i>
                              <p> Send Email</p>
                            </a>
                          </li> -->

                          <!-- <li class="nav-item">
                            <a href="#" class="nav-link">
                            <i class="fas fa-broadcast-tower nav-icon"></i>
                              <p> Broadcast Message</p>
                            </a>
                          </li>  -->
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
                            <a href="{{ url('state/location/senatorial-districts') }}" class="nav-link">
                            <i class="fas fa-map nav-icon"></i>
                              <p>Senatorial Districts</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="{{ url('state/location/federal-constituencies') }}" class="nav-link">
                            <i class="fas fa-landmark nav-icon"></i>
                              <p>Federal Constituencies</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="{{ url('state/location/localgovernments') }}" class="nav-link">
                            <i class="fas fa-map-pin nav-icon"></i>
                              <p>LGA's</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="{{ url('state/location/wards') }}" class="nav-link">
                            <i class="fas fa-map-marked-alt nav-icon"></i>
                              <p>Wards</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="{{ url('state/location/pollingunits') }}" class="nav-link">
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
                            <a href="{{ url('state/profile') }}" class="nav-link">
                              <i class="fas fa-file nav-icon"></i>
                              <p> Manage Profile</p>
                            </a>
                          </li>



                          <li class="nav-item">
                            <a href="{{ url('state/change/password') }}" class="nav-link">
                            <i class="fas fa-lock nav-icon"></i>
                              <p>Change Password</p>
                            </a>
                          </li>



                        </ul>
            </li>
            {{-- End Profile Menu --}}

            {{-- Logout --}}
            <li class="nav-item">
                            <a href="{{ url('state/logout') }}" class="nav-link">
                            <i class="fas fa-power-off nav-icon"></i>
                              <p>Logout</p>
                            </a>
            </li>
            {{-- End Logout --}}

      </ul>
</nav>
@endif
