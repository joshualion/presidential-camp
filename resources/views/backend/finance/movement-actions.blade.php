@if($payment->purpose === 'transfer' && $payment->status === 'approved' && $paystackTransfersEnabled && $capabilities->allows(auth()->user(), 'finance.paystack.approve'))
    <form method="POST" action="{{ route('finance.paystack.approve', $payment->id) }}">
        @csrf
        <button class="btn btn-outline-primary btn-sm">Authorize test payout</button>
    </form>
@endif
@if($payment->purpose === 'transfer' && $payment->status === 'approved' && $paystackTransfersEnabled && $capabilities->allows(auth()->user(), 'finance.paystack.transfer'))
    <form method="POST" action="{{ route('finance.paystack.transfer', $payment->id) }}">
        @csrf
        <button class="btn btn-outline-primary btn-sm">Submit to Paystack test mode</button>
    </form>
@endif
@if($payment->purpose && $payment->status === 'approved' && $capabilities->allows(auth()->user(), 'finance.movements.post'))
    <form method="POST" action="{{ route('finance.movements.store', $payment->id) }}">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <label>{{ $payment->purpose === 'transfer' ? 'Allocation reference' : 'Confirmed payment reference' }}
            <input class="form-control" name="reference" maxlength="200" required>
        </label>
        <button class="btn btn-primary btn-sm">Record {{ $payment->purpose === 'transfer' ? 'allocation' : 'paid advance' }}</button>
    </form>
@endif
@if($payment->purpose === 'advance' && $payment->status === 'recorded' && !$payment->settled_at && $capabilities->allows(auth()->user(), 'finance.advances.settle'))
    <form method="POST" action="{{ route('finance.advances.settle', $payment->id) }}">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <label>Documented expense (NGN)<input class="form-control" name="spent" inputmode="decimal" required></label>
        <label>Evidence reference<input class="form-control" name="evidence" maxlength="300" required></label>
        <label><input type="checkbox" name="returns_received" value="1" required> Any unspent funds have been returned to the source account.</label>
        <button class="btn btn-primary btn-sm">Settle advance</button>
    </form>
@endif
