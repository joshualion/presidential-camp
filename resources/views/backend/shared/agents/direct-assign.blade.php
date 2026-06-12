@extends('backend.template.backend-master')
@section('content')

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Direct Polling Unit Agent Assignment</h3>
                    </div>
                    <form action="{{ route($profileData->access_level.'.agents.direct-assign.store') }}" method="POST" enctype="multipart/form-data">
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
                            @include('backend.shared.agents.partials.identity-form', ['pollingUnits' => collect(), 'selectedPollingUnitId' => old('polling_unit_id'), 'bare' => true, 'hidePollingUnit' => true])
                            <div class="form-group">
                                <label>Assignment Notes</label>
                                <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-check-circle"></i> Assign Agent</button>
                            <a href="{{ route($profileData->access_level.'.agents.index') }}" class="btn btn-default">Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
