@php
    $accessLevel = $profileData->access_level ?? 'user';
    $maxAudienceByAccess = [
        'superadmin' => 'public',
        'nationaladmin' => 'public',
        'regionaladmin' => 'region',
        'stateadmin' => 'state',
        'lgaadmin' => 'lga',
        'wardadmin' => 'ward',
        'puadmin' => 'pu',
        'user' => 'pu',
    ];
    $packageMaxAudience = [
        'national' => 'public',
        'regional' => 'region',
        'state' => 'state',
        'lga' => 'lga',
        'others' => 'pu',
    ];
    $audienceRank = ['public' => 5, 'region' => 4, 'state' => 3, 'lga' => 2, 'ward' => 1, 'pu' => 0];
    $accessAudience = $maxAudienceByAccess[$accessLevel] ?? 'pu';
    $packageAudience = $packageMaxAudience[$SystemSetting->package ?? ''] ?? 'pu';
    $allowedRank = min($audienceRank[$accessAudience] ?? 0, $audienceRank[$packageAudience] ?? 0);
    $defaultAudience = array_search($allowedRank, $audienceRank, true) ?: 'pu';
    $audienceLabels = [
        'public' => 'Nationwide Audience',
        'region' => (optional($profileData->region)->name ?? 'Region') . ' Audience',
        'state' => (optional($profileData->state)->name ?? 'State') . ' Audience',
        'lga' => (optional($profileData->lga)->name ?? 'LGA') . ' Audience',
        'ward' => 'Ward: ' . (optional($profileData->ward)->name ?? '') . ' Audience',
        'pu' => 'PU: ' . (optional($profileData->pollingUnit)->name ?? '') . ' Audience',
    ];
    $defaultAudienceLabel = $audienceLabels[$defaultAudience] ?? 'Everyone';
@endphp

<!-- New Post Section -->
<div class="col-md-12" style="overflow-y: auto; max-height: 150vh;">  
            
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
            <button class="btn btn-light btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#postModal" data-type="video">
                <i class="fas fa-video text-danger me-2"></i>Video
            </button>
            <button class="btn btn-light btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#postModal" data-type="photo">
                <i class="fas fa-camera text-primary me-2"></i>Photo
            </button>
            <button class="btn btn-light btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#postModal" data-type="text">
                <i class="fas fa-pen text-success me-2"></i>Write
            </button>
        </div>
    </div>


   

</div>



{{-- Post Modal --}}
<div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel" aria-hidden="true"
     data-allowed-audience="{{ $defaultAudience }}"
     data-allowed-rank="{{ $allowedRank }}"
     data-access-level="{{ $accessLevel }}"
     data-region-name="{{ optional($profileData->region)->name }}"
     data-state-name="{{ optional($profileData->state)->name }}"
     data-lga-name="{{ optional($profileData->lga)->name }}"
     data-ward-name="{{ optional($profileData->ward)->name }}"
     data-pu-name="{{ optional($profileData->pollingUnit)->name }}">

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
                    Keep it concise: up to 750 characters. Images up to 1MB, videos up to 20MB.
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
                        @if($SystemSetting && $SystemSetting->package == 'national')
                        <li><a class="dropdown-item" href="#" data-value="public">Nationwide Audience</a></li>
                    @endif
                    @if($SystemSetting && in_array($SystemSetting->package, ['national', 'regional']))
                        <li><a class="dropdown-item" href="#" data-value="region">{{ $profileData->region->name }} Audience</a></li>
                    @endif
                    @if($SystemSetting && in_array($SystemSetting->package, ['national', 'regional', 'state']))
                        <li><a class="dropdown-item" href="#" data-value="state">{{ $profileData->state->name }} Audience</a></li>
                    @endif
                    @if($SystemSetting && in_array($SystemSetting->package, ['national', 'regional', 'state', 'lga']))
                        <li><a class="dropdown-item" href="#" data-value="lga">{{ $profileData->lga->name }} Audience</a></li>
                   @endif
                    @if($SystemSetting && in_array($SystemSetting->package, ['national', 'regional', 'state', 'lga', 'others']))
                        <li><a class="dropdown-item" href="#" data-value="ward">Ward: {{ $profileData->ward->name }} Audience</a></li>
                        <li><a class="dropdown-item" href="#" data-value="pu">PU: {{ $profileData->pollingUnit->name }} Audience</a></li>
                    @endif
                    </ul>
                </div>

                <!-- Hidden Field for Audience -->
                <input type="hidden" name="audience" id="audienceInput" value="{{ $defaultAudience }}">

                 <!-- Placeholder for dynamic content -->
                        <div id="postContent"></div>
            </div>
            <div class="modal-footer">
                 <!-- Toolbar -->
                 <div class="d-flex justify-content-between px-3 py-2 border-top mt-1 mb-0 ">
                    <button class="btn btn-light btn-sm" type="button" data-type="video">
                        <i class="fas fa-video text-danger me-2"></i>Video
                    </button>
                    <button class="btn btn-light btn-sm" type="button" data-type="photo">
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
        const modal = new bootstrap.Modal(modalElement);
    
        postForm.addEventListener('submit', function (e) {
            e.preventDefault();
    
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                alert('CSRF token is missing.');
                return;
            }
    
            if (typeof window.validatePostForm === 'function') {
                const validation = window.validatePostForm(postForm);
                if (!validation.valid) {
                    return;
                }
            }

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
                    modal.hide();
    
                    // Wait for the modal to fully close
                    modalElement.addEventListener('hidden.bs.modal', function () {
                        // Reset modal state for reuse
                        modalElement.removeAttribute('inert');
                    });

                    // Refresh the page after a short delay
                setTimeout(() => {
                    location.reload();
                }, 500); // Optional delay to allow the modal transition to complete
    
               } else {
                    alert('Failed to create post.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request.');
            });
        });
    
        // Ensure the modal's inert state is removed when hidden
        modalElement.addEventListener('hidden.bs.modal', function () {
            modalElement.removeAttribute('inert');
        });
    });
</script>

{{-- Audience dynamic Selection--}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Audience Selection with access-level guard
        const audienceInput = document.getElementById('audienceInput');
        const audienceDisplay = document.getElementById('audienceDisplay');
        const modalElement = document.getElementById('postModal');
        const allowedAudience = (modalElement && modalElement.dataset.allowedAudience) ? modalElement.dataset.allowedAudience : 'pu';
        const allowedRank = modalElement && modalElement.dataset.allowedRank ? Number(modalElement.dataset.allowedRank) : 0;
        const audienceRank = { public: 5, region: 4, state: 3, lga: 2, ward: 1, pu: 0 };
        const labels = {
            public: 'Sharing with: Nationwide Audience',
            region: `Sharing with: ${(modalElement && modalElement.dataset.regionName) ? modalElement.dataset.regionName : 'Region'} Audience`,
            state: `Sharing with: ${(modalElement && modalElement.dataset.stateName) ? modalElement.dataset.stateName : 'State'} Audience`,
            lga: `Sharing with: ${(modalElement && modalElement.dataset.lgaName) ? modalElement.dataset.lgaName : 'LGA'} Audience`,
            ward: `Sharing with: Ward: ${(modalElement && modalElement.dataset.wardName) ? modalElement.dataset.wardName : ''} Audience`,
            pu: `Sharing with: PU: ${(modalElement && modalElement.dataset.puName) ? modalElement.dataset.puName : ''} Audience`,
        };

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

        const showAudienceWarning = () => {
            const alertBox = document.getElementById('postValidation');
            if (!alertBox) return;
            alertBox.textContent = `You can share up to ${labels[allowedAudience] || 'your allowed audience'}.`;
            alertBox.classList.remove('d-none', 'alert-danger');
            alertBox.classList.add('alert-warning');
        };

        const hideAudienceWarning = () => {
            const alertBox = document.getElementById('postValidation');
            if (!alertBox) return;
            alertBox.classList.add('d-none');
            alertBox.textContent = '';
        };

        const setAudience = (value) => {
            if (audienceInput) audienceInput.value = value;
            if (audienceDisplay && labels[value]) audienceDisplay.textContent = labels[value];
        };

        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                const selectedValue = this.getAttribute('data-value');
                const allowed = (audienceRank[selectedValue] || 0) <= (allowedRank || audienceRank[allowedAudience] || 0);
                if (!allowed) {
                    showAudienceWarning();
                    setAudience(allowedAudience);
                    return;
                }
                hideAudienceWarning();

                setAudience(selectedValue);
            });
        });

        // Ensure the default audience respects the access level on load
        setAudience(allowedAudience);
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
