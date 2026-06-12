@extends('backend.template.backend-master')
@section('content')

<section class="content">
    <div class="container-fluid">
        <form action="{{ route($profileData->access_level.'.agents.request.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('backend.shared.agents.partials.profile-summary', ['user' => $profileData])
            @if(!$profileData->polling_unit_id)
                <div class="alert alert-warning">
                    Your profile/voter listing does not have a registered polling unit. Please update your profile before requesting Polling Unit Agent status.
                </div>
            @elseif(!$pollingUnitHasCapacity)
                <div class="alert alert-warning">
                    This polling unit already has the maximum number of approved agents.
                </div>
            @else
                @include('backend.shared.agents.partials.identity-form', [
                    'pollingUnits' => collect([$pollingUnitFull]),
                    'selectedPollingUnitId' => $profileData->polling_unit_id,
                    'lockedPollingUnit' => $pollingUnitFull,
                ])
            @endif
            <div class="row">
                <div class="col-12">
                    @if($profileData->polling_unit_id && $pollingUnitHasCapacity)
                        <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i> Submit Agent Request</button>
                    @endif
                    <a href="{{ route($profileData->access_level.'.agents.status') }}" class="btn btn-default">My Request Status</a>
                </div>
            </div>
        </form>
    </div>
</section>

@endsection
