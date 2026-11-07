<?php

namespace App\Services\Enterprise;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Audit-only reconciliation against a supplied bank statement. Never books cash.
 * A balanced check means supplied figures agree; it is not provider verification.
 */
class CampaignFinanceStatementService
{
    public function __construct(private CampaignFinanceService $finance) {}

    public function prepare(
        User $actor, int $accountId, string $date, int $statementBalance, string $reference
    ): int {
        $this->finance->authorize($actor, 'finance.reconcile');
        $reference = trim($reference);
        if (mb_strlen($reference) < 3 || mb_strlen($reference) > 300
            || !$this->validDate($date)
            || $date > now()->toDateString() || $statementBalance < 0
            || $statementBalance > 9000000000000000) {
            $this->invalid('Provide a valid statement date, balance and evidence reference.');
        }
        return DB::transaction(function () use ($actor, $accountId, $date, $statementBalance, $reference) {
            $account = $this->finance->account($actor, $accountId, true);
            if (DB::table('campaign_finance_statement_checks')
                ->where('account_id', $accountId)->where('statement_date', $date)->lockForUpdate()->first()) {
                $this->invalid('A reconciliation check already exists for this account and date.');
            }
            app(CampaignFinancePeriodService::class)->assertOpen($accountId, $date);
            $journal = app(CampaignFinanceJournal::class);
            $journal->reconcile($account);
            $journal->assertIntegrity($account);
            $entry = DB::table('campaign_finance_entries')
                ->where('account_id', $accountId)
                ->whereDate('created_at', '<=', $date)
                ->orderByDesc('created_at')->orderByDesc('id')->lockForUpdate()->first();
            $ledger = $entry ? (int) $entry->balance_after_minor : 0;
            $id = DB::table('campaign_finance_statement_checks')->insertGetId([
                'account_id' => $accountId, 'statement_date' => $date,
                'evidence_reference' => $reference, 'ledger_entry_id' => $entry?->id,
                'ledger_balance_minor' => $ledger,
                'statement_balance_minor' => $statementBalance,
                'variance_minor' => $statementBalance - $ledger,
                'prepared_by' => $actor->id,
                'review_outcome' => 'pending',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('campaign_finance_audits')->insert([
                'account_id' => $accountId, 'actor_id' => $actor->id,
                'action' => 'statement.check.prepared',
                'details' => json_encode(['check_id' => $id, 'statement_date' => $date,
                    'statement_balance_minor' => $statementBalance, 'ledger_balance_minor' => $ledger,
                    'variance_minor' => $statementBalance - $ledger], JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
            return $id;
        }, 3);
    }

    public function import(
        User $actor, int $accountId, string $start, string $end, int $opening, int $closing,
        string $reference, string $csv
    ): int {
        $this->finance->authorize($actor, 'finance.reconcile');
        $this->finance->account($actor, $accountId);
        if (!$this->validDate($start) || !$this->validDate($end) || $start > $end
            || strlen($csv) > 512 * 1024 || $opening < 0 || $opening > 9000000000000000
            || !mb_check_encoding($csv, 'UTF-8') || str_contains($csv, "\0")) {
            $this->invalid('Provide a valid statement period, opening balance and UTF-8 CSV under 512 KB.');
        }

        $stream = fopen('php://temp', 'w+');
        try {
            fwrite($stream, $csv);
            rewind($stream);
            if (fgetcsv($stream, 0, ',', '"', '') !== ['date', 'reference', 'amount_minor']) {
                $this->invalid('Use the CSV header date,reference,amount_minor.');
            }
            $lines = [];
            $balance = $opening;
            while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                if (count($row) !== 3 || count($lines) >= 5000) {
                    $this->invalid('Each CSV row must have three columns. The import limit is 5,000 rows.');
                }
                [$date, $entryReference, $amount] = $row;
                if (!$this->validDate($date) || $date < $start || $date > $end
                    || trim($entryReference) === '' || mb_strlen($entryReference) > 150
                    || !preg_match('/^-?[1-9][0-9]{0,14}$/D', $amount)) {
                    $this->invalid('Each row requires a date in the statement period, a reference and a signed minor-unit amount.');
                }
                $balance += (int) $amount;
                if (abs($balance) > 9000000000000000) {
                    $this->invalid('A running statement balance exceeds the supported range.');
                }
                $lines[] = ['date' => $date, 'reference' => $entryReference, 'amount_minor' => (int) $amount];
            }
        } finally {
            fclose($stream);
        }

        if ($balance !== $closing) {
            $this->invalid('The opening balance plus the CSV movements must equal the stated closing balance.');
        }

        return DB::transaction(function () use ($actor, $accountId, $start, $end, $opening, $closing, $reference, $csv, $lines) {
            $id = $this->prepare($actor, $accountId, $end, $closing, $reference);
            DB::table('campaign_finance_statement_imports')->insert([
                'check_id' => $id, 'starts_on' => $start, 'opening_minor' => $opening,
                'sha256' => hash('sha256', $csv), 'lines' => json_encode($lines, JSON_THROW_ON_ERROR),
            ]);
            return $id;
        }, 3);
    }

    public function resolve(User $actor, int $checkId, int $matchedCheckId, string $reason): void
    {
        $this->finance->authorize($actor, 'finance.reconcile');
        $reason = trim($reason);
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 500) {
            $this->invalid('Provide an investigation and adjustment reference (3–500 characters).');
        }

        DB::transaction(function () use ($actor, $checkId, $matchedCheckId, $reason) {
            $checks = DB::table('campaign_finance_statement_checks');
            $accountId = (clone $checks)->where('id', $checkId)->value('account_id');
            abort_unless($accountId, 404);
            $this->finance->account($actor, (int) $accountId, true);
            $checks->where('account_id', $accountId);
            $original = (clone $checks)->where('id', $checkId)->lockForUpdate()->firstOrFail();
            $matched = (clone $checks)->where('id', $matchedCheckId)->lockForUpdate()->firstOrFail();
            abort_if(in_array((int) $actor->id, [(int) $original->prepared_by, (int) $matched->prepared_by], true), 403,
                'A finance officer who prepared neither statement must resolve the discrepancy.');
            if ($original->review_outcome !== 'variance' || $matched->review_outcome !== 'balanced'
                || $matched->statement_date <= $original->statement_date
                || !DB::table('campaign_finance_statement_imports')->where('check_id', $matchedCheckId)->lockForUpdate()->first()
                || DB::table('campaign_finance_statement_resolutions')->where('check_id', $checkId)->lockForUpdate()->first()) {
                $this->invalid('Select an unresolved variance and a later reviewed, balanced CSV statement for this account.');
            }

            DB::table('campaign_finance_statement_resolutions')->insert([
                'check_id' => $checkId, 'matched_check_id' => $matchedCheckId,
                'actor_id' => $actor->id, 'reason' => $reason, 'created_at' => now(),
            ]);
            DB::table('campaign_finance_audits')->insert([
                'account_id' => $accountId, 'actor_id' => $actor->id,
                'action' => 'statement.variance.resolved',
                'details' => json_encode(['check_id' => $checkId, 'matched_check_id' => $matchedCheckId], JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
        }, 3);
    }

    public function review(User $actor, int $checkId, string $notes): void
    {
        $this->finance->authorize($actor, 'finance.reconcile');
        $notes = trim($notes);
        if (mb_strlen($notes) < 3 || mb_strlen($notes) > 500) {
            $this->invalid('Provide review notes (3–500 characters).');
        }
        DB::transaction(function () use ($actor, $checkId, $notes) {
            $accountId = DB::table('campaign_finance_statement_checks')
                ->where('id', $checkId)->value('account_id');
            abort_unless($accountId, 404);
            $this->finance->account($actor, (int) $accountId, true);
            $check = DB::table('campaign_finance_statement_checks')
                ->where('id', $checkId)->lockForUpdate()->firstOrFail();
            abort_if((int) $check->prepared_by === (int) $actor->id, 403,
                'Another finance officer must review your statement check.');
            if ($check->review_outcome !== 'pending') {
                $this->invalid('This statement check is already reviewed.');
            }
            $outcome = (int) $check->variance_minor === 0 ? 'balanced' : 'variance';
            DB::table('campaign_finance_statement_checks')->where('id', $checkId)->update([
                'review_outcome' => $outcome, 'reviewed_by' => $actor->id,
                'review_notes' => $notes, 'reviewed_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('campaign_finance_audits')->insert([
                'account_id' => $accountId, 'actor_id' => $actor->id,
                'action' => 'statement.check.reviewed',
                'details' => json_encode(['check_id' => $checkId, 'outcome' => $outcome,
                    'variance_minor' => (int) $check->variance_minor], JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
        }, 3);
    }

    private function validDate(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
            && (\DateTimeImmutable::createFromFormat('!Y-m-d', $date) ?: null)?->format('Y-m-d') === $date;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['finance' => $message]);
    }
}
