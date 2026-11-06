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


    /**
     * Global governance starts closed. Role capabilities alone never open financial
     * records or money movement; the Super Admin explicitly delegates each action.
     */
    public function governance(): ?object
    {
        if (!Schema::hasTable('campaign_finance_governance')) {
            return null;
        }
        return DB::table('campaign_finance_governance')->latest('id')->first();
    }

    public function permits(User $actor, string $permission): bool
    {
        if ($actor->status !== 'active' || !str_starts_with($permission, 'finance.')) {
            return false;
        }
        if ($actor->access_level === 'superadmin') {
            return true;
        }
        // An ordinary member cannot acquire financial authority via an unrelated
        // role or a mistaken access-level grant.
        if ($actor->access_level === 'user') {
            return false;
        }
        $policy = $this->governance();
        if (!$policy) {
            return false;
        }
        $grants = json_decode($policy->grants, true, 512, JSON_THROW_ON_ERROR);
        $subjects = ['level:'.$actor->access_level];
        foreach ($actor->roles->where('guard_name', 'web') as $role) {
            $subjects[] = 'role:'.$role->id;
        }
        $allowed = false;
        foreach ($subjects as $subject) {
            if (in_array($permission, $grants[$subject] ?? [], true)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            return false;
        }
        if ($policy->mode === 'delegated') {
            return true;
        }
        if (in_array($permission, ['finance.view', 'finance.dashboard.view', 'finance.requests.view', 'finance.disbursement.create'], true)) {
            return true;
        }
        // Central finance remains operable by an explicitly permitted root-office
        // officer; a presidential root is national, a governorship root is state, etc.
        $package = \App\Models\SystemSetting::query()->value('package');
        $levels = config('campaign_roles.packages.'. $package .'.access_levels', []);
        $root = collect($levels)->first(fn ($level) => $level !== 'superadmin');
        return $root !== null && $root !== 'user' && $actor->access_level === $root;
    }

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
        if (!$this->permits($actor, 'finance.view')) {
            return [];
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
            // No saved navigation rule must not result in unrestricted menu visibility.
            $allowed = ['dashboard', 'requests'];
        }

        $sections = array_diff_key(array_intersect_key(self::SECTIONS, array_flip($allowed)), ['settings' => true]);
        // The approval, accounting and audit pages are not inferred from finance.view.
        $requirements = [
            'dashboard' => 'finance.dashboard.view',
            'requests' => 'finance.requests.view',
            'income' => 'finance.record',
            'expenditure' => 'finance.expenses.record',
            'approvals' => 'finance.disbursement.approve',
            'disbursements' => 'finance.movements.post',
            'budgets' => 'finance.budget.manage',
            'accounts' => 'finance.accounts.manage',
            'reports' => 'finance.reports.export',
        ];
        foreach ($requirements as $section => $capability) {
            if (!app(\App\Services\CampaignCapabilityService::class)->allows($actor, $capability)
                || !$this->permits($actor, $capability)) {
                unset($sections[$section]);
            }
        }
        return $sections;
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
