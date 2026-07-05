<?php

namespace App\Services\Sms;

use App\Models\User;
use App\Services\ModuleGateService;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class SmsAccessService
{
    public function __construct(private ModuleGateService $modules) {}

    public function moduleEnabled(): bool
    {
        return $this->modules->enabled('sms') || $this->modules->enabled('bulk_sms');
    }

    public function allows(?User $user, string $permission): bool
    {
        if (!$user || !$this->moduleEnabled()) return false;
        if ($user->access_level === 'superadmin') return true;
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            // Legacy access-level fallback applies only until the permission record is installed.
        }

        $admins = ['nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin'];
        return match ($permission) {
            'sms.view', 'sms.wallet.view', 'sms.wallet.topup', 'sms.credit.request' => in_array($user->access_level, $admins, true),
            'sms.compose', 'sms.send', 'sms.sender_id.request' => in_array($user->access_level, $admins, true),
            'sms.reports' => in_array($user->access_level, $admins, true),
            'sms.organization_wallet.view', 'sms.organization_wallet.use' => in_array($user->access_level, ['nationaladmin'], true),
            'sms.wallet.transfer' => false,
            default => false,
        };
    }
}
