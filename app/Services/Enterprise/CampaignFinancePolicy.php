<?php

namespace App\Services\Enterprise;

use App\Models\CampaignFinanceAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CampaignFinancePolicy
{
    public const SECTIONS = [
        'dashboard' => 'Dashboard',
        'income' => 'Income & Donations',
        'expenditure' => 'Expenditure',
        'requests' => 'Funding Requests',
        'disbursements' => 'Disbursements',
        'budgets' => 'Budgets & Allocations',
        'accounts' => 'Financial Accounts',
        'approvals' => 'Approvals',
        'reports' => 'Reports & Audit',
        'settings' => 'Finance Settings',
    ];

    public const OFFICES = [
        'treasurer' => 'Campaign Treasurer',
        'secretary' => 'Financial Secretary',
        'auditor' => 'Finance Auditor',
        'approver' => 'Finance Approver',
        'disbursement_officer' => 'Authorized Disbursement Officer',
    ];

    public const BOUNDARIES = [
        'region_id' => 'Region',
        'state_id' => 'State',
        'senatorial_district_id' => 'Senatorial District',
        'federal_constituency_id' => 'Federal Constituency',
        'lga_id' => 'Local Government Area',
        'ward_id' => 'Ward',
        'polling_unit_id' => 'Polling Unit',
    ];

    public function current(): ?object
    {
        if (!Schema::hasTable('campaign_finance_policies')) {
            return null;
        }

        return DB::table('campaign_finance_policies')->latest('id')->first();
    }

    public function visible(User $actor): array
    {
        if ($actor->access_level === 'superadmin') {
            return self::SECTIONS;
        }

        $policy = $this->current();
        $menus = $policy ? json_decode($policy->menus, true, 512, JSON_THROW_ON_ERROR) : [];
        $subjects = $actor->roles->where('guard_name', 'web')->map(fn ($role) => 'role:'.$role->id)->all();
        $subjects[] = 'level:'.$actor->access_level;
        $allowed = [];
        foreach ($subjects as $subject) {
            if (array_key_exists($subject, $menus)) {
                $allowed = array_merge($allowed, $menus[$subject]);
            }
        }
        if (!array_intersect($subjects, array_keys($menus))) {
            $allowed = array_keys(self::SECTIONS);
        }

        return array_diff_key(array_intersect_key(self::SECTIONS, array_flip($allowed)), ['settings' => true]);
    }

    public function snapshot(int $requestId, int $amount): void
    {
        $policy = $this->current();
        if (!$policy) {
            return;
        }
        $rules = json_decode($policy->approval_rules, true, 512, JSON_THROW_ON_ERROR);
        $stages = [];
        foreach ($rules as $rule) {
            if ($amount >= $rule['minimum_minor']) {
                $stages = $rule['stages'];
            }
        }
        if ($stages === []) {
            return;
        }
        DB::table('campaign_finance_workflows')->insert([
            'request_id' => $requestId,
            'policy_id' => $policy->id,
            'stages' => json_encode($stages, JSON_THROW_ON_ERROR),
        ]);
    }

    public function mayDecide(User $actor, string $subject, CampaignFinanceAccount $account): bool
    {
        if (str_starts_with($subject, 'office:')) {
            return DB::table('campaign_finance_appointments')
                ->where('user_id', $actor->id)
                ->where('office', substr($subject, strlen('office:')))
                ->where('starts_at', '<=', now())
                ->whereNull('revoked_at')
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->lockForUpdate()
                ->get(['boundary_column', 'boundary_id'])
                ->contains(fn ($office) => isset(self::BOUNDARIES[$office->boundary_column])
                    && (int) $account->{$office->boundary_column} === (int) $office->boundary_id);
        }

        if ($subject === 'level:'.$actor->access_level) {
            return true;
        }

        return $actor->roles->where('guard_name', 'web')->contains(fn ($role) => $subject === 'role:'.$role->id);
    }
}
