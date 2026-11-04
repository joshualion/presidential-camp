<?php

namespace App\Services\Enterprise;

use App\Services\DeploymentRegistrationService;
use App\Services\PackageScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Read-only upgrade snapshot. Runtime requests can never create a legacy grant. */
class LegacySmsAccess
{
    public function allowed(): bool
    {
        try {
            if (! Schema::hasTable('campaign_enterprise_legacy_access')
                || parse_url(config('app.url'), PHP_URL_SCHEME) !== 'https') {
                return false;
            }
            $grant = DB::table('campaign_enterprise_legacy_access')->where('feature', 'sms_messaging')->first();
            if (! $grant || ! $grant->eligible || $grant->policy !== 'configured-direct-provider-v125') {
                return false;
            }
            $registration = app(DeploymentRegistrationService::class);
            $scope = app(PackageScopeService::class)->current();
            $package = $scope->package_type === 'chairmanship' ? 'lga' : $scope->package_type;
            if ($scope->fallback_used || $grant->domain !== $registration->domain() || $grant->package_type !== $package) {
                return false;
            }
            if ($grant->installation_id !== null) {
                $state = $registration->summary();
                return ! empty($state['registered']) && empty($state['pending_revoke'])
                    && ($state['id'] ?? null) === $grant->installation_id;
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
