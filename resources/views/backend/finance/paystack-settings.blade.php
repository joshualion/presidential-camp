@extends('backend.template.backend-master')
@section('content')
<section class="content cm-ui"><div class="container-fluid">
    <h2>Campaign Payment Settings</h2>
    <p>One campaign-owned Paystack test connection serves all locations. A transaction's ledger account determines which officers can see it.</p>
    <p>Test transactions appear in their own activity log. They do not change the campaign ledger or real balances.</p>
    @if(session('finance_status'))<div class="alert alert-success">{{ session('finance_status') }}</div>@endif
    @foreach($errors->all() as $error)<div class="alert alert-danger">{{ $error }}</div>@endforeach
    <form method="POST" action="{{ route('finance.paystack.settings.save') }}" class="card card-body">
        @csrf
        <label>Environment
            <select class="form-control" name="mode">
                <option value="test" @selected($settings?->mode !== 'live')>Test</option>
                <option value="live" @selected($settings?->mode === 'live')>Live connection check only</option>
            </select>
        </label>
        @foreach(['test', 'live'] as $mode)
            <fieldset class="mb-3">
                <legend class="h5">{{ ucfirst($mode) }} credentials</legend>
                @foreach(['public', 'secret', 'webhook'] as $key)
                    <label>{{ ucfirst($key) }} key
                        <input name="{{ $mode }}_{{ $key }}" type="password" maxlength="120" class="form-control" autocomplete="new-password">
                    </label>
                @endforeach
                <p>Webhook URL <code>{{ route('finance.paystack.webhook', $mode) }}</code></p>
            </fieldset>
        @endforeach
        <p class="small">Blank fields retain saved keys. Replacing an API secret prevents verification of activity tied to its previous value.</p>
        <label><input type="checkbox" name="confirm_live" value="1"> Check the live connection in read-only mode</label>
        <label><input type="checkbox" name="collections" value="1" @checked($settings?->collections)> Accept test collections</label>
        <label><input type="checkbox" name="transfers" value="1" @checked($settings?->transfers)> Submit approved test transfers</label>
        <button class="btn btn-primary">Save payment settings</button>
    </form>
    <form method="POST" action="{{ route('finance.paystack.recipient') }}" class="card card-body">
        @csrf
        <label>Destination ledger account
            <select class="form-control" name="account_id" required>
                <option value="">Select account</option>
                @foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach
            </select>
        </label>
        <label>Paystack test recipient code<input class="form-control" name="recipient" maxlength="80" required></label>
        <button class="btn btn-primary">Verify and link test recipient</button>
    </form>
    <a href="{{ route('finance.paystack.index') }}">View Paystack test activity</a>
</div></section>
@endsection
