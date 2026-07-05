@extends('backend.template.backend-master')
@section('content')
<section class="content"><div class="container-fluid">
    <div class="row">
        <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><h3>{{ $wallet ? $wallet->currency.' '.number_format((float) $wallet->available_balance, 2) : 'Restricted' }}</h3><p>My available SMS balance</p></div>@if($canViewWallet)<a href="{{ route($profileData->access_level.'.sms.wallet') }}" class="small-box-footer">Wallet and ledger <i class="fas fa-arrow-circle-right"></i></a>@endif</div></div>
        <div class="col-md-4"><div class="small-box bg-success"><div class="inner"><h3>{{ number_format($approvedSenderCount) }}</h3><p>Approved sender IDs</p></div><a href="{{ route($profileData->access_level.'.sms.sender-ids') }}" class="small-box-footer">Manage sender IDs <i class="fas fa-arrow-circle-right"></i></a></div></div>
        <div class="col-md-4"><div class="small-box bg-warning"><div class="inner"><h3>{{ $organizationWallet ? $organizationWallet->currency.' '.number_format((float) $organizationWallet->available_balance, 2) : 'Restricted' }}</h3><p>Organization SMS balance</p></div>@if($organizationWallet)<a href="{{ route($profileData->access_level.'.sms.organization-wallet') }}" class="small-box-footer">Organization ledger <i class="fas fa-arrow-circle-right"></i></a>@endif</div></div>
    </div>
    <div class="card"><div class="card-header"><h3 class="card-title">SMS workspace</h3></div><div class="card-body">
        @if($approvedSenderCount && $canCompose)<a class="btn btn-primary" href="{{ route($profileData->access_level.'.sms.compose') }}"><i class="fas fa-paper-plane mr-1"></i> Compose SMS</a>@elseif(!$approvedSenderCount)<div class="alert alert-warning mb-0">No approved sender ID is available. <a href="{{ route($profileData->access_level.'.sms.sender-ids') }}">Request or synchronize a sender ID</a> before sending.</div>@endif
        @if($canViewWallet)<a class="btn btn-outline-secondary" href="{{ route($profileData->access_level.'.sms.wallet') }}">Top up or request credit</a>@endif
    </div></div>
    <div class="card"><div class="card-header">Recent SMS batches</div><div class="card-body table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Date</th><th>Sender</th><th>Recipients</th><th>Status</th><th></th></tr></thead><tbody>
        @forelse($recentBatches as $batch)<tr><td>{{ $batch->created_at?->format('d M Y H:i') }}</td><td>{{ $batch->sender_id }}</td><td>{{ number_format($batch->valid_recipient_count) }}</td><td>{{ str($batch->portal_status ?: $batch->status)->replace('_', ' ')->title() }}</td><td><a href="{{ route($profileData->access_level.'.sms.batches.show', $batch) }}">View</a></td></tr>@empty<tr><td colspan="5" class="text-center text-muted">{{ $canViewReports ? 'No SMS batches are visible to you.' : 'SMS report access is not assigned to your account.' }}</td></tr>@endforelse
    </tbody></table></div></div>
</div></section>
@endsection
