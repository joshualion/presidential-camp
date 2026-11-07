<details class="card card-body mb-3">
    <summary>Request funding</summary>
    <form method="POST" action="{{ route('finance.requests.store') }}">
        @csrf
        <input type="hidden" name="account_id" value="{{ $account->id }}">
        <label>Funding type
            <select class="form-control" name="purpose">
                <option value="expense">Direct payment or reimbursement</option>
                <option value="transfer">Internal allocation</option>
                <option value="advance">Advance to a person</option>
            </select>
        </label>
        <label>Destination for an internal allocation
            <select class="form-control" name="destination_id">
                <option value="">Choose an account for allocations</option>
                @foreach($accounts as $candidate)
                    @if($candidate->id !== $account->id)
                        <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                    @endif
                @endforeach
            </select>
        </label>
        <label>Recipient for an advance<input class="form-control" name="recipient" maxlength="160"></label>
        <label>Budget
            <select class="form-control" name="budget_id">
                <option value="">Unbudgeted or internal allocation</option>
                @foreach($budgets as $budget)
                    <option value="{{ $budget->id }}">{{ $budget->name }}</option>
                @endforeach
            </select>
        </label>
        <label><input type="checkbox" name="use_profile" value="1" @checked(old('use_profile'))>
            Use the bank details already saved in my profile for this reimbursement</label>
        @if($capabilities->allows(auth()->user(), 'finance.paystack.recipients.manage')
            && app(\App\Services\Enterprise\CampaignFinancePolicy::class)->permits(auth()->user(), 'finance.paystack.recipients.manage'))
            <label>Or enter the username of the member being reimbursed
                <input class="form-control" name="profile_username" maxlength="100" value="{{ old('profile_username') }}">
            </label>
            <p class="small">Leave the named recipient rows blank. Finance uses the member’s saved bank details after independent approval.</p>
        @endif
        <details class="card card-body mb-2">
            <summary>Optional named recipients (direct expense requests only)</summary>
            <p class="small">You can specify up to five people here. For larger itemized requests, the API supports twenty. The named amounts must sum to the request total. A named recipient request cannot be marked paid until the recipient-level disbursement workflow is available.</p>
            @foreach(range(0, 4) as $index)
                <div class="form-row">
                    <div class="col-md-8">
                        <label for="payee-name-{{ $index }}">Recipient {{ $index + 1 }}</label>
                        <input id="payee-name-{{ $index }}" class="form-control" name="recipients[{{ $index }}][name]" maxlength="160" value="{{ old('recipients.'.$index.'.name', '') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="payee-amount-{{ $index }}">Amount (NGN)</label>
                        <input id="payee-amount-{{ $index }}" class="form-control" name="recipients[{{ $index }}][amount]" inputmode="decimal" value="{{ old('recipients.'.$index.'.amount', '') }}">
                    </div>
                </div>
            @endforeach
        </details>
        <label>Amount (NGN)<input class="form-control" name="amount" inputmode="decimal" required></label>
        <label>Purpose<textarea class="form-control" name="description" maxlength="500" required></textarea></label>
        <button class="btn btn-primary">Submit request</button>
    </form>
</details>
