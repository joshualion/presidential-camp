<!-- Followers Modal -->
<div class="modal fade" id="followersModal" tabindex="-1" aria-labelledby="followersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="followersModalLabel">Followers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group">
                    @foreach ($followers as $follower)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a class="d-flex align-items-center" href="{{ ($follower->id == $profileData->id) ? '/community/profile' : '/community/' . $follower->username . '/profile/timeline' }}">
                                <img src="{{(!empty($follower->photo)) ? url('uploads/member_images/'.$follower->photo) : url('uploads/no_image.jpg')}}" 
                                     alt="Profile Picture" 
                                     class="rounded-square me-2" 
                                     style="width: 25px; height: 25px; object-fit: cover;">
                                <span>{{ $follower->firstname }} {{ $follower->lastname }}</span>
                            </a>
                            <!-- Follow Back Button (only show if the follower is not being followed) -->
                            @if (!$profileData->isFollowing($follower))
                                <button class="btn btn-sm btn-success follow-back-btn" data-user-id="{{ $follower->id }}">
                                    Follow Back
                                </button>
                            @else
                                <span class="badge bg-secondary">Following</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if ($followers->hasPages())
                    <div class="mt-3">
                        {{ $followers->links() }}
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Following Modal -->
<div class="modal fade" id="followingModal" tabindex="-1" aria-labelledby="followingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="followingModalLabel">Following</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group">
                    @foreach ($following as $followed)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a class="d-flex align-items-center" href="{{ ($followed->id == $profileData->id) ? '/community/profile' : '/community/' . $followed->username . '/profile/timeline' }}">
                                <img src="{{(!empty($followed->photo)) ? url('uploads/member_images/'.$followed->photo) : url('uploads/no_image.jpg')}}" 
                                     alt="Profile Picture" 
                                     class="rounded-square me-2" 
                                     style="width: 25px; height: 25px; object-fit: cover;">
                                <span>{{ $followed->firstname }} {{ $followed->lastname }}</span>
                            </a>
                            <!-- Unfollow Button -->
                            <button class="btn btn-sm btn-danger unfollow-btn" data-user-id="{{ $followed->id }}">
                                Unfollow
                            </button>
                        </li>
                    @endforeach
                </ul>
                @if ($following->hasPages())
                    <div class="mt-3">
                        {{ $following->links() }}
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Follow Back Button Click
        $(document).on('click', '.follow-back-btn', function () {
            const userId = $(this).data('user-id');
            const button = $(this);

            $.ajax({
                url: '{{ route("follow.back") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    user_id: userId
                },
                success: function (response) {
                    if (response.success) {
                        // Replace the Follow Back button with a "Following" badge
                        button.replaceWith('<span class="badge bg-secondary">Following</span>');
                    }
                },
                error: function (xhr) {
                    alert('An error occurred. Please try again.');
                }
            });
        });

        // Unfollow Button Click
        $(document).on('click', '.unfollow-btn', function () {
            const userId = $(this).data('user-id');
            const button = $(this);

            $.ajax({
                url: '{{ route("unfollow") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    user_id: userId
                },
                success: function (response) {
                    if (response.success) {
                        // Remove the list item from the modal
                        button.closest('li').remove();
                    }
                },
                error: function (xhr) {
                    alert('An error occurred. Please try again.');
                }
            });
        });
    });
</script>