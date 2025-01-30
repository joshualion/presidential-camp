@php
 use Illuminate\Support\Facades\Auth;

$profileData = Auth::check() ? Auth::user() : null;
$userAudience = $profileData ? $profileData->getAudience() : 'public';
@endphp

<div id="app" class="newsfeed">
    <!-- Dynamically pass the audience as a prop to the Vue component -->
    <news-feed 
    :audience="'{{ $userAudience }}'"
    :profile-data="{{ json_encode($profileData) }}"></news-feed>
    

</div>
