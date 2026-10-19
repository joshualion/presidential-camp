@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid">
@if(session('message'))<div class="alert alert-info" role="status">{{ session('message') }}</div>@endif
<div class="d-flex flex-wrap justify-content-between mb-3"><div><h3>SMS overview</h3><p class="text-muted">Your own provider account · messages and reports within your permitted oversight.</p></div><div><a class="btn btn-primary mb-2" href="{{ route($prefix.'compose') }}">Compose SMS</a> <a class="btn btn-outline-secondary mb-2" href="{{ route($prefix.'settings') }}">My provider settings</a></div></div>
@if(!$settings?->direct_sms_enabled)<div class="alert alert-warning">SMS sending is disabled. Super Admin can enable it in SMS access settings.</div>@endif
@if(!$account)<div class="alert alert-info">Connect your SMS provider before composing a message.</div>@endif
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Created</th><th>Sent by</th><th>Provider</th><th>Visible recipients</th><th>Status</th><th>Report</th></tr></thead><tbody>@forelse($batches as $batch)<tr><td>{{ $batch->created_at->format('j M Y H:i') }}</td><td>{{ $batch->sender?->firstname }} {{ $batch->sender?->lastname }}</td><td>{{ $batch->provider }}</td><td>{{ number_format($batch->visible_count) }}</td><td>{{ str_replace('_',' ',$batch->status) }}</td><td><a class="btn btn-outline-primary btn-sm" href="{{ route($prefix.'show',$batch) }}">View report</a></td></tr>@empty<tr><td colspan="6" class="text-muted p-4">No SMS batches are visible in your oversight area.</td></tr>@endforelse</tbody></table></div></div><div class="card-footer">{{ $batches->links() }}</div></div>
</div></section>
@endsection
