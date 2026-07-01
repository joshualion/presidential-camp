<?php

namespace App\Services;

use App\Models\EmailNotificationPermission;
use App\Models\User;
use App\Support\SafeDatabase;

class EmailNotificationPermissionService
{
    public const MODULE_KEY = 'messaging';

    public function __construct(
        private CampaignPackageRoleService $packageRoles,
        private ModuleGateService $moduleGate,
        private LocationScopeService $locationScope,
    ) {
    }

    public function canSend(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->moduleGate->disabled(self::MODULE_KEY)) {
            return false;
        }

        // Super Admin cannot be disabled by access-level communication settings.
        // The package-wide messaging module gate still applies to the feature itself.
        if ($user->access_level === 'superadmin') {
            return true;
        }

        if ($user->access_level === 'user' || !array_key_exists($user->access_level, $this->packageRoles->allowedAccessLevels())) {
            return false;
        }

        if (!in_array($user->access_level, ['nationaladmin'], true) && !$this->locationScope->getScopeId($user)) {
            return false;
        }

        return SafeDatabase::hasTable('email_notification_permissions')
            && EmailNotificationPermission::query()
                ->where('access_level', $user->access_level)
                ->where('enabled', true)
                ->exists();
    }

    public function settings(): array
    {
        $enabled = SafeDatabase::hasTable('email_notification_permissions')
            ? EmailNotificationPermission::query()->pluck('enabled', 'access_level')
            : collect();

        return collect($this->packageRoles->allowedAccessLevels())
            ->except('user')
            ->map(fn (string $label, string $level) => [
                'access_level' => $level,
                'label' => $label,
                'enabled' => $level === 'superadmin' || (bool) ($enabled[$level] ?? false),
                'locked' => $level === 'superadmin',
            ])
            ->values()
            ->all();
    }
}
