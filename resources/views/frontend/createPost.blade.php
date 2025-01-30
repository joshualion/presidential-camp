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


     <!-- Include Newsfeed -->
     @include('frontend.newsfeed')
     <!-- Include Newsfeed -->

</div>



{{-- Post Modal --}}
<div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel" aria-hidden="true">

    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="postModalLabel">Create a Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" id="postForm">
                @csrf
            <div class="modal-body">
                <div class="d-flex mb-3">
                    <!-- Profile Picture -->
                    <img src="{{(!empty($profileData->photo)) ? url('uploads/member_images/'.$profileData->photo) : url('uploads/no_image.jpg')}}" 
                         alt="Profile Picture" 
                         class="rounded-circle me-3" 
                         style="width: 50px; height: 50px; object-fit: cover;">
                    <div>
                        <h6 class="mb-0">{{ $profileData->firstname }} {{ $profileData->lastname }}</h6>
                        <small class="text-muted" id="audienceDisplay">
                            Sharing with: 
                            @if($SystemSetting && $SystemSetting->package == 'national' && $profileData->access_level=='nationaladmin')
                                Nationwide Audience
                            @elseif($SystemSetting && $profileData->access_level=='regionaladmin' && in_array($SystemSetting->package, ['national', 'regional']))
                                {{ $profileData->region->name }} Region
                            @elseif($SystemSetting && $profileData->access_level=='stateadmin' && in_array($SystemSetting->package, ['national', 'regional', 'state']))
                                {{ $profileData->state->name }} State                           
                            @elseif($SystemSetting && $profileData->access_level=='lgaadmin' && in_array($SystemSetting->package, ['national', 'regional', 'state', 'lga']))
                                {{ $profileData->lga->name }} LGA
                            @else
                                Everyone
                            @endif
                        </small>
                    </div>
                </div>

                <!-- Icon Dropdown for Audience Selection -->
                <div class="dropdown mb-3">
                    <button class="btn btn-light dropdown-toggle" type="button" id="audienceDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-people"></i><small> Change <i class="fas fa-users"></i></small>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="audienceDropdown">
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
                <input type="hidden" name="audience" id="audienceInput" value="public">

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
    // Audience Selection
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const selectedValue = this.getAttribute('data-value');
            document.getElementById('audienceInput').value = selectedValue;
        });
    });
});

</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Audience Selection
        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                // Get the selected value
                const selectedValue = this.getAttribute('data-value');

                // Update the hidden input
                const audienceInput = document.getElementById('audienceInput');
                audienceInput.value = selectedValue;

                // Update the audience display text
                const audienceDisplay = document.getElementById('audienceDisplay');
                let displayText = '';

                // Define the display text based on the selected value
                switch (selectedValue) {
                    case 'public':
                        displayText = 'Sharing with: Nationwide Audience';
                        break;
                    case 'region':
                        displayText = `Sharing with: {{ $profileData->region->name }} Audience`;
                        break;
                    case 'state':
                        displayText = `Sharing with: {{ $profileData->state->name }} Audience`;
                        break;
                    case 'lga':
                        displayText = `Sharing with: {{ $profileData->lga->name }} Audience`;
                        break;
                    case 'ward':
                        displayText = `Sharing with: Ward: {{ $profileData->ward->name }} Audience`;
                        break;
                    case 'pu':
                        displayText = `Sharing with: PU: {{ $profileData->pollingUnit->name }} Audience`;
                        break;
                    default:
                        displayText = 'Sharing with: Everyone';
                        break;
                }

                // Update the text in the audienceDisplay
                audienceDisplay.textContent = displayText;
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
