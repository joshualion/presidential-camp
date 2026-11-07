@extends('backend.template.backend-master')
@section('content')
<section class="content cm-ui"><div class="container-fluid">

<p>Record campaign funds, allocate budgets and approve payment requests. Amounts are in Nigerian naira. This workspace records campaign operations in NGN. Recorded balances are accounting balances.</p>
@if(session('finance_status'))<div class="alert alert-success" role="status">{{ session('finance_status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

@if($section === 'requests')
    <h2 class="h5 text-primary">Ask campaign headquarters for funding</h2>
    <p>Authorized leaders can submit a request for their unit without seeing or controlling headquarters accounts. Finance will review the proposal and assign a paying account later. Submitting this form does not move funds.</p>
    @if($capabilities->allows(auth()->user(), 'finance.disbursement.create')
        && app(\App\Services\Enterprise\CampaignFinancePolicy::class)->permits(auth()->user(), 'finance.disbursement.create'))
    <details class="card card-body mb-3"><summary>New funding proposal</summary>
        <form method="POST" action="{{ route('finance.proposals.store') }}">
            @csrf
            <input type="hidden" name="submission_key" value="{{ (string)\Illuminate\Support\Str::uuid() }}">
            <label>Funding amount (NGN)<input name="amount" class="form-control" inputmode="decimal" required></label>
            <label>Purpose / project / event<textarea name="purpose" class="form-control" maxlength="500" required></textarea></label>
            <p class="small">Optional named recipients: leave all rows blank if the branch itself will receive the allocation, subject to Finance approval.</p>
            @foreach(range(0, 4) as $i)
                <div class="form-row">
                    <div class="col-md-8"><label>Recipient {{ $i + 1 }}<input name="recipients[{{ $i }}][name]" class="form-control" maxlength="160"></label></div>
                    <div class="col-md-4"><label>Amount (NGN)<input name="recipients[{{ $i }}][amount]" class="form-control" inputmode="decimal"></label></div>
                </div>
            @endforeach
            <button class="btn btn-primary">Submit proposal</button>
        </form>
    </details>
    @endif
    <h3 class="h6 text-primary">My proposals to Finance</h3>
    <div class="table-responsive"><table class="table table-striped">
        <thead><tr><th>ID</th><th>Purpose</th><th>Amount</th><th>Status</th><th>Next action</th></tr></thead>
        <tbody>
        @forelse($fundingProposals as $proposal)
            <tr>
                <td><a href="{{ route('finance.proposals.history', $proposal->id) }}">#{{ $proposal->id }} history</a></td>
                <td>{{ $proposal->purpose }}</td>
                <td>NGN {{ number_format($proposal->amount_minor / 100, 2) }}</td>
                <td>{{ ucfirst($proposal->status) }}@if($proposal->payment_status) — Payment {{ $proposal->payment_status }}@endif</td>
                <td>
                    @if($proposal->status === 'returned')
                        <details><summary>Revise and resubmit</summary>
                        <form method="POST" action="{{ route('finance.proposals.resubmit', $proposal->id) }}">
                            @csrf
                            <input type="hidden" name="revision" value="{{ $proposal->revision }}">
                            <label>Amount (NGN)<input class="form-control" name="amount" value="{{ number_format($proposal->amount_minor / 100, 2, '.', '') }}" required></label>
                            <label>Purpose<textarea class="form-control" name="purpose" required maxlength="500">{{ $proposal->purpose }}</textarea></label>
                            <p class="small">You may change recipients. Blank rows are ignored. Recipient amounts must equal the request total.</p>
                            @php($previousRecipients = json_decode($proposal->recipients, true, 512, JSON_THROW_ON_ERROR))
                            @foreach(range(0, max(4, count($previousRecipients) - 1)) as $i)
                                <div class="form-row">
                                    <div class="col-md-7"><label>Recipient {{ $i + 1 }}<input name="recipients[{{ $i }}][name]" class="form-control" maxlength="160" value="{{ $previousRecipients[$i]['name'] ?? '' }}"></label></div>
                                    <div class="col-md-5"><label>NGN<input name="recipients[{{ $i }}][amount]" class="form-control" value="{{ isset($previousRecipients[$i]['amount_minor']) ? number_format($previousRecipients[$i]['amount_minor'] / 100, 2, '.', '') : '' }}"></label></div>
                                </div>
                            @endforeach
                            <button class="btn btn-primary btn-sm mt-2">Resubmit for review</button>
                        </form></details>
                    @elseif($proposal->status === 'rejected')
                        <span class="small">Closed — submit a new proposal if needed</span>
                    @elseif($proposal->status === 'assigned')
                        <span class="small">Await independent approvals on request #{{ $proposal->assigned_request_id }}</span>
                    @else
                        <span class="small">Pending Finance review</span>
                    @endif
                </td>
            </tr>
        @empty<tr><td colspan="5">No headquarters funding proposals submitted.</td></tr>@endforelse
        </tbody>
    </table></div>
    {{ $fundingProposals->links('pagination::bootstrap-4') }}
@endif


@if($section === 'approvals' && $incomingFundingProposals)
    <h2 class="h5 text-primary">Funding proposals from your jurisdiction</h2>
    <p class="small">Review intake requests, return them for correction, reject them, or assign an authorized paying account. Assignment is not approval; each linked payment follows the independent threshold-based Finance approval chain and reserves funds only at final approval. Real bank transfers are not initiated here.</p>
    <div class="table-responsive"><table class="table table-striped">
        <thead><tr><th>ID</th><th>Origin</th><th>Purpose / recipients</th><th>Amount</th><th>Status / review</th></tr></thead>
        <tbody>
            @forelse($incomingFundingProposals as $proposal)
                <tr>
                    <td><a href="{{ route('finance.proposals.history', $proposal->id) }}">#{{ $proposal->id }} history</a></td>
                    <td>{{ ucfirst($proposal->requesting_level) }} #{{ $proposal->requesting_location_id }}</td>
                    <td>{{ $proposal->purpose }}
                        @php($beneficiaries = json_decode($proposal->recipients, true, 512, JSON_THROW_ON_ERROR))
                        @if($beneficiaries)
                        <ul class="small">@foreach($beneficiaries as $beneficiary)
                            <li>{{ $beneficiary['name'] }} — NGN {{ number_format($beneficiary['amount_minor'] / 100, 2) }}</li>
                        @endforeach</ul>
                        @else <p class="small text-muted">Unassigned recipients; may allocate to verified branch account</p> @endif
                    </td>
                    <td>NGN {{ number_format($proposal->amount_minor / 100, 2) }}</td>
                    <td>{{ ucfirst($proposal->status) }}
                        @if($proposal->assigned_request_id)<div class="small">Linked payment request #{{ $proposal->assigned_request_id }}</div>@endif
                        @if($proposal->status === 'submitted' && (int) $proposal->requested_by !== (int) auth()->id())
                            <details><summary>Return or reject</summary>
                                <form method="POST" action="{{ route('finance.proposals.review', $proposal->id) }}">
                                    @csrf<input type="hidden" name="revision" value="{{ $proposal->revision }}">
                                    <label>Review reason<textarea class="form-control" name="reason" maxlength="500" required></textarea></label>
                                    <button class="btn btn-outline-warning btn-sm" name="decision" value="return">Return for correction</button>
                                    <button class="btn btn-outline-danger btn-sm" name="decision" value="reject">Reject</button>
                                </form>
                            </details>
                            @if($capabilities->allows(auth()->user(), 'finance.disbursement.create')
                                && app(\App\Services\Enterprise\CampaignFinancePolicy::class)->permits(auth()->user(), 'finance.disbursement.create'))
                            <details><summary>Assign funding account</summary>
                                <form method="POST" action="{{ route('finance.proposals.assign', $proposal->id) }}">
                                    @csrf<input type="hidden" name="revision" value="{{ $proposal->revision }}">
                                    <label>Paying Finance account
                                        <select class="form-control" name="account_id" required>
                                            <option value="">Select paying account</option>
                                            @foreach($accounts as $source)
                                                <option value="{{ $source->id }}">{{ $source->name }} (#{{ $source->id }})</option>
                                            @endforeach
                                        </select></label>
                                    <label>Expense budget (optional, must match paying account)
                                        <select class="form-control" name="budget_id"><option value="">No budget</option>
                                            @foreach($fundingBudgets as $budget)
                                                <option value="{{ $budget->id }}">{{ $budget->name }} — account #{{ $budget->account_id }}</option>
                                            @endforeach
                                        </select></label>
                                    @unless($beneficiaries)
                                        <label>Receiving branch Finance account (optional)
                                            <select class="form-control" name="destination_id"><option value="">Leave blank for an expense request</option>
                                                @foreach($accounts as $destination)
                                                    <option value="{{ $destination->id }}">{{ $destination->name }} (#{{ $destination->id }})</option>
                                                @endforeach
                                            </select></label>
                                    @endunless
                                    <p class="small">Configured approval stages and separate approvers are required before this allocation can be submitted.</p>
                                    <button class="btn btn-primary btn-sm">Assign for independent approval</button>
                                </form>
                            </details>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty<tr><td colspan="5">No funding proposals for review.</td></tr>@endforelse
        </tbody>
    </table></div>
    {{ $incomingFundingProposals->links('pagination::bootstrap-4') }}
@endif

@if($section === 'reports'
    && $capabilities->allows(auth()->user(), 'finance.reconcile')
    && app(\App\Services\Enterprise\CampaignFinancePolicy::class)->permits(auth()->user(), 'finance.reconcile'))
    <h2 class="h5 text-primary">Bank statement reconciliation</h2>
    <p class="small">Compare an external bank-statement ending balance against the Finance ledger for a specified date. This records an internal check, not confirmation from a bank or payment gateway. Discrepancies require investigation; no funds are posted or adjusted automatically.</p>
    <details class="card card-body mb-3"><summary>Record statement balance</summary>
        <form method="POST" action="{{ route('finance.statements.prepare') }}">
            @csrf
            <label>Finance account<select name="account_id" class="form-control" required>
                @foreach($accounts as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
            </select></label>
            <label>Statement ending date<input type="date" max="{{ today()->toDateString() }}" name="statement_date" class="form-control" required></label>
            <label>Statement ending balance (NGN)<input name="statement_balance" class="form-control" inputmode="decimal" required></label>
            <label>Bank statement / evidence reference<input name="evidence_reference" class="form-control" maxlength="300" required></label>
            <button class="btn btn-primary btn-sm">Record for independent review</button>
        </form>
    </details>
    @if($period?->closed_through)
        <p>Selected account closed through {{ $period->closed_through }}.</p>
        @if($capabilities->allows(auth()->user(), 'finance.periods.reopen'))
            <form method="POST" action="{{ route('finance.periods.reopen', $account->id) }}" class="mb-3">
                @csrf
                <input type="hidden" name="event_id" value="{{ $period->id }}">
                <label>Reason to reopen<input class="form-control" name="reason" maxlength="500" required></label>
                <button class="btn btn-outline-warning btn-sm">Reopen most recent period</button>
            </form>
        @endif
    @endif
    <div class="table-responsive"><table class="table table-striped">
        <thead><tr><th>Date / Account</th><th>Ledger NGN</th><th>Statement NGN</th><th>Variance NGN</th><th>Review</th></tr></thead>
        <tbody>
        @forelse($statementChecks as $check)
            <tr><td>{{ $check->statement_date }} / #{{ $check->account_id }}<div class="small">{{ $check->evidence_reference }}</div></td>
                <td>{{ number_format($check->ledger_balance_minor / 100, 2) }}</td>
                <td>{{ number_format($check->statement_balance_minor / 100, 2) }}</td>
                <td>{{ number_format($check->variance_minor / 100, 2) }}</td>
                <td>{{ ucfirst($check->review_outcome) }}
                    @if($check->review_outcome === 'pending' && (int) $check->prepared_by !== (int) auth()->id())
                    <form method="POST" action="{{ route('finance.statements.review', $check->id) }}">
                        @csrf
                        <label>Review notes<input name="review_notes" class="form-control" maxlength="500" required></label>
                        <button class="btn btn-outline-primary btn-sm">Record independent review</button>
                    </form>@endif
                    @if($check->review_outcome === 'balanced' && $check->statement_date < today()->toDateString()
                        && (int) $check->prepared_by !== (int) auth()->id() && $capabilities->allows(auth()->user(), 'finance.periods.close'))
                    <form method="POST" action="{{ route('finance.periods.close', $check->account_id) }}">
                        @csrf
                        <input type="hidden" name="statement_id" value="{{ $check->id }}">
                        <label>Reason to close<input class="form-control" name="reason" maxlength="500" required></label>
                        <button class="btn btn-outline-primary btn-sm">Close through this statement date</button>
                    </form>
                    @endif
                </td>
            </tr>
        @empty <tr><td colspan="5">No statement checks recorded.</td></tr>
        @endforelse
        </tbody>
    </table></div>
@endif

@unless($section === 'accounts' && $accounts->isEmpty())
<form method="GET" class="card card-body mb-3">
    <div class="form-row align-items-end">
        <div class="col-md-4">
            <label for="finance-account">Financial account</label>
            <select id="finance-account" name="account" class="form-control">
                @if($section === 'dashboard')<option value="">All accounts in your jurisdiction</option>@endif
                @foreach($accounts as $item)
                    <option value="{{ $item->id }}" @selected(($filters['account'] ?? null) == $item->id || ($section !== 'dashboard' && $account?->id === $item->id))>{{ $item->name }}</option>
                @endforeach
            </select>
        </div>
        @if(in_array($section, ['dashboard', 'income', 'expenditure', 'reports'], true))
            <div class="col-md-3"><label for="finance-from">From</label><input id="finance-from" type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}"></div>
            <div class="col-md-3"><label for="finance-to">Through</label><input id="finance-to" type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}"></div>
        @elseif($section === 'requests')
            <div class="col-md-4"><label for="finance-status">Request status</label><select id="finance-status" name="status" class="form-control"><option value="">All statuses</option>
            @foreach(['pending', 'approved', 'rejected', 'cancelled', 'recorded'] as $status)
                <option @selected(($filters['status'] ?? null) === $status) value="{{ $status }}">{{ ucfirst($status) }}</option>
            @endforeach
            </select></div>
        @endif
        <div class="col-md-2"><button class="btn btn-primary mt-2">Apply filters</button></div>
    </div>
</form>
@endunless
@if($section === 'accounts')
@if($capabilities->allows(auth()->user(), 'finance.accounts.manage'))
<details class="card card-body mb-3" @if($custodianSearch !== '') open @endif><summary>Create account</summary>
<form method="GET" class="my-3"><input type="hidden" name="account" value="{{ $account?->id }}"><label for="custodian-search">Find a custodian by name or username</label><input id="custodian-search" name="custodian_search" class="form-control" maxlength="100" value="{{ $custodianSearch }}"><button class="btn btn-outline-primary mt-2">Find members</button></form>
<form method="POST" action="{{ route('finance.accounts.store') }}">@csrf<label for="account-name">Account name</label><input id="account-name" name="name" class="form-control" maxlength="120" required><label for="account-custodian">Custodian</label><select id="account-custodian" name="owner_id" class="form-control" required><option value="">Choose an authorized member</option>@foreach($custodians as $custodian)<option value="{{ $custodian->id }}">{{ trim($custodian->firstname.' '.$custodian->lastname) }} ({{ $custodian->username }})</option>@endforeach</select><p class="small">The account retains the custodian's current geographic jurisdiction. Only active members in your permitted area are listed. Search to narrow the first 100 matches.</p><button class="btn btn-primary">Create financial account</button></form></details>
@endif
@endif
@if($account)
<h2 class="h5 text-primary">{{ $section === 'dashboard' && empty($filters['account']) ? 'All authorized financial accounts' : $account->name }}</h2>
@if($section === 'dashboard')
<div class="row">
@foreach([
    'Recorded income' => $totals['income'] ?? 0,
    'Recorded expenditure' => $totals['expenditure'] ?? 0,
    'Recorded treasury' => $treasury,
    'Reserved funds' => $reserved,
    'Available recorded funds' => $treasury - $reserved,
] as $label => $amount)
    <div class="col-sm-6 col-lg-4 mb-3"><div class="card card-body h-100"><h2 class="h5 text-primary">{{ $label }}</h2><strong>NGN {{ number_format($amount / 100, 2) }}</strong></div></div>
@endforeach
    <div class="col-sm-6 col-lg-4 mb-3"><div class="card card-body h-100"><h2 class="h5 text-primary">Pending funding requests</h2><strong>{{ $pending }}</strong></div></div>
</div>
<p class="small">Date filters apply to income and expenditure. Balances and reservations are current.</p>
@elseif($section === 'budgets')
@if($capabilities->allows(auth()->user(), 'finance.budget.manage'))
<details class="card card-body mb-3"><summary>Allocate budget</summary><form method="POST" action="{{ route('finance.budgets.store') }}">@csrf<input type="hidden" name="account_id" value="{{ $account->id }}"><label>Budget name<input class="form-control" name="name" maxlength="120" required></label><label>Allocation (NGN)<input class="form-control" name="amount" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" required></label><button class="btn btn-primary">Save allocation</button></form></details>
@endif
<h2 class="h4 text-primary">Budget utilization</h2><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Budget</th><th>Allocation (NGN)</th><th>Utilized (NGN)</th><th>Available (NGN)</th></tr></thead><tbody>@forelse($budgets as $budget)<tr><td>{{ $budget->name }}</td><td>{{ number_format($budget->allocation_minor / 100, 2) }}</td><td>{{ number_format($budget->utilized_minor / 100, 2) }}</td><td>{{ number_format(($budget->allocation_minor - $budget->utilized_minor) / 100, 2) }}</td></tr>@empty<tr><td colspan="4">No budget allocations yet.</td></tr>@endforelse</tbody></table></div>
@elseif(in_array($section, ['requests', 'approvals', 'disbursements'], true))
@if($section === 'requests')
@if($capabilities->allows(auth()->user(), 'finance.disbursement.create'))
@include('backend.finance.funding-request')
@endif
@endif
@if($section === 'disbursements')
<p><a href="{{ route('finance.paystack.index') }}">View Paystack test activity</a></p>
<p>Track approved requests and recorded expenses. Bank transfers are unavailable.</p>
@endif
<h2 class="h4 text-primary">Funding requests</h2><p class="small">Payment requests in this account. Requesters cannot decide their own requests. Approvers cannot record expenditure against their own approvals.</p><div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Purpose</th><th>Amount (NGN)</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($requests as $payment)<tr><td>{{ $payment->id }}</td><td>{{ $payment->description }}
@if($payment->purpose === 'transfer')<div class="small">Internal transfer to account #{{ $payment->destination_id }}</div>@endif
@if($payment->purpose === 'advance')<div class="small">Advance to {{ $payment->recipient }}{{ $payment->settled_at ? ' (settled)' : '' }}</div>@endif
</td><td>{{ number_format($payment->amount_minor / 100, 2) }}</td><td>{{ ucfirst($payment->status) }} @if($section === 'approvals' && $payment->status === 'pending' && $payment->stages) (Stage {{ $payment->next_stage + 1 }} of {{ count(json_decode($payment->stages, true)) }}) @endif</td><td>@if($payment->status === 'pending' && (int)$payment->requested_by !== (int)auth()->id() && $capabilities->allows(auth()->user(), 'finance.disbursement.approve'))<form method="POST" action="{{ route('finance.requests.decide', $payment->id) }}">@csrf<button name="decision" value="approve" class="btn btn-success btn-sm">Approve</button><button name="decision" value="reject" class="btn btn-danger btn-sm">Reject</button></form>@endif
@if($section === 'requests' && in_array($payment->status, ['pending', 'approved'], true) && (int) $payment->requested_by === (int) auth()->id() && $capabilities->allows(auth()->user(), 'finance.disbursement.create'))
<form method="POST" action="{{ route('finance.requests.cancel', $payment->id) }}">@csrf<button class="btn btn-outline-danger btn-sm">Cancel request</button></form>
@endif@if($section === 'disbursements')
    @include('backend.finance.movement-actions')
@endif
</td></tr>@empty<tr><td colspan="5">No payment requests yet.</td></tr>@endforelse</tbody></table></div>{{ $requests->links('pagination::bootstrap-4') }}
@elseif(in_array($section, ['income', 'expenditure'], true))
@if($section === 'income')
    <p><a href="{{ route('finance.paystack.index') }}">View Paystack test activity</a></p>
@endif
@if($section === 'expenditure' && $capabilities->allows(auth()->user(), 'finance.expenses.record'))
    @include('backend.finance.manual-expense')
@endif
@if($capabilities->allows(auth()->user(), $section === 'income' ? 'finance.record' : 'finance.disbursement.execute'))
<details class="card card-body mb-3"><summary>{{ $section === 'income' ? 'Record manual income' : 'Record a completed payment against an approved expense request' }}</summary><form method="POST" action="{{ route('finance.entries.store') }}">@csrf<input type="hidden" name="account_id" value="{{ $account->id }}"><input type="hidden" name="idempotency_key" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><input type="hidden" name="kind" value="{{ $section === 'income' ? 'income' : 'expenditure' }}">
@if($section !== 'income')<label>Approved funding request ID<input class="form-control" type="number" name="request_id" min="1" required></label>@endif<label>Amount (NGN)<input class="form-control" name="amount" inputmode="decimal" required></label><label>Description<textarea class="form-control" name="description" maxlength="500" required></textarea></label><button class="btn btn-primary">Record transaction</button></form></details>
@endif
@include('backend.finance.entries')
@elseif($section === 'reports')
<h2 class="h4 text-primary">Journal and reconciliation</h2>
@if($ledgerCheck)
    <p>Historical balance checked on {{ $ledgerCheck->checked_at }}.</p>
@else
    <p>The opening transaction history is awaiting a balance check.</p>
    @if($capabilities->allows(auth()->user(), 'finance.reconcile'))
        <form method="POST" action="{{ route('finance.reconcile', $account->id) }}">@csrf<button class="btn btn-primary mb-3">Check historical balance</button></form>
    @endif
@endif
<dl>
    @foreach($chartOfAccounts as $code => $label)<dt>{{ $code }}</dt><dd>{{ $label }}</dd>@endforeach
</dl>
@if($capabilities->allows(auth()->user(), 'finance.reports.export'))<a class="btn btn-outline-primary mb-3" href="{{ route('finance.statement', $account->id) }}">Export full account statement (CSV)</a>@endif
@include('backend.finance.entries')
@if($documents)
<h2 class="h4 text-primary">Supporting documents</h2><p class="small">PDF, JPEG or PNG, up to 10 MB per file. Files remain private and are checked when downloaded.</p>
@if($capabilities->allows(auth()->user(), 'finance.record'))<form class="card card-body mb-3" method="POST" enctype="multipart/form-data" action="{{ route('finance.documents.store') }}">@csrf<input type="hidden" name="account_id" value="{{ $account->id }}"><label>Payment request ID (optional)<input class="form-control" type="number" min="1" name="request_id"></label><label>Document<input class="form-control" type="file" name="document" accept="application/pdf,image/jpeg,image/png" required></label><button class="btn btn-primary">Attach document</button></form>@endif
<ul>@forelse($documents as $document)<li><a href="{{ route('finance.documents.download', $document->id) }}">{{ $document->filename }}</a> @if($document->request_id) (Request {{ $document->request_id }}) @endif</li>@empty<li>No supporting documents attached.</li>@endforelse</ul>{{ $documents->links('pagination::bootstrap-4') }}
@endif
@if($audits)
<details class="card card-body" @if(request()->filled('audits_page')) open @endif><summary>Audit trail</summary><div class="table-responsive"><table class="table"><thead><tr><th>Date</th><th>Actor ID</th><th>Action</th><th>Details</th></tr></thead><tbody>@foreach($audits as $event)<tr><td>{{ $event->created_at }}</td><td>{{ $event->actor_id }}</td><td>{{ $event->action }}</td><td>{{ $event->details }}</td></tr>@endforeach</tbody></table></div>{{ $audits->links('pagination::bootstrap-4') }}</details>
@endif
@elseif($section === 'accounts')
<p>Recorded balance for {{ $account->name }}: NGN {{ number_format($account->balance_minor / 100, 2) }}.</p>
@endif
@else<p class="alert alert-info">No financial accounts are visible in your permitted campaign area.</p>@endif
</div></section>
@endsection
