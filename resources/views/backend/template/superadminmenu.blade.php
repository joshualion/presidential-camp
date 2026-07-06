
@if(Auth::user()->access_level == 'superadmin')
@php($packageVisibility = $packageVisibility ?? app(\App\Services\PackageVisibilityService::class))
@php($packageUi = $packageUi ?? app(\App\Services\CampaignPackageUiService::class))
<nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column sidebar-nav" data-widget="treeview" role="menu" data-accordion="false">

            <li class="tab nav-item menu-close">
                        <a href="{{ url('superadmin/dashboard') }}" class="nav-link">
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
                  <a href="{{ url('superadmin/members/fetch') }}" class="nav-link">
                    <i class="fas fa-user nav-icon"></i>
                    <p>All Members</p>
                  </a>
                </li>

                @if($packageUi->supportsMemberNavigationLevel('regions'))
                <li class="nav-item">
                  <a href="{{ route('superadmin.members.region') }}" class="nav-link">
                    <i class="fas fa-globe-africa nav-icon"></i>
                    <p>Members by Regions</p>
                  </a>
                </li>
                @endif

                @if($packageUi->supportsMemberNavigationLevel('states'))
                <li class="nav-item">
                  <a href="{{ route('superadmin.members.byState') }}" class="nav-link">
                    <i class="fas fa-map nav-icon"></i>
                    <p>Members by States</p>
                  </a>
                </li>
                @endif
                @if($packageUi->supportsMemberNavigationLevel('lgas'))
                <li class="nav-item">
                  <a href="{{ url('superadmin/members/bylga') }}" class="nav-link">
                  <i class="fas fa-user nav-icon"></i>
                    <p>Members by LGA</p>
                  </a>
                </li>
                @endif

                @if($packageUi->supportsMemberNavigationLevel('wards'))
                <li class="nav-item">
                  <a href="{{ url('superadmin/members/byward') }}" class="nav-link">
                  <i class="fas fa-user nav-icon"></i>
                    <p>Members By Wards</p>
                  </a>
                </li>
                @endif

                @if($packageUi->supportsMemberNavigationLevel('polling_units'))
                <li class="nav-item">
                  <a href="{{ url('superadmin/members/bypu') }}" class="nav-link">
                  <i class="fas fa-user nav-icon"></i>
                    <p>Members By PU</p>
                  </a>
                </li>
                @endif

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
                          <a href="{{ url('superadmin/elections') }}" class="nav-link">
                            <i class="fas fa-award nav-icon"></i>
                            <p>Manage Elections</p>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="{{ url('superadmin/election/operations-center') }}" class="nav-link">
                            <i class="fas fa-broadcast-tower nav-icon"></i>
                            <p>Situation Room</p>
                          </a>
                        </li>
                        @if(app(\App\Services\PollingUnitResultPermissionService::class)->userHasAnyApprovedAssignment($profileData))
                        <li class="nav-item">
                          <a href="{{ url('superadmin/vote/add') }}" class="nav-link">
                          <i class="fa fa-cloud nav-icon"></i>
                            <p>Upload Polls Result</p>
                          </a>
                        </li>

                        <li class="nav-item">
                          <a href="{{ url('superadmin/incident/add') }}" class="nav-link">
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
                  <a href="{{ url('superadmin/dashboard') }}" class="nav-link">
                    <i class="fas fa-award nav-icon"></i>
                    <p>Elections</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="{{ url('superadmin/dashboard') }}" class="nav-link">
                    <i class="fas fa-vote-yea nav-icon"></i>
                    <p>Members</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="{{ url('superadmin/state/dashboard') }}" class="nav-link">
                  <i class="fa fa-thumbs-up nav-icon"></i>
                    <p>Finances</p>
                  </a>
                </li>
              </ul>
  </li> --}}
  {{-- / Reports --}}

  {{-- Finances --}} {{--
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
          <a href="{{ url('superadmin/products') }}" class="nav-link">
            <i class="fas fa-shopping-cart nav-icon"></i>
            <p>Funding Sources</p>
          </a>
      </li>

      <li class="nav-item">
          <a href="{{ url('superadmin/dashboard') }}" class="nav-link">
            <i class="fas fa-hand-holding-usd nav-icon"></i>
            <p>Income</p>
          </a>
      </li>



        <li class="nav-item">
          <a href="{{ url('superadmin/dashboard') }}" class="nav-link">
            <i class="fas fa-credit-card nav-icon"></i>
            <p>Expenses</p>
          </a>
        </li>

        {{-- <li class="nav-item">
          <a href="{{ url('superadmin/dashboard') }}" class="nav-link">
            <i class="fas fa-shopping-cart nav-icon"></i>
            <p>Sales Records</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="{{ url('superadmin/state/dashboard') }}" class="nav-link">
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
          <a href="{{ url('superadmin/events') }}" class="nav-link">
            <i class="fas fa-award nav-icon"></i>
            <p>All Events</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="{{ url('superadmin/event/add') }}" class="nav-link">
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
                    <a href="{{ route($profileData->access_level.'.messages.page') }}" class="nav-link">
                      <i class="fas fa-comments nav-icon"></i>
                      <p>Internal Communication</p>
                    </a>
                  </li>

                  @include('backend.template.partials.email-notifications-menu-item')

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
            <a href="{{ url('admin/products') }}" class="nav-link">
            <i class="fas fa-gem nav-icon"></i>
              <p>Products </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ url('admin/product/categories') }}" class="nav-link">
            <i class="fas fa-shopping-basket nav-icon"></i>
              <p>Categories </p>
            </a>
          </li>

          <li class="nav-item">
            <a href="{{ url('admin/product/add') }}" class="nav-link">
            <i class="fas fa-upload nav-icon"></i>
              <p> Upload Product </p>
            </a>
          </li>

          <li class="nav-item">
            <a href="{{ url('admin/product/category/add') }}" class="nav-link">
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

                @if($packageVisibility->canSeeRegionalModules())
                  <li class="nav-item">
                    <a href="{{ url('superadmin/location/regions') }}" class="nav-link">
                      <i class="	fas fa-map-marker	 nav-icon"></i>
                      <p>Regions</p>
                    </a>
                  </li>
                @endif

                @if($packageVisibility->canSeeStateModules())
                  <li class="nav-item">
                    <a href="{{ url('superadmin/location/states') }}" class="nav-link">
                    <i class="fas fa-map-marker-alt nav-icon"></i>
                      <p>States</p>
                    </a>
                  </li>
                @endif
                @if($packageVisibility->canSeeSenatorialModules())
                <li class="nav-item">
                  <a href="{{ url('superadmin/location/senatorial-districts') }}" class="nav-link">
                  <i class="fas fa-map nav-icon"></i>
                    <p>Senatorial Districts</p>
                  </a>
                </li>
                @endif
                @if($packageVisibility->canSeeFederalModules())
                <li class="nav-item">
                  <a href="{{ url('superadmin/location/federal-constituencies') }}" class="nav-link">
                  <i class="fas fa-landmark nav-icon"></i>
                    <p>Federal Constituencies</p>
                  </a>
                </li>
                @endif
                @if($packageVisibility->canSeeLgaModules())
                <li class="nav-item">
                  <a href="{{ url('superadmin/location/localgovernments') }}" class="nav-link">
                  <i class="fas fa-map-pin nav-icon"></i>
                    <p>LGA's</p>
                  </a>
                </li>
                @endif

                @if($packageVisibility->canSeeWardModules())
                <li class="nav-item">
                  <a href="{{ url('superadmin/location/wards') }}" class="nav-link">
                  <i class="fas fa-map-marked-alt nav-icon"></i>
                    <p> Wards</p>
                  </a>
                </li>
                @endif
                @if($packageVisibility->canSeePollingUnitModules())
                <li class="nav-item">
                  <a href="{{ url('superadmin/location/pollingunits') }}" class="nav-link">
                  <i class="fas fa-monument nav-icon"></i>
                    <p> Polling Units</p>
                  </a>
                </li>
                @endif
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
          @if(($profileData->hasRole('National ICT Director')) || ( $profileData->access_level == 'superadmin'))

                <li class="nav-item">
                  <a href="{{ url('superadmin/system/settings') }}" class="nav-link">
                    <i class="fas fa-tools	 nav-icon"></i>
                    <p>General Settings</p>
                  </a>
                </li>
                @endif

                <li class="nav-item">
                  <a href="{{ route('admin.license.show') }}" class="nav-link">
                    <i class="fas fa-certificate nav-icon"></i>
                    <p>License Status</p>
                  </a>
                </li>

                @if(app(\App\Services\ModuleGateService::class)->enabled('sms'))
                <li class="nav-item">
                  <a href="{{ route('superadmin.sms.settings.index') }}" class="nav-link">
                    <i class="fas fa-sms nav-icon"></i>
                    <p>Portal SMS Settings</p>
                  </a>
                </li>
                @endif

                <li class="nav-item">
                  <a href="{{ url('superadmin/settings/smtp') }}" class="nav-link">
                  <i class="fas fa-award nav-icon"></i>
                    <p> SMTP Settings</p>
                  </a>
                </li>

                {{-- Payment Settings temporarily disabled pending finance module completion.
                <li class="nav-item">
                  <a href="{{ url('superadmin/settings/paymentgateway') }}" class="nav-link">
                  <i class="fas fa-credit-card nav-icon"></i>
                    <p> Payment Settings</p>
                  </a>
                </li>
                --}}

                <li class="nav-item">
                  <a href="{{ route('superadmin.settings.email-notifications.edit') }}" class="nav-link">
                    <i class="fas fa-envelope-open-text nav-icon"></i>
                    <p>Email Notification</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="{{ url('superadmin/setting/volunteer/group') }}" class="nav-link">
                  <i class="fas fa-handshake nav-icon"></i>
                    <p> Volunteer Group Settings</p>
                  </a>
                </li>



                <li class="nav-item">
                  <a href="{{ url('superadmin/election/setting/politicalparty') }}" class="nav-link">
                    <i class="fas fa-vote-yea nav-icon"></i>
                    <p>Political Parties</p>
                  </a>
                </li>


                @if(app(\App\Services\CommunityRealtimeService::class)->moduleAvailable())
                <li class="nav-item">
                  <a href="{{ url('superadmin/community/forum') }}" class="nav-link">
                  <i class="fas fa-comments nav-icon"></i>
                    <p> Community Settings</p>
                  </a>
                </li>
                @endif

                <li class="nav-item">
                  <a href="{{ url('superadmin/setting/agegrade') }}" class="nav-link">
                  <i class="fas fa-user-graduate nav-icon"></i>
                    <p> Age Grade Settings</p>
                  </a>
                </li>

                <li class="nav-item">
                  <a href="{{ url('superadmin/setting/religion') }}" class="nav-link">
                  <i class="fas fa-praying-hands nav-icon"></i>
                    <p> Religion Settings</p>
                  </a>
                </li>

              </ul>

  </li>

            {{-- Roles & Permission Menu --}}
            <li class="tab nav-item menu-close">
                <a href="#" class="nav-link">
                  <i class="fas fa-ban  nav-icon"></i>
                  <p>
                    Roles & Permission
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">

                <li class="nav-item">
                    <a href="{{ url('superadmin/settings/rolespermission') }}" class="nav-link">
                      <i class="fas fa-eye nav-icon"></i>
                      <p>Roles Permissions</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ url('superadmin/settings/add/roles/permission') }}" class="nav-link">
                      <i class="fas fa-plus-square	 nav-icon"></i>
                      <p>Assign Roles Permission</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ url('superadmin/settings/permissions') }}" class="nav-link">
                      <i class="fas fa-door-open	 nav-icon"></i>
                      <p>Manage Permissions</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('superadmin/settings/roles') }}" class="nav-link">
                      <i class="fas fa-crown	 nav-icon"></i>
                      <p>Manage Roles</p>
                    </a>
                </li>
              </ul>

            </li>
        {{--  End Roles & Permission Menu  --}}
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
                            <a href="{{ url('superadmin/profile') }}" class="nav-link">
                              <i class="fas fa-file nav-icon"></i>
                              <p> Manage Profile</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="{{ url('superadmin/change/password') }}" class="nav-link">
                            <i class="fas fa-lock nav-icon"></i>
                              <p>Change Password</p>
                            </a>
                          </li>



                        </ul>
            </li>
            <li class="nav-item">
                            <a href="{{ url('superadmin/logout') }}" class="nav-link">
                            <i class="fas fa-power-off nav-icon"></i>
                              <p>Logout</p>
                            </a>
            </li>

      </ul>
</nav>
@endif
