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
