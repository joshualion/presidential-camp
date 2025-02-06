@php
use Illuminate\Support\Facades\Auth;

$profileData = Auth::check() ? Auth::user() : null;
$userAudience = $profileData ? $profileData->getAudience() : 'public';
$coverImage =  $profileData->cover_image ? url('uploads/member_images/'.$profileData->cover_image) : url('uploads/no_image.jpg');
@endphp

<!-- Cover Image -->
<div class="cover-image" style="background-image: url({{$coverImage}}); background-size: cover; background-position: center; height: 300px;">
</div>

<!-- Profile Image and Name (Positioned to the left for desktop) -->
<div class="container mt-n5">
    <div class="row align-items-end">
        <div class="col-md-2">
            <img class="profile-user-img img-fluid img-circle shadow-lg" src="{{(!empty($profileData->photo)) ? url('uploads/member_images/'.$profileData->photo) : url('uploads/no_image.jpg')}}" alt="profile" width="120" height="120" style="margin-top: -45%;">
        </div>
        <div class="col-md-10">
            <h2 class="mt-2" style="color: {{ $SystemSetting->dark_theme_color ?? '#008751' }};">{{$profileData->firstname}} {{$profileData->lastname}}</h2>
        </div>
    </div>
</div>

<!-- Three Column Layout for Links -->
<div class="container mt-4">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h5>
                    <p class="card-text">Admin Backend</p>
                    <a href="{{route($profileData->access_level.'.dashboard')}}"><button class="btn btn-primary btn-sm w-100 text-white"><i class="fas fa-eye"></i> Dashboard </button></a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-users me-2"></i>Followers</h5>
                    <p class="card-text">{{ number_format($followersCount) }} Followers</p>
                    <button class="btn btn-primary btn-sm w-100 text-white" @click.prevent="$root.$emit('show-followers')"><i class="fas fa-eye"></i> Followers</button>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-biking me-2"></i>Following</h5>
                    <p class="card-text">{{ number_format($followingCount) }} Following</p>
                    <button class="btn btn-primary btn-sm w-100 text-white" @click.prevent="showFollowing" ><i class="fas fa-eye"></i> Following</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Location and Volunteer Groups -->
<div class="container mt-4">
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-landmark me-2"></i>Location</h5>
                    <ul class="list-unstyled">
                        @if(($SystemSetting) && ($SystemSetting->package == 'national'))
                        <li><i class="fas fa-globe me-2"></i>Country: {{ ucwords(strtolower($profileData->country->name)) }}</li>
                        @endif
                        @if(($SystemSetting) && ($SystemSetting->package == 'national') || ($SystemSetting->package == 'regional'))
                        <li><i class="fas fa-globe me-2"></i>Region: {{ ucwords(strtolower($profileData->region->name)) }}</li>
                        @endif
                        <li><i class="fas fa-globe me-2"></i>State: {{ ucwords(strtolower($profileData->state->name)) }}</li>
                        <li><i class="fas fa-globe me-2"></i>LGA: {{ ucwords(strtolower($profileData->lga->name)) }}</li>
                        <li><i class="fas fa-globe me-2"></i>Ward: {{ ucwords(strtolower($profileData->ward->name)) }}</li>
                        <li><i class="fas fa-globe me-2"></i>PU: {{ ucwords(strtolower($profileData->pollingUnit->name)) }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-users me-2"></i>Volunteer Group(s)</h5>
                    <ul class="list-unstyled">
                        @foreach($userSupportGroups as $group)
                        <li class="d-flex justify-content-between align-items-center">
                            <span>{{ $group }}</span>
                            <a href="{{ route('select.support.group') }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Container for post and ads -->
<div class="container mt-4">
    <div class="row">
        <!-- Left Side: Create & View Post Section -->
        <div class="col-md-8">
            <div class="create-post">
                 @include('frontend.createProfilePost') <!-- No extra divs needed -->
            </div>

            <div class="shadow-sm">
                <div class=" card card-body">   
                    <h5 class="card-title"><i class="fas fa-comment me-2"></i> Posts</h5>
                    <div id="app" class="newsfeed">
                        <profile-feed :audience="'{{ $userAudience }}'"></profile-feed>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Sidebar (Visible on Desktop) -->
        @desktop()
        <div class="col-md-4 sidebar">
            @include('frontend.right-sidebar')
        </div>
        @enddesktop()
    </div>
</div>

<style>
    .cover-image {
        position: relative;
        height: 300px;
        background-size: cover;
        background-position: center;
    }

    .profile-user-img {
        position: relative;
        z-index: 1;
        border: 4px solid #fff; /* Add a border to make the profile image stand out */
    }

    .create-post {
        width: 100%;
    }

    /* Ensure the profile image overlaps the cover image by 45% */
    .profile-user-img {
        margin-top: -45%;
    }

    /* Adjust the profile name positioning */
    .col-md-10 h2 {
        margin-top: 20px; /* Add some spacing between the profile image and the name */
    }
</style>