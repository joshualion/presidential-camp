<?php

namespace App\Services;

class ModuleGateService
{
    private const DISABLED_BY_DEFAULT_MODULES = [
        'bulk_sms',
        'campaign_store',
        'donations',
        'finance',
        'finance_requests',
        'sms',
        'sponsored_placements',
    ];

    public function __construct(private PackageScopeService $scope)
    {
    }

    public function enabled(string $moduleKey): bool
    {
        $moduleKey = $this->normalize($moduleKey);
        $configured = config('campaign.modules.'.$moduleKey);

        if (!$this->scope->hasLocalLicense()) {
            if (in_array($moduleKey, self::DISABLED_BY_DEFAULT_MODULES, true)) {
                return $this->scope->isSelfHosted() && $configured === true;
            }

            return true;
        }

        $explicitState = $this->scope->localLicenseHasModule($moduleKey);
        if ($moduleKey === CommunityRealtimeService::MODULE_KEY && $explicitState === null) {
            return true;
        }

        if ($explicitState !== null) {
            return $explicitState;
        }

        if (in_array($moduleKey, self::DISABLED_BY_DEFAULT_MODULES, true)) {
            return $configured === true;
        }

        return in_array($moduleKey, array_map([$this, 'normalize'], $this->scope->enabledModules()), true);
    }

    public function disabled(string $moduleKey): bool
    {
        return !$this->enabled($moduleKey);
    }

    public function requireEnabled(string $moduleKey): void
    {
        abort_if($this->disabled($moduleKey), 403, 'This module is not enabled for the current installation.');
    }

    private function normalize(string $moduleKey): string
    {
        return strtolower(trim(str_replace(['-', ' '], '_', $moduleKey)));
    }
}


