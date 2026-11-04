@extends('backend.template.backend-master')
@section('content')
<section class="content cm-ui"><div class="container-fluid">
    <h1 class="h3 text-primary">Campaign workforce reports</h1>
    <p>Membership and polling-unit coverage within your campaign jurisdiction. Date filters select members by their registration date.</p>
    <form method="GET" class="card card-body mb-3">
        <div class="row">
            <div class="col-md-3 mb-2"><label for="report-group">Group by</label><select id="report-group" name="group" class="form-control">
                @foreach(['state' => 'State', 'lga' => 'Local government', 'ward' => 'Ward', 'polling_unit' => 'Polling unit'] as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['group'] ?? 'state') === $key)>{{ $label }}</option>
                @endforeach
            </select></div>
            <div class="col-md-3 mb-2"><label for="report-from">Registered from</label><input id="report-from" class="form-control" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
            <div class="col-md-3 mb-2"><label for="report-to">Registered through</label><input id="report-to" class="form-control" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
            <div class="col-md-3 mb-2 align-self-end"><button class="btn btn-primary">Apply filters</button> <a class="btn btn-outline-secondary" href="{{ route('enterprise.reports.workforce') }}">Reset</a></div>
        </div>
        @foreach(['state_id', 'lga_id', 'ward_id', 'polling_unit_id'] as $key)
            @if(!empty($filters[$key]))<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif
        @endforeach
        @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    </form>
    <div class="row">
        @foreach(['members' => 'Members', 'active' => 'Active accounts', 'registered_voters' => 'Registered voters', 'covered_polling_units' => 'Covered polling units', 'uncovered_polling_units' => 'Uncovered polling units'] as $key => $label)
            <div class="col-6 col-lg mb-3"><article class="card h-100"><div class="card-body"><h2 class="h6">{{ $label }}</h2><strong class="h3 text-primary">{{ number_format($summary[$key]) }}</strong></div></article></div>
        @endforeach
    </div>
    <p>{{ $summary['coverage_percent'] }}% coverage: {{ number_format($summary['covered_polling_units']) }} of {{ number_format($summary['polling_units']) }} authorized polling units have a member matching these filters.</p>
    @if($summary['uncovered_polling_units'] > 0)<p class="alert alert-info">Prioritize recruiting at polling units that have no matching members. Coverage counts membership presence, not verified operational readiness.</p>@endif
    @if($canExport)<a class="btn btn-primary mb-3" href="{{ route('enterprise.reports.workforce.export', $filters) }}">Export CSV</a>@endif
    <div class="card"><div class="card-header"><h2 class="card-title">Geographic distribution</h2></div><div class="card-body">
        <div class="table-responsive"><table class="table table-striped"><thead><tr><th scope="col">Location</th><th scope="col">Members</th><th scope="col">Active accounts</th><th scope="col">Registered voters</th></tr></thead><tbody>
            @forelse($rows as $row)<tr><td>{{ $row->location_name ?? 'Unassigned' }}</td><td>{{ number_format($row->members) }}</td><td>{{ number_format($row->active_members) }}</td><td>{{ number_format($row->registered_voters) }}</td></tr>
            @empty<tr><td colspan="4">No members match these filters in your authorized jurisdiction.</td></tr>@endforelse
        </tbody></table></div>
        {{ $rows->links() }}
    </div></div>
    <p class="text-muted">AI analysis and PDF reporting are not available in this increment.</p>
</div></section>
@endsection
