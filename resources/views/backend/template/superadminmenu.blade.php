
@if(Auth::user()->access_level == 'superadmin')
<nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

            <li class="tab nav-item menu-close">
                        <a href="/superadmin/dashboard" class="nav-link">
                          <i class="nav-icon fas fa-home"></i>
                          <p>
                            Dashboard
                            <i class="right fas fa-angle-left"></i>
                          </p>
                        </a>

            </li>

            <li class="tab nav-item menu-close">
                        <a href="#" class="nav-link">
                          <i class="nav-icon fas fa-user-secret"></i>
                          <p>
                            Officials
                            <i class="right fas fa-angle-left"></i>
                          </p>
                        </a>
                        <ul class="nav nav-treeview">

                          <li class="nav-item">
                            <a href="/admin/dashboard" class="nav-link">
                              <i class="fas fa-user nav-icon"></i>
                              <p>National leaders</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/admin/region/leaders/" class="nav-link">
                              <i class="fas fa-tachometer-alt nav-icon"></i>
                              <p>Regional Leaders</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/admin/state/dashboard" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>State leaders</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="./index2.html" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>LGA Leaders</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="./index3.html" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Ward Leaders</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="./index3.html" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>PU Leaders</p>
                            </a>
                          </li>

                        </ul>
            </li>
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
                            <a href="#" class="nav-link">
                              <i class="fas fa-user nav-icon"></i>
                              <p>All Members</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/admin/region/dashboard/home" class="nav-link">
                              <i class="fas fa-tachometer-alt nav-icon"></i>
                              <p>Members By Region</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/admin/state/dashboard" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>members by States</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="./index2.html" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Members by LGA</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="./index3.html" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Members By Wards</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="./index3.html" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                              <p>Members By PU</p>
                            </a>
                          </li>

                        </ul>
            </li>
            <li class="tab nav-item menu-close">
                        <a href="#" class="nav-link">
                          <i class="nav-icon fas fa-book"></i>
                          <p>
                            Reports
                            <i class="right fas fa-angle-left"></i>
                          </p>
                        </a>
                        <ul class="nav nav-treeview">

                        <li class="nav-item">
                            <a href="/admin/dashboard" class="nav-link">
                              <i class="fas fa-award nav-icon"></i>
                              <p>Election Results</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/admin/dashboard" class="nav-link">
                              <i class="fas fa-vote-yea nav-icon"></i>
                              <p>Election Reports</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/admin/state/dashboard" class="nav-link">
                            <i class="fa fa-thumbs-up nav-icon"></i>
                              <p>Votes</p>
                            </a>
                          </li>
                           <li class="nav-item">
                            <a href="#" class="nav-link">
                            <i class="fas fa-hand-holding-usd nav-icon"></i>
                              <p>Donations</p>
                            </a>
                          </li>
                        </ul>
            </li>
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
                            <a href="/admin/bulksms/send" class="nav-link">
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
                            <a href="/admin/dashboard" class="nav-link">
                              <i class="	fas fa-map-marker	 nav-icon"></i>
                              <p>Regions</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/admin/state/dashboard" class="nav-link">
                            <i class="fas fa-map-marker-alt nav-icon"></i>
                              <p>States</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/admin/state/dashboard" class="nav-link">
                            <i class="fas fa-map-pin nav-icon"></i>
                              <p>LGA's</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/admin/state/dashboard" class="nav-link">
                            <i class="fas fa-map-marked-alt nav-icon"></i>
                              <p> Wards</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/admin/state/dashboard" class="nav-link">
                            <i class="fas fa-monument nav-icon"></i>
                              <p> Polling Centers</p>
                            </a>
                          </li>
                        </ul>

            </li>

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
                            <a href="/admin/system/settings" class="nav-link">
                              <i class="fas fa-tools	 nav-icon"></i>
                              <p>General Settings</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/admin/state/dashboard" class="nav-link">
                            <i class="fas fa-award nav-icon"></i>
                              <p> Election Settings</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/admin/bulksms/setting" class="nav-link">
                            <i class="fas fa-sms nav-icon"></i>
                              <p>SMS Settings</p>
                            </a>
                          </li>
                          <!-- <li class="nav-item">
                            <a href="/admin/state/dashboard" class="nav-link">
                            <i class="fas fa-money-check-alt nav-icon"></i>
                              <p>Payment Settings</p>
                            </a>
                          </li> -->

                          <li class="nav-item">
                            <a href="/admin/leadership/offices" class="nav-link">
                            <i class="fas fa-sitemap nav-icon"></i>
                              <p> Leadership Settings</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/admin/volunteer/group" class="nav-link">
                            <i class="fas fa-handshake nav-icon"></i>
                              <p> Volunteer Settings</p>
                            </a>
                          </li>

                        </ul>

            </li>
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
                            <a href="/admin/profile" class="nav-link">
                              <i class="fas fa-file nav-icon"></i>
                              <p> Manage Profile</p>
                            </a>
                          </li>

                          <li class="nav-item">
                            <a href="/superadmin/change/password" class="nav-link">
                            <i class="fas fa-lock nav-icon"></i>
                              <p>Change Password</p>
                            </a>
                          </li>



                        </ul>
            </li>
            <li class="nav-item">
                            <a href="/superadmin/logout" class="nav-link">
                            <i class="fas fa-power-off nav-icon"></i>
                              <p>Logout</p>
                            </a>
            </li>

      </ul>
</nav>
@endif
