<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;

class CampaignPackagePermissionService
{
    public function __construct(
        private CampaignPackageRoleService $roles,
        private PackageScopeService $scope,
    ) {
    }

    public function permissionsForRole(Role|string|null $role): array
    {
        $roleName = $role instanceof Role ? $role->name : $role;

        if (!$roleName) {
            return [];
        }

        $group = config("campaign_rbac.role_capability_groups.{$roleName}");

        return $group ? config("campaign_rbac.capabilities.{$group}", []) : [];
    }

    public function roleHasCapability(Role|string|null $role, string $capability): bool
    {
        $permissions = $this->permissionsForRole($role);

        return in_array('*', $permissions, true)
            || in_array($capability, $permissions, true);
    }

    public function userCan(User $actor, string $capability): bool
    {
        $service = app(CampaignCapabilityService::class);
        if ($service->known($capability)) {
            return $service->allows($actor, $capability);
        }
        if ($actor->access_level === 'superadmin') { return true; }
        if ($actor->capabilities_managed || $actor->roles->contains(fn ($role) => (bool) $role->capabilities_managed)) {
            return $actor->checkPermissionTo($capability);
        }
        foreach ($actor->roles as $role) {
            if ($actor->checkPermissionTo($capability) || $this->roleHasCapability($role, $capability)) { return true; }
        }
        return false;
    }

    public function canEditUsers(User $actor): bool
    {
        return $this->userCan($actor, 'members.edit');
    }

    public function canDeleteUsers(User $actor): bool
    {
        return $this->userCan($actor, 'members.delete');
    }

    public function canManageRole(?User $actor, Role $role): bool
    {
        if (!$actor || $actor->access_level !== 'superadmin') {
            return false;
        }

        return $this->roles->roleIsPackageAllowed($role);
    }

    public function canCreateRole(?User $actor, string $roleName, string $accessLevel): bool
    {
        if (!$actor || $actor->access_level !== 'superadmin') {
            return false;
        }

        return $this->roles->roleNameIsPackageAllowed($roleName, $accessLevel);
    }

    public function packageModeActive(): bool
    {
        return $this->scope->hasLocalLicense();
    }
}
