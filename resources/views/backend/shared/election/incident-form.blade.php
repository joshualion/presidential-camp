@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid"><div class="card card-primary">
<div class="card-header"><h1 class="card-title">Report Election Incident</h1></div>
<form method="post" enctype="multipart/form-data" action="{{ route($profileData->access_level.'.incident.store') }}">@csrf
<div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<p><strong>{{ $election->name }}</strong> — {{ \App\Models\PollingUnit::find($pollingUnitId)?->name }}</p>
@foreach(['election_id' => $election->id, 'region_id' => $regionId, 'state_id' => $stateId, 'lga_id' => $lgaId, 'ward_id' => $wardId, 'polling_unit_id' => $pollingUnitId] as $field => $value)<input type="hidden" name="{{ $field }}" value="{{ $value }}">@endforeach
<div class="row"><div class="col-md-6 form-group"><label for="incident-type">Incident type</label><select id="incident-type" name="incident_type" class="form-control" required>
@foreach(['violence' => 'Violence', 'intimidation' => 'Intimidation', 'equipment_failure' => 'Equipment failure', 'late_opening' => 'Late opening', 'result_dispute' => 'Result dispute', 'other' => 'Other'] as $value => $label)<option value="{{ $value }}" @selected(old('incident_type') === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-6 form-group"><label for="incident-severity">Severity</label><select id="incident-severity" name="severity" class="form-control">@foreach(['low', 'medium', 'high', 'critical'] as $level)<option value="{{ $level }}" @selected(old('severity', 'medium') === $level)>{{ ucfirst($level) }}</option>@endforeach</select></div></div>
<div class="form-group"><label for="incident-details">What happened?</label><textarea id="incident-details" name="remarks" class="form-control" rows="5" maxlength="5000" required>{{ old('remarks') }}</textarea></div>
@include('backend.shared.election.evidence-inputs')
</div><div class="card-footer"><button class="btn btn-primary">Submit incident report</button> <a class="btn btn-outline-secondary" href="{{ route($profileData->access_level.'.elections') }}">Back to elections</a></div>
</form></div></div></section>
@endsection
