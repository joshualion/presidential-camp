@php
    $supportGroupIds = \App\Support\SafeDatabase::hasTable('support_groups') && \App\Support\SafeDatabase::hasTable('user_support_group')
        ? $profileData->supportGroups()->pluck('support_groups.id')->values()->all()
        : [];

    $userScope = [
        'region_id' => $profileData->region_id ?? null,
        'state_id' => $profileData->state_id ?? null,
        'senatorial_district_id' => $profileData->senatorial_district_id ?? null,
        'federal_constituency_id' => $profileData->federal_constituency_id ?? null,
        'lga_id' => $profileData->lga_id ?? null,
        'ward_id' => $profileData->ward_id ?? null,
        'pu_id' => $profileData->polling_unit_id ?? null,
        'support_group_ids' => $supportGroupIds,
    ];
@endphp
<!-- Mobile Menu -->
<div class="d-lg-none fixed-top bg-primary border-top shadow-sm">
    <!-- Top Bar with Logo and Search -->
    <div class="d-flex justify-content-between align-items-center px-3 py-2">
      <!-- Logo -->
      <a class="navbar-brand" href="{{ route('timeline') }}" style="width: 100px; height: 60px; overflow: hidden;">
        <img
          src="{{ !empty($SystemSetting->logo) ? asset('uploads/system_images/'.$SystemSetting->logo) : asset('logo.png') }}"
          class="logo img-responsive embossed-image"
          style="max-width: 100%; height: auto;"
          alt="Logo"
        />
      </a>

      <!-- Search Box -->
      <div class="flex-grow-1 ms-2 me-2" id="mobile-search-root">
        <search-box mode="mobile" placeholder="Search posts, people..."></search-box>
      </div>



    </div>

    <!-- Bottom Navigation Menu -->
    <div class="d-flex justify-content-around py-2">
      <!-- Menu Item 1: Home -->
      <button class="btn btn-link text-muted shadow" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;" title="Home" onclick="location.href='{{ route('timeline') }}'">
        <i class="fas fa-home fa-lg" style="font-size: 1.2rem; color: #f9f9f9;"></i>
      </button>

      <!-- Menu Item 2: Activity -->
      <div style="width: 35px; height: 35px;" id="activity-bell-mobile">
        <activity-bell
            mode="mobile"
            :user-scope='@json($userScope)'
            fetch-url="{{ route('notifications.feed') }}"
            count-url="{{ route('notifications.unread') }}"
            mark-url="{{ route('notifications.markRead') }}"
            mark-all-url="{{ route('notifications.markAllRead') }}"
            bell-color="{{ $SystemSetting->dark_theme_color ?? '#008751' }}"
        ></activity-bell>
      </div>

      <!-- Menu Item 3: Profile -->
      <button class="btn btn-link text-muted shadow" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;" title="Profile" onclick="location.href='{{ route('profile.timeline') }}'">
        <i class="fas fa-user fa-lg" style="font-size: 1.2rem; color: #f9f9f9;"></i>
      </button>

      <!-- Menu Item 4: Messages -->
       <button class="btn btn-link text-muted shadow position-relative" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;" title="Messages" onclick="window.dispatchEvent(new Event('open-messenger'));">
        <i class="fas fa-envelope fa-lg" style="font-size: 1.2rem; color: #f9f9f9;"></i>
        <span id="mobile-message-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size: 0.65rem;">0</span>
      </button>

      <!-- Menu Item 5: Logout -->
      <form method="POST" action="{{ route('logout') }}" class="m-0">
        @csrf
        <button type="submit" class="btn btn-link text-muted shadow" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;" title="Logout">
          <i class="fas fa-sign-out-alt fa-lg" style="font-size: 1.2rem; color: #f9f9f9;"></i>
        </button>
      </form>
    </div>
  </div>
<!-- End of Mobile Menu -->
{{-- vertical Gap --}}
<div style="height: 80px;"></div>
