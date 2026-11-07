<?php

namespace App\Services\Enterprise;

use App\Models\User;
use App\Services\LocationScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CampaignFundingProposalService
{
    public function __construct(private CampaignFinanceService $finance) {}

    /** Financial reviewers see the original jurisdiction, never a user's moved profile. */
    public function reviewerQueue(User $actor)
    {
        $this->finance->authorize($actor, 'finance.disbursement.approve');

        // Keep the snapshot table as the unaliased SQL "from" table. The
        // licence-scope query service validates this table's real geography
        // columns and would fail closed on a joined proposal alias instead.
        $query = DB::table('campaign_finance_proposal_locations')
            ->join('campaign_finance_funding_proposals as proposals',
                'proposals.id', '=', 'campaign_finance_proposal_locations.proposal_id')
            ->join('campaign_finance_proposal_controls as control',
                'control.proposal_id', '=', 'proposals.id')
            ->select('proposals.*', 'control.revision', 'control.assigned_by');

        return app(LocationScopeService::class)->applyScope(
            $query, $actor, 'users', 'campaign_finance_proposal_locations'
        );
    }

    private function scoped(User $actor, int $id, bool $lock = false): object
    {
        $query = $this->reviewerQueue($actor)->where('proposals.id', $id);
        return ($lock ? $query->lockForUpdate() : $query)->firstOrFail();
    }

    private function revision(int $id): object
    {
        return DB::table('campaign_finance_proposal_controls')
            ->where('proposal_id', $id)->lockForUpdate()->firstOrFail();
    }

    private function checkRevision(int $expected, object $control): void
    {
        if ($expected !== (int) $control->revision) {
            $this->invalid('This proposal changed. Refresh it before taking another action.');
        }
    }

    private function event(int $id, User $actor, int $revision, string $action, ?string $from, string $to, array $details): void
    {
        DB::table('campaign_finance_proposal_events')->insert([
            'proposal_id' => $id, 'actor_id' => $actor->id, 'revision' => $revision,
            'action' => $action, 'from_status' => $from, 'to_status' => $to,
            'details' => json_encode($details, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }

    /**
     * Return and reject are terminal for this revision, not money movements.
     * A returned proposal may be revised by its original requesting officer.
     */
    public function review(User $actor, int $id, int $revision, string $decision, string $reason): void
    {
        if (!in_array($decision, ['return', 'reject'], true)) {
            $this->invalid('Choose return or reject.');
        }
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 500) {
            $this->invalid('Provide a review reason (up to 500 characters).');
        }
        DB::transaction(function () use ($actor, $id, $revision, $decision, $reason) {
            $proposal = $this->scoped($actor, $id, true);
            $control = $this->revision($id);
            $this->checkRevision($revision, $control);
            abort_if((int) $proposal->requested_by === (int) $actor->id, 403,
                'You cannot review your own funding proposal.');
            if ($proposal->status !== 'submitted') {
                $this->invalid('Only submitted proposals can be reviewed.');
            }
            $next = $decision === 'return' ? 'returned' : 'rejected';
            DB::table('campaign_finance_funding_proposals')->where('id', $id)->update([
                'status' => $next, 'updated_at' => now(),
            ]);
            DB::table('campaign_finance_proposal_controls')->where('proposal_id', $id)
                ->update(['revision' => $revision + 1]);
            $this->event($id, $actor, $revision + 1, $decision, 'submitted', $next,
                ['reason' => $reason]);
        }, 3);
    }

    private function recipients(array $recipients, int $amount): array
    {
        if (count($recipients) > 20) {
            $this->invalid('A proposal can contain no more than twenty recipients.');
        }
        $total = 0;
        $seen = [];
        $normalized = [];
        foreach ($recipients as $payee) {
            $name = trim((string) ($payee['name'] ?? ''));
            $value = $payee['amount_minor'] ?? null;
            if ($name === '' || mb_strlen($name) > 160 || !is_int($value) || $value < 1) {
                $this->invalid('Each recipient needs a name and a positive amount.');
            }
            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                $this->invalid('Combine duplicate recipient allocations.');
            }
            $seen[$key] = true;
            $total += $value;
            $normalized[] = ['name' => $name, 'amount_minor' => $value];
        }
        if ($normalized && $total !== $amount) {
            $this->invalid('Recipient amounts must equal the proposed total.');
        }
        return $normalized;
    }

    public function resubmit(User $actor, int $id, int $revision, int $amount, string $purpose, array $recipients): void
    {
        $this->finance->authorize($actor, 'finance.disbursement.create');
        if ($amount < 1 || $amount > 100000000000000) {
            $this->invalid('Enter a valid positive amount.');
        }
        $purpose = trim($purpose);
        if ($purpose === '' || mb_strlen($purpose) > 500) {
            $this->invalid('Provide a valid purpose.');
        }
        $recipients = $this->recipients($recipients, $amount);
        DB::transaction(function () use ($actor, $id, $revision, $amount, $purpose, $recipients) {
            $proposal = DB::table('campaign_finance_funding_proposals')
                ->where('id', $id)->where('requested_by', $actor->id)->lockForUpdate()->firstOrFail();
            $control = $this->revision($id);
            $this->checkRevision($revision, $control);
            if ($proposal->status !== 'returned' || $proposal->assigned_request_id !== null) {
                $this->invalid('Only returned, unassigned proposals can be resubmitted.');
            }
            DB::table('campaign_finance_funding_proposals')->where('id', $id)->update([
                'amount_minor' => $amount, 'purpose' => $purpose,
                'recipients' => json_encode($recipients, JSON_THROW_ON_ERROR),
                'status' => 'submitted', 'updated_at' => now(),
            ]);
            DB::table('campaign_finance_proposal_controls')->where('proposal_id', $id)
                ->update(['revision' => $revision + 1]);
            $this->event($id, $actor, $revision + 1, 'resubmit', 'returned', 'submitted', [
                'previous_amount_minor' => (int) $proposal->amount_minor,
                'amount_minor' => $amount,
                'previous_recipients' => json_decode($proposal->recipients, true, 512, JSON_THROW_ON_ERROR),
                'recipients' => $recipients,
                'previous_purpose' => $proposal->purpose, 'purpose' => $purpose,
            ]);
        }, 3);
    }

    /**
     * Funding-account assignment is NOT approval. The existing approval-chain
     * snapshot and final-approval reservation rules remain authoritative.
     */
    public function assign(
        User $actor, int $id, int $revision, int $accountId,
        ?int $budgetId = null, ?int $destinationId = null
    ): int {
        $this->finance->authorize($actor, 'finance.disbursement.approve');
        $this->finance->authorize($actor, 'finance.disbursement.create');

        return DB::transaction(function () use ($actor, $id, $revision, $accountId, $budgetId, $destinationId) {
            // Lock financial account first, consistent with the normal request approval path.
            $source = $this->finance->account($actor, $accountId, true);
            $destination = $destinationId === null ? null : $this->finance->account($actor, $destinationId);
            $proposal = $this->scoped($actor, $id, true);
            $control = $this->revision($id);
            $this->checkRevision($revision, $control);
            abort_if((int) $proposal->requested_by === (int) $actor->id, 403,
                'You cannot allocate your own funding proposal.');
            if ($proposal->status !== 'submitted' || $proposal->assigned_request_id !== null) {
                $this->invalid('Only unassigned submitted proposals can be allocated.');
            }
            if ($destination && (int) $destination->id === (int) $source->id) {
                $this->invalid('A branch transfer needs a different destination account.');
            }
            if ($budgetId !== null && !DB::table('campaign_finance_budgets')
                ->where('id', $budgetId)->where('account_id', $source->id)->exists()) {
                $this->invalid('The chosen budget must belong to the paying account.');
            }
            $recipients = $this->recipients(
                json_decode($proposal->recipients, true, 512, JSON_THROW_ON_ERROR),
                (int) $proposal->amount_minor
            );
            if ($destination && ($recipients || $budgetId !== null)) {
                $this->invalid('An internal branch allocation cannot include named payees or an expenditure budget.');
            }
            $requestId = $this->finance->request(
                $actor, $accountId, $budgetId, (int) $proposal->amount_minor,
                $proposal->purpose, $destination ? 'transfer' : 'expense',
                $destination?->id, null, $recipients
            );
            // An explicit chain is compulsory for branch-to-headquarters funding.
            // Missing approval policy fails closed and rolls back the new request.
            $chain = DB::table('campaign_finance_workflows')->where('request_id', $requestId)->first();
            if (!$chain || !json_decode($chain->stages, true, 512, JSON_THROW_ON_ERROR)) {
                $this->invalid('Configure a multi-officer Finance approval chain before assigning headquarters funds.');
            }
            // The original branch officer, not the allocating reviewer, remains the
            // requester; preserve original geography independent of account geography.
            DB::table('campaign_finance_requests')->where('id', $requestId)
                ->update(['requested_by' => $proposal->requested_by]);
            DB::table('campaign_finance_request_origins')->where('request_id', $requestId)->update([
                'requesting_level' => $proposal->requesting_level,
                'requesting_location_id' => $proposal->requesting_location_id,
                'beneficiary_level' => $destination ? 'account' : $proposal->requesting_level,
                'beneficiary_location_id' => $destination ? $destination->id : $proposal->requesting_location_id,
            ]);
            DB::table('campaign_finance_funding_proposals')->where('id', $id)->update([
                'status' => 'assigned', 'assigned_request_id' => $requestId, 'updated_at' => now(),
            ]);
            DB::table('campaign_finance_proposal_controls')->where('proposal_id', $id)->update([
                'revision' => $revision + 1, 'assigned_by' => $actor->id, 'assigned_at' => now(),
            ]);
            $this->event($id, $actor, $revision + 1, 'assign', 'submitted', 'assigned', [
                'request_id' => $requestId, 'account_id' => $accountId,
                'budget_id' => $budgetId, 'destination_id' => $destinationId,
                'recipients' => $recipients, 'amount_minor' => (int) $proposal->amount_minor,
            ]);
            return $requestId;
        }, 3);
    }

    public function assertIndependentApprover(User $actor, int $requestId): void
    {
        $allocation = DB::table('campaign_finance_funding_proposals as proposals')
            ->join('campaign_finance_proposal_controls as control', 'control.proposal_id', '=', 'proposals.id')
            ->where('proposals.assigned_request_id', $requestId)->first(['control.assigned_by']);
        abort_if($allocation && (int) $allocation->assigned_by === (int) $actor->id, 403,
            'An officer who assigns headquarters funding cannot approve that same request.');
    }

    public function history(User $actor, int $id): array
    {
        $proposal = DB::table('campaign_finance_funding_proposals')
            ->where('id', $id)->firstOrFail();
        if ((int) $proposal->requested_by === (int) $actor->id) {
            $this->finance->authorize($actor, 'finance.disbursement.create');
        } else {
            $this->scoped($actor, $id);
        }
        $events = DB::table('campaign_finance_proposal_events')
            ->where('proposal_id', $id)->orderBy('revision')->get()->map(function ($event) {
                $details = json_decode($event->details, true, 512, JSON_THROW_ON_ERROR);
                unset($details['account_id'], $details['budget_id'], $details['destination_id']);
                $event->details = json_encode($details, JSON_THROW_ON_ERROR);
                return $event;
            });
        $request = $proposal->assigned_request_id
            ? DB::table('campaign_finance_requests')->where('id', $proposal->assigned_request_id)
                ->first(['id', 'status', 'amount_minor'])
            : null;

        return [$proposal, $events, $request];
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['finance' => $message]);
    }
}
