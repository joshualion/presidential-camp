<?php

namespace App\Services\Enterprise;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignFinancePeriodService
{
    public function __construct(private CampaignFinanceService $finance) {}

    public function latest(int $accountId, bool $lock = false): ?object
    {
        $query = DB::table('campaign_finance_period_events')->where('account_id', $accountId)->latest('id');
        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    /** The caller holds the account lock throughout the write. */
    public function assertOpen(int $accountId, string $date): void
    {
        $closed = $this->latest($accountId, true)?->closed_through;
        if ($closed !== null && $date <= $closed) {
            $this->invalid('The accounting period is closed for this date.');
        }
    }

    public function close(User $actor, int $accountId, int $statementId, string $reason): void
    {
        $this->finance->authorize($actor, 'finance.periods.close');
        $reason = $this->reason($reason);
        DB::transaction(function () use ($actor, $accountId, $statementId, $reason) {
            $account = $this->finance->account($actor, $accountId, true);
            $statement = DB::table('campaign_finance_statement_checks')->where('account_id', $accountId)
                ->where('id', $statementId)->lockForUpdate()->firstOrFail();
            if ($statement->review_outcome !== 'balanced' || (int) $statement->variance_minor !== 0
                || $statement->statement_date >= today()->toDateString()
                || (int) $statement->prepared_by === (int) $actor->id) {
                $this->invalid('Closing requires a balanced, independently reviewed statement for a completed day.');
            }
            $this->assertOpen($accountId, $statement->statement_date);
            $openIssues = DB::table('campaign_finance_statement_checks as checks')
                ->leftJoin('campaign_finance_statement_resolutions as resolution', 'resolution.check_id', '=', 'checks.id')
                ->leftJoin('campaign_finance_statement_checks as matched', 'matched.id', '=', 'resolution.matched_check_id')
                ->where('checks.account_id', $accountId)
                ->where('checks.statement_date', '<=', $statement->statement_date)
                ->whereIn('checks.review_outcome', ['pending', 'variance'])
                ->where(fn ($query) => $query->whereNull('resolution.check_id')
                    ->orWhere('matched.statement_date', '>', $statement->statement_date))
                ->select('checks.id')->lockForUpdate()->first();
            if ($openIssues) {
                $this->invalid('Resolve all pending statement reviews and variances through the closing date.');
            }
            $journal = app(CampaignFinanceJournal::class);
            $journal->reconcile($account);
            $journal->assertIntegrity($account);
            $entry = DB::table('campaign_finance_entries')->where('account_id', $accountId)
                ->whereDate('created_at', '<=', $statement->statement_date)->orderByDesc('created_at')->orderByDesc('id')->lockForUpdate()->first();
            if ((int) $entry?->id !== (int) $statement->ledger_entry_id
                || (int) $entry?->balance_after_minor !== (int) $statement->ledger_balance_minor) {
                $this->invalid('The reviewed statement snapshot no longer matches the journal.');
            }
            $latest = $this->latest($accountId, true);
            $prior = $latest?->action === 'closed' ? $latest->id : $latest?->prior_close_id;
            $this->event($actor, $accountId, 'closed', $statement->statement_date, $statementId, $reason, $prior);
        }, 3);
    }

    public function reopen(User $actor, int $accountId, int $expectedEventId, string $reason): void
    {
        $this->finance->authorize($actor, 'finance.periods.reopen');
        $reason = $this->reason($reason);
        DB::transaction(function () use ($actor, $accountId, $expectedEventId, $reason) {
            $this->finance->account($actor, $accountId, true);
            $latest = $this->latest($accountId, true);
            if (!$latest?->closed_through || (int) $latest->id !== $expectedEventId) {
                $this->invalid('The closing boundary changed. Reload the account before reopening it.');
            }
            $active = $latest->action === 'closed' ? $latest : $this->closedEvent($accountId, (int) $latest->prior_close_id);
            $prior = $active->prior_close_id ? $this->closedEvent($accountId, (int) $active->prior_close_id) : null;
            $this->event($actor, $accountId, 'reopened', $prior?->closed_through, null, $reason, $prior?->id);
        }, 3);
    }

    private function closedEvent(int $accountId, int $id): object
    {
        return DB::table('campaign_finance_period_events')->where('account_id', $accountId)
            ->where('action', 'closed')->where('id', $id)->firstOrFail();
    }

    private function event(User $actor, int $accountId, string $action, ?string $closedThrough, ?int $statementId, string $reason, ?int $priorCloseId): void
    {
        DB::table('campaign_finance_period_events')->insert([
            'account_id' => $accountId, 'action' => $action, 'closed_through' => $closedThrough,
            'statement_id' => $statementId, 'prior_close_id' => $priorCloseId,
            'actor_id' => $actor->id, 'reason' => $reason, 'created_at' => now(),
        ]);
    }

    private function reason(string $reason): string
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 500) {
            $this->invalid('Provide a reason between 3 and 500 characters.');
        }
        return $reason;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['finance' => $message]);
    }
}
