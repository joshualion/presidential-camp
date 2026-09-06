<?php

namespace App\Services;

use App\Models\LocalLicense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CampaignInstallationSettingsService
{
    public const SCOPE_OPTIONS = [
        PackageGovernanceService::PRESIDENTIAL => [
            'label' => 'Presidential / National',
            'scope_type' => 'national',
            'default_scope_name' => 'Nigeria',
            'requires' => [],
        ],
        PackageGovernanceService::GOVERNORSHIP => [
            'label' => 'Governorship / State',
            'scope_type' => 'state',
            'default_scope_name' => 'Selected State',
            'requires' => ['state_id'],
        ],
        PackageGovernanceService::SENATORIAL => [
            'label' => 'Senatorial',
            'scope_type' => 'senatorial_district',
            'default_scope_name' => 'Selected Senatorial District',
            'requires' => ['state_id', 'senatorial_district_id'],
        ],
        PackageGovernanceService::FEDERAL => [
            'label' => 'Federal Constituency / House of Representatives',
            'scope_type' => 'federal_constituency',
            'default_scope_name' => 'Selected Federal Constituency',
            'requires' => ['state_id', 'federal_constituency_id'],
        ],
        PackageGovernanceService::CHAIRMANSHIP => [
            'label' => 'LGA / Chairmanship',
            'scope_type' => 'lga',
            'default_scope_name' => 'Selected LGA',
            'requires' => ['state_id', 'lga_id'],
        ],
    ];

    public function __construct(private PackageGovernanceService $packageGovernance)
    {
    }

    public function options(): array
    {
        return self::SCOPE_OPTIONS;
    }

    public function option(string $packageType): ?array
    {
        $packageType = $this->packageGovernance->normalize($packageType);

        return self::SCOPE_OPTIONS[$packageType] ?? null;
    }

    public function supportedPackageTypes(): array
    {
        return array_keys(self::SCOPE_OPTIONS);
    }

    public function scopeTypeForPackage(?string $packageType): string
    {
        $packageType = $this->packageGovernance->normalize($packageType);

        return self::SCOPE_OPTIONS[$packageType]['scope_type'] ?? 'national';
    }

    public function defaultScopeName(?string $scopeType): ?string
    {
        foreach (self::SCOPE_OPTIONS as $option) {
            if ($option['scope_type'] === $scopeType) {
                return $option['default_scope_name'];
            }
        }

        return null;
    }

    public function completeGeography(array $campaignScope, array $campaignGeography): bool
    {
        $option = $this->option((string) ($campaignScope['package_type'] ?? ''));
        if (!$option) {
            return false;
        }

        foreach ($option['requires'] as $field) {
            $portalField = 'portal_'.$field;
            if (empty($campaignGeography[$field]) && empty($campaignGeography[$portalField])) {
                return !empty($campaignGeography['scope_name']);
            }
        }

        return true;
    }

    public function persist(array $campaignScope, array $campaignGeography = [], array $campaignIdentity = []): void
    {
        if (!Schema::hasTable('system_settings')) {
            return;
        }

        $packageType = $this->packageGovernance->normalize($campaignScope['package_type'] ?? PackageGovernanceService::PRESIDENTIAL);
        $scopeType = $campaignScope['scope_type'] ?? $this->scopeTypeForPackage($packageType);
        $scopeName = $campaignGeography['scope_name'] ?? $campaignScope['scope_name'] ?? $this->defaultScopeName($scopeType);

        $values = [
            'package' => $packageType,
            'updated_at' => now(),
        ];

        $columnValues = [
            'installation_mode' => 'community',
            'campaign_scope_type' => $scopeType,
            'campaign_scope_name' => $scopeName,
            'campaign_state_id' => $campaignGeography['state_id'] ?? null,
            'campaign_senatorial_district_id' => $campaignGeography['senatorial_district_id'] ?? null,
            'campaign_federal_constituency_id' => $campaignGeography['federal_constituency_id'] ?? null,
            'campaign_lga_id' => $campaignGeography['lga_id'] ?? null,
            'system_name' => $campaignIdentity['system_name'] ?? null,
            'campaign_slogan' => $campaignIdentity['campaign_slogan'] ?? null,
        ];

        foreach ($columnValues as $column => $value) {
            if (Schema::hasColumn('system_settings', $column) && $value !== null) {
                $values[$column] = $value;
            }
        }

        DB::table('system_settings')->updateOrInsert(['id' => 1], $values + ['created_at' => now()]);
    }

    public function syncFromLocalLicense(LocalLicense $license): void
    {
        $scope = $license->scope;
        if (!$scope) {
            return;
        }

        $this->persist(
            [
                'package_type' => $license->package_type,
                'scope_type' => $scope->scope_type,
                'scope_name' => $scope->scope_name,
            ],
            [
                'scope_name' => $scope->scope_name,
                'state_id' => $scope->state_id,
                'senatorial_district_id' => $scope->senatorial_district_id,
                'federal_constituency_id' => $scope->federal_constituency_id,
                'lga_id' => $scope->lga_id,
            ]
        );
    }
}
