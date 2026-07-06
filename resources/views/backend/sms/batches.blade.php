@extends('backend.template.backend-master')
@section('content')
<section class="content"><div class="container-fluid">
<div class="card"><div class="card-header"><h3 class="card-title">SMS batch history</h3><a class="btn btn-sm btn-primary float-right text-white" href="{{ route($profileData->access_level.'.sms.compose') }}">Compose SMS</a></div><div class="card-body table-responsive">
<p class="text-muted">Delivery and billing are reported separately. Failed delivery does not itself establish refund eligibility.</p>
<table class="table table-bordered table-striped table-sm"><thead><tr><th>Created</th><th>Sent by</th><th>Sender / route</th><th>Audience</th><th>Delivery</th><th>Billing</th><th>Wallet</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($batches as $batch)<tr>
    <td>{{ $batch->created_at?->format('d M Y H:i') }}@if($batch->submitted_at)<br><small>Submitted {{ $batch->submitted_at->format('d M H:i') }}</small>@endif</td>
    <td>{{ trim(($batch->createdBy?->firstname ?? '').' '.($batch->createdBy?->lastname ?? '')) ?: 'Unknown' }}<br><small>{{ $batch->createdBy?->access_level }}</small></td>
    <td><strong>{{ $batch->sender_id }}</strong><br>{{ str($batch->route)->title() }}</td>
    <td>{{ number_format($batch->valid_recipient_count) }} valid<br><small>{{ str($batch->source_context ?: 'unknown')->after(':')->replace('_', ' ')->title() }} · {{ data_get($batch->target_scope_metadata, 'scope', 'Recorded scope') }}</small></td>
    <td><span class="badge badge-success">Delivered {{ number_format($batch->delivered_count) }}</span> <span class="badge badge-danger">Failed {{ number_format($batch->failed_count) }}</span> @if($batch->dnd_blocked_count)<span class="badge badge-secondary">DND {{ number_format($batch->dnd_blocked_count) }}</span>@endif</td>
    <td><span class="badge badge-info">Charged {{ number_format($batch->charged_count) }}</span> <span class="badge badge-success">Refunded/released {{ number_format($batch->refunded_count) }}</span>@if($batch->billing_review_count)<br><span class="badge badge-warning">Billing Review Required {{ $batch->billing_review_count }}</span>@endif</td>
    <td>{{ $batch->wallet?->owner_type === 'organization' ? 'Organization' : 'User' }}<br><small>Debited {{ number_format((float) $batch->debited_amount, 2) }} · Refunded {{ number_format((float) $batch->refunded_amount, 2) }} · Released {{ number_format((float) $batch->released_amount, 2) }}</small></td>
    <td>{{ str($batch->portal_status ?: $batch->status)->replace('_', ' ')->title() }}<br><small>{{ $batch->last_synced_at ? 'Synced '.$batch->last_synced_at->diffForHumans() : 'Not synced' }}</small></td>
    <td><a class="btn btn-sm btn-info" href="{{ route($profileData->access_level.'.sms.batches.show', $batch) }}">Details</a></td>
</tr>@empty<tr><td colspan="9" class="text-center">No SMS batches are visible to you.</td></tr>@endforelse
</tbody></table>{{ $batches->links() }}</div></div>
</div></section>
@endsection
