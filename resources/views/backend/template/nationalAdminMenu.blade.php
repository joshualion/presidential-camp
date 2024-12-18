<style>
    .nav-treeview{
        background: #ffffff !important;
    }
</style>
@if(Auth::user()->access_level == 'nationaladmin')
<nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

            <li class="tab nav-item menu-close">
                        <a href="/national/dashboard" class="nav-link">
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
                            <a href="/national/members/fetch/" class="nav-link">
                              <i class="fas fa-user nav-icon"></i>
                              <p>All Members</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/members/region" class="nav-link">
                              <i class="fas fa-tachometer-alt nav-icon"></i>
                              <p>Members By Region</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/members/bystate" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>members by States</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/members/bylga" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Members by LGA</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/members/byward/" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Members By Wards</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/members/bypu/" class="nav-link">
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
                                    <a href="/national/elections" class="nav-link">
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
                                    <a href="/national/vote/add" class="nav-link">
                                    <i class="fa fa-cloud nav-icon"></i>
                                      <p>Upload Polls Result</p>
                                    </a>
                                  </li>

                                  <li class="nav-item">
                                    <a href="/national/incident/add" class="nav-link">
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
                            <a href="/national/dashboard" class="nav-link">
                              <i class="fas fa-award nav-icon"></i>
                              <p>Elections</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/dashboard" class="nav-link">
                              <i class="fas fa-vote-yea nav-icon"></i>
                              <p>Members</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/state/dashboard" class="nav-link">
                            <i class="fa fa-thumbs-up nav-icon"></i>
                              <p>Finances</p>
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
                    Finances
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">

                <li class="nav-item">
                    <a href="/national/dashboard" class="nav-link">
                      <i class="fas fa-hand-holding-usd nav-icon"></i>
                      <p>Donations</p>
                    </a>
                  </li>



                  <li class="nav-item">
                    <a href="/national/dashboard" class="nav-link">
                      <i class="fas fa-credit-card nav-icon"></i>
                      <p>Expenses</p>
                    </a>
                  </li>

                  {{-- <li class="nav-item">
                    <a href="/national/dashboard" class="nav-link">
                      <i class="fas fa-shopping-cart nav-icon"></i>
                      <p>Sales Records</p>
                    </a>
                  </li> --}}

                  <li class="nav-item">
                    <a href="/national/state/dashboard" class="nav-link">
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
                    <a href="/national/events" class="nav-link">
                      <i class="fas fa-award nav-icon"></i>
                      <p>All Events</p>
                    </a>
                  </li>

                  <li class="nav-item">
                    <a href="/national/event/add" class="nav-link">
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
                            <a href="/national/bulksms/send" class="nav-link">
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

               {{--  Products Menu --}}
               {{-- <li class="nav-item  menu-close">
                <a href="#" class="nav-link">
                  <i class="nav-icon fa fa-shopping-cart"></i>
                  <p>
                  Store
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview" style="display: none;">
                    <li class="nav-item">
                      <a href="/admin/products" class="nav-link">
                      <i class="fas fa-gem nav-icon"></i>
                        <p>Products </p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="/admin/product/categories" class="nav-link">
                      <i class="fas fa-shopping-basket nav-icon"></i>
                        <p>Categories </p>
                      </a>
                    </li>

                    <li class="nav-item">
                      <a href="/admin/product/add" class="nav-link">
                      <i class="fas fa-upload nav-icon"></i>
                        <p> Upload Product </p>
                      </a>
                    </li>

                    <li class="nav-item">
                      <a href="/admin/product/category/add" class="nav-link">
                      <i class="fas fa-plus  nav-icon"></i>
                        <p> Add Category </p>
                      </a>
                    </li>
                </ul>
             </li> --}}
             {{--  End Products Menu --}}

            {{-- Location --}}
            <li class="tab nav-item menu-close">
                        <a href="#" class="nav-link">
                          <i class="nav-icon fas fa-globe-africa "></i>
                          <p>
                            Locations
                            <i class="right fas fa-angle-left"></i>
                          </p>
                        </a>
                        <ul class="nav nav-treeview">

                          <li class="nav-item">
                            <a href="/national/location/regions" class="nav-link">
                              <i class="	fas fa-map-marker	 nav-icon"></i>
                              <p>Regions</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/location/states/" class="nav-link">
                            <i class="fas fa-map-marker-alt nav-icon"></i>
                              <p>States</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/national/location/localgovernments" class="nav-link">
                            <i class="fas fa-map-pin nav-icon"></i>
                              <p>LGA's</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/location/wards" class="nav-link">
                            <i class="fas fa-map-marked-alt nav-icon"></i>
                              <p> Wards</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/national/location/pollingunits" class="nav-link">
                            <i class="fas fa-monument nav-icon"></i>
                              <p> Polling Units</p>
                            </a>
                          </li>
                        </ul>

            </li>
            {{-- / Location --}}

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
                    @if(($profileData->hasRole('National ICT/Media Director')) || ( $profileData->access_level == 'superadmin'))

                          <li class="nav-item">
                            <a href="/national/system/settings" class="nav-link">
                              <i class="fas fa-tools	 nav-icon"></i>
                              <p>General Settings</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/settings/smtp" class="nav-link">
                            <i class="fas fa-award nav-icon"></i>
                              <p> SMTP Settings</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/settings/smtp" class="nav-link">
                            <i class="fas fa-dollar-sign nav-icon"></i>
                              <p> Payment Settings</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/setting/volunteer/group" class="nav-link">
                            <i class="fas fa-handshake nav-icon"></i>
                              <p> Volunteer Group Settings</p>
                            </a>
                          </li>

                          

                          <li class="nav-item">
                            <a href="/national/election/setting/politicalparty" class="nav-link">
                              <i class="fas fa-vote-yea nav-icon"></i>
                              <p>Political Parties</p>
                            </a>
                          </li>


                          <li class="nav-item">
                            <a href="/national/community/forum" class="nav-link">
                            <i class="fas fa-comments nav-icon"></i>
                              <p> Community Settings</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/setting/agegrade" class="nav-link">
                            <i class="fas fa-user-graduate nav-icon"></i>
                              <p> Age Grade Settings</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/setting/religion" class="nav-link">
                            <i class="fas fa-praying-hands nav-icon"></i>
                              <p> Religion Settings</p>
                            </a>
                          </li>

                    @endif

                          <li class="nav-item">
                            <a href="/national/bulksms/setting" class="nav-link">
                            <i class="fas fa-sms nav-icon"></i>
                              <p>SMS Settings</p>
                            </a>
                          </li>
                        </ul>

            </li>

        @if(($profileData->hasRole('National ICT/Media Director')))
            {{-- Roles & Permission Menu --}}
            {{-- <li class="tab nav-item menu-close">
                <a href="#" class="nav-link">
                  <i class="fas fa-tools nav-icon"></i>
                  <p>
                    Roles & Permission
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">

                <li class="nav-item">
                    <a href="/national/settings/rolespermission" class="nav-link">
                      <i class="fas fa-ban nav-icon"></i>
                      <p>Roles Permissions</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="/national/settings/add/roles/permission" class="nav-link">
                      <i class="fas fa-plus-square	 nav-icon"></i>
                      <p>Add Roles Permission</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="/national/settings/permissions" class="nav-link">
                      <i class="fas fa-door-open	 nav-icon"></i>
                      <p>Manage Permissions</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/national/settings/roles" class="nav-link">
                      <i class="fas fa-crown	 nav-icon"></i>
                      <p>Manage Roles</p>
                    </a>
                </li>
              </ul>

            </li> --}}
            {{--  End Roles & Permission Menu  --}}
        @endif

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
                            <a href="/national/profile" class="nav-link">
                              <i class="fas fa-file nav-icon"></i>
                              <p> Manage Profile</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/bankdetails" class="nav-link">
                              <i class="fas fa-university nav-icon"></i>
                              <p> Bank Details</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/national/change/password" class="nav-link">
                            <i class="fas fa-lock nav-icon"></i>
                              <p>Change Password</p>
                            </a>
                          </li>



                        </ul>
            </li>
            {{-- End Profile Menu --}}

            {{-- Logout --}}
            <li class="nav-item">
                            <a href="/national/logout" class="nav-link">
                            <i class="fas fa-power-off nav-icon"></i>
                              <p>Logout</p>
                            </a>
            </li>
            {{-- End Logout --}}

      </ul>
</nav>
@endif
