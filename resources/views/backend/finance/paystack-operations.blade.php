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
    @if(count($donationReviews))
        <h3>Settled external donations</h3>
        <p>Review single-payment settlements with a matching unposted bank credit. This process records money already received.</p>
        @foreach($donationReviews as $review)
            <details class="card card-body mb-2">
                <summary>Settlement {{ $review->provider_settlement }} · {{ $accounts[$review->account_id] ?? 'Finance account' }} · NGN {{ number_format($review->net_minor / 100, 2) }}</summary>
                @if($review->income_entry_id)
                    <p>Posted as journal entry #{{ $review->income_entry_id }}.</p>
                @elseif($review->reference)
                    <p>Paystack reference {{ $review->reference }} · Donor case {{ $review->evidence['donor_case'] }} · Review record {{ $review->evidence['review_record'] }}.</p>
                    <p>Reviewed cumulative contributions NGN {{ number_format($review->reviewed_total_minor / 100, 2) }}.</p>
                    @if($mayPostDonations)
                        <form method="POST" action="{{ route('finance.paystack.donation.post', $review->id) }}">
                            @csrf
                            <label><input type="checkbox" name="attest" value="1" required> I independently reviewed the donor and merchant eligibility, source of funds, contribution limit and bank evidence, and confirmed this credit has not been posted.</label>
                            <button class="btn btn-primary">Verify and post</button>
                        </form>
                    @endif
                @else
                    <form method="POST" action="{{ route('finance.paystack.donation.prepare', $review->id) }}">
                        @csrf
                        <label>Paystack transaction reference<input class="form-control" name="reference" maxlength="100" required></label>
                        <label>Internal donor case ID<input class="form-control" name="donor_case" maxlength="64" required></label>
                        <label>Internal compliance review record ID<input class="form-control" name="review_record" maxlength="120" required></label>
                        <label>Other contributions outside this workflow (kobo)<input class="form-control" name="other_minor" type="number" value="0" min="0" required></label>
                        <label><input type="checkbox" name="attest" value="1" required> I checked the donor’s identity, eligibility and source of funds and recorded the evidence in the restricted case file.</label>
                        <button class="btn btn-outline-primary">Prepare for independent review</button>
                    </form>
                @endif
            </details>
        @endforeach
    @endif
    <div class="table-responsive"><table class="table">
        <thead><tr><th>Reference</th><th>Type</th><th>Source or attributed account</th><th>Destination</th><th>Amount (NGN)</th><th>Test status</th><th>Action</th></tr></thead>
        <tbody>
            @forelse($operations as $operation)
                <tr>
                    <td>{{ $operation->reference }}
                        @if($operation->request_id)<div>Funding request #{{ $operation->request_id }}</div>@endif
                        @if($operation->submitted_by)<div>Submitted by officer #{{ $operation->submitted_by }}</div>@endif
                    </td><td>{{ ucfirst($operation->kind) }}</td>
                    <td>{{ $accounts[$operation->account_id] ?? 'Parent or external jurisdiction' }}</td>
                    <td>{{ $operation->destination_id ? ($accounts[$operation->destination_id] ?? 'Another jurisdiction') : ($operation->kind === 'transfer' ? 'Verified test beneficiary' : 'Main campaign Paystack account') }}</td>
                    <td>{{ number_format($operation->amount_minor / 100, 2) }}</td><td>{{ ucfirst($operation->status) }}
                        @if($operation->processed_refunds_minor > 0)
                            <div>Verified refunds NGN {{ number_format($operation->processed_refunds_minor / 100, 2) }}</div>
                        @endif
                    </td>
                    <td><form method="POST" action="{{ route('finance.paystack.verify', $operation->reference) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-primary">Verify with Paystack</button>
                    </form>
                    @if($operation->kind === 'collection' && app(\App\Services\CampaignCapabilityService::class)->allows(auth()->user(), 'finance.reconcile'))
                        <form method="POST" action="{{ route('finance.paystack.refund', $operation->reference) }}" class="mt-2">
                            @csrf
                            <label>Existing Paystack refund ID<input type="number" min="1" name="refund_id" class="form-control" required></label>
                            <button class="btn btn-sm btn-outline-primary">Verify refund</button>
                        </form>
                    @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No Paystack test activity in your jurisdiction.</td></tr>
            @endforelse
        </tbody>
    </table></div>
    {{ $operations->links() }}
</div></section>
@endsection
