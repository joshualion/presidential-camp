<?php

namespace App\Services;

use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\LocalLicense;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\Ward;

class LocationProvisioningIntegrityService
{
    public function verifyBeforeInstallLock(LocalLicense $license, array $provisioning): array
    {
        if (($provisioning['status'] ?? null) === 'completed') {
            $scopeFailure = $this->scopeFailure($license, $provisioning);
            if ($scopeFailure) {
                return [
                    'ok' => false,
                    'message' => $scopeFailure,
                    'counts' => $this->counts(),
                ];
            }
        }

        if (!$this->requiresFullNationalIntegrity($license, $provisioning)) {
            return [
                'ok' => true,
                'message' => 'Location integrity check not required for this package profile.',
                'counts' => $this->counts(),
            ];
        }

        $expected = config('release.profiles.full.expected_location_records', []);
        $counts = $this->counts();
        $short = [];

        foreach ($expected as $key => $minimum) {
            if (($counts[$key] ?? 0) < (int) $minimum) {
                $short[$key] = [
                    'expected' => (int) $minimum,
                    'actual' => (int) ($counts[$key] ?? 0),
                ];
            }
        }

        if ($short !== []) {
            return [
                'ok' => false,
                'message' => 'Campaign location setup is incomplete. Please continue location setup or contact support before completing installation.',
                'counts' => $counts,
                'short' => $short,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Full campaign location data integrity verified.',
            'counts' => $counts,
        ];
    }

    private function scopeFailure(LocalLicense $license, array $provisioning): ?string
    {
        $scope = $license->scope;
        $scopeType = strtolower((string) $scope?->scope_type);

        $requiredBoundary = match ($scopeType) {
            'state' => $scope?->state_id,
            'senatorial_district' => $scope?->senatorial_district_id,
            'federal_constituency' => $scope?->federal_constituency_id,
            'lga' => $scope?->lga_id,
            'national' => true,
            default => null,
        };

        if (!$requiredBoundary) {
            return 'Campaign location setup could not resolve the licensed scope to the imported local geography.';
        }

        $available = data_get($provisioning, 'summary._available_datasets', LocationProvisioningImportService::DATASETS);
        $available = is_array($available) ? $available : LocationProvisioningImportService::DATASETS;
        $requiredDatasets = match ($scopeType) {
            'state' => ['states', 'senatorial_districts', 'federal_constituencies', 'local_government_areas', 'wards', 'polling_units'],
            'senatorial_district' => ['states', 'senatorial_districts', 'local_government_areas', 'wards', 'polling_units'],
            'federal_constituency' => ['states', 'federal_constituencies', 'local_government_areas', 'wards', 'polling_units'],
            'lga' => ['states', 'local_government_areas', 'wards', 'polling_units'],
            default => [],
        };

        foreach (array_intersect($requiredDatasets, $available) as $dataset) {
            if ((int) data_get($provisioning, "summary.{$dataset}.imported", 0) < 1) {
                return 'Campaign location setup completed without required '.str_replace('_', ' ', $dataset).' data for the licensed scope.';
            }
        }

        return null;
    }

    private function requiresFullNationalIntegrity(LocalLicense $license, array $provisioning): bool
    {
        $package = strtolower((string) $license->package_type);
        $scope = strtolower((string) ($license->scope?->scope_type ?: data_get($provisioning, 'scope_type')));
        $dataProfile = strtolower((string) data_get($provisioning, 'data_profile', 'full'));

        return $dataProfile === 'full'
            && ($package === 'presidential' || in_array($scope, ['presidential', 'national'], true));
    }

    private function counts(): array
    {
        return [
            'regions' => Region::query()->count(),
            'states' => State::query()->count(),
            'senatorial_districts' => SenatorialDistrict::query()->count(),
            'federal_constituencies' => FederalConstituency::query()->count(),
            'lgas' => LocalGovernmentArea::query()->count(),
            'wards' => Ward::query()->count(),
            'polling_units' => PollingUnit::query()->count(),
        ];
    }
}
