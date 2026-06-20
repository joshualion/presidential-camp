<?php

namespace App\Models\Concerns;

use App\Services\PackageScopeService;
use App\Support\SafeDatabase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait WithinLicensedScope
{
    public function scopeWithinLicensedScope(Builder $query, ?PackageScopeService $scope = null): Builder
    {
        $scope ??= app(PackageScopeService::class);
        $model = $query->getModel();

        if (!$scope->hasLocalLicense()) {
            return $query;
        }

        return match (true) {
            $this->hasLicensedScopeColumn($model->getTable(), 'state_id') && $scope->stateId() => $query->where($model->getTable().'.state_id', $scope->stateId()),
            $this->hasLicensedScopeColumn($model->getTable(), 'senatorial_district_id') && $scope->senatorialDistrictId() => $query->where($model->getTable().'.senatorial_district_id', $scope->senatorialDistrictId()),
            $this->hasLicensedScopeColumn($model->getTable(), 'federal_constituency_id') && $scope->federalConstituencyId() => $query->where($model->getTable().'.federal_constituency_id', $scope->federalConstituencyId()),
            $this->hasLicensedScopeColumn($model->getTable(), 'lga_id') && $scope->lgaId() => $query->where($model->getTable().'.lga_id', $scope->lgaId()),
            default => $query,
        };
    }

    private function hasLicensedScopeColumn(string $table, string $column): bool
    {
        return SafeDatabase::hasTable($table) && Schema::hasColumn($table, $column);
    }
}
