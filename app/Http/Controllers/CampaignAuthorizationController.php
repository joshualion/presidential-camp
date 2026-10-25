<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CampaignCapabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CampaignAuthorizationController extends Controller
{
    private function admin(Request $request): void
    {
        abort_unless($request->user()?->access_level === 'superadmin', 403);
    }

    public function index(Request $request, CampaignCapabilityService $capabilities)
    {
        $this->admin($request);
        $catalog = $capabilities->catalog();
        $selectedUser = $request->filled('user') ? User::where('uuid', $request->string('user')->toString())->firstOrFail() : null;
        $effective = $selectedUser ? collect($catalog)->mapWithKeys(fn ($name) => [$name => $capabilities->decision($selectedUser, $name)])->all() : [];
        $restrictions = $selectedUser ? DB::table('campaign_permission_restrictions')->where('user_id', $selectedUser->id)->pluck('permission_name')->all() : [];
        $users = User::query()->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
            $term = mb_substr($request->string('search')->toString(), 0, 100);
            $q->where('email', 'like', '%'.$term.'%')->orWhere('firstname', 'like', '%'.$term.'%')->orWhere('lastname', 'like', '%'.$term.'%');
        }))->orderBy('id')->paginate(20)->withQueryString();

        return view('backend.shared.authorization', [
            'pageTitle' => 'Roles & Capabilities', 'profileData' => $request->user(),
            'roles' => Role::where('guard_name', 'web')->with('permissions')->orderBy('name')->get(),
            'catalog' => $catalog, 'users' => $users, 'selectedUser' => $selectedUser,
            'effective' => $effective, 'restrictions' => $restrictions,
            'levels' => config('campaign_roles.access_level_labels', []),
            'defaults' => DB::table('campaign_default_roles')->pluck('role_id', 'access_level')->all(),
        ]);
    }

    public function saveRole(Request $request, CampaignCapabilityService $capabilities, ?Role $role = null)
    {
        $this->admin($request);
        foreach (['permissions', 'roles', 'grants', 'restrictions'] as $field) {
            if ($request->input($field) === null || $request->input($field) === '') { $request->merge([$field => []]); }
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id)],
            'permissions' => ['present', 'array'], 'permissions.*' => ['string', Rule::in($capabilities->catalog())],
        ]);
        abort_if($role && $role->guard_name !== 'web', 422);
        DB::transaction(function () use ($data, $role, $capabilities) {
            $role ??= Role::create(['name' => $data['name'], 'guard_name' => 'web', 'group_name' => 'user']);
            $role->forceFill(['name' => $data['name'], 'capabilities_managed' => true])->save();
            foreach ($data['permissions'] as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['group_name' => 'user']);
            }
            // Preserve legacy permission definitions and customer-specific non-catalog assignments.
            $legacy = $role->permissions()->whereNotIn('name', $capabilities->catalog())->pluck('name')->all();
            $role->syncPermissions(array_values(array_unique(array_merge($legacy, $data['permissions']))));
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('message', 'Role capabilities saved. Administrative access levels are unchanged.');
    }

    public function saveUser(Request $request, User $user, CampaignCapabilityService $capabilities)
    {
        $this->admin($request);
        abort_if(array_diff(array_keys($request->except('_token', '_method')), ['roles', 'grants', 'restrictions']), 422);
        foreach (['roles', 'grants', 'restrictions'] as $field) {
            if ($request->input($field) === null || $request->input($field) === '') { $request->merge([$field => []]); }
        }
        $data = $request->validate([
            'roles' => ['present', 'array'], 'roles.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'grants' => ['present', 'array'], 'grants.*' => ['string', Rule::in($capabilities->catalog())],
            'restrictions' => ['present', 'array'], 'restrictions.*' => ['string', Rule::in($capabilities->catalog())],
        ]);
        DB::transaction(function () use ($data, $user, $capabilities) {
            $user->syncRoles(Role::whereIn('id', $data['roles'])->where('guard_name', 'web')->get());
            foreach ($data['grants'] as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['group_name' => 'user']);
            }
            $legacy = $user->permissions()->whereNotIn('name', $capabilities->catalog())->pluck('name')->all();
            $user->syncPermissions(array_values(array_unique(array_merge($legacy, $data['grants']))));
            DB::table('campaign_permission_restrictions')->where('user_id', $user->id)->delete();
            foreach (array_unique($data['restrictions']) as $name) {
                DB::table('campaign_permission_restrictions')->insert(['user_id' => $user->id, 'permission_name' => $name]);
            }
            $user->forceFill(['capabilities_managed' => true])->save();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('message', 'User permissions saved. Restrictions take precedence over grants.');
    }

    public function saveDefault(Request $request)
    {
        $this->admin($request);
        $data = $request->validate([
            'access_level' => ['required', Rule::in(array_keys(config('campaign_roles.access_level_labels', [])))],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
        ]);
        DB::table('campaign_default_roles')->updateOrInsert(['access_level' => $data['access_level']], ['role_id' => $data['role_id']]);

        return back()->with('message', 'Default role saved for future user creation. Existing assignments are unchanged.');
    }
}
