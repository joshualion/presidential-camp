@extends('backend.template.backend-master')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Elections and Scoped Results</h3>
        @if($canSubmit)<div class="card-tools"><a href="{{ route('user.elections.workspace') }}" class="btn btn-success btn-sm"><i class="fas fa-clipboard-check"></i> Agent Workspace</a></div>@endif
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Election</th><th>Date</th><th>Status</th><th>Votes in my polling unit</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($elections as $election)
                <tr>
                    <td>{{ $election->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($election->year)->format('d M Y') }}</td>
                    <td><span class="badge badge-info">{{ ucfirst($election->dynamic_status) }}</span></td>
                    <td>{{ number_format((int) ($election->scoped_total_votes ?? 0)) }}</td>
                    <td><a href="{{ route('user.elections.results', $election->uuid) }}" class="btn btn-primary btn-sm"><i class="fas fa-chart-bar"></i> View Results</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No elections are available.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
