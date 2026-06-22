@php
    $importRoute = $profileData->access_level.'.member.import';
@endphp

@if(Route::has($importRoute))
    <a href="{{ route($importRoute) }}">
        <button class="btn btn-outline-light">
            <i class="fa fa-upload"></i> Import Members
        </button>
    </a>
@endif
