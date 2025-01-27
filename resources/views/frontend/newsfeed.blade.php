@php
    use App\Models\Post;

    // Determine the user audience based on the user's profile, location, etc.
    $userAudience = 'public'; // Default to 'public' or determine dynamically
    // Example: Check user's location or profile to determine audience
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->lga_id) {
            $userAudience = 'lga';
        } elseif ($user->state_id) {
            $userAudience = 'state';
        } elseif ($user->region_id) {
            $userAudience = 'region';
        }
    }


@endphp

<div id="app" class="newsfeed">
    <!-- Dynamically pass the audience as a prop to the Vue component -->
    <news-feed :audience="'{{ $userAudience }}'"></news-feed>
</div>
