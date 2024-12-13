
  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-light-primary elevation-4 nav-pills">
    <!-- Brand Logo -->
    <a href="#" class="brand-link">
        <img src="{{ !empty($SystemSetting->logo) ? url('uploads/system_images/'.$SystemSetting->logo) : url('uploads/system_images/logo.png') }}" alt="Logo" class="brand-image" width="100%" height="100%" style="opacity: .8">

      <span class="brand-text font-weight-light" style="opacity:0;">::</span>

    </a>



    <!-- Sidebar -->
    <div class="sidebar" style="overflow-y: auto;">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
             <img src="{{(!empty($profileData->photo)) ? url('uploads/member_images/'.$profileData->photo) : url('uploads/no_image.jpg')}}" class="img-circle elevation-2" alt="User Image">
            </div>
        <div class="info">

            @php
            $url = '';




            if(Auth::user()->access_level === 'superadmin') {
                      $url = '/superadmin/profile';
                  }elseif(Auth::user()->access_level==='admin') {
                        $url = '/admin/profile';
                  }elseif(Auth::user()->access_level==='nationaladmin') {
                      $url = '/national/profile';
                  }elseif (Auth::user()->access_level==='regionaladmin') {
                      $url = '/regional/profile';
                  }elseif (Auth::user()->access_level==='stateadmin') {
                      $url = '/state/profile';
                  }elseif (Auth::user()->access_level==='lgaadmin') {
                      $url = '/lga/profile';
                  }elseif (Auth::user()->access_level==='wardadmin'){
                      $url = '/ward/profile';
                  }elseif (Auth::user()->access_level==='puadmin'){
                      $url = '/pu/profile';
                  }elseif (Auth::user()->access_level==='user') {
                      $url = '/member/profile';
                  }else{
                      $url = '/member/profile';
                  }
              @endphp

          <a href="{{$url}}" class="d-block">{{(!empty($profileData->firstname)) ? ($profileData->firstname.' '. $profileData->lastname ) : ('Unamed User')}}</a>
        </div>
      </div>

<!-- super admin Menu -->
@Include('backend.template.superadminmenu')
<!-- /super admin Menu -->

<!-- National admin Menu -->
@if(Auth::user()->access_level == 'nationaladmin')
 @Include('backend.template.nationalAdminMenu')
@endif
<!-- /National admin Menu -->

<!-- Regional admin Menu -->
@if(Auth::user()->access_level == 'regionaladmin')
 @Include('backend.template.regionalAdminMenu')
@endif
<!-- /Regional admin Menu -->

<!-- State admin Menu -->
@if(Auth::user()->access_level == 'stateadmin')
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


<!-- Member Menu -->
@if(Auth::user()->access_level == 'user')
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
              <li class="breadcrumb-item"><a href="{{ url()->previous() }}">Back</a>
              </li>
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
