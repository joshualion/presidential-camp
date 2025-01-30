<!-- Mobile Menu -->
<div class="d-lg-none fixed-top bg-primary border-top shadow-sm">
    <!-- Top Bar with Logo and Search -->
    <div class="d-flex justify-content-between align-items-center px-3 py-2">
      <!-- Logo -->
      <a class="navbar-brand" href="{{ route('timeline') }}" style="width: 100px; height: 60px; overflow: hidden;">
        <img 
          src="{{ !empty($SystemSetting->logo) ? url('uploads/system_images/'.$SystemSetting->logo) : url('uploads/system_images/logo.png') }}" 
          class="logo img-responsive embossed-image" 
          style="max-width: 100%; height: auto;" 
          alt="Logo"
        />
      </a>
  
      <!-- Search Box -->
      <form class="d-flex align-items-center flex-grow-1 ms-2 me-2" method="GET" action="/search">
        <div class="position-relative" style="width: 100%;">
          <!-- Search Input -->
          <input 
            type="text" 
            class="form-control form-control-lg" 
            placeholder="Search..." 
            style="border-radius: 20px; padding-right: 2.5rem;" 
            name="query"
          />
          <!-- Search Icon -->
          <i 
            class="fas fa-search text-muted position-absolute" 
            style="top: 50%; right: 0.8rem; transform: translateY(-50%); cursor: pointer;" 
            onclick="this.closest('form').submit()"
          ></i>
        </div>
      </form>
      
      
      
    </div>
  
    <!-- Bottom Navigation Menu -->
    <div class="d-flex justify-content-around py-2">
      <!-- Menu Item 1: Home -->
      <button class="btn btn-link text-muted shadow" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;" title="Home" onclick="location.href='/community/feed'">
        <i class="fas fa-home fa-lg" style="font-size: 1.2rem; color: #f9f9f9;"></i>
      </button>
  
      <!-- Menu Item 2: Activity -->
      <button class="btn btn-link text-muted shadow" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;" title="Activity" onclick="location.href='/activity'">
        <i class="fas fa-bell fa-lg" style="font-size: 1.2rem; color: #f9f9f9;"></i>
      </button>
  
      <!-- Menu Item 3: Profile -->
      <button class="btn btn-link text-muted shadow" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;" title="Profile" onclick="location.href='/community/profile'">
        <i class="fas fa-user fa-lg" style="font-size: 1.2rem; color: #f9f9f9;"></i>
      </button>
  
      <!-- Menu Item 4: Messages -->
      <button class="btn btn-link text-muted shadow" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;" title="Messages" onclick="location.href='/messages'">
        <i class="fas fa-envelope fa-lg" style="font-size: 1.2rem; color: #f9f9f9;"></i>
      </button>
  
      <!-- Menu Item 5: Logout (Replaced Explore with Logout) -->
      <button class="btn btn-link text-muted shadow" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;" title="Logout" onclick="location.href='{{route($profileData->access_level.'.logout')}}'">
        <i class="fas fa-sign-out-alt fa-lg" style="font-size: 1.2rem; color: #f9f9f9;"></i>
      </button>
    </div>
  </div>
  
 