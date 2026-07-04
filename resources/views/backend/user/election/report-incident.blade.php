@extends('backend.template.backend-master')

@section('content')
<div class="card card-danger">
    <div class="card-header"><h3 class="card-title">Report Election Incident</h3></div>
    <form method="POST" action="{{ route('user.elections.incident.store', [$election->uuid, $assignment->uuid]) }}" enctype="multipart/form-data">
        @csrf
        <div class="card-body">
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="alert alert-info"><strong>{{ $election->name }}</strong><br>Assigned polling unit: {{ $assignment->pollingUnit?->name }}</div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="incident_type">Incident type</label>
                    <select id="incident_type" name="incident_type" class="form-control" required>
                        @foreach(['violence' => 'Violence', 'intimidation' => 'Intimidation', 'equipment_failure' => 'Equipment failure', 'late_opening' => 'Late opening', 'result_dispute' => 'Result dispute', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('incident_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label for="severity">Severity</label>
                    <select id="severity" name="severity" class="form-control" required>
                        @foreach(['low', 'medium', 'high', 'critical'] as $severity)<option value="{{ $severity }}" @selected(old('severity', 'medium') === $severity)>{{ ucfirst($severity) }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="form-group"><label for="remarks">Details</label><textarea id="remarks" name="remarks" class="form-control" rows="6" maxlength="5000" required>{{ old('remarks') }}</textarea></div>
            <div class="form-group"><label for="pictures">Picture evidence <small class="text-muted">up to 3 images, 2 MB each</small></label><input id="pictures" type="file" name="pictures[]" class="form-control" accept=".jpg,.jpeg,.png" multiple></div>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-danger">Report Incident</button> <a href="{{ route('user.elections.workspace') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
@endsection
