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
            $location = [];
            foreach (['region_id', 'state_id', 'senatorial_district_id', 'federal_constituency_id', 'lga_id', 'ward_id', 'polling_unit_id'] as $key) {
                $location[$key] = $owner->{$key};
            }
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

    public function request(User $actor, int $accountId, ?int $budgetId, int $amount, string $description): int
    {
        $this->authorize($actor, 'finance.disbursement.create');
        $this->amount($amount); $description = $this->text($description, 500);
        return DB::transaction(function () use ($actor, $accountId, $budgetId, $amount, $description) {
            $this->account($actor, $accountId, true);
            $this->budget($accountId, $budgetId);
            $id = DB::table('campaign_finance_requests')->insertGetId(['account_id' => $accountId, 'budget_id' => $budgetId,
                'amount_minor' => $amount, 'description' => $description, 'requested_by' => $actor->id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
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
            $this->account($actor, (int) $accountId, true);
            $request = DB::table('campaign_finance_requests')->where('id', $id)->lockForUpdate()->first();
            abort_if((int) $request->requested_by === (int) $actor->id, 403, 'You cannot approve or reject your own payment request.');
            if ($request->status !== 'pending') { $this->invalid('This request has already been decided.'); }
            DB::table('campaign_finance_requests')->where('id', $id)->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $actor->id, 'decided_at' => now(), 'updated_at' => now()]);
            $this->audit((int) $accountId, $actor, $approve ? 'request.approved' : 'request.rejected', ['request_id' => $id]);
        });
    }

    public function record(User $actor, int $accountId, string $kind, int $amount, string $description, string $key, ?int $requestId = null): int
    {
        $this->authorize($actor, $kind === 'income' ? 'finance.record' : 'finance.disbursement.execute');
        $this->amount($amount); $description = $this->text($description, 500);
        if (!in_array($kind, ['income', 'expenditure'], true) || !Str::isUuid($key)) { $this->invalid('Invalid transaction kind or submission key.'); }
        if (($kind === 'expenditure') !== ($requestId !== null)) { $this->invalid('Every expenditure must reference an approved payment request.'); }
        return DB::transaction(function () use ($actor, $accountId, $kind, $amount, $description, $key, $requestId) {
            $account = $this->account($actor, $accountId, true);
            $existing = DB::table('campaign_finance_entries')->where('idempotency_key', $key)->first();
            if ($existing) {
                if ((int) $existing->account_id !== $accountId || $existing->kind !== $kind || (int) $existing->amount_minor !== $amount
                    || (int) $existing->recorded_by !== (int) $actor->id || $existing->description !== $description || (int) $existing->request_id !== (int) $requestId) {
                    $this->invalid('This submission key was already used for another transaction.');
                }
                return (int) $existing->id;
            }
            $budgetId = null;
            if ($requestId !== null) {
                $request = DB::table('campaign_finance_requests')->where('id', $requestId)->where('account_id', $accountId)->lockForUpdate()->first();
                if (!$request || $request->status !== 'approved' || (int) $request->amount_minor !== $amount) { $this->invalid('An approved request for this exact account and amount is required.'); }
                abort_if((int) $request->decided_by === (int) $actor->id, 403, 'The approver cannot execute the same payment request.');
                $budgetId = $request->budget_id;
                $budget = $this->budget($accountId, $budgetId);
                if ($budget && $amount > (int) $budget->allocation_minor - (int) $budget->utilized_minor) { $this->invalid('The budget allocation would be exceeded.'); }
                if ($amount > $account->balance_minor) { $this->invalid('The account has insufficient recorded funds.'); }
                if ($budget) { DB::table('campaign_finance_budgets')->where('id', $budgetId)->increment('utilized_minor', $amount, ['updated_at' => now()]); }
                DB::table('campaign_finance_requests')->where('id', $requestId)->update(['status' => 'recorded', 'updated_at' => now()]);
            }
            $balance = $kind === 'income' ? $account->balance_minor + $amount : $account->balance_minor - $amount;
            if ($balance > 9000000000000000) { $this->invalid('Account balance limit exceeded.'); }
            $account->update(['balance_minor' => $balance]);
            $id = DB::table('campaign_finance_entries')->insertGetId(['account_id' => $accountId, 'budget_id' => $budgetId,
                'request_id' => $requestId, 'idempotency_key' => $key, 'kind' => $kind, 'amount_minor' => $amount,
                'balance_after_minor' => $balance, 'description' => $description, 'recorded_by' => $actor->id, 'created_at' => now()]);
            $this->audit($accountId, $actor, 'entry.recorded', ['entry_id' => $id, 'kind' => $kind, 'amount_minor' => $amount, 'balance_after_minor' => $balance]);
            return $id;
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
