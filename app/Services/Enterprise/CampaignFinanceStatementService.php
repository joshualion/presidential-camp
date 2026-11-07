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
            || !\DateTimeImmutable::createFromFormat('!Y-m-d', $date)
            || \DateTimeImmutable::createFromFormat('!Y-m-d', $date)->format('Y-m-d') !== $date
            || $date > now()->toDateString() || $statementBalance < 0
            || $statementBalance > 9000000000000000) {
            $this->invalid('Provide a valid statement date, balance and evidence reference.');
        }
        return DB::transaction(function () use ($actor, $accountId, $date, $statementBalance, $reference) {
            $account = $this->finance->account($actor, $accountId, true);
            if (DB::table('campaign_finance_statement_checks')
                ->where('account_id', $accountId)->where('statement_date', $date)->exists()) {
                $this->invalid('A reconciliation check already exists for this account and date.');
            }
            $journal = app(CampaignFinanceJournal::class);
            $journal->reconcile($account);
            $journal->assertIntegrity($account);
            $entry = DB::table('campaign_finance_entries')
                ->where('account_id', $accountId)
                ->whereDate('created_at', '<=', $date)
                ->orderByDesc('created_at')->orderByDesc('id')->first();
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

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['finance' => $message]);
    }
}
