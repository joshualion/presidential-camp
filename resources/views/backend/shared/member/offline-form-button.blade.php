@php($offlineAddRoute = $profileData->access_level.'.member.add')
@if(Route::has($offlineAddRoute))
<a class="btn btn-outline-primary" href="{{ route($offlineAddRoute, ['offline_form' => 1]) }}">
    <i class="fas fa-print" aria-hidden="true"></i> Print Offline Registration Form
</a>
@endif
