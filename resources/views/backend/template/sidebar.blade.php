
  @php
    $sidebarDarkColor = $SystemSetting->dark_theme_color ?? '#008751';
    $sidebarLightColor = $SystemSetting->light_theme_color ?? '#f7f7f7';
    $sidebarMode = $SystemSetting->sidebar_theme_mode ?? 'light';
  @endphp

  <!-- Main Sidebar Container -->
  <style>
    #app-sidebar {
      --sidebar-light-bg: {{ $sidebarLightColor }};
      --sidebar-light-text: {{ $sidebarDarkColor }};
      --sidebar-dark-bg: {{ $sidebarDarkColor }};
      --sidebar-dark-text: #ffffff;
      /* Derive a softer tint from the dark color instead of using the light theme */
      --sidebar-link-tint: color-mix(in srgb, {{ $sidebarDarkColor }} 35%, white 65%);
    }

    .sidebar-themed {
      background: var(--sidebar-dark-bg) !important;
      color: var(--sidebar-dark-text) !important;
    }
    .sidebar-themed .brand-link,
    .sidebar-themed .brand-link .brand-text,
    .sidebar-themed .user-panel .info a,
    .sidebar-themed .sidebar-nav .nav-link,
    .sidebar-themed .sidebar-nav .nav-link p,
    .sidebar-themed .sidebar-nav .nav-link i {
      color: var(--sidebar-link-tint) !important;
    }
    .sidebar-themed .sidebar-nav .nav-link.active,
    .sidebar-themed .sidebar-nav .nav-link:hover {
      background: rgba(255,255,255,0.08) !important;
      color: var(--sidebar-link-tint) !important;
    }
    /* Light mode uses light background with brand text color */
    .main-sidebar:not(.sidebar-themed) {
      background: var(--sidebar-light-bg) !important;
      color: var(--sidebar-light-text) !important;
    }
    /* Ensure nav container inherits sidebar background */
    .main-sidebar nav {
      background: transparent !important;
    }
    .sidebar-themed nav,
    .sidebar-themed .nav-sidebar {
      background: transparent !important;
    }
    /* Only affect dropdown (treeview) items in dark mode */
    .sidebar-themed .nav-sidebar .nav-treeview {
      background: rgba(255,255,255,0.04) !important;
    }
    .sidebar-themed .nav-sidebar .nav-treeview>.nav-item>.nav-link {
      background: transparent !important;
      color: var(--sidebar-link-tint) !important;
    }
    .sidebar-themed .nav-sidebar .nav-treeview>.nav-item>.nav-link.active,
    .sidebar-themed .nav-sidebar .nav-treeview>.nav-item>.nav-link:hover {
      background: rgba(255,255,255,0.08) !important;
      color: var(--sidebar-link-tint) !important;
    }
    .main-sidebar:not(.sidebar-themed) .sidebar-nav .nav-link,
    .main-sidebar:not(.sidebar-themed) .sidebar-nav .nav-link p,
    .main-sidebar:not(.sidebar-themed) .sidebar-nav .nav-link i,
    .main-sidebar:not(.sidebar-themed) .brand-link,
    .main-sidebar:not(.sidebar-themed) .brand-link .brand-text,
    .main-sidebar:not(.sidebar-themed) .user-panel .info a {
      color: var(--sidebar-light-text) !important;
    }
    .main-sidebar:not(.sidebar-themed) .sidebar-nav .nav-link.active,
    .main-sidebar:not(.sidebar-themed) .sidebar-nav .nav-link:hover {
      background: rgba(0,0,0,0.04) !important;
      color: var(--sidebar-light-text) !important;
    }
    /* Card headers using the dark theme color get tinted text for contrast */
    .card-header[style*="{{ $sidebarDarkColor }}"],
    .card-header[style*="dark_theme_color"] {
      color: var(--sidebar-link-tint) !important;
    }
    /* Nudge brand logo upward and keep it crisp */
    .main-sidebar .brand-link {
      padding-top: 0.5rem;
      padding-bottom: 0.35rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      flex-wrap: nowrap;
      width: 100%;
      text-align: center;
    }
    .main-sidebar .brand-link img {
      display: block;
      max-height: 56px; /* expand logo to reveal embedded tagline */
      max-width: 100%;
      height: auto;
      width: auto;
      object-fit: contain;
      image-rendering: auto;
    }
    /* Keep written text from squeezing logo; hide if present */
    .main-sidebar .brand-link .brand-text {
      opacity: 0 !important;
      width: 0;
      overflow: hidden;
      padding: 0;
      margin: 0;
    }
  </style>

  @php
    $sidebarThemeRoute = \Illuminate\Support\Facades\Route::has('superadmin.settings.sidebar-theme')
      ? route('superadmin.settings.sidebar-theme')
      : null;
    $packageVisibility = app(\App\Services\PackageVisibilityService::class);
  @endphp

  <aside class="main-sidebar sidebar-light-primary elevation-4 nav-pills {{ $sidebarMode === 'brand' ? 'sidebar-themed' : '' }}" id="app-sidebar" data-theme-light="{{ $sidebarLightColor }}" data-theme-dark="{{ $sidebarDarkColor }}" data-sidebar-mode="{{ $sidebarMode }}">
    <!-- Brand Logo add brand-image class to logo -->
    <a href="#" class="brand-link">
        <img src="{{ !empty($SystemSetting->logo) ? url('uploads/system_images/'.$SystemSetting->logo) : url('logo.png') }}" alt="Logo" class="img img-responsive" height="25%" >

      <span class="brand-text font-weight-light" style="opacity:0;">::</span>

    </a>



    <!-- Sidebar -->
    <div class="sidebar" style="overflow-y: auto;">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-2 pb-3 mb-3 d-flex align-items-center">
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
                  }elseif (Auth::user()->access_level==='senatorialadmin') {
                      $url = '/senatorial/profile';
                  }elseif (Auth::user()->access_level==='federaladmin') {
                      $url = '/federal/profile';
                  }elseif (Auth::user()->access_level==='lgaadmin') {
                      $url = '/lga/profile';
                  }elseif (Auth::user()->access_level==='wardadmin'){
                      $url = '/ward/profile';
                  }elseif (Auth::user()->access_level==='puadmin'){
                      $url = '/pu/profile';
                  }elseif (Auth::user()->access_level==='user') {
                      $url = '/profile';
                  }else{
                      $url = '/profile';
                  }
              @endphp

          <a href="{{ url(ltrim($url, '/')) }}" class="d-block">{{(!empty($profileData->firstname)) ? ($profileData->firstname.' '. $profileData->lastname ) : ('Unamed User')}}</a>
        </div>
        @if(Auth::user()->access_level === 'superadmin')
          <div class="ms-auto">
            <button id="sidebarThemeToggle" type="button" class="btn btn-sm btn-outline-secondary" title="Toggle sidebar theme">
              <i class="fas fa-adjust"></i>
            </button>
          </div>
        @endif
      </div>

<!-- super admin Menu -->
@Include('backend.template.superadminmenu')
<!-- /super admin Menu -->

<!-- National admin Menu -->
@if(Auth::user()->access_level == 'nationaladmin' && $packageVisibility->canSeeNationalModules())
 @Include('backend.template.nationalAdminMenu')
@endif
<!-- /National admin Menu -->

<!-- Regional admin Menu -->
@if(Auth::user()->access_level == 'regionaladmin' && $packageVisibility->canSeeRegionalModules())
 @Include('backend.template.regionalAdminMenu')
@endif
<!-- /Regional admin Menu -->

<!-- State admin Menu -->
@if(Auth::user()->access_level == 'stateadmin' && $packageVisibility->canSeeStateModules())
@Include('backend.template.stateAdminMenu')
@endif
<!-- /State admin Menu -->

<!-- Senatorial admin Menu -->
@if(Auth::user()->access_level == 'senatorialadmin' && $packageVisibility->canSeeSenatorialModules())
@Include('backend.template.senatorialAdminMenu')
@endif
<!-- /Senatorial admin Menu -->

<!-- Federal admin Menu -->
@if(Auth::user()->access_level == 'federaladmin' && $packageVisibility->canSeeFederalModules())
@Include('backend.template.federalAdminMenu')
@endif
<!-- /Federal admin Menu -->

<!-- lga admin Menu -->
@if(Auth::user()->access_level == 'lgaadmin' && $packageVisibility->canSeeLgaModules())
@Include('backend.template.lgaAdminMenu')
@endif
<!-- /lga admin Menu -->


<!-- ward admin Menu -->
@if(Auth::user()->access_level == 'wardadmin' && $packageVisibility->canSeeWardModules())
@Include('backend.template.wardAdminMenu')
@endif
<!-- /ward admin Menu -->

<!-- Pu admin Menu -->
@if(Auth::user()->access_level == 'puadmin' && $packageVisibility->canSeePollingUnitModules())
@Include('backend.template.puAdminMenu')
@endif
<!-- /Pu admin Menu -->

<!-- Member Menu -->
@if(Auth::user()->access_level == 'user')
  @Include('backend.template.userMenu')
@endif
<!-- /Member Menu -->



</div>
    <!-- /.sidebar -->
</aside>

<script>
  (function() {
    const sidebar = document.getElementById('app-sidebar');
    const toggleBtn = document.getElementById('sidebarThemeToggle');
    if (!sidebar) return;

    const applyTheme = (mode) => {
      if (mode === 'brand') {
        sidebar.classList.add('sidebar-themed');
      } else {
        sidebar.classList.remove('sidebar-themed');
      }
    };

    let currentMode = sidebar.dataset.sidebarMode || 'light';
    applyTheme(currentMode);

    const persistTheme = async (mode) => {
      const routeUrl = "{{ $sidebarThemeRoute }}";
      if (!routeUrl) return; // gracefully skip if route not available (e.g., cached routes not updated yet)
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      if (!token) return;

      try {
        await fetch(routeUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
          },
          body: JSON.stringify({ mode }),
        });
      } catch (error) {
        console.error('Could not save sidebar theme', error);
      }
    };

    if (toggleBtn) {
      toggleBtn.addEventListener('click', () => {
        currentMode = currentMode === 'brand' ? 'light' : 'brand';
        applyTheme(currentMode);
        persistTheme(currentMode);
      });
    }

    const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
    let exactSidebarMatch = false;
    sidebar.querySelectorAll('.sidebar-nav a.nav-link[href]').forEach((link) => {
      const href = link.getAttribute('href');
      if (!href || href === '#') return;

      const linkPath = new URL(href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
      if (linkPath !== currentPath) return;

      exactSidebarMatch = true;
      link.classList.add('active');

      let navItem = link.closest('.nav-item');
      while (navItem && navItem !== sidebar) {
        navItem.classList.add('menu-open');
        const parentLink = navItem.querySelector(':scope > a.nav-link');
        if (parentLink && parentLink !== link) {
          parentLink.classList.add('active');
        }
        navItem = navItem.parentElement?.closest('.nav-item');
      }
    });

    if (!exactSidebarMatch) {
      const section = currentPath.split('/').filter(Boolean)[0];
      const isElectionPath = section && (
        currentPath.startsWith(`/${section}/election/`) ||
        currentPath.startsWith(`/${section}/vote`) ||
        currentPath.startsWith(`/${section}/incident`)
      );

      if (isElectionPath) {
        const electionIndexLink = sidebar.querySelector(`.sidebar-nav a.nav-link[href="{{ url('${section}/elections') }}"]`);
        const electionMenuItem = electionIndexLink?.closest('.nav-treeview')?.closest('.nav-item');
        const electionMenuLink = electionMenuItem?.querySelector(':scope > a.nav-link');
        electionMenuItem?.classList.add('menu-open');
        electionMenuLink?.classList.add('active');
      }
    }
  })();
</script>


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
