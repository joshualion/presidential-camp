@if(Auth::user()->access_level == 'nationaladmin')
<nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column sidebar-nav" data-widget="treeview" role="menu" data-accordion="false">

            <li class="tab nav-item menu-close">
                        @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/dashboard')))
<a href="{{ url('national/dashboard') }}" class="nav-link">
                          <i class="nav-icon fas fa-home"></i>
                          <p>
                            Dashboard
                            <i class="right fas fa-angle-left"></i>
                          </p>
                        </a>
@endif


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
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/members/fetch')))
<a href="{{ url('national/members/fetch') }}" class="nav-link">
                              <i class="fas fa-user nav-icon"></i>
                              <p>All Members</p>
                            </a>
@endif
                          </li>

                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/members/region')))
<a href="{{ url('national/members/region') }}" class="nav-link">
                              <i class="fas fa-tachometer-alt nav-icon"></i>
                              <p>Members By Region</p>
                            </a>
@endif
                          </li>

                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/members/bystate')))
<a href="{{ url('national/members/bystate') }}" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>members by States</p>
                            </a>
@endif
                          </li>

                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/members/bylga')))
<a href="{{ url('national/members/bylga') }}" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Members by LGA</p>
                            </a>
@endif
                          </li>

                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/members/byward')))
<a href="{{ url('national/members/byward') }}" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Members By Wards</p>
                            </a>
@endif
                          </li>

                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/members/bypu')))
<a href="{{ url('national/members/bypu') }}" class="nav-link">
                              <i class="fas fa-user nav-icon"></i>
                              <p>Members By PU</p>
                            </a>
@endif
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
                                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/elections')))
<a href="{{ url('national/elections') }}" class="nav-link">
                                      <i class="fas fa-award nav-icon"></i>
                                      <p>Manage Elections</p>
                                    </a>
@endif
                                  </li>
                                  <li class="nav-item">
                                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/election/operations-center')))
<a href="{{ url('national/election/operations-center') }}" class="nav-link">
                                      <i class="fas fa-broadcast-tower nav-icon"></i>
                                      <p>Situation Room</p>
                                    </a>
@endif
                                  </li>
                                  @if(app(\App\Services\PollingUnitResultPermissionService::class)->userHasAnyApprovedAssignment($profileData))
                                  <li class="nav-item">
                                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/vote/add')))
<a href="{{ url('national/vote/add') }}" class="nav-link">
                                    <i class="fa fa-cloud nav-icon"></i>
                                      <p>Upload Polls Result</p>
                                    </a>
@endif
                                  </li>

                                  <li class="nav-item">
                                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/incident/add')))
<a href="{{ url('national/incident/add') }}" class="nav-link">
                                    <i class="fa fa-book nav-icon"></i>
                                      <p>Report  Incidents</p>
                                    </a>
@endif
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
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/dashboard')))
<a href="{{ url('national/dashboard') }}" class="nav-link">
                              <i class="fas fa-award nav-icon"></i>
                              <p>Elections</p>
                            </a>
@endif
                          </li>

                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/dashboard')))
<a href="{{ url('national/dashboard') }}" class="nav-link">
                              <i class="fas fa-vote-yea nav-icon"></i>
                              <p>Members</p>
                            </a>
@endif
                          </li>

                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/state/dashboard')))
<a href="{{ url('national/state/dashboard') }}" class="nav-link">
                            <i class="fa fa-thumbs-up nav-icon"></i>
                              <p>Finances</p>
                            </a>
@endif
                          </li>
                        </ul>
            </li> --}}
            {{-- / Reports --}}

            <!-- Finances temporarily disabled pending finance model review.
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
                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/dashboard')))
<a href="{{ url('national/dashboard') }}" class="nav-link">
                      <i class="fas fa-hand-holding-usd nav-icon"></i>
                      <p>Dues & Donations</p>
                    </a>
@endif
                  </li>



                  <li class="nav-item">
                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/dashboard')))
<a href="{{ url('national/dashboard') }}" class="nav-link">
                      <i class="fas fa-credit-card nav-icon"></i>
                      <p>Expenses</p>
                    </a>
@endif
                  </li>

                  {{-- <li class="nav-item">
                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/dashboard')))
<a href="{{ url('national/dashboard') }}" class="nav-link">
                      <i class="fas fa-shopping-cart nav-icon"></i>
                      <p>Sales Records</p>
                    </a>
@endif
                  </li> --}}

                  <li class="nav-item">
                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/state/dashboard')))
<a href="{{ url('national/state/dashboard') }}" class="nav-link">
                    <i class="fas fa-dollar-sign nav-icon"></i>
                      <p>Funding Request</p>
                    </a>
@endif
                  </li>

                </ul>
            </li>
            / Finances -->


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
                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/events')))
<a href="{{ url('national/events') }}" class="nav-link">
                      <i class="fas fa-award nav-icon"></i>
                      <p>All Events</p>
                    </a>
@endif
                  </li>

                  <li class="nav-item">
                    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/event/add')))
<a href="{{ url('national/event/add') }}" class="nav-link">
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
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('messages')))
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
                      @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('admin/products')))
<a href="{{ url('admin/products') }}" class="nav-link">
                      <i class="fas fa-gem nav-icon"></i>
                        <p>Products </p>
                      </a>
@endif
                    </li>
                    <li class="nav-item">
                      @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('admin/product/categories')))
<a href="{{ url('admin/product/categories') }}" class="nav-link">
                      <i class="fas fa-shopping-basket nav-icon"></i>
                        <p>Categories </p>
                      </a>
@endif
                    </li>

                    <li class="nav-item">
                      @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('admin/product/add')))
<a href="{{ url('admin/product/add') }}" class="nav-link">
                      <i class="fas fa-upload nav-icon"></i>
                        <p> Upload Product </p>
                      </a>
@endif
                    </li>

                    <li class="nav-item">
                      @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('admin/product/category/add')))
<a href="{{ url('admin/product/category/add') }}" class="nav-link">
                      <i class="fas fa-plus  nav-icon"></i>
                        <p> Add Category </p>
                      </a>
@endif
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
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/location/regions')))
<a href="{{ url('national/location/regions') }}" class="nav-link">
                              <i class="	fas fa-map-marker	 nav-icon"></i>
                              <p>Regions</p>
                            </a>
@endif
                          </li>

                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/location/states')))
<a href="{{ url('national/location/states') }}" class="nav-link">
                            <i class="fas fa-map-marker-alt nav-icon"></i>
                              <p>States</p>
                            </a>
@endif
                          </li>
                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/location/senatorial-districts')))
<a href="{{ url('national/location/senatorial-districts') }}" class="nav-link">
                            <i class="fas fa-map nav-icon"></i>
                              <p>Senatorial Districts</p>
                            </a>
@endif
                          </li>
                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/location/federal-constituencies')))
<a href="{{ url('national/location/federal-constituencies') }}" class="nav-link">
                            <i class="fas fa-landmark nav-icon"></i>
                              <p>Federal Constituencies</p>
                            </a>
@endif
                          </li>
                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/location/localgovernments')))
<a href="{{ url('national/location/localgovernments') }}" class="nav-link">
                            <i class="fas fa-map-pin nav-icon"></i>
                              <p>LGA's</p>
                            </a>
@endif
                          </li>

                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/location/wards')))
<a href="{{ url('national/location/wards') }}" class="nav-link">
                            <i class="fas fa-map-marked-alt nav-icon"></i>
                              <p> Wards</p>
                            </a>
@endif
                          </li>
                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/location/pollingunits')))
<a href="{{ url('national/location/pollingunits') }}" class="nav-link">
                            <i class="fas fa-monument nav-icon"></i>
                              <p> Polling Units</p>
                            </a>
@endif
                          </li>
                        </ul>

            </li>
            {{-- / Location --}}

        @if($profileData->can('settings.technical'))
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
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/profile')))
<a href="{{ url('national/profile') }}" class="nav-link">
                              <i class="fas fa-file nav-icon"></i>
                              <p> Manage Profile</p>
                            </a>
@endif
                          </li>



                          <li class="nav-item">
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/change/password')))
<a href="{{ url('national/change/password') }}" class="nav-link">
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
                            @if(app(\App\Services\CampaignCapabilityService::class)->canVisit(Auth::user() ?? $profileData ?? null, url('national/logout')))
<a href="{{ url('national/logout') }}" class="nav-link">
                            <i class="fas fa-power-off nav-icon"></i>
                              <p>Logout</p>
                            </a>
@endif
            </li>
            {{-- End Logout --}}

      </ul>
</nav>
@endif
