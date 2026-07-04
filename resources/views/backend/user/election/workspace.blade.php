@extends('backend.template.backend-master')

@section('content')
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Approved Agent Election Workspace</h3></div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('message'))<div class="alert alert-info">{{ session('message') }}</div>@endif
        <div class="alert alert-info">Submissions are restricted to polling units where your agent assignment is currently approved.</div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Election</th><th>Assigned polling unit</th><th>Result status</th><th>My incidents</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($elections as $election)
                    @foreach($assignments as $assignment)
                        @php
                            $key = $election->id.'-'.$assignment->polling_unit_id;
                            $result = $resultMap->get($key);
                            $editable = !$result || app(\App\Services\PollingUnitResultPermissionService::class)->canEditPollingUnitResult($profileData, $result);
                        @endphp
                        <tr>
                            <td>{{ $election->name }}<br><small>{{ \Carbon\Carbon::parse($election->year)->format('d M Y') }}</small></td>
                            <td>{{ $assignment->pollingUnit?->name }}<br><small>{{ $assignment->pollingUnit?->ward?->name }} / {{ $assignment->pollingUnit?->ward?->localGovernmentArea?->name }}</small></td>
                            <td><span class="badge badge-{{ $result ? ($editable ? 'warning' : 'success') : 'secondary' }}">{{ $result ? ucfirst($result->verification_status ?? $result->result_status ?? 'submitted') : 'Not submitted' }}</span></td>
                            <td>{{ $incidentCounts->get($key, 0) }}</td>
                            <td class="text-nowrap">
                                @if($editable)
                                    <a href="{{ route('user.elections.result.form', [$election->uuid, $assignment->uuid]) }}" class="btn btn-success btn-sm"><i class="fas fa-poll"></i> {{ $result ? 'Amend Result' : 'Submit Result' }}</a>
                                @endif
                                <a href="{{ route('user.elections.incident.form', [$election->uuid, $assignment->uuid]) }}" class="btn btn-danger btn-sm"><i class="fas fa-exclamation-triangle"></i> Report Incident</a>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="5" class="text-center text-muted">There is no open election.</td></tr>
                @endforelse
                @if($assignments->isEmpty())
                    <tr><td colspan="5" class="text-center text-muted">You do not have an approved polling-unit agent assignment.</td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
