@php
    $importRoute = $profileData->access_level.'.member.import';
@endphp

@if(Route::has($importRoute))
    @if(app(\App\Services\CampaignCapabilityService::class)->canVisit($profileData, route($importRoute)))
<a href="{{ route($importRoute) }}">
        <button class="btn btn-dark text-white">
            <i class="fa fa-upload"></i> Import Members
        </button>
    </a>
@endif
@endif

@include('backend.shared.member.offline-form-button')
