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
        $stateCode = $this->code($scope, 'state_code', 'inec_state_code');
        $stateId = $stateCode
            ? $this->locationIdByCode('states', 'inec_state_code', $stateCode)
            : null;
        $stateId ??= $this->locationId('states', $stateName);
        if (!$stateId) {
            throw new RuntimeException("The licensed state [{$stateName}] was not found in the imported location data.");
        }

        $resolved = ['state_id' => $stateId];

        if ($scopeType === 'senatorial_district') {
            $name = $this->name($scope, 'senatorial_district_name', true);
            $code = $this->code($scope, 'senatorial_district_code', 'inec_senatorial_district_code');
            $resolved['senatorial_district_id'] = ($code ? $this->boundaryIdByCode('senatorial_districts', 'inec_senatorial_district_code', $stateId, $code) : null)
                ?: $this->boundaryId('senatorial_districts', $stateId, $name)
                ?: throw new RuntimeException("The licensed senatorial district [{$name}] was not found in the imported location data.");
        }

        if ($scopeType === 'federal_constituency') {
            $name = $this->name($scope, 'federal_constituency_name', true);
            $code = $this->code($scope, 'federal_constituency_code', 'inec_federal_constituency_code');
            $resolved['federal_constituency_id'] = ($code ? $this->boundaryIdByCode('federal_constituencies', 'inec_federal_constituency_code', $stateId, $code) : null)
                ?: $this->boundaryId('federal_constituencies', $stateId, $name)
                ?: throw new RuntimeException("The licensed federal constituency [{$name}] was not found in the imported location data.");
            $resolved['senatorial_district_id'] = $this->relatedId('federal_constituencies', $resolved['federal_constituency_id'], 'senatorial_district_id');
        }

        if ($scopeType === 'lga') {
            $name = $this->name($scope, 'lga_name', true);
            $code = $this->code($scope, 'lga_code', 'inec_lga_code');
            $resolved['lga_id'] = ($code ? $this->boundaryIdByCode('local_government_areas', 'inec_lga_code', $stateId, $code) : null)
                ?: $this->boundaryId('local_government_areas', $stateId, $name)
                ?: throw new RuntimeException("The licensed LGA [{$name}] was not found in the imported location data.");
            $resolved['senatorial_district_id'] = $this->relatedId('local_government_areas', $resolved['lga_id'], 'senatorial_district_id');
            $resolved['federal_constituency_id'] = $this->relatedId('local_government_areas', $resolved['lga_id'], 'federal_constituency_id');
        }

        $scope->forceFill(array_filter($resolved, fn ($value) => $value !== null))->save();

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

    private function code(LocalLicenseScope $scope, string ...$fields): ?string
    {
        $raw = $scope->raw_payload ?: [];

        foreach ($fields as $field) {
            $value = data_get($raw, $field);
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function locationId(string $table, string $name): ?int
    {
        if (!Schema::hasTable($table)) {
            return null;
        }

        return $this->integerId($this->matchingName(DB::table($table), $name)->value('id'));
    }

    private function locationIdByCode(string $table, string $column, string $code): ?int
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return null;
        }

        return $this->integerId(DB::table($table)->where($column, $code)->value('id'));
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

    private function boundaryIdByCode(string $table, string $column, int $stateId, string $code): ?int
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return null;
        }

        return $this->integerId(DB::table($table)
            ->where('state_id', $stateId)
            ->where($column, $code)
            ->value('id'));
    }

    private function relatedId(string $table, ?int $id, string $column): ?int
    {
        if (!$id || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return null;
        }

        return $this->integerId(DB::table($table)->where('id', $id)->value($column));
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
