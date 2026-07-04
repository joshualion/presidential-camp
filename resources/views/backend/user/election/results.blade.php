@extends('backend.template.backend-master')

@section('content')
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">{{ $election->name }} — My Polling Unit Results</h3></div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><h3>{{ number_format((int) ($summaryStats['total_votes'] ?? 0)) }}</h3><p>Total votes</p></div></div></div>
            <div class="col-md-4"><div class="small-box bg-success"><div class="inner"><h3>{{ number_format((int) ($summaryStats['submitted_polling_units'] ?? $summaryStats['submitted'] ?? 0)) }}</h3><p>Results submitted</p></div></div></div>
            <div class="col-md-4"><div class="small-box bg-secondary"><div class="inner"><h3>{{ count($summary) }}</h3><p>Parties reported</p></div></div></div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Party</th><th>Acronym</th><th>Votes</th></tr></thead>
                <tbody>
                @forelse($summary as $row)
                    <tr>
                        <td>{{ $row['party_name'] ?? '' }}</td>
                        <td>{{ $row['party_acronym'] ?? '' }}</td>
                        <td>{{ number_format((int) ($row['total_votes'] ?? 0)) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted">No result has been submitted for your polling unit.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <a href="{{ route('user.elections') }}" class="btn btn-default">Back to Elections</a>
    </div>
</div>
@endsection
