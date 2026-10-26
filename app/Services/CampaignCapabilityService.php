<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/** Action permission decisions never replace record/location policies. */
class CampaignCapabilityService
{
    private ?array $catalogCache = null;
    private array $standardNames = [];
    private array $restrictionCache = [];
    private ?bool $restrictionsAvailable = null;

    public function catalog(): array
    {
        if ($this->catalogCache !== null) { return $this->catalogCache; }
        $names = ['authorization.manage'];
        foreach (array_unique(array_values(config('capability_routes.actions', []))) as $name) {
            foreach (explode('|', $name) as $part) { if ($part) { $names[] = $part; } }
        }
        foreach (config('capability_routes.modules', []) as $module) {
            foreach (['view', 'create', 'update', 'delete'] as $action) { $names[] = $module.'.'.$action; }
        }

        $this->standardNames = array_values(array_unique($names));
        foreach (config('campaign_rbac.capabilities', []) as $legacy) {
            foreach ($legacy as $name) { if ($name !== '*') { $names[] = $this->canonical($name); } }
        }
        return $this->catalogCache = array_values(array_unique($names));
    }

    public function canonical(string $name): string
    {
        return ['members.edit' => 'members.update'][$name] ?? $name;
    }

    public function known(string $name): bool
    {
        return in_array($this->canonical($name), $this->catalog(), true);
    }

    public function decision(User $user, string $name): array
    {
        $name = $this->canonical($name);
        if (! $this->known($name)) { return ['allowed' => false, 'reason' => 'Unknown capability']; }
        if ($user->status === 'inactive') { return ['allowed' => false, 'reason' => 'Account inactive']; }
        if ($name === 'authorization.manage') {
            return ['allowed' => $user->access_level === 'superadmin', 'reason' => 'Only Super Admin can manage authorization'];
        }
        if ($user->access_level === 'superadmin') { return ['allowed' => true, 'reason' => 'Super Admin has full system access']; }
        $this->restrictionsAvailable ??= Schema::hasTable('campaign_permission_restrictions');
        if ($this->restrictionsAvailable) {
            $key = (string) $user->id;
            $this->restrictionCache[$key] ??= DB::table('campaign_permission_restrictions')->where('user_id', $user->id)->pluck('permission_name')->all();
            if (in_array($name, $this->restrictionCache[$key], true)) {
                return ['allowed' => false, 'reason' => 'Individual restriction'];
            }
        }
        $user->loadMissing(['permissions', 'roles.permissions']);
        if ($user->permissions->contains(fn ($permission) => $permission->name === $name && $permission->guard_name === 'web')) {
            return ['allowed' => true, 'reason' => 'Individual grant'];
        }
        $roles = $user->roles->filter(fn ($role) => $role->guard_name === 'web'
            && $role->permissions->contains('name', $name))->pluck('name')->all();
        if ($roles) { return ['allowed' => true, 'reason' => 'Role: '.implode(', ', $roles)]; }
        if ($user->capabilities_managed || $user->roles->contains(fn ($role) => (bool) $role->capabilities_managed)) {
            return ['allowed' => false, 'reason' => 'No role or individual grant'];
        }

        // Compatibility mode preserves customer assignments until Super Admin explicitly adopts managed capabilities.
        $admin = in_array($user->access_level, array_diff(array_keys(config('campaign_roles.access_level_labels', [])), ['user']), true);
        if (in_array($name, ['members.update', 'members.delete'], true)) {
            $legacy = $name === 'members.update' ? 'members.edit' : $name;
            $allowed = $user->roles->contains(function ($role) use ($legacy) {
                $group = config('campaign_rbac.role_capability_groups.'.$role->name);
                return $group && in_array($legacy, config('campaign_rbac.capabilities.'.$group, []), true);
            });
        } elseif (! in_array($name, $this->standardNames, true) && collect(config('campaign_rbac.capabilities', []))->flatten()->contains($name)) {
            $allowed = $user->roles->contains(function ($role) use ($name) {
                $group = config('campaign_rbac.role_capability_groups.'.$role->name);
                $permissions = $group ? config('campaign_rbac.capabilities.'.$group, []) : [];
                return in_array($name, $permissions, true) || in_array('*', $permissions, true);
            });
        } elseif (in_array($name, ['election.results.create', 'election.results.update', 'incidents.create'], true)) {
            $allowed = app(PollingUnitResultPermissionService::class)->userHasAnyApprovedAssignment($user);
        } else {
            $allowed = $admin || in_array($name, ['dashboard.view', 'community.view', 'community.create', 'community.update',
                'community.delete', 'announcements.view', 'notifications.view', 'notifications.update', 'notifications.delete', 'messaging.view',
                'messaging.create', 'messaging.update', 'election.results.view', 'incidents.view', 'agents.view', 'agents.create'], true);
        }

        return ['allowed' => (bool) $allowed, 'reason' => $allowed ? 'Default role permissions' : 'Permission not assigned'];
    }

    public function allows(User $user, string $name): bool
    {
        return $this->decision($user, $name)['allowed'];
    }

    public function defaultRole(string $accessLevel): ?Role
    {
        if (Schema::hasTable('campaign_default_roles')) {
            $id = DB::table('campaign_default_roles')->where('access_level', $accessLevel)->value('role_id');
            if ($id) { return Role::find($id); }
        }

        return Role::where('guard_name', 'web')->where('group_name', $accessLevel)->oldest('id')->first()
            ?: Role::where('guard_name', 'web')->where('name', 'Member')->first();
    }

    public function rolePermissions(Role $role): array
    {
        $role->loadMissing('permissions');
        if ($role->capabilities_managed) { return $role->permissions->pluck('name')->all(); }
        $representative = new User;
        $representative->forceFill(['access_level' => $role->group_name, 'status' => 'active']);
        $representative->setRelation('roles', collect([$role]));
        $representative->setRelation('permissions', collect());
        return array_values(array_unique(array_merge($role->permissions->pluck('name')->all(),
            ['election.results.create', 'election.results.update', 'incidents.create'],
            array_values(array_filter($this->catalog(), fn ($name) => $this->allows($representative, $name))))));
    }

    public function canVisit(?User $user, string $url, string $method = 'GET'): bool
    {
        if (! $user) { return false; }
        try {
            $route = app('router')->getRoutes()->match(\Illuminate\Http\Request::create($url, strtoupper($method)));
            $permission = $this->routePermission($route);
            return ! $permission || collect(explode('|', $permission))->contains(fn ($name) => $this->allows($user, $name));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) { return false; }
    }

    public function routePermission(\Illuminate\Routing\Route $route): ?string
    {
        $action = ltrim($route->getActionName(), '\\');
        return config('capability_routes.actions.'.$action);
    }
}
