<details class="card card-body mb-3">
    <summary>Record an expense already paid outside Campaign Manager</summary>
    <p class="small">Post the expense to {{ $account->name }} using the original payment details. This action records the payment in Finance.</p>
    <form method="POST" action="{{ route('finance.expenses.store') }}">
        @csrf
        <input type="hidden" name="account_id" value="{{ $account->id }}">
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
        <label>Budget
            <select class="form-control" name="budget_id">
                <option value="">Unbudgeted</option>
                @foreach($budgets as $budget)
                    <option value="{{ $budget->id }}" @selected(old('budget_id') == $budget->id)>{{ $budget->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Payee<input class="form-control" name="payee" maxlength="160" value="{{ old('payee') }}" required></label>
        <label>Date paid<input class="form-control" type="date" name="paid_on" max="{{ today()->toDateString() }}" value="{{ old('paid_on') }}" required></label>
        <label>Payment reference<input class="form-control" name="reference" maxlength="120" value="{{ old('reference') }}" required></label>
        <label>Receipt number or evidence reference<input class="form-control" name="evidence_reference" maxlength="300" value="{{ old('evidence_reference') }}" required></label>
        <label>Amount (NGN)<input class="form-control" name="amount" inputmode="decimal" value="{{ old('amount') }}" required></label>
        <label>Description<textarea class="form-control" name="description" maxlength="500" required>{{ old('description') }}</textarea></label>
        <button class="btn btn-primary">Record paid expense</button>
    </form>
</details>
