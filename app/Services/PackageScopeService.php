<?php

namespace App\Services;

use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\LocalLicense;
use App\Models\PollingUnit;
use App\Models\SenatorialDistrict;
use App\Models\SystemSetting;
use App\Models\Ward;
use App\Support\CurrentPackageScope;
use App\Support\SafeDatabase;
use Illuminate\Support\Facades\Schema;

class PackageScopeService
{
    public function __construct(private DeploymentModeService $deploymentMode)
    {
    }

    public function current(): CurrentPackageScope
    {
        $license = $this->localLicense();

        if ($license) {
            $scope = $license->scope;

            return new CurrentPackageScope(
                deployment_mode: $license->deployment_mode,
                package_type: $license->package_type ?: $this->normalizePackage(null),
                scope_type: $scope?->scope_type,
                scope_name: $scope?->scope_name,
                state_id: $scope?->state_id,
                state_name: $scope?->state_name,
                senatorial_district_id: $scope?->senatorial_district_id,
                senatorial_district_name: $scope?->senatorial_district_name,
                federal_constituency_id: $scope?->federal_constituency_id,
                federal_constituency_name: $scope?->federal_constituency_name,
                lga_id: $scope?->lga_id,
                lga_name: $scope?->lga_name,
                modules: $license->modules->where('enabled', true)->pluck('module_key')->values()->all(),
                fallback_used: false,
                license_status: $license->status,
            );
        }

        $settings = $this->systemSettings();
        if ($settings) {
            $package = $this->normalizePackage($settings->package ?? null);
            $scopeType = $this->settingColumn('campaign_scope_type')
                ? ($settings->campaign_scope_type ?: $this->scopeTypeForPackage($package))
                : $this->scopeTypeForPackage($package);

            return new CurrentPackageScope(
                deployment_mode: $this->deploymentMode->mode(),
                package_type: $package,
                scope_type: $scopeType,
                scope_name: $this->settingColumn('campaign_scope_name')
                    ? ($settings->campaign_scope_name ?: $this->defaultScopeName($scopeType))
                    : $this->defaultScopeName($scopeType),
                state_id: $this->settingColumn('campaign_state_id') ? $settings->campaign_state_id : null,
                state_name: null,
                senatorial_district_id: $this->settingColumn('campaign_senatorial_district_id') ? $settings->campaign_senatorial_district_id : null,
                senatorial_district_name: null,
                federal_constituency_id: $this->settingColumn('campaign_federal_constituency_id') ? $settings->campaign_federal_constituency_id : null,
                federal_constituency_name: null,
                lga_id: $this->settingColumn('campaign_lga_id') ? $settings->campaign_lga_id : null,
                lga_name: null,
                modules: [],
                fallback_used: false,
                license_status: null,
            );
        }

        $fallbackPackage = $this->fallbackPackage();

        return new CurrentPackageScope(
            deployment_mode: $this->deploymentMode->mode(),
            package_type: $fallbackPackage,
            scope_type: null,
            scope_name: null,
            state_id: null,
            state_name: null,
            senatorial_district_id: null,
            senatorial_district_name: null,
            federal_constituency_id: null,
            federal_constituency_name: null,
            lga_id: null,
            lga_name: null,
            modules: [],
            fallback_used: true,
            license_status: null,
        );
    }

    public function localLicense(): ?LocalLicense
    {
        if (!SafeDatabase::hasTable('local_licenses')) {
            return null;
        }

        return LocalLicense::with(['scope', 'modules'])->latest('id')->first();
    }

    public function deploymentMode(): string
    {
        return $this->current()->deployment_mode ?? $this->deploymentMode->mode();
    }

    public function packageType(): ?string
    {
        return $this->current()->package_type;
    }

    public function scopeType(): ?string
    {
        return $this->current()->scope_type;
    }

    public function scopeName(): ?string
    {
        return $this->current()->scope_name;
    }

    public function enabledModules(): array
    {
        return $this->current()->modules;
    }

    public function localLicenseHasModule(string $moduleKey): ?bool
    {
        $license = $this->localLicense();

        if (!$license) {
            return null;
        }

        $normalized = strtolower(trim(str_replace(['-', ' '], '_', $moduleKey)));
        $module = $license->modules
            ->first(fn ($module) => strtolower(trim(str_replace(['-', ' '], '_', (string) $module->module_key))) === $normalized);

        return $module ? (bool) $module->enabled : null;
    }

    public function isSelfHosted(): bool
    {
        return $this->deploymentMode() === DeploymentModeService::SELF_HOSTED;
    }

    public function isManaged(): bool
    {
        return $this->deploymentMode() === DeploymentModeService::MANAGED;
    }

    public function isPresidential(): bool
    {
        return $this->packageType() === PackageGovernanceService::PRESIDENTIAL;
    }

    public function isGovernorship(): bool
    {
        return $this->packageType() === PackageGovernanceService::GOVERNORSHIP;
    }

    public function isSenatorial(): bool
    {
        return $this->packageType() === PackageGovernanceService::SENATORIAL;
    }

    public function isFederalConstituency(): bool
    {
        return $this->packageType() === PackageGovernanceService::FEDERAL;
    }

    public function isLga(): bool
    {
        return in_array($this->packageType(), [PackageGovernanceService::CHAIRMANSHIP, 'lga'], true);
    }

    public function stateId(): ?int
    {
        return $this->current()->state_id;
    }

    public function stateName(): ?string
    {
        return $this->current()->state_name;
    }

    public function senatorialDistrictId(): ?int
    {
        return $this->current()->senatorial_district_id;
    }

    public function senatorialDistrictName(): ?string
    {
        return $this->current()->senatorial_district_name;
    }

    public function federalConstituencyId(): ?int
    {
        return $this->current()->federal_constituency_id;
    }

    public function federalConstituencyName(): ?string
    {
        return $this->current()->federal_constituency_name;
    }

    public function lgaId(): ?int
    {
        return $this->current()->lga_id;
    }

    public function lgaName(): ?string
    {
        return $this->current()->lga_name;
    }

    public function dashboardLabel(): string
    {
        return app(CampaignPackageUiService::class)->dashboardTitle();
    }

    public function display(): string
    {
        $package = $this->packageType() ? str_replace('_', ' ', $this->packageType()) : 'Unlicensed package';
        $scope = $this->scopeName() ?: ($this->scopeType() ? str_replace('_', ' ', $this->scopeType()) : 'No scope');

        return ucwords($package).' - '.$scope;
    }

    public function allowsState(?int $stateId): bool
    {
        if (!$this->hasCampaignScope() || !$stateId) {
            return true;
        }

        $scope = $this->current();

        return match ($scope->scope_type) {
            'national' => true,
            'state' => (int) $scope->state_id === (int) $stateId,
            'senatorial_district' => $this->relatedStateId(SenatorialDistrict::class, $scope->senatorial_district_id) === (int) $stateId,
            'federal_constituency' => $this->relatedStateId(FederalConstituency::class, $scope->federal_constituency_id) === (int) $stateId,
            'lga' => $this->relatedStateId(LocalGovernmentArea::class, $scope->lga_id) === (int) $stateId,
            default => false,
        };
    }

    public function allowsSenatorialDistrict(?int $id): bool
    {
        if (!$this->hasCampaignScope() || !$id) {
            return true;
        }

        $scope = $this->current();

        return match ($scope->scope_type) {
            'national' => true,
            'state' => $this->belongsToState(SenatorialDistrict::class, $id, $scope->state_id),
            'senatorial_district' => (int) $scope->senatorial_district_id === (int) $id,
            'federal_constituency' => $this->sameColumn(FederalConstituency::class, $scope->federal_constituency_id, 'senatorial_district_id', $id),
            'lga' => $this->sameColumn(LocalGovernmentArea::class, $scope->lga_id, 'senatorial_district_id', $id),
            default => false,
        };
    }

    public function allowsFederalConstituency(?int $id): bool
    {
        if (!$this->hasCampaignScope() || !$id) {
            return true;
        }

        $scope = $this->current();

        return match ($scope->scope_type) {
            'national' => true,
            'state' => $this->belongsToState(FederalConstituency::class, $id, $scope->state_id),
            'senatorial_district' => $this->sameColumn(FederalConstituency::class, $id, 'senatorial_district_id', $scope->senatorial_district_id),
            'federal_constituency' => (int) $scope->federal_constituency_id === (int) $id,
            'lga' => $this->sameColumn(LocalGovernmentArea::class, $scope->lga_id, 'federal_constituency_id', $id),
            default => false,
        };
    }

    public function allowsLga(?int $id): bool
    {
        if (!$this->hasCampaignScope() || !$id) {
            return true;
        }

        $scope = $this->current();

        return match ($scope->scope_type) {
            'national' => true,
            'state' => $this->belongsToState(LocalGovernmentArea::class, $id, $scope->state_id),
            'senatorial_district' => $this->sameColumn(LocalGovernmentArea::class, $id, 'senatorial_district_id', $scope->senatorial_district_id),
            'federal_constituency' => $this->sameColumn(LocalGovernmentArea::class, $id, 'federal_constituency_id', $scope->federal_constituency_id),
            'lga' => (int) $scope->lga_id === (int) $id,
            default => false,
        };
    }

    public function allowsWard(?int $id): bool
    {
        if (!$this->hasCampaignScope() || !$id || !SafeDatabase::hasTable('wards')) {
            return true;
        }

        $ward = Ward::query()->find($id);

        return $ward ? $this->allowsLga($ward->lga_id) : false;
    }

    public function allowsPollingUnit(?int $id): bool
    {
        if (!$this->hasCampaignScope() || !$id || !SafeDatabase::hasTable('polling_units')) {
            return true;
        }

        $pollingUnit = PollingUnit::query()->find($id);

        if (!$pollingUnit) {
            return false;
        }

        if ($pollingUnit->ward_id && !$this->allowsWard($pollingUnit->ward_id)) {
            return false;
        }

        if ($pollingUnit->senatorial_district_id && !$this->allowsSenatorialDistrict($pollingUnit->senatorial_district_id)) {
            return false;
        }

        if ($pollingUnit->federal_constituency_id && !$this->allowsFederalConstituency($pollingUnit->federal_constituency_id)) {
            return false;
        }

        return true;
    }

    public function hasLocalLicense(): bool
    {
        return $this->localLicense() !== null;
    }

    public function hasCampaignScope(): bool
    {
        return filled($this->current()->scope_type);
    }

    public function fallbackUsed(): bool
    {
        return $this->current()->fallback_used;
    }

    private function fallbackPackage(): string
    {
        if ($settings = $this->systemSettings()) {
            return $this->normalizePackage($settings->package);
        }

        return $this->normalizePackage(env('CAMPAIGN_PACKAGE_TYPE', PackageGovernanceService::PRESIDENTIAL));
    }

    private function normalizePackage(?string $package): string
    {
        return app(PackageGovernanceService::class)->normalize($package);
    }

    private function systemSettings(): ?SystemSetting
    {
        return SafeDatabase::hasTable('system_settings')
            ? SystemSetting::query()->first()
            : null;
    }

    private function settingColumn(string $column): bool
    {
        return SafeDatabase::hasTable('system_settings') && Schema::hasColumn('system_settings', $column);
    }

    private function scopeTypeForPackage(?string $package): string
    {
        return match ($this->normalizePackage($package)) {
            PackageGovernanceService::PRESIDENTIAL => 'national',
            PackageGovernanceService::GOVERNORSHIP => 'state',
            PackageGovernanceService::SENATORIAL => 'senatorial_district',
            PackageGovernanceService::FEDERAL => 'federal_constituency',
            PackageGovernanceService::CHAIRMANSHIP => 'lga',
            default => 'national',
        };
    }

    private function defaultScopeName(?string $scopeType): ?string
    {
        return match ($scopeType) {
            'national' => 'National',
            'state' => 'Selected State',
            'senatorial_district' => 'Selected Senatorial District',
            'federal_constituency' => 'Selected Federal Constituency',
            'lga' => 'Selected LGA',
            default => null,
        };
    }

    private function relatedStateId(string $modelClass, ?int $id): ?int
    {
        if (!$id) {
            return null;
        }

        return SafeDatabase::hasTable((new $modelClass())->getTable())
            ? $modelClass::query()->whereKey($id)->value('state_id')
            : null;
    }

    private function belongsToState(string $modelClass, ?int $id, ?int $stateId): bool
    {
        if (!$id || !$stateId) {
            return false;
        }

        return $this->relatedStateId($modelClass, $id) === (int) $stateId;
    }

    private function sameColumn(string $modelClass, ?int $id, string $column, ?int $expected): bool
    {
        if (!$id || !$expected || !SafeDatabase::hasTable((new $modelClass())->getTable())) {
            return false;
        }

        return (int) $modelClass::query()->whereKey($id)->value($column) === (int) $expected;
    }
}

