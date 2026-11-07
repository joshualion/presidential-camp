<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Enterprise\CampaignFinancePolicy;
use App\Services\LocationScopeService;
use App\Services\Enterprise\CampaignFinanceService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CampaignFinanceSettingsController extends Controller
{
    public function show(Request $request, CampaignFinanceService $finance, CampaignFinancePolicy $policy)
    {
        $finance->authorize($request->user(), 'authorization.manage');
        $current = $policy->current();
        $subjects = $this->subjects();
        $subject = $request->validate(['subject' => ['nullable', Rule::in(array_keys($subjects))]])['subject'] ?? null;

        return response()->view('backend.finance.settings', [
            'profileData' => $request->user(),
            'pageTitle' => 'Finance Settings',
            'current' => $current,
            'menus' => $current ? json_decode($current->menus, true, 512, JSON_THROW_ON_ERROR) : [],
            'rules' => $current ? json_decode($current->approval_rules, true, 512, JSON_THROW_ON_ERROR) : [],
            'sections' => array_diff_key(CampaignFinancePolicy::SECTIONS, ['settings' => true]),
            'subjects' => $subjects,
            'stageSubjects' => $subjects + $this->officeSubjects(),
            'boundaries' => CampaignFinancePolicy::BOUNDARIES,
            'offices' => CampaignFinancePolicy::OFFICES,
            'appointments' => DB::table('campaign_finance_appointments')->orderByDesc('id')->paginate(20)->withQueryString(),
            'subject' => $subject,
            'governance' => $policy->governance(),
            'financeCapabilities' => array_values(array_filter(config('enterprise.permissions', []),
                fn ($name) => str_starts_with($name, 'finance.'))),
        ])->header('Cache-Control', 'no-store');
    }

    public function save(Request $request, CampaignFinanceService $finance, CampaignFinancePolicy $policy)
    {
        $finance->authorize($request->user(), 'authorization.manage');
        $subjects = array_keys($this->subjects());
        $rules = $request->input('rules');
        if (is_array($rules)) {
            foreach ($rules as $index => $rule) {
                if (!is_array($rule) || !isset($rule['stages']) || !is_array($rule['stages'])) {
                    continue;
                }
                $rules[$index]['stages'] = array_values(array_filter($rule['stages'], fn ($stage) => $stage !== null && $stage !== ''));
                if (($rule['minimum_minor'] ?? '') === '' && !$rules[$index]['stages']) {
                    unset($rules[$index]);
                }
            }
            $request->merge(['rules' => $rules]);
        }
        $data = $request->validate([
            'version' => 'required|integer|min:0',
            'subject' => ['nullable', Rule::in($subjects)],
            'menus' => 'array',
            'menus.*' => [Rule::in(array_diff(array_keys(CampaignFinancePolicy::SECTIONS), ['settings']))],
            'rules' => 'array|max:10',
            'rules.*' => 'array:minimum_minor,stages',
            'rules.*.minimum_minor' => 'required|integer|min:0|max:100000000000000|distinct',
            'rules.*.stages' => 'required|array|min:1|max:5',
            'rules.*.stages.*' => ['required', Rule::in(array_merge($subjects, array_keys($this->officeSubjects())))],
        ]);
        $current = $policy->current();
        $previous = (int) ($current?->id ?? 0);
        if ($previous !== (int) $data['version']) {
            $this->outdated();
        }

        $menus = $current ? json_decode($current->menus, true, 512, JSON_THROW_ON_ERROR) : [];
        $rules = $data['rules'] ?? [];
        if (!empty($data['subject'])) {
            $menus[$data['subject']] = array_values(array_unique($data['menus'] ?? []));
        }
        usort($rules, fn ($first, $second) => (int) $first['minimum_minor'] <=> (int) $second['minimum_minor']);
        if ($rules && (int) $rules[0]['minimum_minor'] !== 0) {
            throw ValidationException::withMessages(['rules' => 'Include a zero threshold to cover requests below the higher thresholds.']);
        }
        foreach ($rules as &$rule) {
            $rule = ['minimum_minor' => (int) $rule['minimum_minor'], 'stages' => array_values($rule['stages'])];
        }
        unset($rule);

        try {
            DB::table('campaign_finance_policies')->insert([
                'previous_id' => $previous,
                'actor_id' => $request->user()->id,
                'menus' => json_encode($menus, JSON_THROW_ON_ERROR),
                'approval_rules' => json_encode($rules, JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->outdated();
        }

        return redirect()->route('finance.settings', array_filter(['subject' => $data['subject'] ?? null]))
            ->with('finance_status', 'Finance settings saved. Pending requests retain their original approval policy.');
    }


    public function saveGovernance(Request $request, CampaignFinanceService $finance)
    {
        $finance->authorize($request->user(), 'authorization.manage');
        $subjects = array_keys($this->subjects());
        $capabilities = array_values(array_filter(config('enterprise.permissions', []),
            fn ($name) => str_starts_with($name, 'finance.')));
        $data = $request->validate([
            'version' => 'required|integer|min:0',
            'mode' => ['required', Rule::in(['central', 'delegated'])],
            'subject' => ['required', Rule::in($subjects)],
            'grants' => 'sometimes|array',
            'grants.*' => [Rule::in($capabilities)],
        ]);
        DB::transaction(function () use ($request, $data) {
            $current = DB::table('campaign_finance_governance')->orderByDesc('id')->lockForUpdate()->first();
            $previous = (int) ($current?->id ?? 0);
            if ($previous !== (int) $data['version']) {
                $this->outdated();
            }
            $grants = $current ? json_decode($current->grants, true, 512, JSON_THROW_ON_ERROR) : [];
            $grants[$data['subject']] = array_values(array_unique($data['grants'] ?? []));
            DB::table('campaign_finance_governance')->insert([
                'previous_id' => $previous,
                'actor_id' => $request->user()->id,
                'mode' => $data['mode'],
                'grants' => json_encode($grants, JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
        }, 3);
        return redirect()->route('finance.settings', ['subject' => $data['subject']])
            ->with('finance_status', 'Finance governance saved. Changes apply immediately to future actions.');
    }

    public function appoint(Request $request, CampaignFinanceService $finance)
    {
        $actor = $request->user();
        $finance->authorize($actor, 'authorization.manage');
        $data = $request->validate([
            'user_id' => 'required|integer|min:1',
            'account_id' => 'required|integer|min:1',
            'office' => ['required', Rule::in(array_keys(CampaignFinancePolicy::OFFICES))],
            'boundary_column' => ['required', Rule::in(array_keys(CampaignFinancePolicy::BOUNDARIES))],
            'expires_at' => 'nullable|date_format:Y-m-d|after:today',
        ]);

        $account = $finance->account($actor, (int) $data['account_id']);
        $column = $data['boundary_column'];
        $boundaryId = $account->{$column};
        if (!$boundaryId) {
            throw ValidationException::withMessages(['boundary_column' => 'Choose a jurisdiction present on the reference account.']);
        }
        app(LocationScopeService::class)
            ->applyScope(User::query()->where('status', 'active'), $actor, 'users')
            ->where($column, $boundaryId)
            ->whereKey($data['user_id'])
            ->firstOrFail();

        DB::table('campaign_finance_appointments')->insert([
            'user_id' => $data['user_id'],
            'office' => $data['office'],
            'boundary_column' => $column,
            'boundary_id' => $boundaryId,
            'starts_at' => now(),
            'expires_at' => $data['expires_at'] ?? null,
            'appointed_by' => $actor->id,
        ]);
        return back()->with('finance_status', 'Finance appointment created.');
    }

    public function revoke(Request $request, int $id, CampaignFinanceService $finance)
    {
        $finance->authorize($request->user(), 'authorization.manage');
        $updated = DB::table('campaign_finance_appointments')->where('id', $id)->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'revoked_by' => $request->user()->id]);
        abort_unless($updated, 404);
        return back()->with('finance_status', 'Finance appointment revoked.');
    }

    private function officeSubjects(): array
    {
        $subjects = [];
        foreach (CampaignFinancePolicy::OFFICES as $key => $label) {
            $subjects['office:'.$key] = 'Finance appointment · '.$label;
        }
        return $subjects;
    }

    private function outdated(): never
    {
        throw ValidationException::withMessages(['version' => 'Another administrator changed Finance settings. Reload and try again.']);
    }

    private function subjects(): array
    {
        $subjects = [];
        foreach (config('campaign_roles.access_level_labels', []) as $level => $label) {
            $subjects['level:'.$level] = 'Access level · '.$label;
        }
        foreach (Role::where('guard_name', 'web')->orderBy('name')->get(['id', 'name']) as $role) {
            $subjects['role:'.$role->id] = 'Role · '.$role->name;
        }

        return $subjects;
    }
}
