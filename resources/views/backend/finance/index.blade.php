@extends('backend.template.backend-master')
@section('content')
<section class="content cm-ui"><div class="container-fluid">

<p>Record campaign funds, allocate budgets and approve payment requests. Amounts are in Nigerian naira. This workspace records campaign operations in NGN. Recorded balances are accounting balances.</p>
@if(session('finance_status'))<div class="alert alert-success" role="status">{{ session('finance_status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
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
<details class="card card-body mb-3"><summary>Request payment</summary><form method="POST" action="{{ route('finance.requests.store') }}">@csrf<input type="hidden" name="account_id" value="{{ $account->id }}"><label>Budget<select class="form-control" name="budget_id"><option value="">Unbudgeted</option>@foreach($budgets as $budget)<option value="{{ $budget->id }}">{{ $budget->name }}</option>@endforeach</select></label><label>Amount (NGN)<input class="form-control" name="amount" inputmode="decimal" required></label><label>Purpose<textarea class="form-control" name="description" maxlength="500" required></textarea></label><button class="btn btn-primary">Submit request</button></form></details>
@endif
@endif
@if($section === 'disbursements')
<p>Track approved requests and recorded expenses. Bank transfers are unavailable.</p>
@endif
<h2 class="h4 text-primary">Funding requests</h2><p class="small">Payment requests in this account. Requesters cannot decide their own requests. Approvers cannot record expenditure against their own approvals.</p><div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Purpose</th><th>Amount (NGN)</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($requests as $payment)<tr><td>{{ $payment->id }}</td><td>{{ $payment->description }}</td><td>{{ number_format($payment->amount_minor / 100, 2) }}</td><td>{{ ucfirst($payment->status) }} @if($section === 'approvals' && $payment->status === 'pending' && $payment->stages) (Stage {{ $payment->next_stage + 1 }} of {{ count(json_decode($payment->stages, true)) }}) @endif</td><td>@if($payment->status === 'pending' && (int)$payment->requested_by !== (int)auth()->id() && $capabilities->allows(auth()->user(), 'finance.disbursement.approve'))<form method="POST" action="{{ route('finance.requests.decide', $payment->id) }}">@csrf<button name="decision" value="approve" class="btn btn-success btn-sm">Approve</button><button name="decision" value="reject" class="btn btn-danger btn-sm">Reject</button></form>@endif
@if($section === 'requests' && in_array($payment->status, ['pending', 'approved'], true) && (int) $payment->requested_by === (int) auth()->id() && $capabilities->allows(auth()->user(), 'finance.disbursement.create'))
<form method="POST" action="{{ route('finance.requests.cancel', $payment->id) }}">@csrf<button class="btn btn-outline-danger btn-sm">Cancel request</button></form>
@endif</td></tr>@empty<tr><td colspan="5">No payment requests yet.</td></tr>@endforelse</tbody></table></div>{{ $requests->links('pagination::bootstrap-4') }}
@elseif(in_array($section, ['income', 'expenditure'], true))
@if($capabilities->allows(auth()->user(), $section === 'income' ? 'finance.record' : 'finance.disbursement.execute'))
<details class="card card-body mb-3"><summary>{{ $section === 'income' ? 'Record manual income' : 'Record a completed expense' }}</summary><form method="POST" action="{{ route('finance.entries.store') }}">@csrf<input type="hidden" name="account_id" value="{{ $account->id }}"><input type="hidden" name="idempotency_key" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><input type="hidden" name="kind" value="{{ $section === 'income' ? 'income' : 'expenditure' }}">
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
