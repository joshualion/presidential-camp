@php
    $accessLevel = $profileData->access_level ?? 'user';
    $audienceData = app(\App\Services\CommunityAudienceService::class)->options($profileData);
    $defaultAudience = $audienceData['default'];
    $audienceOptions = $audienceData['options'];
    $defaultAudienceLabel = $defaultAudience['label'] ?? 'My Campaign Scope';
    $communityMediaPolicy = app(\App\Services\CommunityModerationService::class)->mediaPolicy();
@endphp

<!-- New Post Section -->
<div class="col-md-6" style="overflow-y: auto; max-height: 150vh;">  
            
    <div class="card mb-3 shadow-sm">
        <div class="card-body d-flex align-items-center">
            <!-- Profile Picture -->
            <img src="{{(!empty($profileData->photo)) ? url('uploads/member_images/'.$profileData->photo) : url('uploads/no_image.jpg')}}" alt="Profile Picture" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
    
            <!-- Trigger Button -->
            <button class="btn btn-light w-100 text-start" data-bs-toggle="modal" data-bs-target="#postModal" data-type="text">
                Got something on your mind? Share it with us!
            </button>
        </div>
    
        <!-- Toolbar -->
        <div class="d-flex justify-content-between px-3 py-2 border-top mt-1 mb-0 ">
            <button class="btn btn-light btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#postModal" data-type="video" @disabled(!$communityMediaPolicy['videos_enabled'])>
                <i class="fas fa-video text-danger me-2"></i>Video
            </button>
            <button class="btn btn-light btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#postModal" data-type="photo" @disabled(!$communityMediaPolicy['images_enabled'])>
                <i class="fas fa-camera text-primary me-2"></i>Photo
            </button>
            <button class="btn btn-light btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#postModal" data-type="text">
                <i class="fas fa-pen text-success me-2"></i>Write
            </button>
        </div>
    </div>


     <!-- Include Newsfeed -->
     @include('frontend.newsfeed')
     <!-- Include Newsfeed -->

</div>



{{-- Post Modal --}}
<div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel" aria-hidden="true"
     data-access-level="{{ $accessLevel }}"
     data-support-group-status="{{ $audienceData['support_group_status'] ?? 'deferred' }}">

    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="postModalLabel">Create a Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" id="postForm">
                @csrf
            <div class="modal-body">
                <div id="postValidation" class="alert alert-danger d-none small" role="alert"></div>
                <div class="alert alert-info py-2 small mb-3" role="status">
                    Keep it concise: up to 750 characters. Images up to {{ $communityMediaPolicy['image_max_mb'] }}MB.
                    @if($communityMediaPolicy['videos_enabled'])
                        Videos up to {{ $communityMediaPolicy['video_max_mb'] }}MB.
                    @else
                        Video uploads are currently disabled.
                    @endif
                </div>
                <div class="d-flex mb-3">
                    <!-- Profile Picture -->
                    <img src="{{(!empty($profileData->photo)) ? url('uploads/member_images/'.$profileData->photo) : url('uploads/no_image.jpg')}}" 
                         alt="Profile Picture" 
                         class="rounded-circle me-3" 
                         style="width: 50px; height: 50px; object-fit: cover;">
                    <div>
                        <h6 class="mb-0">{{ $profileData->firstname }} {{ $profileData->lastname }}</h6>
                        <small class="text-muted" id="audienceDisplay">
                            Sharing with: {{ $defaultAudienceLabel }}
                        </small>
                    </div>
                </div>

                <!-- Icon Dropdown for Audience Selection -->
                <div class="dropdown mb-3 audience-dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="audienceDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-people"></i><small> Change <i class="fas fa-users"></i></small>
                    </button>
                    <ul class="dropdown-menu" id="audienceDropdownMenu" aria-labelledby="audienceDropdown">
                    @foreach($audienceOptions as $option)
                        <li>
                            <a class="dropdown-item" href="#"
                               data-label="{{ $option['label'] }}"
                               data-audience="{{ $option['audience_type'] }}"
                               data-scope-type="{{ $option['audience_scope_type'] }}"
                               data-scope-id="{{ $option['audience_scope_id'] }}"
                               data-group-id="{{ $option['audience_group_id'] }}">
                                {{ $option['label'] }}
                            </a>
                        </li>
                    @endforeach
                    </ul>
                </div>

                <!-- Hidden Field for Audience -->
                <input type="hidden" name="audience_type" id="audienceTypeInput" value="{{ $defaultAudience['audience_type'] }}">
                <input type="hidden" name="audience_scope_type" id="audienceScopeTypeInput" value="{{ $defaultAudience['audience_scope_type'] }}">
                <input type="hidden" name="audience_scope_id" id="audienceScopeIdInput" value="{{ $defaultAudience['audience_scope_id'] }}">
                <input type="hidden" name="audience_group_id" id="audienceGroupIdInput" value="">
                <input type="hidden" name="audience" id="audienceInput" value="{{ $defaultAudience['audience'] }}">

                 <!-- Placeholder for dynamic content -->
                        <div id="postContent"></div>
            </div>
            <div class="modal-footer">
                 <!-- Toolbar -->
                 <div class="d-flex justify-content-between px-3 py-2 border-top mt-1 mb-0 ">
                    <button class="btn btn-light btn-sm" type="button" data-type="video" @disabled(!$communityMediaPolicy['videos_enabled'])>
                        <i class="fas fa-video text-danger me-2"></i>Video
                    </button>
                    <button class="btn btn-light btn-sm" type="button" data-type="photo" @disabled(!$communityMediaPolicy['images_enabled'])>
                        <i class="fas fa-camera text-primary me-2"></i>Photo
                    </button>
                    <button class="btn btn-light btn-sm" type="button" data-type="text">
                        <i class="fas fa-pen text-success me-2"></i>Write
                    </button>
                </div>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Post</button>
            </div>
        </form>
        </div>
    </div>
</div>

<!-- Include Bootstrap JS and jQuery -->
<script src="{{asset('frontend/js/bootstrap-bundle.min.js')}}"></script>
<script src="{{asset('frontend/js/jquery.min.js')}}"></script>
<script src="{{asset('frontend/js/post.js')}}"></script>

{{-- Submit form with Ajax --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const postForm = document.getElementById('postForm');
    const modalElement = document.getElementById('postModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

    const cleanModalState = () => {
        document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        modalElement.removeAttribute('inert');
    };

    const hideModalCleanly = () => {
        modal.hide();
        cleanModalState();
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
        modalElement.removeAttribute('aria-modal');
        modalElement.removeAttribute('role');
    };

    // Before showing, clear any stale backdrops/body locks from previous modals
    modalElement.addEventListener('show.bs.modal', cleanModalState);

    postForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (typeof window.validatePostForm === 'function') {
            const validation = window.validatePostForm(postForm);
            if (!validation.valid) {
                return;
            }
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token not found');
            alert('CSRF token is missing.');
            return;
        }

        // Disable the submit button to prevent multiple submissions
        const submitButton = postForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Posting...';

        // Create a FormData object
        const formData = new FormData(this);

        // Send an AJAX request
        fetch('{{ route('posts.store') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            },
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // Clear the form
                    postForm.reset();

                    // Hide the modal properly
                    hideModalCleanly();

                    if (data.post) {
                        window.dispatchEvent(new CustomEvent('community-post-created', { detail: data.post }));
                    }

                    // Log success (no page reload)
                    console.log('Post submitted successfully. No page reload needed.');
                } else {
                    throw new Error(data.message || 'Failed to create post.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request. Please try again.');
            })
            .finally(() => {
                // Re-enable the submit button
                submitButton.disabled = false;
                submitButton.textContent = 'Post';
            });
    });

    // Ensure the modal's inert state is removed when hidden
    modalElement.addEventListener('hidden.bs.modal', function () {
        cleanModalState();
        modalElement.removeAttribute('inert');
    });
});
</script>

{{-- Audience dynamic Selection--}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Audience Selection
        const audienceInput = document.getElementById('audienceInput');
        const audienceTypeInput = document.getElementById('audienceTypeInput');
        const audienceScopeTypeInput = document.getElementById('audienceScopeTypeInput');
        const audienceScopeIdInput = document.getElementById('audienceScopeIdInput');
        const audienceGroupIdInput = document.getElementById('audienceGroupIdInput');
        const audienceDisplay = document.getElementById('audienceDisplay');
        const dropdownToggle = document.getElementById('audienceDropdown');
        const dropdownMenu = document.getElementById('audienceDropdownMenu');

        // Manual toggle to ensure dropdown opens reliably
        if (dropdownToggle && dropdownMenu) {
            const wrapper = dropdownToggle.closest('.audience-dropdown');
            if (wrapper) {
                wrapper.style.position = 'relative';
                wrapper.style.overflow = 'visible';
            }

            dropdownToggle.addEventListener('click', (evt) => {
                evt.preventDefault();
                dropdownMenu.classList.toggle('show');
                dropdownMenu.style.display = dropdownMenu.classList.contains('show') ? 'block' : 'none';
                dropdownToggle.setAttribute('aria-expanded', dropdownMenu.classList.contains('show'));
            });

            document.addEventListener('click', (evt) => {
                if (!dropdownMenu.classList.contains('show')) return;
                if (dropdownToggle.contains(evt.target) || dropdownMenu.contains(evt.target)) return;
                dropdownMenu.classList.remove('show');
                dropdownToggle.setAttribute('aria-expanded', 'false');
            });
        }

        const setAudience = (item) => {
            if (!item) return;
            const legacyAudience = {
                region: 'region',
                state: 'state',
                senatorial_district: 'state',
                federal_constituency: 'state',
                lga: 'lga',
                ward: 'ward',
                pu: 'pu',
            };
            if (audienceTypeInput) audienceTypeInput.value = item.dataset.audience || 'own_scope';
            if (audienceScopeTypeInput) audienceScopeTypeInput.value = item.dataset.scopeType || '';
            if (audienceScopeIdInput) audienceScopeIdInput.value = item.dataset.scopeId || '';
            if (audienceGroupIdInput) audienceGroupIdInput.value = item.dataset.groupId || '';
            if (audienceInput) audienceInput.value = item.dataset.audience === 'global' ? 'public' : (legacyAudience[item.dataset.scopeType] || 'public');
            if (audienceDisplay) audienceDisplay.textContent = `Sharing with: ${item.dataset.label || 'My Campaign Scope'}`;
            dropdownMenu?.classList.remove('show');
        };

        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                setAudience(this);
            });
        });
    });
</script>


<style>


.drag-drop-area {
    border: 2px dashed #ccc;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: background-color 0.3s;
}

.drag-drop-area.drag-over {
    background-color: #f0f8ff;
    border-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
}

.drag-drop-area .drag-drop-text {
    font-size: 16px;
    color: #666;
}

.file-input {
    display: none;
}

</style>
