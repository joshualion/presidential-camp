<?php

namespace App\Services;

use App\Models\LocalLicense;
use App\Models\LocalLicenseScope;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class LocalLicenseScopeReconciliationService
{
    public function reconcile(LocalLicense $license): LocalLicenseScope
    {
        $scope = $license->scope;

        if (!$scope) {
            throw new RuntimeException('The active license does not contain a location scope.');
        }

        $scopeType = strtolower((string) $scope->scope_type);
        if ($scopeType === 'national') {
            return $scope;
        }

        $stateName = $this->name($scope, 'state_name', $scopeType === 'state');
        $stateId = $this->locationId('states', $stateName);
        if (!$stateId) {
            throw new RuntimeException("The licensed state [{$stateName}] was not found in the imported location data.");
        }

        $resolved = ['state_id' => $stateId];

        if ($scopeType === 'senatorial_district') {
            $name = $this->name($scope, 'senatorial_district_name', true);
            $resolved['senatorial_district_id'] = $this->boundaryId('senatorial_districts', $stateId, $name)
                ?: throw new RuntimeException("The licensed senatorial district [{$name}] was not found in the imported location data.");
        }

        if ($scopeType === 'federal_constituency') {
            $name = $this->name($scope, 'federal_constituency_name', true);
            $resolved['federal_constituency_id'] = $this->boundaryId('federal_constituencies', $stateId, $name)
                ?: throw new RuntimeException("The licensed federal constituency [{$name}] was not found in the imported location data.");
        }

        if ($scopeType === 'lga') {
            $name = $this->name($scope, 'lga_name', true);
            $resolved['lga_id'] = $this->boundaryId('local_government_areas', $stateId, $name)
                ?: throw new RuntimeException("The licensed LGA [{$name}] was not found in the imported location data.");
        }

        $scope->forceFill($resolved)->save();

        return $scope->fresh();
    }

    private function name(LocalLicenseScope $scope, string $field, bool $scopeNameFallback): string
    {
        $raw = $scope->raw_payload ?: [];
        $value = $scope->{$field}
            ?: data_get($raw, $field)
            ?: ($scopeNameFallback ? $scope->scope_name : null)
            ?: ($scopeNameFallback ? data_get($raw, 'scope_name') : null);

        $value = trim((string) $value);
        if ($value === '') {
            throw new RuntimeException('The license scope is missing the location name required for local reconciliation.');
        }

        return $value;
    }

    private function locationId(string $table, string $name): ?int
    {
        if (!Schema::hasTable($table)) {
            return null;
        }

        return $this->integerId($this->matchingName(DB::table($table), $name)->value('id'));
    }

    private function boundaryId(string $table, int $stateId, string $name): ?int
    {
        if (!Schema::hasTable($table)) {
            return null;
        }

        $query = DB::table($table)->where('state_id', $stateId);
        if (Schema::hasColumn($table, 'normalized_name')) {
            $query->where('normalized_name', $this->normalized($name));
        } else {
            $this->matchingName($query, $name);
        }

        return $this->integerId($query->value('id'));
    }

    private function matchingName(Builder $query, string $name): Builder
    {
        return $query->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))]);
    }

    private function normalized(string $name): string
    {
        return Str::of($name)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }

    private function integerId(mixed $id): ?int
    {
        return $id ? (int) $id : null;
    }
}
