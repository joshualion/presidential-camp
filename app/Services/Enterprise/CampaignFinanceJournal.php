<?php

namespace App\Services\Enterprise;

use App\Models\CampaignFinanceAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Paired postings share one minor-unit amount, so each journal entry balances by construction. */
class CampaignFinanceJournal
{
    public const ACCOUNTS = [
        '1000' => 'Recorded treasury',
        '4000' => 'Historical and manual receipts',
        '5000' => 'Campaign expenditure',
    ];

    /**
     * Run within the posting transaction after locking the settlement account.
     * An unexplained opening balance or a broken history prevents posting on this account.
     */
    public function reconcile(CampaignFinanceAccount $account): void
    {
        if (DB::table('campaign_finance_ledger_checks')->where('account_id', $account->id)->exists()) {
            return;
        }

        $balance = 0;
        $count = 0;
        $entries = DB::table('campaign_finance_entries')->where('account_id', $account->id)->lazyById(500);
        foreach ($entries as $entry) {
            $amount = (int) $entry->amount_minor;
            if ($amount < 1 || $amount > 100000000000000 || !in_array($entry->kind, ['income', 'expenditure'], true)) {
                $this->unreconciled();
            }
            $balance += $entry->kind === 'income' ? $amount : -$amount;
            if ($balance < 0 || $balance > 9000000000000000 || $balance !== (int) $entry->balance_after_minor) {
                $this->unreconciled();
            }

            $this->append((int) $entry->id, $entry->kind, $amount);
            $count++;
        }

        if ($balance !== (int) $account->balance_minor) {
            $this->unreconciled();
        }

        DB::table('campaign_finance_ledger_checks')->insert([
            'account_id' => $account->id,
            'historical_entries' => $count,
            'historical_balance_minor' => $balance,
            'checked_at' => now(),
        ]);
    }

    public function append(int $entryId, string $kind, int $amount): void
    {
        [$debit, $credit] = match ($kind) {
            'income' => ['1000', '4000'],
            'expenditure' => ['5000', '1000'],
        };

        $this->postPair($entryId, $debit, $credit, $amount);
    }

    public function reverse(int $entryId, int $reversalId): void
    {
        $original = DB::table('campaign_finance_journal')->where('entry_id', $entryId)->firstOrFail();
        $this->postPair($reversalId, $original->credit_code, $original->debit_code, (int) $original->amount_minor);
    }

    private function postPair(int $entryId, string $debit, string $credit, int $amount): void
    {
        DB::table('campaign_finance_journal')->insert([
            'entry_id' => $entryId,
            'debit_code' => $debit,
            'credit_code' => $credit,
            'amount_minor' => $amount,
        ]);
    }

    private function unreconciled(): never
    {
        throw ValidationException::withMessages([
            'finance' => 'The historical transactions do not reconcile to the recorded balance. Review the existing statement and supporting records before posting to this account.',
        ]);
    }
}
