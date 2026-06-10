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
                            @if ($profileData->id !== $follower->id)
                                @php $isFollowing = $profileData->isFollowing($follower); @endphp
                                <button
                                    class="btn btn-sm follow-toggle-btn {{ $isFollowing ? 'btn-outline-secondary' : 'btn-primary text-white' }}"
                                    data-user-id="{{ $follower->id }}"
                                    data-following="{{ $isFollowing ? '1' : '0' }}"
                                >
                                    {{ $isFollowing ? 'Unfollow' : 'Follow' }}
                                </button>
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
                        @if ($profileData->id !== $followed->id)
                            @php $isFollowing = $profileData->isFollowing($followed); @endphp
                            <button
                                class="btn btn-sm follow-toggle-btn {{ $isFollowing ? 'btn-outline-secondary' : 'btn-primary text-white' }}"
                                data-user-id="{{ $followed->id }}"
                                data-following="{{ $isFollowing ? '1' : '0' }}"
                            >
                                {{ $isFollowing ? 'Unfollow' : 'Follow' }}
                            </button>
                        @endif
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
        const toggleFollowState = (btn, isFollowing) => {
            btn.attr('data-following', isFollowing ? '1' : '0');
            if (isFollowing) {
                btn.removeClass('btn-primary text-white').addClass('btn-outline-secondary').text('Unfollow');
            } else {
                btn.removeClass('btn-outline-secondary').addClass('btn-primary text-white').text('Follow');
            }
        };

        // Follow / Unfollow Button Click
        $(document).on('click', '.follow-toggle-btn', function () {
            const userId = $(this).data('user-id');
            const button = $(this);
            const isFollowing = button.data('following') === 1 || button.data('following') === '1';
            const method = isFollowing ? 'DELETE' : 'POST';

            $.ajax({
                url: `/community/users/${userId}/follow`,
                type: method,
                data: { _token: '{{ csrf_token() }}' },
                success: function (response) {
                    if (response.success) {
                        toggleFollowState(button, !isFollowing);
                    }
                },
                error: function (xhr) {
                    alert('An error occurred. Please try again.');
                }
            });
        });
    });
</script>
