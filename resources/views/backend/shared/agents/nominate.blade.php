@extends('backend.template.backend-master')
@section('content')

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Nominate Polling Unit Agent</h3>
                    </div>
                    <form action="{{ route($profileData->access_level.'.agents.nominate.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>User</label>
                                <select name="user_id" id="agent-user-select" class="form-control" required>
                                    <option value="">Select User</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" data-polling-unit-id="{{ $user->polling_unit_id }}" data-polling-unit-label="{{ $user->pollingUnit?->name }} - {{ $user->pollingUnit?->ward?->name }} / {{ $user->pollingUnit?->ward?->localGovernmentArea?->name }}" @selected(old('user_id') == $user->id)>{{ $user->firstname }} {{ $user->lastname }} - {{ $user->email }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @include('backend.shared.agents.partials.assignment-selector', ['states' => $states])
                            <div class="form-group">
                                <label>Appointment/Nomination Note</label>
                                <textarea name="appointment_note" rows="3" class="form-control">{{ old('appointment_note') }}</textarea>
                            </div>
                            <div class="alert alert-info">
                                Identity documents may be attached now, or the nominated user can complete identity verification later from their request status page.
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Means of Identification Type</label>
                                        <input type="text" name="identity_type" value="{{ old('identity_type') }}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Means of Identification Number</label>
                                        <input type="text" name="identity_number" value="{{ old('identity_number') }}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Identity Document</label>
                                        <input type="file" name="identity_document" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Voter Evidence / Voter's Card</label>
                                        <input type="file" name="voter_evidence_document" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Passport Photo</label>
                                        <input type="file" name="passport_photo" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-user-plus"></i> Submit Nomination</button>
                            <a href="{{ route($profileData->access_level.'.agents.index') }}" class="btn btn-default">Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
