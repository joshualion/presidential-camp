<h2 class="h4 text-primary">Transaction history</h2>
<p class="small">Entries are shown newest first. A reversal keeps the original entry in the journal.</p>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Reference</th><th>Date</th><th>Type</th><th>Description</th><th>Amount (NGN)</th>
                <th>Balance (NGN)</th><th>Debit / credit</th>
                @if($section === 'reports')<th>Correction</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->id }}</td>
                    <td>{{ $entry->created_at }}</td>
                    <td>{{ ucfirst($entry->kind) }}</td>
                    <td>{{ $entry->description }}</td>
                    <td>{{ number_format($entry->amount_minor / 100, 2) }}</td>
                    <td>{{ number_format($entry->balance_after_minor / 100, 2) }}</td>
                    <td>{{ $entry->debit_code ? $entry->debit_code.' / '.$entry->credit_code : 'Historical' }}</td>
                    @if($section === 'reports')
                        <td>
                            @if($entry->reversal_id)
                                Reversed by entry {{ $entry->reversal_id }}
                            @elseif($entry->reverses_entry_id)
                                Reverses entry {{ $entry->reverses_entry_id }}
                            @elseif((int) $entry->recorded_by !== (int) auth()->id() && $capabilities->allows(auth()->user(), 'finance.entries.reverse'))
                                <details>
                                    <summary>Reverse entry</summary>
                                    <form method="POST" action="{{ route('finance.entries.reverse', ['accountId' => $account->id, 'entryId' => $entry->id]) }}">
                                        @csrf
                                        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                                        <label>Reason<textarea name="reason" class="form-control" required maxlength="500"></textarea></label>
                                        <button class="btn btn-sm btn-outline-danger">Post reversal</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $section === 'reports' ? 8 : 7 }}">No transactions recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $entries->links('pagination::bootstrap-4') }}
