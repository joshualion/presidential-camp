@php
use Illuminate\Support\Facades\Auth;

$profileData = Auth::check() ? Auth::user() : null;
$userAudience = $profileData ? $profileData->getAudience() : 'public';
$coverImage = $profileData->cover_image ? url('uploads/member_images/'.$profileData->cover_image) : url('uploads/no_image.jpg');
$profileImage = !empty($profileData->photo) ? url('uploads/member_images/'.$profileData->photo) : url('uploads/no_image.jpg');
@endphp

<style>
    .community-profile-stat-card .card-title {
        color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important;
    }

    .community-profile-stat-card .card-text {
        color: #212529 !important;
    }
</style>

<!-- Cover Image -->
<div class="cover-image position-relative" style="background-image: url({{ $coverImage }});">
    <button class="btn btn-primary position-absolute bottom-0 end-0 m-3 update-cover-btn" data-bs-toggle="modal" data-bs-target="#updateCoverModal" title="Change Cover Photo">
        <i class="fas fa-camera"></i>
    </button>
</div>

<!-- Profile Image and Name -->
<div class="container mt-n5">
    <div class="row align-items-end">
        <div class="col-md-2 profile-img-overlap position-relative">
            <div class="profile-img-container position-relative" style="width: 120px; height: 120px;">
                <img class="profile-user-img img-fluid img-circle shadow-lg" src="{{ $profileImage }}" alt="profile" width="120" height="120">
                <button class="btn btn-primary position-absolute update-profile-btn" style="right: 10px; bottom: 0; width: 30px; height: 30px; padding: 0; border-radius: 50%;" data-bs-toggle="modal" data-bs-target="#updateProfileModal" title="Change Profile Photo">
                    <i class="fas fa-camera fa-sm"></i>
                </button>
            </div>
        </div>
        <div class="col-md-10">
            <h2 class="mt-2" style="color: {{ $SystemSetting->dark_theme_color ?? '#008751' }};">
                {{ $profileData->firstname }} {{ $profileData->lastname }}
            </h2>
        </div>
    </div>
</div>

<!-- Modals and Scripts remain unchanged -->


<!-- Three Column Layout for Links -->
<div class="container mt-4">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm community-profile-stat-card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h5>
                    <p class="card-text">Backend</p>
                    <a href="{{ route($profileData->access_level . '.dashboard') }}">
                        <button class="btn btn-primary btn-sm w-100 text-white"><i class="fas fa-eye"></i> Dashboard</button>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm community-profile-stat-card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-users me-2"></i>Followers</h5>
                    <p class="card-text">{{ number_format($followersCount) }} Followers</p>
                    <button class="btn btn-primary btn-sm w-100 text-white" data-bs-toggle="modal" data-bs-target="#followersModal">
                        <i class="fas fa-users"></i> Followers
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm community-profile-stat-card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-biking me-2"></i>Following</h5>
                    <p class="card-text">{{ number_format($followingCount) }} Following</p>
                    <button class="btn btn-primary btn-sm w-100 text-white" data-bs-toggle="modal" data-bs-target="#followingModal">
                        <i class="fas fa-biking"></i> Following
                    </button>
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
                            <a href="{{ route(auth()->user()->access_level.'.account.select-support-group') }}" class="btn btn-sm btn-default"><i class="fas fa-edit"></i></a>
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
        <div class="col-md-9">
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
        <div class="col-md-3 sidebar">
            @include('frontend.right-sidebar')
        </div>
        @enddesktop()
    </div>
</div>

<!-- Modals for Updating Images -->
<!-- Cover Photo Modal -->
<div class="modal fade" id="updateCoverModal" tabindex="-1" aria-labelledby="updateCoverModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Cover Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateCoverForm" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="cover_image" class="form-control" accept="image/*">
                    <button type="submit" class="btn btn-primary mt-3">Upload & Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Profile Photo Modal -->
<div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Profile Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateProfileForm" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    <button type="submit" class="btn btn-primary mt-3">Upload & Save</button>
                </form>
            </div>
        </div>
    </div>
</div>


{{-- Include Followers Modals --}}
@include('frontend.followers')


<script>
    $(document).ready(function() {
    const hideModalCleanly = (modalId) => {
        const el = document.getElementById(modalId);
        if (!el) return;
        const modal = bootstrap.Modal.getOrCreateInstance(el);
        modal.hide();
        // Remove any lingering backdrop and body lock
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
    };

    // Handle Profile Photo Upload
    $('#updateProfileForm').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        $.ajax({
            url: "{{ route('update.profile.photo') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.success) {
                    $('.profile-user-img').attr('src', response.image_url);
                    hideModalCleanly('updateProfileModal');
                } else {
                    alert(response.error || 'Upload failed. Please try a different image.');
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Upload failed. Please try a different image (max 5MB, jpg/png/gif/webp).');
            }
        });
    });

    // Handle Cover Photo Upload
    $('#updateCoverForm').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        $.ajax({
            url: "{{ route('update.cover.photo') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.success) {
                    $('.cover-image').css('background-image', 'url(' + response.image_url + ')');
                    hideModalCleanly('updateCoverModal');
                } else {
                    alert(response.error || 'Upload failed. Please try a different image.');
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Upload failed. Please try a different image (max 5MB, jpg/png/gif/webp).');
            }
        });
    });
});

</script>

<style>
    .cover-image {
        position: relative;
        height: 300px;
        background-size: cover;
        background-position: center;
    }

    .profile-user-img {

    }

    .create-post {
        width: 100%;
    }

    /* Adjust the profile name positioning */
    .col-md-10 h2 {
        margin-top: 20px; /* Add some spacing between the profile image and the name */
    }

    /* Ensure the profile image button stays within the profile image */
    .profile-img-container {
        overflow: hidden; /* Clip the button to the container */
        border-radius: 50%; /* Ensure the container is circular */
        margin-top: -45%; /* Ensure the profile image overlaps the cover image by 45% */
        position: relative;
        z-index: 1;
        border: 4px solid #fff; /* Add a border to make the profile image stand out */

    }

    /* Position the camera icon directly on top of the profile photo in the bottom right corner */
    .update-profile-btn {
        right: 15px;
        bottom: 15px;
        width: 30px;
        height: 30px;

        padding: 0;
        border-radius: 50%;
    }

    /* Mobile-specific adjustments */
    @media (max-width: 767.98px) {
        .profile-img-container {
            margin-top: -30%; /* Adjust overlap for mobile */
        }
        .update-profile-btn {
            right: 5px !important; /* Adjust button position */
            bottom: 5px !important;
        }
    }
</style>
