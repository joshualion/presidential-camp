@extends('backend.template.backend-master')
@section('content')
<section class="content cm-ui"><div class="container-fluid">
    <a class="btn btn-outline-secondary btn-sm mb-3" href="{{ route('finance.section', ['section' => 'requests']) }}">Back to funding requests</a>
    <h2 class="h4 text-primary">Funding proposal #{{ $proposal->id }}</h2>
    <dl class="row">
        <dt class="col-md-3">Purpose</dt><dd class="col-md-9">{{ $proposal->purpose }}</dd>
        <dt class="col-md-3">Requested amount</dt><dd class="col-md-9">NGN {{ number_format($proposal->amount_minor / 100, 2) }}</dd>
        <dt class="col-md-3">Origin</dt><dd class="col-md-9">{{ ucfirst($proposal->requesting_level) }} #{{ $proposal->requesting_location_id }}</dd>
        <dt class="col-md-3">Current status</dt><dd class="col-md-9">{{ ucfirst($proposal->status) }}</dd>
        @if($payment)
        <dt class="col-md-3">Payment request</dt><dd class="col-md-9">#{{ $payment->id }} — {{ ucfirst($payment->status) }} (not a bank confirmation)</dd>
        @endif
    </dl>
    <h3 class="h5 text-primary">Decision and revision trail</h3>
    <p class="small">Original requests, returned versions, reviewer decisions, allocations and account assignments remain in the audit history. A financial approval or bank transfer is not inferred from a submitted proposal.</p>
    <div class="table-responsive"><table class="table table-striped">
        <thead><tr><th>Revision</th><th>Date</th><th>Officer ID</th><th>Action</th><th>From</th><th>To</th><th>Details</th></tr></thead>
        <tbody>
            @forelse($events as $event)
                <tr>
                    <td>{{ $event->revision }}</td>
                    <td>{{ $event->created_at }}</td>
                    <td>{{ $event->actor_id }}</td>
                    <td>{{ ucfirst($event->action) }}</td>
                    <td>{{ $event->from_status ?? 'New' }}</td>
                    <td>{{ $event->to_status }}</td>
                    <td><details><summary>Review changes</summary><pre class="small" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ json_encode(json_decode($event->details, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details></td>
                </tr>
            @empty<tr><td colspan="7">No proposal decisions yet.</td></tr>@endforelse
        </tbody>
    </table></div>
</div></section>
@endsection
