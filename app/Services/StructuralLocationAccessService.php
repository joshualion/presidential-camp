<?php

namespace App\Services;

use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Builder;

class StructuralLocationAccessService
{
    public function __construct(
        private readonly LocationScopeService $scopeService,
        private readonly LicensedScopeQueryService $licensedScope
    )
    {
    }

    public function canCreateOrDelete(?User $user): bool
    {
        return $user?->access_level === 'superadmin';
    }

    public function assertCanCreateOrDelete(?User $user): void
    {
        abort_unless($this->canCreateOrDelete($user), 403);
    }

    public function assertCanUpdate(?User $user, object $record): void
    {
        abort_unless($this->isWithinScope($user, $record), 403);
    }

    public function applyScope(Builder $query, ?User $user, string $subject): Builder
    {
        if (!$user || in_array($user->access_level, ['superadmin', 'nationaladmin'], true)) {
            return $this->licensedScope->applyToSubject($query, $subject);
        }

        $query = match ($subject) {
            'regions' => $this->scopeRegions($query, $user),
            'states' => $this->scopeStates($query, $user),
            'senatorial_districts' => $this->scopeSenatorialDistricts($query, $user),
            'federal_constituencies' => $this->scopeFederalConstituencies($query, $user),
            'local_government_areas' => $this->scopeLgas($query, $user),
            'wards' => $this->scopeWards($query, $user),
            'polling_units' => $this->scopePollingUnits($query, $user),
            default => $query->whereRaw('1 = 0'),
        };

        return $this->licensedScope->applyToSubject($query, $subject);
    }

    public function isWithinScope(?User $user, object $record): bool
    {
        if (!$user) {
            return false;
        }

        if (in_array($user->access_level, ['superadmin', 'nationaladmin'], true)) {
            return true;
        }

        $query = $record::query()->whereKey($record->getKey());
        $subject = match (true) {
            $record instanceof Region => 'regions',
            $record instanceof State => 'states',
            $record instanceof SenatorialDistrict => 'senatorial_districts',
            $record instanceof FederalConstituency => 'federal_constituencies',
            $record instanceof LocalGovernmentArea => 'local_government_areas',
            $record instanceof Ward => 'wards',
            $record instanceof PollingUnit => 'polling_units',
            default => null,
        };

        return $subject ? $this->applyScope($query, $user, $subject)->exists() : false;
    }

    private function scopeRegions(Builder $query, User $user): Builder
    {
        return match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => $query->where('id', $user->region_id),
            LocationScopeService::STATE,
            LocationScopeService::SENATORIAL,
            LocationScopeService::FEDERAL,
            LocationScopeService::LGA,
            LocationScopeService::WARD,
            LocationScopeService::POLLING_UNIT => $query->whereHas('states', fn (Builder $stateQuery) => $this->scopeStates($stateQuery, $user)),
            default => $query,
        };
    }

    private function scopeStates(Builder $query, User $user): Builder
    {
        return match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => $query->where('region_id', $user->region_id),
            LocationScopeService::STATE => $query->where('id', $user->state_id),
            LocationScopeService::SENATORIAL => $query->whereHas('senatorialDistricts', fn (Builder $districtQuery) => $districtQuery->where('id', $user->senatorial_district_id)),
            LocationScopeService::FEDERAL => $query->whereHas('federalConstituencies', fn (Builder $constituencyQuery) => $constituencyQuery->where('id', $user->federal_constituency_id)),
            LocationScopeService::LGA => $query->whereHas('localGovernmentAreas', fn (Builder $lgaQuery) => $lgaQuery->where('id', $user->lga_id)),
            LocationScopeService::WARD => $query->whereHas('localGovernmentAreas.wards', fn (Builder $wardQuery) => $wardQuery->where('id', $user->ward_id)),
            LocationScopeService::POLLING_UNIT => $query->whereHas('localGovernmentAreas.wards.pollingUnits', fn (Builder $puQuery) => $puQuery->where('id', $user->polling_unit_id)),
            default => $query,
        };
    }

    private function scopeSenatorialDistricts(Builder $query, User $user): Builder
    {
        return match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => $query->whereHas('state', fn (Builder $stateQuery) => $stateQuery->where('region_id', $user->region_id)),
            LocationScopeService::STATE => $query->where('state_id', $user->state_id),
            LocationScopeService::SENATORIAL => $query->where('id', $user->senatorial_district_id),
            LocationScopeService::FEDERAL,
            LocationScopeService::LGA,
            LocationScopeService::WARD,
            LocationScopeService::POLLING_UNIT => $query->whereIn('id', $this->scopedPollingUnits($user)->select('senatorial_district_id')),
            default => $query,
        };
    }

    private function scopeFederalConstituencies(Builder $query, User $user): Builder
    {
        return match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => $query->whereHas('state', fn (Builder $stateQuery) => $stateQuery->where('region_id', $user->region_id)),
            LocationScopeService::STATE => $query->where('state_id', $user->state_id),
            LocationScopeService::SENATORIAL => $query->where('senatorial_district_id', $user->senatorial_district_id),
            LocationScopeService::FEDERAL => $query->where('id', $user->federal_constituency_id),
            LocationScopeService::LGA,
            LocationScopeService::WARD,
            LocationScopeService::POLLING_UNIT => $query->whereIn('id', $this->scopedPollingUnits($user)->select('federal_constituency_id')),
            default => $query,
        };
    }

    private function scopeLgas(Builder $query, User $user): Builder
    {
        $this->scopeService->applyScope($query, $user, 'local_government_areas', 'local_government_areas');

        return $query;
    }

    private function scopeWards(Builder $query, User $user): Builder
    {
        $this->scopeService->applyScope($query, $user, 'wards', 'wards');

        return $query;
    }

    private function scopePollingUnits(Builder $query, User $user): Builder
    {
        $this->scopeService->applyScope($query, $user, 'polling_units', 'polling_units');

        return $query;
    }

    private function scopedPollingUnits(User $user): Builder
    {
        $query = PollingUnit::query();
        $this->scopeService->applyScope($query, $user, 'polling_units', 'polling_units');

        return $query;
    }
}
