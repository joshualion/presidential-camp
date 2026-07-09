<!-- Sidebar -->
<div class="card shadow-lg border-0 rounded-lg" style="background-color: #f8f9fa; overflow: hidden;">
    <div class="card-header bg-primary text-white text-center">
        <h6 class="card-title mb-0">Profile</h6>
    </div>
    <div class="card-body">
        <ul class="nav flex-column">
            <div class="text-center mb-4">
                <a class="nav-link text-dark p-0" href="{{route('profile.timeline')}}">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <img class="profile-user-img img-fluid img-circle shadow-sm" src="{{(!empty($profileData->photo)) ? url('uploads/member_images/'.$profileData->photo) : url('uploads/no_image.jpg')}}" alt="profile" width="60" height="60">
                        <div class="text-start">
                            <h6 class="mb-0 fw-bold" style="color: {{ $SystemSetting->dark_theme_color ?? '#008751' }};">{{$profileData->firstname}} {{$profileData->lastname}}</h6>
                            @php
                                $primaryRole = optional($profileData->roles->first())->name ?? ucfirst($profileData->access_level ?? 'member');
                            @endphp
                            <small class="text-muted d-block">{{ $primaryRole }}</small>
                        </div>
                    </div>
                </a>
              
               @mobile()
               <hr>
<ul class="nav d-flex justify-content-around align-items-center" style="gap: 5px; list-style: none; padding: 0; margin: 0;">
    <!-- Notification Icon -->
    <li class="nav-item position-relative">
        <a class="nav-link p-1 rounded-circle d-flex justify-content-center align-items-center shadow" href="#" title="Notifications" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 17.5px; height: 17.5px;">
            <i class="fas fa-bell" style="font-size: 0.6rem; color: #f9f9f9;"></i>
        </a>
        <!-- Optional Badge -->
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning" style="font-size: 0.375rem;">
            3
        </span>
    </li>

    <!-- Message Icon -->
    <li class="nav-item position-relative">
        <a class="nav-link p-1 rounded-circle d-flex justify-content-center align-items-center shadow" href="#" title="Messages" style="background-color:  {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 17.5px; height: 17.5px;">
            <i class="fas fa-comment" style="font-size: 0.6rem; color: #f9f9f9;"></i>
        </a>
        <!-- Optional Badge -->
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.375rem;">
            5
        </span>
    </li>

    <!-- Logout Icon -->
    <li class="nav-item">
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="nav-link p-1 rounded-circle d-flex justify-content-center align-items-center shadow border-0" title="Logout" style="background-color:  {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 17.5px; height: 17.5px;">
                <i class="fas fa-power-off" style="font-size: 0.6rem; color:#f9f9f9;"></i>
            </button>
        </form>
    </li>
</ul>
@endmobile()

                <hr>
            </div>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-dark d-flex align-items-center" href="{{route($profileData->access_level.'.dashboard')}}">
                    <i class="fas fa-tachometer-alt nav-icon  me-2"></i> Dashboard <span class="badge bg-primary ms-auto"><i class="fas  fa-eye"></i></span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-dark d-flex align-items-center" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#followersModal">
                    <i class="fas fa-users nav-icon  me-2"></i> Followers <span class="badge bg-primary ms-auto">{{ number_format($followersCount) }}</span>
                </a>
            </li>
            <li class="nav-item mb-4">
                <a class="nav-link text-dark d-flex align-items-center" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#followingModal">
                    <i class="fas fa-biking nav-icon  me-2"></i> Following <span class="badge bg-primary ms-auto">{{number_format($followingCount) }}</span>
                </a>
            </li>
            
            <hr class="my-3">
        @desktop()
            <h6 class="card-title mb-2" style="color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important;">
                <i class="fas fa-landmark me-2 "></i> Location
            </h6>
            @if(($SystemSetting) && ($SystemSetting->package == 'national'))
            <li class="nav-item small">
                <a class="nav-link text-dark ps-3"><i class="fas fa-globe"></i> Country: {{ ucwords(strtolower($profileData->country->name)) }}
                    
                </a>
            </li>
            @endif
            @if(($SystemSetting) && ($SystemSetting->package == 'national') || ($SystemSetting->package == 'regional'))
            <li class="nav-item small">
                <a class="nav-link text-dark ps-3"><i class="fas fa-globe"></i> Region: {{ ucwords(strtolower($profileData->region->name)) }}
                    
                </a>
            </li>
            @endif
            <li class="nav-item small">
                <a class="nav-link text-dark ps-3"><i class="fas fa-globe"></i> State: {{ ucwords(strtolower($profileData->state->name)) }}
                    
                </a>
            </li>
            <li class="nav-item small">
                <a class="nav-link text-dark ps-3"><i class="fas fa-globe"></i> LGA: {{ ucwords(strtolower($profileData->lga->name)) }}
                    
                </a>
            </li>
            <li class="nav-item small">
                <a class="nav-link text-dark ps-3"><i class="fas fa-globe"></i> Ward: {{ ucwords(strtolower($profileData->ward->name)) }}
                    
                </a>
            </li>
            <li class="nav-item small">
                <a class="nav-link text-dark ps-3"><i class="fas fa-globe"></i> PU: {{ ucwords(strtolower($profileData->pollingUnit->name)) }}
                     </a>
            </li>

            <hr class="my-3">
            <h6 class="card-title mb-2" style="color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important;">
                <i class="fas fa-users me-2 "></i> Volunteer Group(s)
            </h6>
            @foreach($userSupportGroups as $group)
            <li class="nav-item small d-flex align-items-center justify-content-between">
                <a class="nav-link text-dark flex-grow-1 ps-3">{{ $group }}</a>
                <a class="btn btn-sm " href="{{ route(auth()->user()->access_level.'.account.select-support-group') }}">
                    <i class="fas fa-edit"></i>
                </a>
            </li>
            @endforeach
    @enddesktop()
        </ul>
    </div>
</div>

@include('frontend.followers')
