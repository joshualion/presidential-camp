<?php

namespace App\Services;

use App\Models\User;

class PackageVisibilityService
{
    public function __construct(private PackageGovernanceService $packageGovernanceService)
    {
    }

    public function package(): string
    {
        return $this->packageGovernanceService->package();
    }

    public function visibleModules(): array
    {
        return match ($this->package()) {
            PackageGovernanceService::PRESIDENTIAL => ['national', 'regional', 'state', 'senatorial', 'federal', 'lga', 'ward', 'polling_unit'],
            PackageGovernanceService::GOVERNORSHIP => ['state', 'senatorial', 'federal', 'lga', 'ward', 'polling_unit'],
            PackageGovernanceService::SENATORIAL => ['senatorial', 'federal', 'lga', 'ward', 'polling_unit'],
            PackageGovernanceService::FEDERAL => ['federal', 'lga', 'ward', 'polling_unit'],
            PackageGovernanceService::CHAIRMANSHIP => ['lga', 'ward', 'polling_unit'],
        };
    }

    public function canSeeModule(string $module): bool
    {
        $module = match (strtolower($module)) {
            'pu', 'pollingunit', 'polling_unit' => 'polling_unit',
            'federal_constituency' => 'federal',
            'senatorial_district' => 'senatorial',
            default => strtolower($module),
        };

        return in_array($module, $this->visibleModules(), true);
    }

    public function canSeeAccessLevel(string $accessLevel): bool
    {
        return match (strtolower($accessLevel)) {
            'superadmin', 'admin', 'user' => true,
            'nationaladmin' => $this->canSeeNationalModules(),
            'regionaladmin' => $this->canSeeRegionalModules(),
            'stateadmin' => $this->canSeeStateModules(),
            'senatorialadmin' => $this->canSeeSenatorialModules(),
            'federaladmin' => $this->canSeeFederalModules(),
            'lgaadmin' => $this->canSeeLgaModules(),
            'wardadmin' => $this->canSeeWardModules(),
            'puadmin', 'pollingunitadmin' => $this->canSeePollingUnitModules(),
            default => false,
        };
    }

    public function canUserSeeOwnMenu(?User $user): bool
    {
        return $user ? $this->canSeeAccessLevel($user->access_level) : false;
    }

    public function canSeeNationalModules(): bool
    {
        return $this->canSeeModule('national');
    }

    public function canSeeRegionalModules(): bool
    {
        return $this->canSeeModule('regional');
    }

    public function canSeeStateModules(): bool
    {
        return $this->canSeeModule('state');
    }

    public function canSeeSenatorialModules(): bool
    {
        return $this->canSeeModule('senatorial');
    }

    public function canSeeFederalModules(): bool
    {
        return $this->canSeeModule('federal');
    }

    public function canSeeLgaModules(): bool
    {
        return $this->canSeeModule('lga');
    }

    public function canSeeWardModules(): bool
    {
        return $this->canSeeModule('ward');
    }

    public function canSeePollingUnitModules(): bool
    {
        return $this->canSeeModule('polling_unit');
    }

    public function canSeeSituationRoom(?User $user = null): bool
    {
        return !$user || $this->canSeeAccessLevel($user->access_level);
    }

    public function canSeeElectionReports(?User $user = null): bool
    {
        return !$user || $this->canSeeAccessLevel($user->access_level);
    }

    public function highestAuthorityAccessLevel(): string
    {
        return $this->packageGovernanceService->finalDisputeAuthorityAccessLevel();
    }

    public function deploymentMatrix(): array
    {
        return [
            PackageGovernanceService::PRESIDENTIAL => [
                'highest_authority' => 'nationaladmin',
                'visible_modules' => ['National', 'Regional', 'State', 'Senatorial', 'Federal', 'LGA', 'Ward', 'Polling Unit'],
                'hidden_modules' => [],
            ],
            PackageGovernanceService::GOVERNORSHIP => [
                'highest_authority' => 'stateadmin',
                'visible_modules' => ['State', 'Senatorial', 'Federal', 'LGA', 'Ward', 'Polling Unit'],
                'hidden_modules' => ['National', 'Regional'],
            ],
            PackageGovernanceService::SENATORIAL => [
                'highest_authority' => 'senatorialadmin',
                'visible_modules' => ['Senatorial', 'Federal', 'LGA', 'Ward', 'Polling Unit'],
                'hidden_modules' => ['National', 'Regional', 'State'],
            ],
            PackageGovernanceService::FEDERAL => [
                'highest_authority' => 'federaladmin',
                'visible_modules' => ['Federal', 'LGA', 'Ward', 'Polling Unit'],
                'hidden_modules' => ['National', 'Regional', 'State', 'Senatorial'],
            ],
            PackageGovernanceService::CHAIRMANSHIP => [
                'highest_authority' => 'lgaadmin',
                'visible_modules' => ['LGA', 'Ward', 'Polling Unit'],
                'hidden_modules' => ['National', 'Regional', 'State', 'Senatorial', 'Federal'],
            ],
        ];
    }
}
