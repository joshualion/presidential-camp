<style>
    .nav-treeview{
        background: #ffffff !important;
    }
</style>

@php
$ward = \App\Models\Ward::find(Auth::user()->ward_id);
@endphp

@if(Auth::user()->access_level == 'wardadmin')

<nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column sidebar-nav" data-widget="treeview" role="menu" data-accordion="false">

            <li class="tab nav-item menu-close">
                        @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/dashboard')))
<a href="{{ url('ward/dashboard') }}" class="nav-link">
                          <i class="nav-icon fas fa-home"></i>
                          <p>
                            Dashboard
                            <i class="right fas fa-angle-left"></i>
                          </p>
                        </a>
@endif


            </li>

            @if($ward)
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
                            <a href="{{ url('ward/members/fetch') }}/{{$ward->uuid}}" class="nav-link">
                              <i class="fas fa-user nav-icon"></i>
                              <p>All Members</p>
                            </a>
                          </li>



                          <li class="nav-item">
                            <a href="{{ url('ward/members/bypu') }}/{{$ward->uuid}}" class="nav-link">
                              <i class="fas fa-user nav-icon"></i>
                              <p>Members By PU</p>
                            </a>
                          </li>

                        </ul>
            </li>
            {{-- / Members --}}
            @endif

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
              @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/elections')))
<a href="{{ url('ward/elections') }}" class="nav-link">
                <i class="fas fa-award nav-icon"></i>
                <p>Manage Elections</p>
              </a>
@endif
            </li>
            <li class="nav-item">
              @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/election/operations-center')))
<a href="{{ url('ward/election/operations-center') }}" class="nav-link">
                <i class="fas fa-broadcast-tower nav-icon"></i>
                <p>Situation Room</p>
              </a>
@endif
            </li>
            @if(app(\App\Services\PollingUnitResultPermissionService::class)->userHasAnyApprovedAssignment($profileData))
            <li class="nav-item">
              @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/vote/add')))
<a href="{{ url('ward/vote/add') }}" class="nav-link">
              <i class="fa fa-cloud nav-icon"></i>
                <p>Upload Polls Result</p>
              </a>
@endif
            </li>

            <li class="nav-item">
              @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/incident/add')))
<a href="{{ url('ward/incident/add') }}" class="nav-link">
              <i class="fa fa-book nav-icon"></i>
                <p>Report  Incidents</p>
              </a>
@endif
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
                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/dashboard')))
<a href="{{ url('ward/dashboard') }}" class="nav-link">
                      <i class="fas fa-credit-card nav-icon"></i>
                      <p>Expenses</p>
                    </a>
@endif
                  </li>



                  <li class="nav-item">
                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/lga/dashboard')))
<a href="{{ url('ward/lga/dashboard') }}" class="nav-link">
                    <i class="fas fa-dollar-sign nav-icon"></i>
                      <p>Funding Request</p>
                    </a>
@endif
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
                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/events')))
<a href="{{ url('ward/events') }}" class="nav-link">
                      <i class="fas fa-award nav-icon"></i>
                      <p>All Events</p>
                    </a>
@endif
                  </li>

                  <li class="nav-item">
                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/event/add')))
<a href="{{ url('ward/event/add') }}" class="nav-link">
                      <i class="fas fa-plus nav-icon"></i>
                      <p>Add Event</p>
                    </a>
@endif
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
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('messages')))
<a href="{{ url('messages') }}" class="nav-link">
                              <i class="fas fa-comments nav-icon"></i>
                              <p>Messenger</p>
                            </a>
@endif
                          </li>

                          @include('backend.template.partials.email-notifications-menu-item')
@include('backend.template.partials.sms-menu')
@include('backend.template.partials.announcements-menu-item')

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
@include('backend.template.partials.sms-settings-menu')


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
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/location/pollingunits')))
<a href="{{ url('ward/location/pollingunits') }}" class="nav-link">
                            <i class="fas fa-monument nav-icon"></i>
                              <p>Polling Units</p>
                            </a>
@endif
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
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/profile')))
<a href="{{ url('ward/profile') }}" class="nav-link">
                              <i class="fas fa-file nav-icon"></i>
                              <p> Manage Profile</p>
                            </a>
@endif
                          </li>



                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/change/password')))
<a href="{{ url('ward/change/password') }}" class="nav-link">
                            <i class="fas fa-lock nav-icon"></i>
                              <p>Change Password</p>
                            </a>
@endif
                          </li>



                        </ul>
            </li>
            {{-- End Profile Menu --}}

            {{-- Logout --}}
            <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user(), url('ward/logout')))
<a href="{{ url('ward/logout') }}" class="nav-link">
                            <i class="fas fa-power-off nav-icon"></i>
                              <p>Logout</p>
                            </a>
@endif
            </li>
            {{-- End Logout --}}

      </ul>
</nav>
@endif
