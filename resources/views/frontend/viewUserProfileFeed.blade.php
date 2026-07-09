@php
use App\Services\ScopedMessagingRecipientService;

$userAudience = $userData ? $userData->getAudience() : 'public';
$coverImage = $userData->cover_image ? url('uploads/member_images/'.$userData->cover_image) : url('uploads/no_image.jpg');
$profileImage = !empty($userData->photo) ? url('uploads/member_images/'.$userData->photo) : url('uploads/no_image.jpg');
$canMessage = $profileData && $userData
    ? app(ScopedMessagingRecipientService::class)->canOpenConversationWith($profileData, $userData)
    : false;
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
    @if($canMessage)
        <button
            type="button"
            class="btn message-cover-btn"
            onclick="window.dispatchEvent(new CustomEvent('open-messenger', { detail: { userId: {{ $userData->id }} } }))"
            title="Send Message"
        >
            <i class="fas fa-comment-dots me-2"></i> Message
        </button>
    @endif
</div>

<!-- Start Chat Modal -->
<div class="modal fade" id="startChatModal" tabindex="-1" aria-labelledby="startChatModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="startChatModalLabel">Send a message to {{ $userData->firstname }} {{ $userData->lastname }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="startChatForm">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea class="form-control" name="message" rows="3" placeholder="Type your message..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary text-white">Send</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Profile Image and Name -->
<div class="container mt-n5">
    <div class="row align-items-end">
        <div class="col-md-2 profile-img-overlap position-relative">
            <div class="profile-img-container position-relative" style="width: 120px; height: 120px;">
                <img class="profile-user-img img-fluid img-circle shadow-lg" src="{{ $profileImage }}" alt="profile" width="120" height="120">

            </div>
        </div>
        <div class="col-md-10">
            <h2 class="mt-2" style="color: {{ $SystemSetting->dark_theme_color ?? '#008751' }};">
                {{ $userData->firstname }} {{ $userData->lastname }}
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
                    <h5 class="card-title"><i class="fas fa-file-alt me-2"></i>Posts</h5>
                    <p class="card-text">{{ number_format($postsCount ?? 0) }} Posts</p>
                    <button class="btn btn-primary btn-sm w-100 text-white" onclick="scrollToPosts()">
                        <i class="fas fa-arrow-down"></i> View Posts
                    </button>
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
                        <li><i class="fas fa-globe me-2"></i>Country: {{ ucwords(strtolower($userData->country->name)) }}</li>
                        @endif
                        @if(($SystemSetting) && ($SystemSetting->package == 'national') || ($SystemSetting->package == 'regional'))
                        <li><i class="fas fa-globe me-2"></i>Region: {{ ucwords(strtolower($userData->region->name)) }}</li>
                        @endif
                        <li><i class="fas fa-globe me-2"></i>State: {{ ucwords(strtolower($userData->state->name)) }}</li>
                        <li><i class="fas fa-globe me-2"></i>LGA: {{ ucwords(strtolower($userData->lga->name)) }}</li>
                        <li><i class="fas fa-globe me-2"></i>Ward: {{ ucwords(strtolower($userData->ward->name)) }}</li>
                        <li><i class="fas fa-globe me-2"></i>PU: {{ ucwords(strtolower($userData->pollingUnit->name)) }}</li>
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
        <div class="col-md-9" id="user-posts-section">

                    <div class="shadow-sm">
                <div class=" card card-body">
                    <h5 class="card-title"><i class="fas fa-comment me-2"></i> Posts</h5>
                    @php
                        $messageRoutes = [
                            'conversations' => route(auth()->user()->access_level.'.messages.conversations'),
                            'conversationMessages' => route(auth()->user()->access_level.'.messages.conversation.messages', ['conversation' => '__CONVERSATION__']),
                            'conversationSend' => route(auth()->user()->access_level.'.messages.conversation.send', ['conversation' => '__CONVERSATION__']),
                            'conversationTyping' => route(auth()->user()->access_level.'.messages.conversation.typing', ['conversation' => '__CONVERSATION__']),
                            'conversationRead' => route(auth()->user()->access_level.'.messages.conversation.read', ['conversation' => '__CONVERSATION__']),
                            'recipients' => route(auth()->user()->access_level.'.messages.recipients'),
                            'start' => route(auth()->user()->access_level.'.messages.start'),
                            'ensure' => route(auth()->user()->access_level.'.messages.ensure'),
                            'noImage' => asset('uploads/no_image.jpg'),
                            'memberImageBase' => asset('uploads/member_images'),
                        ];
                    @endphp
                    <div id="app" class="newsfeed">
                        <profile-feed
                          :audience="'{{ $userAudience }}'"
                          :profile-data='@json($profileData)'
                          :username="'{{ $userData->username }}'"
                        ></profile-feed>
                        <messenger-widget
                          :profile-data='@json($profileData)'
                          :routes='@json($messageRoutes)'
                        ></messenger-widget>
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



{{-- Include Followers Modals --}}
@include('frontend.followers')


<script>
    $(document).ready(function() {
    const hideModalCleanly = (modalId) => {
        const el = document.getElementById(modalId);
        if (!el) return;
        const modal = bootstrap.Modal.getOrCreateInstance(el);
        modal.hide();
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

    // Smooth scroll to posts section
    function scrollToPosts() {
        const postsSection = document.getElementById('user-posts-section');
        if (!postsSection) return;
        postsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Start conversation modal submission
    const startChatForm = document.getElementById('startChatForm');
    if (startChatForm) {
        startChatForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = startChatForm.querySelector('button[type="submit"]');
            const textarea = startChatForm.querySelector('textarea[name="message"]');
            const body = textarea.value.trim();
            if (!body) {
                alert('Please enter a message.');
                return;
            }
            btn.disabled = true;
            try {
                await fetch(@json(route(auth()->user()->access_level.'.messages.start')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        recipient_id: "{{ $userData->id }}",
                        message: body,
                    }),
                });
                textarea.value = '';
                const modalEl = document.getElementById('startChatModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            } catch (error) {
                alert('Unable to send message. Please try again.');
            } finally {
                btn.disabled = false;
            }
        });
    }
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

    .message-cover-btn {
        position: absolute;
        right: 12px;
        bottom: 12px;
        background: {{ $SystemSetting->dark_theme_color ?? '#008751' }};
        color: #fff;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.18);
        font-weight: 600;
    }

    .message-cover-btn:hover {
        opacity: 0.9;
        color: #fff;
    }
</style>
