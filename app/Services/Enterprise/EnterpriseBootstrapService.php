<?php

namespace App\Services\Enterprise;

use App\Services\DeploymentRegistrationService;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use RuntimeException;

class EnterpriseBootstrapService
{
    /** Repeated installs/upgrades preserve customer grants and signed caches. */
    public function initialize(): array
    {
        if (!Schema::hasTable('permissions')) {
            throw new RuntimeException('The permission database is not ready for enterprise setup.');
        }

        // Read the newly installed source even when the updater held an old config cache.
        $configuration = require config_path('enterprise.php');
        foreach ($configuration['permissions'] ?? [] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['group_name' => 'user']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $result = ['permissions_ready' => true, 'synchronized' => false, 'warnings' => []];
        $pem = (string) config('enterprise.public_key');
        $key = $pem !== '' ? openssl_pkey_get_public($pem) : false;
        $details = $key ? openssl_pkey_get_details($key) : false;
        if (!$details || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 2048) {
            $result['warnings'][] = 'Add-on verification trust is unavailable. Install an official signed update; no manual private-key setup is required.';
            return $result;
        }

        try {
            $state = app(DeploymentRegistrationService::class)->summary();
            if (empty($state['registered']) || !empty($state['pending_revoke']) || empty($state['contact_verified'])) {
                $result['warnings'][] = 'Complete installation contact verification, then open Add-ons and refresh licence status.';
                return $result;
            }
            app(EntitlementService::class)->refresh();
            $result['synchronized'] = true;
        } catch (\Throwable) {
            // The entitlement service retains valid cached grants on transport outages.
            // Explicit authorization rejection still invalidates them through its normal policy.
            $result['warnings'][] = 'Add-on status could not be synchronized. Open Add-ons and refresh licence status when Portal connectivity is restored.';
        }

        return $result;
    }
}
