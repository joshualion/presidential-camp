@extends('backend.template.backend-master')
@section('content')
<section class="content cm-ui"><div class="container-fluid">
    <h2>Paystack test activity</h2>
    <p>All test collections use the campaign Paystack connection. The selected ledger account attributes the collection to its jurisdiction.</p>
    @if(session('finance_status'))<div class="alert alert-success">{{ session('finance_status') }}</div>@endif
    @foreach($errors->all() as $error)<div class="alert alert-danger">{{ $error }}</div>@endforeach
    @if($settings?->collections && app(\App\Services\CampaignCapabilityService::class)->allows(auth()->user(), 'finance.paystack.collect'))
        <form class="card card-body" method="POST" action="{{ route('finance.paystack.collect') }}">
            @csrf
            <label>Attributed ledger account
                <select class="form-control" name="account_id" required>
                    <option value="">Select account</option>
                    @foreach($accounts as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                </select>
            </label>
            <label>Test payer email<input class="form-control" type="email" name="email" required maxlength="254"></label>
            <label>Amount (NGN)<input class="form-control" name="amount" inputmode="decimal" required></label>
            <button class="btn btn-primary">Open Paystack test checkout</button>
        </form>
    @endif
    <div class="table-responsive"><table class="table">
        <thead><tr><th>Reference</th><th>Type</th><th>Source or attributed account</th><th>Destination</th><th>Amount (NGN)</th><th>Test status</th><th>Action</th></tr></thead>
        <tbody>
            @forelse($operations as $operation)
                <tr>
                    <td>{{ $operation->reference }}</td><td>{{ ucfirst($operation->kind) }}</td>
                    <td>{{ $accounts[$operation->account_id] ?? 'Parent or external jurisdiction' }}</td>
                    <td>{{ $operation->destination_id ? ($accounts[$operation->destination_id] ?? 'Another jurisdiction') : 'Main campaign Paystack account' }}</td>
                    <td>{{ number_format($operation->amount_minor / 100, 2) }}</td><td>{{ ucfirst($operation->status) }}</td>
                    <td><form method="POST" action="{{ route('finance.paystack.verify', $operation->reference) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-primary">Verify with Paystack</button>
                    </form></td>
                </tr>
            @empty
                <tr><td colspan="7">No Paystack test activity in your jurisdiction.</td></tr>
            @endforelse
        </tbody>
    </table></div>
    {{ $operations->links() }}
</div></section>
@endsection
