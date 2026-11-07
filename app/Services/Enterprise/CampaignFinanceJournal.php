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
        '1100' => 'Internal transfers in transit',
        '1200' => 'Outstanding advances',
        '4000' => 'Historical and manual receipts',
        '5000' => 'Campaign expenditure',
    ];

    /**
     * Run within the posting transaction after locking the settlement account.
     * An unexplained opening balance or a broken history prevents posting on this account.
     */
    public function reconcile(CampaignFinanceAccount $account): void
    {
        if (DB::table('campaign_finance_ledger_checks')->where('account_id', $account->id)->lockForUpdate()->first(['account_id'])) {
            return;
        }

        $balance = 0;
        $count = 0;
        $entries = DB::table('campaign_finance_entries')->where('account_id', $account->id)->lockForUpdate()->lazyById(500);
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
        [$debit, $credit] = $this->codes($kind);
        $this->postPair($entryId, $debit, $credit, $amount);
    }

    public function assertIntegrity(CampaignFinanceAccount $account): void
    {
        $rows = DB::table('campaign_finance_entries as entry')
            ->leftJoin('campaign_finance_journal as journal', 'journal.entry_id', '=', 'entry.id')
            ->leftJoin('campaign_finance_reversals as reversal', 'reversal.reversal_id', '=', 'entry.id')
            ->leftJoin('campaign_finance_entries as original', 'original.id', '=', 'reversal.entry_id')
            ->where('entry.account_id', $account->id)->select([
                'entry.id', 'entry.kind', 'entry.amount_minor', 'entry.balance_after_minor',
                'journal.debit_code', 'journal.credit_code', 'journal.amount_minor as journal_amount',
                'original.kind as original_kind', 'original.account_id as original_account',
                'original.amount_minor as original_amount', 'original.id as original_id',
            ])->lazyById(500, 'entry.id', 'id');

        $balance = 0;
        foreach ($rows as $entry) {
            $amount = (int) $entry->amount_minor;
            if ($entry->kind === 'reversal') {
                if ((int) $entry->original_account !== (int) $account->id || (int) $entry->original_amount !== $amount
                    || (int) $entry->original_id >= (int) $entry->id || !in_array($entry->original_kind, ['income', 'expenditure'], true)) {
                    $this->unreconciled();
                }
                [$credit, $debit] = $this->codes($entry->original_kind);
            } else {
                [$debit, $credit] = $this->codes($entry->kind);
            }
            $balance += ($debit === '1000' ? $amount : 0) - ($credit === '1000' ? $amount : 0);
            if ($amount < 1 || $amount > 100000000000000 || $amount !== (int) $entry->journal_amount
                || $entry->debit_code !== $debit || $entry->credit_code !== $credit
                || $balance < 0 || $balance > 9000000000000000 || $balance !== (int) $entry->balance_after_minor) {
                $this->unreconciled();
            }
        }
        if ($balance !== (int) $account->balance_minor) {
            $this->unreconciled();
        }
    }

    private function codes(string $kind): array
    {
        return match ($kind) {
            'income' => ['1000', '4000'],
            'expenditure' => ['5000', '1000'],
            'transfer_out' => ['1100', '1000'],
            'transfer_in' => ['1000', '1100'],
            'advance_out' => ['1200', '1000'],
            'advance_return' => ['1000', '1200'],
            'advance_expense' => ['5000', '1200'],
            default => $this->unreconciled(),
        };
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
