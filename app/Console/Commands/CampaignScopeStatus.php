<?php

namespace App\Console\Commands;

use App\Services\InstallationStateService;
use App\Services\LicensedLocationProvisioningService;
use App\Services\LicensedScopeQueryService;
use App\Services\PackageScopeService;
use App\Support\SafeDatabase;
use Illuminate\Console\Command;

class CampaignScopeStatus extends Command
{
    protected $signature = 'campaign:scope-status';

    protected $description = 'Show non-sensitive package, module, and licensed scope diagnostics.';

    public function handle(
        PackageScopeService $scopeService,
        InstallationStateService $installationState,
        LicensedScopeQueryService $licensedScope,
        LicensedLocationProvisioningService $locationProvisioning,
    ): int
    {
        $scope = $scopeService->current();
        $enforcement = $licensedScope->enforcementSummary();

        $this->line('Campaign Manager package/scope diagnostics');
        $this->line('Deployment mode: '.($scope->deployment_mode ?? 'unknown'));
        $this->line('Installed lock: '.($installationState->installed() ? 'present' : 'missing'));
        $this->line('Required tables: '.$this->requiredTablesStatus());
        $this->line('Local license: '.($scopeService->hasLocalLicense() ? ($scope->license_status ?? 'present') : 'missing'));
        $this->line('Fallback used: '.($scope->fallback_used ? 'yes' : 'no'));
        $this->line('Package type: '.($scope->package_type ?? 'not resolved'));
        $this->line('Scope type: '.($scope->scope_type ?? 'not resolved'));
        $this->line('Scope name: '.($scope->scope_name ?? 'not resolved'));
        $this->line('State: '.$this->formatBoundary($scope->state_id, $scope->state_name));
        $this->line('Senatorial district: '.$this->formatBoundary($scope->senatorial_district_id, $scope->senatorial_district_name));
        $this->line('Federal constituency: '.$this->formatBoundary($scope->federal_constituency_id, $scope->federal_constituency_name));
        $this->line('LGA: '.$this->formatBoundary($scope->lga_id, $scope->lga_name));
        $this->line('Dashboard label: '.$scopeService->dashboardLabel());
        $this->line('Enabled modules: '.($scope->modules ? implode(', ', $scope->modules) : 'none recorded'));
        $this->line('Read-path enforcement helpers: '.($enforcement['available'] ? 'available' : 'missing'));
        $this->line('Read-path enforcement active: '.($enforcement['active'] ? 'yes' : 'no'));
        $this->line('Detail-route guard helpers: available');
        $this->line('Write payload validation helpers: available');
        $this->line('Dashboard aggregate scoping helpers: available');
        $this->line('Route audit checklist: docs/phase-2c-scope-enforcement-route-audit.md');
        $this->line('Allowed state id: '.($enforcement['state_id'] ?? 'all/unknown'));
        $this->line('Allowed senatorial district id: '.($enforcement['senatorial_district_id'] ?? 'all/unknown'));
        $this->line('Allowed federal constituency id: '.($enforcement['federal_constituency_id'] ?? 'all/unknown'));
        $this->line('Allowed LGA id: '.($enforcement['lga_id'] ?? 'all/unknown'));
        $this->line('Location tables: '.$this->locationTablesStatus($enforcement['tables'] ?? []));
        $this->line('Provisioning source: '.$locationProvisioning->defaultSourcePath());
        $this->line('Latest provisioning run: '.$this->latestProvisioningRun($locationProvisioning));
        $this->line('Provisioning preview: php artisan campaign:provision-locations --dry-run');

        return self::SUCCESS;
    }

    private function requiredTablesStatus(): string
    {
        return collect(['local_licenses', 'local_license_scopes', 'local_license_modules'])
            ->map(fn (string $table) => $table.'='.(SafeDatabase::hasTable($table) ? 'present' : 'missing'))
            ->implode(', ');
    }

    private function formatBoundary(?int $id, ?string $name): string
    {
        if (!$id && !$name) {
            return 'not set';
        }

        return trim(($id ? '#'.$id : '').($name ? ' '.$name : ''));
    }

    private function locationTablesStatus(array $tables): string
    {
        if (!$tables) {
            return 'not checked';
        }

        return collect($tables)
            ->map(fn (bool $present, string $table) => $table.'='.($present ? 'present' : 'missing'))
            ->implode(', ');
    }

    private function latestProvisioningRun(LicensedLocationProvisioningService $locationProvisioning): string
    {
        $run = $locationProvisioning->latestRun();

        if (!$run) {
            return 'none recorded';
        }

        return trim(sprintf(
            '#%d %s %s/%s %s',
            $run->id,
            $run->status,
            $run->package_type ?: 'unknown',
            $run->scope_type ?: 'unknown',
            $run->completed_at?->toDateTimeString() ?: 'not completed'
        ));
    }
}
