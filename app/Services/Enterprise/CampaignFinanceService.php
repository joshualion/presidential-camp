<?php

namespace App\Services\Enterprise;

use App\Models\CampaignFinanceAccount;
use App\Models\User;
use App\Services\CampaignCapabilityService;
use App\Services\LocationScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Internal campaign records only. This service never initiates a banking payout. */
class CampaignFinanceService
{
    public function authorize(User $actor, string $permission): void
    {
        abort_unless(app(CampaignCapabilityService::class)->allows($actor, $permission), 403, 'Finance permission not assigned.');
        if (str_starts_with($permission, 'finance.')) {
            abort_unless(app(CampaignFinancePolicy::class)->permits($actor, $permission), 403, 'Finance governance does not delegate this action.');
        }
        abort_unless(app(EntitlementService::class)->active('campaign_finance'), 402, 'An active Campaign Finance licence is required.');
    }

    public function accounts(User $actor): Builder
    {
        // Accounts retain their jurisdiction snapshot even if their custodian moves.
        return app(LocationScopeService::class)->applyScope(CampaignFinanceAccount::query(), $actor, 'users', 'campaign_finance_accounts');
    }

    public function account(User $actor, int $id, bool $lock = false): CampaignFinanceAccount
    {
        $query = $this->accounts($actor)->whereKey($id);
        return ($lock ? $query->lockForUpdate() : $query)->firstOrFail();
    }

    public function createAccount(User $actor, string $name, int $ownerId): CampaignFinanceAccount
    {
        $this->authorize($actor, 'finance.accounts.manage');
        $name = $this->text($name, 120);
        $owner = app(LocationScopeService::class)->applyScope(User::query(), $actor, 'users')->whereKey($ownerId)->firstOrFail();
        return DB::transaction(function () use ($actor, $name, $owner) {
            $location = $this->jurisdiction($owner);
            $account = CampaignFinanceAccount::create($location + ['name' => $name, 'currency' => 'NGN', 'owner_id' => $owner->id, 'created_by' => $actor->id]);
            // Inconsistent historical owner geography must never create an inaccessible account.
            $this->account($actor, $account->id);
            $this->audit($account->id, $actor, 'account.created', ['name' => $name, 'owner_id' => $owner->id]);
            return $account;
        });
    }

    public function createBudget(User $actor, int $accountId, string $name, int $amount): int
    {
        $this->authorize($actor, 'finance.budget.manage');
        $this->amount($amount); $name = $this->text($name, 120);
        return DB::transaction(function () use ($actor, $accountId, $name, $amount) {
            $this->account($actor, $accountId, true);
            $id = DB::table('campaign_finance_budgets')->insertGetId(['account_id' => $accountId, 'name' => $name,
                'allocation_minor' => $amount, 'utilized_minor' => 0, 'created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
            $this->audit($accountId, $actor, 'budget.created', ['budget_id' => $id, 'allocation_minor' => $amount]);
            return $id;
        });
    }


    /**
     * A sub-office may ask headquarters for funding without access to the paying
     * account. This is only intake; it does not approve, reserve or move money.
     */
    public function submitFundingProposal(User $actor, int $amount, string $purpose, string $key, array $recipients = []): int
    {
        $this->authorize($actor, 'finance.disbursement.create');
        $this->amount($amount);
        $purpose = $this->text($purpose, 500);
        if (!Str::isUuid($key) || count($recipients) > 20) {
            $this->invalid('Use a valid submission key and no more than twenty recipients.');
        }
        $total = 0;
        $names = [];
        foreach ($recipients as &$payee) {
            if (!is_array($payee) || !isset($payee['name'], $payee['amount_minor'])
                || !is_int($payee['amount_minor']) || $payee['amount_minor'] <= 0) {
                $this->invalid('Each proposed recipient must have a name and positive amount.');
            }
            $name = $this->text($payee['name'], 160);
            $normalized = mb_strtolower($name);
            if (in_array($normalized, $names, true)) {
                $this->invalid('Combine duplicate recipient allocations.');
            }
            $names[] = $normalized;
            $total += $payee['amount_minor'];
            $payee = ['name' => $name, 'amount_minor' => $payee['amount_minor']];
        }
        unset($payee);
        if ($recipients && $total !== $amount) {
            $this->invalid('Recipient allocations must total the proposed funding amount.');
        }

        return DB::transaction(function () use ($actor, $amount, $purpose, $key, $recipients) {
            $existing = DB::table('campaign_finance_funding_proposals')->where('submission_key', $key)->first();
            if ($existing) {
                if ((int) $existing->requested_by !== (int) $actor->id
                    || (int) $existing->amount_minor !== $amount
                    || $existing->purpose !== $purpose
                    || json_decode($existing->recipients, true, 512, JSON_THROW_ON_ERROR) !== $recipients) {
                    $this->invalid('This submission key already belongs to a different funding proposal.');
                }
                return (int) $existing->id;
            }
            $scope = app(LocationScopeService::class);
            $id = DB::table('campaign_finance_funding_proposals')->insertGetId([
                'submission_key' => $key,
                'requested_by' => $actor->id,
                'requesting_level' => $scope->getScopeType($actor),
                'requesting_location_id' => $scope->getScopeId($actor),
                'purpose' => $purpose,
                'amount_minor' => $amount,
                'recipients' => json_encode($recipients, JSON_THROW_ON_ERROR),
                'status' => 'submitted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('campaign_finance_proposal_controls')->insert([
                'proposal_id' => $id, 'revision' => 1,
            ]);
            DB::table('campaign_finance_proposal_locations')->insert([
                'proposal_id' => $id,
                'region_id' => $actor->region_id,
                'state_id' => $actor->state_id,
                'senatorial_district_id' => $actor->senatorial_district_id,
                'federal_constituency_id' => $actor->federal_constituency_id,
                'lga_id' => $actor->lga_id,
                'ward_id' => $actor->ward_id,
                'polling_unit_id' => $actor->polling_unit_id,
            ]);
            DB::table('campaign_finance_proposal_events')->insert([
                'proposal_id' => $id, 'actor_id' => $actor->id, 'revision' => 1,
                'action' => 'submit', 'from_status' => null, 'to_status' => 'submitted',
                'details' => json_encode([
                    'purpose' => $purpose, 'amount_minor' => $amount, 'recipients' => $recipients,
                    'requesting_level' => $scope->getScopeType($actor),
                    'requesting_location_id' => $scope->getScopeId($actor),
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
            return $id;
        }, 3);
    }

    public function request(User $actor, int $accountId, ?int $budgetId, int $amount, string $description,
        string $purpose = 'expense', ?int $destinationId = null, ?string $recipient = null, array $recipients = []
    ): int {
        $this->authorize($actor, 'finance.disbursement.create');
        $this->amount($amount); $description = $this->text($description, 500);
        if (!in_array($purpose, ['expense', 'transfer', 'advance'], true)) {
            $this->invalid('Choose a valid funding purpose.');
        }
        if ($purpose === 'transfer' && (!$destinationId || $destinationId === $accountId || $budgetId !== null)) {
            $this->invalid('An internal transfer needs a different destination account and no expense budget.');
        }
        $recipient = $purpose === 'advance' ? $this->text($recipient ?? '', 160) : null;
        if (count($recipients) > 20 || ($recipients && $purpose !== 'expense')) {
            $this->invalid('Provide up to twenty named recipients for a direct expense request only.');
        }
        $planned = 0;
        $seen = [];
        foreach ($recipients as &$payee) {
            if (!is_array($payee) || !isset($payee['name'], $payee['amount_minor'])
                || !is_int($payee['amount_minor']) || $payee['amount_minor'] <= 0) {
                $this->invalid('Every recipient needs a name and a positive amount.');
            }
            $payee = ['name' => $this->text($payee['name'], 160), 'amount_minor' => $payee['amount_minor']];
            $normalized = mb_strtolower(trim($payee['name']));
            if (in_array($normalized, $seen, true)) {
                $this->invalid('Duplicate recipients must be combined into one allocation.');
            }
            $seen[] = $normalized;
            $planned += $payee['amount_minor'];
        }
        unset($payee);
        if ($recipients && $planned !== $amount) {
            $this->invalid('Named recipient amounts must equal the total request.');
        }
        return DB::transaction(function () use ($actor, $accountId, $budgetId, $amount, $description, $purpose, $destinationId, $recipient, $recipients) {
            if ($purpose === 'transfer') {
                $this->account($actor, $destinationId);
            }
            $this->account($actor, $accountId, true);
            $this->budget($accountId, $budgetId);
            $id = DB::table('campaign_finance_requests')->insertGetId(['account_id' => $accountId, 'budget_id' => $budgetId,
                'amount_minor' => $amount, 'description' => $description, 'requested_by' => $actor->id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            if ($purpose !== 'expense') {
                DB::table('campaign_finance_request_routes')->insert([
                    'request_id' => $id, 'purpose' => $purpose, 'recipient' => $recipient,
                    'destination_id' => $purpose === 'transfer' ? $destinationId : null,
                ]);
            }
            $locationService = app(LocationScopeService::class);
            $requestingLevel = $locationService->getScopeType($actor);
            $requestingId = $locationService->getScopeId($actor);
            $beneficiary = $purpose === 'transfer'
                ? $this->account($actor, (int) $destinationId)
                : $this->account($actor, $accountId);
            DB::table('campaign_finance_request_origins')->insert([
                'request_id' => $id,
                'requesting_level' => $requestingLevel,
                'requesting_location_id' => $requestingId,
                'beneficiary_level' => $purpose === 'transfer' ? 'account' : $requestingLevel,
                'beneficiary_location_id' => $purpose === 'transfer' ? $beneficiary->id : $requestingId,
                'payment_plan' => $recipients ? 'named_recipients' : ($purpose === 'transfer' ? 'branch_allocation' : 'unassigned'),
                'created_at' => now(),
            ]);
            foreach ($recipients as $position => $payee) {
                DB::table('campaign_finance_request_recipients')->insert([
                    'request_id' => $id,
                    'position' => $position + 1,
                    'recipient_name' => $payee['name'],
                    'amount_minor' => $payee['amount_minor'],
                    'created_at' => now(),
                ]);
            }
            app(CampaignFinancePolicy::class)->snapshot($id, $amount);
            $this->audit($accountId, $actor, 'request.created', ['request_id' => $id, 'amount_minor' => $amount]);
            return $id;
        });
    }

    public function decide(User $actor, int $id, bool $approve): void
    {
        $this->authorize($actor, 'finance.disbursement.approve');
        DB::transaction(function () use ($actor, $id, $approve) {
            // Always lock account before request/budget for a consistent lock order.
            $accountId = DB::table('campaign_finance_requests')->where('id', $id)->value('account_id');
            abort_unless($accountId, 404);
            $account = $this->account($actor, (int) $accountId, true);
            $request = DB::table('campaign_finance_requests')->where('id', $id)->lockForUpdate()->first();
            abort_if((int) $request->requested_by === (int) $actor->id, 403, 'You cannot approve or reject your own payment request.');
            app(CampaignFundingProposalService::class)->assertIndependentApprover($actor, $id);
            if ($request->status !== 'pending') { $this->invalid('This request has already been decided.'); }
            $routing = DB::table('campaign_finance_request_routes')->where('request_id', $id)->first();
            if ($routing?->destination_id) {
                $this->account($actor, (int) $routing->destination_id);
            }
            $workflow = DB::table('campaign_finance_workflows')->where('request_id', $id)->lockForUpdate()->first();
            if ($workflow) {
                $stages = json_decode($workflow->stages, true, 512, JSON_THROW_ON_ERROR);
                $stage = (int) $workflow->next_stage;
                abort_unless(isset($stages[$stage]) && app(CampaignFinancePolicy::class)->mayDecide($actor, $stages[$stage], $account), 403, 'You are not assigned to this approval stage.');
                abort_if(DB::table('campaign_finance_decisions')->where('request_id', $id)->where('actor_id', $actor->id)->exists(), 403, 'You already decided an earlier stage.');
                DB::table('campaign_finance_decisions')->insert([
                    'request_id' => $id, 'stage' => $stage, 'actor_id' => $actor->id,
                    'decision' => $approve ? 'approved' : 'rejected', 'created_at' => now(),
                ]);
                if ($approve && isset($stages[$stage + 1])) {
                    DB::table('campaign_finance_workflows')->where('request_id', $id)->update(['next_stage' => $stage + 1]);
                    $this->audit((int) $accountId, $actor, 'request.stage.approved', ['request_id' => $id, 'stage' => $stage + 1]);
                    return;
                }
            }
            if ($approve && ($workflow || $routing)) {
                $this->assertAvailable($account, $request->budget_id, (int) $request->amount_minor);
            }
            DB::table('campaign_finance_requests')->where('id', $id)->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $actor->id, 'decided_at' => now(), 'updated_at' => now()]);
            $this->audit((int) $accountId, $actor, $approve ? 'request.approved' : 'request.rejected', ['request_id' => $id]);
        }, 3);
    }

    public function record(User $actor, int $accountId, string $kind, int $amount, string $description, string $key, ?int $requestId = null): int
    {
        $this->authorize($actor, $kind === 'income' ? 'finance.record' : 'finance.disbursement.execute');
        $this->amount($amount); $description = $this->text($description, 500);
        if (!in_array($kind, ['income', 'expenditure'], true) || !Str::isUuid($key)) { $this->invalid('Invalid transaction kind or submission key.'); }
        if (($kind === 'expenditure') !== ($requestId !== null)) { $this->invalid('Every expenditure must reference an approved payment request.'); }
        return DB::transaction(function () use ($actor, $accountId, $kind, $amount, $description, $key, $requestId) {
            $account = $this->account($actor, $accountId, true);
            $journal = app(CampaignFinanceJournal::class);
            $journal->reconcile($account);
            // A scoped account lookup can establish a snapshot before its row lock is acquired.
            $existing = DB::table('campaign_finance_entries')->where('idempotency_key', $key)->lockForUpdate()->first();
            if ($existing) {
                if ((int) $existing->account_id !== $accountId || $existing->kind !== $kind || (int) $existing->amount_minor !== $amount
                    || (int) $existing->recorded_by !== (int) $actor->id || $existing->description !== $description || (int) $existing->request_id !== (int) $requestId) {
                    $this->invalid('This submission key was already used for another transaction.');
                }
                return (int) $existing->id;
            }
            $budgetId = null;
            if ($requestId !== null) {
                // Itemized beneficiary requests require recipient-level payment
                // execution and reconciliation. Do not falsely mark a lump-sum
                // ledger entry as a completed multi-person payment.
                if (DB::table('campaign_finance_request_recipients')->where('request_id', $requestId)->exists()) {
                    $this->invalid('Itemized recipient requests require verified recipient disbursement processing.');
                }
                if (DB::table('campaign_finance_request_routes')->where('request_id', $requestId)->exists()) {
                    $this->invalid('Use the approved internal transfer or advance workflow for this request.');
                }
                $request = DB::table('campaign_finance_requests')->where('id', $requestId)->where('account_id', $accountId)->lockForUpdate()->first();
                if (!$request || $request->status !== 'approved' || (int) $request->amount_minor !== $amount) { $this->invalid('An approved request for this exact account and amount is required.'); }
                abort_if((int) $request->decided_by === (int) $actor->id
                    || DB::table('campaign_finance_decisions')->where('request_id', $requestId)->where('actor_id', $actor->id)->exists(),
                    403, 'An approver cannot execute the same payment request.');
                $budgetId = $request->budget_id;
                $budget = $this->budget($accountId, $budgetId);
                if ($budget && $amount > (int) $budget->allocation_minor - (int) $budget->utilized_minor) { $this->invalid('The budget allocation would be exceeded.'); }
                $this->assertAvailable($account, $budgetId, $amount, $requestId);
                if ($budget) { DB::table('campaign_finance_budgets')->where('id', $budgetId)->increment('utilized_minor', $amount, ['updated_at' => now()]); }
                DB::table('campaign_finance_requests')->where('id', $requestId)->update(['status' => 'recorded', 'updated_at' => now()]);
            }
            return $this->post($actor, $account, $kind, $amount, $description, $key, $budgetId, $requestId);
        }, 3);
    }

    public function recordManualExpense(User $actor, int $accountId, int $amount, string $description, string $key, array $receipt, ?int $budgetId = null): int
    {
        $this->authorize($actor, 'finance.expenses.record');
        $this->amount($amount);
        $description = $this->text($description, 500);
        $receipt = \Illuminate\Support\Facades\Validator::make($receipt, [
            'payee' => 'required|string|max:160',
            'paid_on' => 'required|date_format:Y-m-d|before_or_equal:today',
            'reference' => 'required|string|max:120',
            'evidence_reference' => 'required|string|max:300',
        ])->validate();
        $referenceKey = hash('sha256', mb_strtolower(trim($receipt['reference'])));
        if (!Str::isUuid($key)) {
            $this->invalid('A valid submission key is required.');
        }

        return DB::transaction(function () use ($actor, $accountId, $amount, $description, $key, $receipt, $referenceKey, $budgetId) {
            $account = $this->account($actor, $accountId, true);
            $existing = DB::table('campaign_finance_entries')->where('idempotency_key', $key)->lockForUpdate()->first();
            if ($existing) {
                $saved = DB::table('campaign_finance_manual_expenses')->where('entry_id', $existing->id)->lockForUpdate()->first();
                if (!$saved || (int) $existing->account_id !== $accountId || (int) $existing->recorded_by !== (int) $actor->id
                    || (int) $existing->amount_minor !== $amount || $existing->description !== $description
                    || (int) $existing->budget_id !== (int) $budgetId || array_intersect_key((array) $saved, $receipt) != $receipt) {
                    $this->invalid('This submission key was already used for another transaction.');
                }
                return (int) $existing->id;
            }
            if (DB::table('campaign_finance_manual_expenses')->where('account_id', $accountId)->where('reference_key', $referenceKey)->lockForUpdate()->first(['entry_id'])) {
                $this->invalid('That payment reference is already recorded on this account.');
            }

            app(CampaignFinanceJournal::class)->reconcile($account);
            app(CampaignFinancePeriodService::class)->assertOpen($accountId, $receipt['paid_on']);
            $this->assertAvailable($account, $budgetId, $amount);
            if ($budgetId !== null) {
                DB::table('campaign_finance_budgets')->where('id', $budgetId)
                    ->increment('utilized_minor', $amount, ['updated_at' => now()]);
            }
            $id = $this->post($actor, $account, 'expenditure', $amount, $description, $key, $budgetId);
            DB::table('campaign_finance_manual_expenses')->insert($receipt + [
                'entry_id' => $id, 'account_id' => $accountId, 'reference_key' => $referenceKey,
            ]);
            $this->audit($accountId, $actor, 'expense.recorded_manually', ['entry_id' => $id, 'amount_minor' => $amount]);
            return $id;
        }, 3);
    }

    public function postApprovedMovement(User $actor, int $requestId, string $key, string $reference): int
    {
        $this->authorize($actor, 'finance.movements.post');
        $reference = $this->text($reference, 200);
        if (!Str::isUuid($key)) {
            $this->invalid('A valid submission key is required.');
        }

        return DB::transaction(function () use ($actor, $requestId, $key, $reference) {
            $routing = DB::table('campaign_finance_request_routes')->where('request_id', $requestId)->firstOrFail();
            $request = DB::table('campaign_finance_requests')->where('id', $requestId)->firstOrFail();
            $sourceId = (int) $request->account_id;
            $destinationId = (int) $routing->destination_id;
            $ids = $destinationId ? [$sourceId, $destinationId] : [$sourceId];
            sort($ids);
            $accounts = [];
            foreach ($ids as $id) {
                $accounts[$id] = $this->account($actor, $id, true);
            }
            $source = $accounts[$sourceId];
            $request = DB::table('campaign_finance_requests')->where('id', $requestId)->lockForUpdate()->firstOrFail();
            $existing = DB::table('campaign_finance_entries')->where('request_id', $requestId)->lockForUpdate()->first();
            if ($existing) {
                if ($existing->idempotency_key !== $key || (int) $existing->recorded_by !== (int) $actor->id || $existing->description !== $reference) {
                    $this->invalid('This movement has already been recorded.');
                }
                return (int) $existing->id;
            }
            abort_if((int) $request->decided_by === (int) $actor->id
                || DB::table('campaign_finance_decisions')->where('request_id', $requestId)->where('actor_id', $actor->id)->exists(),
                403, 'An approver cannot post the same request.');
            if ($request->status !== 'approved') {
                $this->invalid('An approved funding request is required.');
            }
            $amount = (int) $request->amount_minor;
            $this->assertAvailable($source, $request->budget_id, $amount, $requestId);
            foreach ($accounts as $account) {
                app(CampaignFinanceJournal::class)->reconcile($account);
            }
            $entry = $this->post($actor, $source, $destinationId ? 'transfer_out' : 'advance_out',
                $amount, $reference, $key, $request->budget_id, $requestId);
            if ($destinationId) {
                $this->post($actor, $accounts[$destinationId], 'transfer_in', $amount,
                    'Internal transfer #'.$requestId.' (entry #'.$entry.')', (string) Str::uuid());
            } elseif ($request->budget_id !== null) {
                DB::table('campaign_finance_budgets')->where('id', $request->budget_id)
                    ->increment('utilized_minor', $amount, ['updated_at' => now()]);
            }
            DB::table('campaign_finance_requests')->where('id', $requestId)->update(['status' => 'recorded', 'updated_at' => now()]);
            $this->audit($sourceId, $actor, 'movement.recorded', ['request_id' => $requestId, 'destination_id' => $destinationId ?: null]);
            return $entry;
        }, 3);
    }

    public function settleAdvance(User $actor, int $requestId, int $spent, string $evidence, string $key): void
    {
        $this->authorize($actor, 'finance.advances.settle');
        $evidence = $this->text($evidence, 300);
        if (!Str::isUuid($key) || $spent < 0) {
            $this->invalid('Enter a nonnegative expense and a valid submission key.');
        }

        DB::transaction(function () use ($actor, $requestId, $spent, $evidence, $key) {
            $accountId = DB::table('campaign_finance_requests')->where('id', $requestId)->value('account_id');
            abort_unless($accountId, 404);
            $account = $this->account($actor, (int) $accountId, true);
            $routing = DB::table('campaign_finance_request_routes')->where('request_id', $requestId)
                ->where('purpose', 'advance')->lockForUpdate()->firstOrFail();
            if ($routing->settled_at !== null) {
                if ($routing->settlement_key !== $key || (int) $routing->settled_by !== (int) $actor->id
                    || (int) $routing->spent_minor !== $spent || $routing->settlement_evidence !== $evidence) {
                    $this->invalid('This advance has already been settled.');
                }
                return;
            }
            $advance = DB::table('campaign_finance_entries')->where('request_id', $requestId)->where('kind', 'advance_out')->lockForUpdate()->first();
            if (!$advance || $spent > (int) $advance->amount_minor) {
                $this->invalid('The advance must be issued and the documented expense must fit within its amount.');
            }
            abort_if((int) $advance->recorded_by === (int) $actor->id, 403, 'Another officer must settle the advance.');
            $returned = (int) $advance->amount_minor - $spent;
            if ($spent > 0) {
                $this->post($actor, $account, 'advance_expense', $spent, 'Advance #'.$requestId.' expense. '.$evidence,
                    (string) Str::uuid(), $advance->budget_id);
            }
            if ($returned > 0) {
                $this->post($actor, $account, 'advance_return', $returned, 'Advance #'.$requestId.' funds returned', (string) Str::uuid());
                if ($advance->budget_id !== null) {
                    DB::table('campaign_finance_budgets')->where('id', $advance->budget_id)
                        ->decrement('utilized_minor', $returned, ['updated_at' => now()]);
                }
            }
            DB::table('campaign_finance_request_routes')->where('request_id', $requestId)->update([
                'settlement_key' => $key, 'settled_by' => $actor->id, 'spent_minor' => $spent,
                'settlement_evidence' => $evidence, 'settled_at' => now(),
            ]);
            $this->audit($account->id, $actor, 'advance.settled', [
                'request_id' => $requestId, 'spent_minor' => $spent, 'returned_minor' => $returned,
            ]);
        }, 3);
    }

    private function post(
        User $actor, CampaignFinanceAccount $account, string $kind, int $amount, string $description, string $key,
        ?int $budgetId = null, ?int $requestId = null
    ): int {
        app(CampaignFinancePeriodService::class)->assertOpen((int) $account->id, today()->toDateString());
        $change = match ($kind) {
            'income', 'transfer_in', 'advance_return' => $amount,
            'advance_expense' => 0,
            'expenditure', 'transfer_out', 'advance_out' => -$amount,
        };
        $balance = $account->balance_minor + $change;
        if ($balance < 0 || $balance > 9000000000000000) {
            $this->invalid('The recorded balance would be outside the supported range.');
        }
        $account->update(['balance_minor' => $balance]);
        $id = DB::table('campaign_finance_entries')->insertGetId([
            'account_id' => $account->id, 'budget_id' => $budgetId, 'request_id' => $requestId,
            'idempotency_key' => $key, 'kind' => $kind, 'amount_minor' => $amount, 'balance_after_minor' => $balance,
            'description' => $description, 'recorded_by' => $actor->id, 'created_at' => now(),
        ]);
        app(CampaignFinanceJournal::class)->append($id, $kind, $amount);
        $this->audit($account->id, $actor, 'entry.recorded', [
            'entry_id' => $id, 'kind' => $kind, 'amount_minor' => $amount, 'balance_after_minor' => $balance,
        ]);
        return $id;
    }

    public function reverse(User $actor, int $accountId, int $entryId, string $reason, string $key): int
    {
        $this->authorize($actor, 'finance.entries.reverse');
        $reason = $this->text($reason, 500);
        if (!Str::isUuid($key)) {
            $this->invalid('A valid submission key is required.');
        }

        return DB::transaction(function () use ($actor, $accountId, $entryId, $reason, $key) {
            $account = $this->account($actor, $accountId, true);
            $journal = app(CampaignFinanceJournal::class);
            $journal->reconcile($account);
            $original = DB::table('campaign_finance_entries')
                ->where('account_id', $accountId)->where('id', $entryId)
                ->whereIn('kind', ['income', 'expenditure'])->firstOrFail();
            abort_if((int) $original->recorded_by === (int) $actor->id, 403, 'Another officer must reverse an entry you posted.');

            $reversalId = DB::table('campaign_finance_reversals')->where('entry_id', $entryId)->lockForUpdate()->value('reversal_id');
            $existing = DB::table('campaign_finance_entries')->where('idempotency_key', $key)->lockForUpdate()->first();
            if ($existing) {
                if ((int) $existing->id !== (int) $reversalId || (int) $existing->recorded_by !== (int) $actor->id || $existing->description !== $reason) {
                    $this->invalid('This submission key was already used for another transaction.');
                }
                return (int) $existing->id;
            }
            if ($reversalId !== null) {
                $this->invalid('This entry already has a reversal.');
            }

            $amount = (int) $original->amount_minor;
            $balance = (int) $account->balance_minor;
            if ($original->kind === 'income') {
                $this->assertAvailable($account, null, $amount);
                $balance -= $amount;
            } else {
                $balance += $amount;
                if ($balance > 9000000000000000) {
                    $this->invalid('Account balance limit exceeded.');
                }
                $budget = $this->budget($accountId, $original->budget_id);
                if ($budget) {
                    if ((int) $budget->utilized_minor < $amount) {
                        $this->invalid('Historical budget utilization must be reconciled before reversing this expenditure.');
                    }
                    DB::table('campaign_finance_budgets')->where('id', $budget->id)
                        ->decrement('utilized_minor', $amount, ['updated_at' => now()]);
                }
            }

            app(CampaignFinancePeriodService::class)->assertOpen($accountId, today()->toDateString());
            $account->update(['balance_minor' => $balance]);
            $reversalId = DB::table('campaign_finance_entries')->insertGetId([
                'account_id' => $accountId, 'budget_id' => $original->budget_id,
                'idempotency_key' => $key, 'kind' => 'reversal', 'amount_minor' => $amount,
                'balance_after_minor' => $balance, 'description' => $reason,
                'recorded_by' => $actor->id, 'created_at' => now(),
            ]);
            DB::table('campaign_finance_reversals')->insert(['entry_id' => $entryId, 'reversal_id' => $reversalId]);
            $journal->reverse($entryId, $reversalId);
            $this->audit($accountId, $actor, 'entry.reversed', [
                'entry_id' => $entryId, 'reversal_id' => $reversalId, 'amount_minor' => $amount,
                'balance_after_minor' => $balance,
            ]);

            return $reversalId;
        }, 3);
    }

    public function cancel(User $actor, int $id): void
    {
        $this->authorize($actor, 'finance.disbursement.create');
        DB::transaction(function () use ($actor, $id) {
            $accountId = DB::table('campaign_finance_requests')->where('id', $id)->value('account_id');
            abort_unless($accountId, 404);
            $this->account($actor, (int) $accountId, true);
            $request = DB::table('campaign_finance_requests')->where('id', $id)->lockForUpdate()->first();
            abort_unless((int) $request->requested_by === (int) $actor->id, 403);
            if (!in_array($request->status, ['pending', 'approved'], true)) {
                $this->invalid('Only pending or approved requests can be cancelled.');
            }
            DB::table('campaign_finance_requests')->where('id', $id)->update(['status' => 'cancelled', 'updated_at' => now()]);
            $this->audit((int) $accountId, $actor, 'request.cancelled', ['request_id' => $id]);
        }, 3);
    }

    public function reservations()
    {
        return DB::table('campaign_finance_requests')
            ->where(function ($query) {
                $query->whereIn('campaign_finance_requests.id', DB::table('campaign_finance_workflows')->select('request_id'))
                    ->orWhereIn('campaign_finance_requests.id', DB::table('campaign_finance_request_routes')->select('request_id'));
            })
            ->where('campaign_finance_requests.status', 'approved');
    }

    private function assertAvailable(CampaignFinanceAccount $account, ?int $budgetId, int $amount, ?int $requestId = null): void
    {
        $reservations = $this->reservations()->where('account_id', $account->id);
        if ($requestId !== null) {
            $reservations->where('campaign_finance_requests.id', '!=', $requestId);
        }
        $reservations = $reservations->lockForUpdate()->get(['amount_minor', 'budget_id']);
        $reserved = (int) $reservations->sum('amount_minor');
        if ($amount > $account->balance_minor - $reserved) {
            $this->invalid('The recorded funds available after reservations are insufficient.');
        }
        $budget = $this->budget($account->id, $budgetId);
        if ($budget && $amount > (int) $budget->allocation_minor - (int) $budget->utilized_minor
            - (int) $reservations->where('budget_id', $budgetId)->sum('amount_minor')) {
            $this->invalid('The budget allocation would be exceeded after reservations.');
        }
    }

    public function reconcileHistory(User $actor, int $accountId): void
    {
        $this->authorize($actor, 'finance.reconcile');
        DB::transaction(function () use ($actor, $accountId) {
            $account = $this->account($actor, $accountId, true);
            app(CampaignFinanceJournal::class)->reconcile($account);
        }, 3);
    }

    public function attachDocument(User $actor, int $accountId, ?int $requestId, \Illuminate\Http\UploadedFile $file): int
    {
        $this->authorize($actor, 'finance.record');
        \Illuminate\Support\Facades\Validator::make(['document' => $file], ['document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240'])->validate();
        $this->account($actor, $accountId);
        $path = $file->store('campaign-finance/'.$accountId, 'local');
        if (!$path) { $this->invalid('The supporting document could not be stored.'); }
        try {
            return DB::transaction(function () use ($actor, $accountId, $requestId, $file, $path) {
                $this->account($actor, $accountId, true);
                if ($requestId !== null && !DB::table('campaign_finance_requests')->where('account_id', $accountId)->where('id', $requestId)->exists()) {
                    $this->invalid('The payment request does not belong to this account.');
                }
                $id = DB::table('campaign_finance_documents')->insertGetId(['account_id' => $accountId, 'request_id' => $requestId, 'path' => $path,
                    'filename' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 180),
                    'mime' => $file->getMimeType(), 'sha256' => hash_file('sha256', $file->getRealPath()), 'uploaded_by' => $actor->id, 'created_at' => now()]);
                $this->audit($accountId, $actor, 'document.attached', ['document_id' => $id, 'request_id' => $requestId]);
                return $id;
            });
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    /** Derive missing parents and reject contradictory legacy member locations. */
    private function jurisdiction(User $owner): array
    {
        $location = [];
        foreach (['region_id', 'state_id', 'senatorial_district_id', 'federal_constituency_id', 'lga_id', 'ward_id', 'polling_unit_id'] as $key) {
            $location[$key] = $owner->{$key} ? (int) $owner->{$key} : null;
        }
        $set = function (string $key, mixed $value) use (&$location): void {
            if (!$value) { return; }
            if ($location[$key] !== null && $location[$key] !== (int) $value) {
                $this->invalid('The custodian has inconsistent geographic records. Correct the member location before creating a financial account.');
            }
            $location[$key] = (int) $value;
        };
        if ($location['polling_unit_id']) {
            $pu = \App\Models\PollingUnit::findOrFail($location['polling_unit_id']);
            $set('ward_id', $pu->ward_id); $set('senatorial_district_id', $pu->senatorial_district_id); $set('federal_constituency_id', $pu->federal_constituency_id);
        }
        if ($location['ward_id']) {
            $ward = \App\Models\Ward::findOrFail($location['ward_id']);
            $set('lga_id', $ward->lga_id);
        }
        if ($location['lga_id']) {
            $lga = \App\Models\LocalGovernmentArea::findOrFail($location['lga_id']);
            $set('state_id', $lga->state_id); $set('senatorial_district_id', $lga->senatorial_district_id); $set('federal_constituency_id', $lga->federal_constituency_id);
        }
        if ($location['federal_constituency_id']) {
            $federal = \App\Models\FederalConstituency::findOrFail($location['federal_constituency_id']);
            $set('state_id', $federal->state_id); $set('senatorial_district_id', $federal->senatorial_district_id);
        }
        if ($location['senatorial_district_id']) {
            $senate = \App\Models\SenatorialDistrict::findOrFail($location['senatorial_district_id']);
            $set('state_id', $senate->state_id);
        }
        if ($location['state_id']) {
            $state = \App\Models\State::findOrFail($location['state_id']);
            $set('region_id', $state->region_id);
        }
        if ($location['region_id']) { \App\Models\Region::findOrFail($location['region_id']); }
        return $location;
    }

    private function budget(int $accountId, ?int $id): ?object
    {
        if ($id === null) { return null; }
        $budget = DB::table('campaign_finance_budgets')->where('account_id', $accountId)->where('id', $id)->lockForUpdate()->first();
        if (!$budget) { $this->invalid('The budget does not belong to this account.'); }
        return $budget;
    }

    private function audit(int $accountId, User $actor, string $action, array $details): void
    {
        DB::table('campaign_finance_audits')->insert(['account_id' => $accountId, 'actor_id' => $actor->id, 'action' => $action,
            'details' => json_encode($details, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }

    private function amount(int $amount): void
    {
        if ($amount < 1 || $amount > 100000000000000) { $this->invalid('Amount must be positive and within the supported limit.'); }
    }

    private function text(string $value, int $max): string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > $max) { $this->invalid('A valid name or description is required.'); }
        return $value;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['finance' => $message]);
    }
}
