@extends('backend.template.backend-master')

@section('content')
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Submit Polling Unit Result</h3></div>
    <form method="POST" action="{{ route('user.elections.result.store', [$election->uuid, $assignment->uuid]) }}" enctype="multipart/form-data">
        @csrf
        <div class="card-body">
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="alert alert-info"><strong>{{ $election->name }}</strong><br>Assigned polling unit: {{ $assignment->pollingUnit?->name }}</div>
            @foreach($parties as $party)
                <div class="form-group row">
                    <label class="col-md-5 col-form-label" for="party-{{ $party->id }}">{{ $party->name }} ({{ $party->acronym }})</label>
                    <div class="col-md-7"><input id="party-{{ $party->id }}" type="number" min="0" name="votes[{{ $party->id }}]" class="form-control" required value="{{ old('votes.'.$party->id, $existingVotes->get($party->id, 0)) }}"></div>
                </div>
            @endforeach
            <div class="form-group">
                <label for="result_sheet">Result sheet <small class="text-muted">PDF/JPG/PNG, maximum 4 MB</small></label>
                <input id="result_sheet" type="file" name="result_sheet" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                @if($result?->result_sheet)<small class="form-text text-muted">An existing result sheet is attached. Uploading another replaces it.</small>@endif
            </div>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary">Submit Result</button> <a href="{{ route('user.elections.workspace') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
@endsection
