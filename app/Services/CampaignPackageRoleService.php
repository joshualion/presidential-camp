<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class CampaignPackageRoleService
{
    public function __construct(private PackageScopeService $scopeService)
    {
    }

    public function accessLevelLabels(): array
    {
        return config('campaign_roles.access_level_labels', []);
    }

    public function allowedAccessLevels(): array
    {
        $labels = $this->accessLevelLabels();

        if (!$this->usesPackageRoleMap()) {
            return $labels;
        }

        $configured = $this->packageConfig()['access_levels'] ?? array_keys($labels);

        return collect($configured)
            ->filter(fn (string $accessLevel) => array_key_exists($accessLevel, $labels))
            ->mapWithKeys(fn (string $accessLevel) => [$accessLevel => $labels[$accessLevel]])
            ->all();
    }

    public function assignableAccessLevels(?User $actor): array
    {
        $actorRank = $this->accessLevelRank($actor?->access_level);

        if ($actorRank === null) {
            return ['user' => $this->accessLevelLabels()['user'] ?? 'Member Access'];
        }

        return collect($this->allowedAccessLevels())
            ->filter(fn (string $label, string $accessLevel) => $this->accessLevelRank($accessLevel) >= $actorRank)
            ->all();
    }

    public function canAssignAccessLevel(?User $actor, ?string $accessLevel): bool
    {
        return $accessLevel !== null
            && array_key_exists($accessLevel, $this->assignableAccessLevels($actor));
    }

    public function rolesForAccessLevel(?User $actor, ?string $accessLevel, ?User $member = null): Collection
    {
        if (!$accessLevel || !$this->canAssignAccessLevel($actor, $accessLevel)) {
            return collect();
        }

        if (!$this->usesPackageRoleMap()) {
            return Role::query()->where('group_name', $accessLevel)->orderBy('id')->get();
        }

        $names = $this->roleNamesForAccessLevel($accessLevel);

        if ($names === []) {
            return collect();
        }

        $roles = collect($names)
            ->map(fn (string $name) => $this->findOrCreateRole($name, $accessLevel))
            ->filter();

        $memberRole = $member?->roles?->first();
        if ($memberRole && $memberRole->group_name === $accessLevel && !$roles->contains('id', $memberRole->id) && $this->roleNameIsAllowed($memberRole->name, $accessLevel)) {
            $roles->push($memberRole);
        }

        return $roles->unique('id')->values();
    }

    public function assignableRoles(?User $actor, ?User $member = null): Collection
    {
        if (!$this->usesPackageRoleMap()) {
            $allowedGroups = array_keys($this->assignableAccessLevels($actor));

            if ($member?->access_level && !in_array($member->access_level, $allowedGroups, true)) {
                $allowedGroups[] = $member->access_level;
            }

            $groupOrder = array_flip(array_keys($this->accessLevelLabels()));

            return Role::query()
                ->whereIn('group_name', $allowedGroups)
                ->orderBy('id')
                ->get()
                ->sortBy(fn (Role $role) => (($groupOrder[$role->group_name] ?? 99) * 1000) + $role->id)
                ->values();
        }

        $roles = collect();

        foreach (array_keys($this->assignableAccessLevels($actor)) as $accessLevel) {
            $roles = $roles->merge($this->rolesForAccessLevel($actor, $accessLevel, $member));
        }

        return $roles->unique('id')->values();
    }

    public function manageableRoles(?User $actor = null): Collection
    {
        return $this->assignableRoles($actor);
    }

    public function manageableRoleGroups(?User $actor = null): array
    {
        return $this->assignableAccessLevels($actor);
    }

    public function roleIsPackageAllowed(Role $role, ?string $accessLevel = null): bool
    {
        if (!$this->usesPackageRoleMap()) {
            return true;
        }

        $accessLevel = $accessLevel ?: $role->group_name;

        return $role->group_name === $accessLevel
            && array_key_exists($accessLevel, $this->allowedAccessLevels())
            && $this->roleNameIsAllowed($role->name, $accessLevel);
    }

    public function roleNameIsPackageAllowed(string $roleName, string $accessLevel): bool
    {
        if (!$this->usesPackageRoleMap()) {
            return true;
        }

        return array_key_exists($accessLevel, $this->allowedAccessLevels())
            && $this->roleNameIsAllowed($roleName, $accessLevel);
    }

    public function packageRoleNames(): array
    {
        if (!$this->usesPackageRoleMap()) {
            return Role::query()->orderBy('id')->pluck('name')->all();
        }

        return collect($this->packageConfig()['roles'] ?? [])
            ->flatten()
            ->values()
            ->all();
    }

    public function canAssignRole(?User $actor, Role $role, ?string $accessLevel = null): bool
    {
        $accessLevel = $accessLevel ?: $role->group_name;

        if (!$this->canAssignAccessLevel($actor, $accessLevel)) {
            return false;
        }

        if (!$this->usesPackageRoleMap()) {
            return $role->group_name === $accessLevel;
        }

        return $role->group_name === $accessLevel
            && $this->roleNameIsAllowed($role->name, $accessLevel);
    }

    public function defaultRole(?User $actor): ?Role
    {
        return $this->rolesForAccessLevel($actor, 'user')->first()
            ?: Role::query()->where('name', 'Member')->where('group_name', 'user')->first();
    }

    public function invalidRoleMessage(Role $role, ?string $accessLevel = null): ?string
    {
        if (!$this->usesPackageRoleMap()) {
            return null;
        }

        $accessLevel = $accessLevel ?: $role->group_name;

        return $this->roleNameIsAllowed($role->name, $accessLevel)
            ? null
            : 'The current role is not available for this licensed campaign package. Please select a package-appropriate role.';
    }

    public function usesPackageRoleMap(): bool
    {
        return $this->scopeService->hasLocalLicense();
    }

    private function roleNamesForAccessLevel(string $accessLevel): array
    {
        return array_values($this->packageConfig()["roles"][$accessLevel] ?? []);
    }

    private function roleNameIsAllowed(string $roleName, string $accessLevel): bool
    {
        return in_array($roleName, $this->roleNamesForAccessLevel($accessLevel), true);
    }

    private function findOrCreateRole(string $name, string $accessLevel): ?Role
    {
        $role = Role::query()->where('name', $name)->where('guard_name', 'web')->first();

        if ($role) {
            return $role;
        }

        return Role::query()->create([
            'name' => $name,
            'guard_name' => 'web',
            'group_name' => $accessLevel,
        ]);
    }

    private function packageConfig(): array
    {
        $package = app(PackageGovernanceService::class)->normalize($this->scopeService->packageType());

        return config("campaign_roles.packages.{$package}")
            ?: config('campaign_roles.packages.presidential', []);
    }

    private function accessLevelRank(?string $accessLevel): ?int
    {
        return [
            'superadmin' => 1,
            'nationaladmin' => 2,
            'regionaladmin' => 3,
            'regionadmin' => 3,
            'stateadmin' => 4,
            'senatorialadmin' => 5,
            'federaladmin' => 5,
            'lgaadmin' => 6,
            'wardadmin' => 7,
            'puadmin' => 8,
            'pollingunitadmin' => 8,
            'user' => 9,
        ][$accessLevel] ?? null;
    }
}
