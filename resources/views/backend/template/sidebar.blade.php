
  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-light-primary elevation-4">
    <!-- Brand Logo -->
    <a href="#" class="brand-link">
      <img src="{{asset('assets/dist/img/Logo2.png')}}" alt="Logo" class="brand-image" width="100%" height="100%" style="opacity: .8">
      <span class="brand-text font-weight-light" style="opacity:0;">::</span>
      
    </a>

   

    <!-- Sidebar -->
    <div class="sidebar" style="overflow-y: auto;">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
             <img src="{{(!empty($profileData->photo)) ? url('uploads/admin_images/'.$profileData->photo) : url('uploads/no_image.jpg')}}" class="img-circle elevation-2" alt="User Image">
            </div>
        <div class="info">
        
            @php
            $url = '';
         

           

            if(Auth::user()->role === 'superadmin') {
                      $url = '/admin/profile';
                  }elseif(Auth::user()->role==='nationaladmin') {
                      $url = '/national/profile';
                  }elseif (Auth::user()->role==='regionaladmin') {
                      $url = '/regional/profile';
                  }elseif (Auth::user()->role==='stateadmin') {
                      $url = '/state/profile';
                  }elseif (Auth::user()->role==='lgaadmin') {
                      $url = '/lga/profile';
                  }elseif (Auth::user()->role==='wardadmin'){
                      $url = '/ward/profile';
                  }elseif (Auth::user()->role==='puadmin'){
                      $url = '/pu/profile';
                  }elseif (Auth::user()->role==='user') {
                      $url = '/member/profile';
                  }else{
                      $url = '/member/profile';
                  }
              @endphp 
        
          <a href="{{$url}}" class="d-block">{{(!empty($profileData->firstname)) ? ($profileData->firstname.' '. $profileData->lastname ) : ('Unamed User')}}</a>
        </div>
      </div>  

<!-- super admin Menu -->
 @if(Auth::user()->role == 'superadmin')
  <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                                
              <li class="tab nav-item menu-close">
                          <a href="/admin/dashboard" class="nav-link">
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
                              <a href="/admin/change/password" class="nav-link">
                              <i class="fas fa-lock nav-icon"></i>
                                <p>Change Password</p>
                              </a>
                            </li>

                            

                          </ul>
              </li> 
              <li class="nav-item">
                              <a href="{{route('admin.logout')}}" class="nav-link">
                              <i class="fas fa-power-off nav-icon"></i>
                                <p>Logout</p>
                              </a>
              </li>

        </ul>
  </nav>
  @endif  
<!-- /super admin Menu -->

<!-- National admin Menu -->
@if(Auth::user()->role == 'nationaladmin')
  <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                                
              <li class="nav-item menu-open">
                          <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chess-king"></i>
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
                              <i class="fas fa-tachometer-alt nav-icon"></i>
                                <p>Ward Leaders</p>
                              </a>
                            </li>

                            <li class="nav-item">
                              <a href="./index3.html" class="nav-link">
                              <i class="fas fa-tachometer-alt nav-icon"></i>
                                <p>PU Leaders</p>
                              </a>
                            </li>

                          </ul>
              </li>    
        </ul>
  </nav>
  @endif
<!-- /National admin Menu -->

<!-- State admin Menu -->
@if(Auth::user()->role == 'stateadmin')
  <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                                
              <li class="nav-item menu-open">
                          <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                              Members
                              <i class="right fas fa-angle-left"></i>
                            </p>
                          </a>
                          <ul class="nav nav-treeview">

                            <li class="nav-item">
                              <a href="/admin/dashboard" class="nav-link">
                                <i class="fas fa-user nav-icon"></i>
                                <p>All Members</p>
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
                                <p>Memberss By PU</p>
                              </a>
                            </li>

                          </ul>
              </li>    
        </ul>
  </nav>
  @endif
<!-- /State admin Menu -->


<!-- super admin Menu -->
@if(Auth::user()->role == 'user')
  <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                                
              <li class="tab nav-item menu-close">
                          <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-home"></i>
                            <p>
                              Dashboard
                              <i class="right fas fa-angle-left"></i>
                            </p>
                          </a>
                          <ul class="nav nav-treeview">

                            <li class="nav-item">
                              <a href="/member/dashboard" class="nav-link">
                                <i class="fas fa-tachometer-alt nav-icon"></i>
                                <p>member Dashboard</p>
                              </a>
                            </li>

                           

                          </ul>
              </li>              
              
             @if(Auth::user()->firstname != '')
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
                              <a href="/member/profile" class="nav-link">
                                <i class="fas fa-file nav-icon"></i>
                                <p> Manage Profile</p>
                              </a>
                            </li>

                            <li class="nav-item">
                              <a href="/member/password/change" class="nav-link">
                              <i class="fas fa-lock nav-icon"></i>
                                <p>Change Password</p>
                              </a>
                            </li>
                            @endif

                            <li class="nav-item">
                              <a href="{{route('member.logout')}}" class="nav-link">
                              <i class="fas fa-power-off nav-icon"></i>
                                <p>Logout</p>
                              </a>
                            </li>

                          </ul>
              </li> 

            

        </ul>
  </nav>
  @endif  
<!-- /Member Menu -->


</div>
    <!-- /.sidebar -->
</aside>


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">@isset($pageTitle){{ $pageTitle }} @endisset</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">@isset($pageTitle){{ $pageTitle }}@else {{Dashboard}} @endisset</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

      <!-- Main content -->
<section class="content">
      <div class="container-fluid">