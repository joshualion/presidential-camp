<style>
    .nav-treeview{
        background: #ffffff !important;
    }
</style>

@php
$region = \App\Models\Region::find(Auth::user()->region_id);
@endphp

@if(Auth::user()->access_level == 'regionaladmin')

<nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

            <li class="tab nav-item menu-close">
                        <a href="/regional/dashboard" class="nav-link">
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
                            <a href="/regional/members/fetch/{{$region->uuid}}" class="nav-link">
                              <i class="fas fa-user nav-icon"></i>
                              <p>All Members</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/regional/members/bystate/{{$region->uuid}}" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>members by States</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/regional/members/bylga/{{$region->uuid}}" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Members by LGA</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/regional/members/byward/{{$region->uuid}}" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Members By Wards</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/regional/members/bypu/{{$region->uuid}}" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Members By PU</p>
                            </a>
                          </li>

                        </ul>
            </li>
            {{-- / Members --}}

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
              <a href="/regional/elections" class="nav-link">
                <i class="fas fa-award nav-icon"></i>
                <p>Manage Elections</p>
              </a>
            </li>
            @if(
              $profileData->hasAnyRole([
                  'National ICT/Media Director',
                  'Regional ICT/Media Director',
                  'State ICT/Media Director',
                  'LGA ICT/Media Director',
                  'Ward ICT/Media Director',
                  'PU ICT/Media Director',
                  'Pu Agent'
              ]) || $profileData->access_level == 'superadmin' )
            <li class="nav-item">
              <a href="/regional/vote/add" class="nav-link">
              <i class="fa fa-cloud nav-icon"></i>
                <p>Upload Polls Result</p>
              </a>
            </li>

            <li class="nav-item">
              <a href="/regional/incident/add" class="nav-link">
              <i class="fa fa-book nav-icon"></i>
                <p>Report  Incidents</p>
              </a>
            </li>
            @endif
          </ul>
</li>
{{-- / Election --}}

            {{-- Reports --}}
            {{-- <li class="tab nav-item menu-close">
                        <a href="#" class="nav-link">
                          <i class="nav-icon fas fa-book"></i>
                          <p>
                            Reports
                            <i class="right fas fa-angle-left"></i>
                          </p>
                        </a>
                        <ul class="nav nav-treeview">

                        <li class="nav-item">
                            <a href="/regional/dashboard" class="nav-link">
                              <i class="fas fa-award nav-icon"></i>
                              <p>Election Results</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/regional/dashboard" class="nav-link">
                              <i class="fas fa-vote-yea nav-icon"></i>
                              <p>Election Incident Reports</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/regional/state/dashboard" class="nav-link">
                            <i class="fa fa-thumbs-up nav-icon"></i>
                              <p>Financial Reports</p>
                            </a>
                          </li>
                        </ul>
            </li> --}}
            {{-- / Reports --}}

            {{-- Finances --}}
            <li class="tab nav-item menu-close">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-money-bill"></i>
                  <p>
                    Finance
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">           

                <li class="nav-item">
                    <a href="/regional/dashboard" class="nav-link">
                      <i class="fas fa-credit-card nav-icon"></i>
                      <p>Expenses</p>
                    </a>
                  </li>

                  <li class="nav-item">
                    <a href="/regional/dashboard" class="nav-link">
                      <i class="fas fa-shopping-cart nav-icon"></i>
                      <p>Sales Records</p>
                    </a>
                  </li>

                  <li class="nav-item">
                    <a href="/regional/state/dashboard" class="nav-link">
                    <i class="fas fa-dollar-sign nav-icon"></i>
                      <p>Funding Request</p>
                    </a>
                  </li>

                </ul>
            </li>
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
                    <a href="/regional/events" class="nav-link">
                      <i class="fas fa-award nav-icon"></i>
                      <p>All Events</p>
                    </a>
                  </li>

                  <li class="nav-item">
                    <a href="/regional/event/add" class="nav-link">
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
                            <a href="/regional/bulksms/send" class="nav-link">
                              <i class="fas fa-sms	 nav-icon"></i>
                              <p>Send SMS</p>
                            </a>
                          </li>

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

               

          

            {{-- Settings --}}
            <li class="tab nav-item menu-close">
                        <a href="#" class="nav-link">
                          <i class="nav-icon fas fa-cogs"></i>
                          <p>
                            Settings
                            <i class="right fas fa-angle-left"></i>
                          </p>
                        </a>
                        <ul class="nav nav-treeview">                   

                          <li class="nav-item">
                            <a href="/regional/bulksms/setting" class="nav-link">
                            <i class="fas fa-sms nav-icon"></i>
                              <p>SMS Settings</p>
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
                            <a href="/regional/profile" class="nav-link">
                              <i class="fas fa-file nav-icon"></i>
                              <p> Manage Profile</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/regional/bankdetails" class="nav-link">
                              <i class="fas fa-university nav-icon"></i>
                              <p> Bank Details</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/regional/change/password" class="nav-link">
                            <i class="fas fa-lock nav-icon"></i>
                              <p>Change Password</p>
                            </a>
                          </li>



                        </ul>
            </li>
            {{-- End Profile Menu --}}

            {{-- Logout --}}
            <li class="nav-item">
                            <a href="/regional/logout" class="nav-link">
                            <i class="fas fa-power-off nav-icon"></i>
                              <p>Logout</p>
                            </a>
            </li>
            {{-- End Logout --}}

      </ul>
</nav>
@endif
