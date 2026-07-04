<?php

namespace App\Services;

use App\Models\ElectionIncident;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\PollingUnitResult;
use App\Models\SenatorialDistrict;
use App\Models\User;
use App\Models\Vote;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class LocationScopeService
{
    public const NATIONAL = 'national';
    public const REGION = 'region';
    public const STATE = 'state';
    public const SENATORIAL = 'senatorial';
    public const FEDERAL = 'federal';
    public const LGA = 'lga';
    public const WARD = 'ward';
    public const POLLING_UNIT = 'polling_unit';

    public function getScopeType(?User $user): string
    {
        return match ($user?->access_level) {
            'regionaladmin' => self::REGION,
            'stateadmin' => self::STATE,
            'senatorialadmin' => self::SENATORIAL,
            'federaladmin' => self::FEDERAL,
            'lgaadmin' => self::LGA,
            'wardadmin' => self::WARD,
            'puadmin', 'pollingunitadmin', 'user' => self::POLLING_UNIT,
            default => self::NATIONAL,
        };
    }

    public function getScopeId(?User $user): ?int
    {
        return match ($this->getScopeType($user)) {
            self::REGION => $user?->region_id,
            self::STATE => $user?->state_id,
            self::SENATORIAL => $user?->senatorial_district_id,
            self::FEDERAL => $user?->federal_constituency_id,
            self::LGA => $user?->lga_id,
            self::WARD => $user?->ward_id,
            self::POLLING_UNIT => $user?->polling_unit_id,
            default => null,
        };
    }

    public function applyScope(EloquentBuilder|QueryBuilder $query, ?User $user, ?string $subject = null, ?string $table = null): EloquentBuilder|QueryBuilder
    {
        $scopeType = $this->getScopeType($user);
        $scopeId = $this->getScopeId($user);
        $subject ??= $this->inferSubject($query);

        if ($scopeType === self::NATIONAL) {
            return $this->applyLicensedScope($query, $subject);
        }

        if (empty($scopeId)) {
            return $query->whereRaw('1 = 0');
        }

        $query = match ($subject) {
            'users', 'members' => $this->applyUserScope($query, $scopeType, $scopeId, $table ?? 'users'),
            'polling_units' => $this->applyPollingUnitScope($query, $scopeType, $scopeId, $table ?? 'polling_units'),
            'wards' => $this->applyWardScope($query, $scopeType, $scopeId, $table ?? 'wards'),
            'lgas', 'local_government_areas' => $this->applyLgaScope($query, $scopeType, $scopeId, $table ?? 'local_government_areas'),
            'votes' => $this->applyVoteScope($query, $scopeType, $scopeId, $table ?? 'votes'),
            'incidents', 'election_incidents' => $this->applyIncidentScope($query, $scopeType, $scopeId, $table ?? 'election_incidents'),
            'polling_unit_results', 'election_results' => $this->applyPollingUnitResultScope($query, $scopeType, $scopeId, $table ?? 'polling_unit_results'),
            default => $this->applyUserScope($query, $scopeType, $scopeId, $table),
        };

        return $this->applyLicensedScope($query, $subject);
    }

    public function applyBoundaryFilter(EloquentBuilder|QueryBuilder $query, string $boundaryType, int $boundaryId, ?string $subject = null, ?string $table = null): EloquentBuilder|QueryBuilder
    {
        $subject ??= $this->inferSubject($query);

        $query = match ($subject) {
            'users', 'members' => $this->applyUserScope($query, $boundaryType, $boundaryId, $table ?? 'users'),
            'polling_units' => $this->applyPollingUnitScope($query, $boundaryType, $boundaryId, $table ?? 'polling_units'),
            'wards' => $this->applyWardScope($query, $boundaryType, $boundaryId, $table ?? 'wards'),
            'lgas', 'local_government_areas' => $this->applyLgaScope($query, $boundaryType, $boundaryId, $table ?? 'local_government_areas'),
            'votes' => $this->applyVoteScope($query, $boundaryType, $boundaryId, $table ?? 'votes'),
            'incidents', 'election_incidents' => $this->applyIncidentScope($query, $boundaryType, $boundaryId, $table ?? 'election_incidents'),
            'polling_unit_results', 'election_results' => $this->applyPollingUnitResultScope($query, $boundaryType, $boundaryId, $table ?? 'polling_unit_results'),
            default => $query,
        };

        return $this->applyLicensedScope($query, $subject);
    }

    public function resolveRecordLocation(object $record): array
    {
        $pollingUnit = $this->resolvePollingUnit($record);
        $ward = $pollingUnit?->ward ?? $record->ward ?? null;
        $lga = $ward?->localGovernmentArea ?? $pollingUnit?->ward?->localGovernmentArea ?? $record->lga ?? $record->localGovernmentArea ?? null;
        $state = $lga?->state ?? $record->state ?? null;
        $senatorialDistrict = $pollingUnit?->senatorialDistrict ?? $lga?->senatorialDistrict ?? $record->senatorialDistrict ?? null;
        $federalConstituency = $pollingUnit?->federalConstituency ?? $lga?->federalConstituency ?? $record->federalConstituency ?? null;

        return [
            'polling_unit' => $pollingUnit,
            'ward' => $ward,
            'lga' => $lga,
            'state' => $state,
            'senatorial_district' => $senatorialDistrict,
            'federal_constituency' => $federalConstituency,
        ];
    }

    private function applyUserScope(EloquentBuilder|QueryBuilder $query, string $scopeType, int $scopeId, ?string $table = 'users'): EloquentBuilder|QueryBuilder
    {
        return match ($scopeType) {
            self::REGION => $this->whereColumn($query, $table, 'region_id', $scopeId),
            self::STATE => $this->whereColumn($query, $table, 'state_id', $scopeId),
            self::SENATORIAL => $this->whereDirectBoundaryWithPollingUnitFallback($query, $table ?? 'users', 'senatorial_district_id', $scopeType, $scopeId),
            self::FEDERAL => $this->whereDirectBoundaryWithPollingUnitFallback($query, $table ?? 'users', 'federal_constituency_id', $scopeType, $scopeId),
            self::LGA => $this->whereColumn($query, $table, 'lga_id', $scopeId),
            self::WARD => $this->whereColumn($query, $table, 'ward_id', $scopeId),
            self::POLLING_UNIT => $this->whereColumn($query, $table, 'polling_unit_id', $scopeId),
            default => $query,
        };
    }

    private function applyVoteScope(EloquentBuilder|QueryBuilder $query, string $scopeType, int $scopeId, string $table): EloquentBuilder|QueryBuilder
    {
        return match ($scopeType) {
            self::REGION => $this->whereColumn($query, $table, 'region_id', $scopeId),
            self::STATE => $this->whereDirectBoundaryWithPollingUnitFallback($query, $table, 'state_id', $scopeType, $scopeId),
            self::SENATORIAL => $this->whereDirectBoundaryWithPollingUnitFallback($query, $table, 'senatorial_district_id', $scopeType, $scopeId),
            self::FEDERAL => $this->whereDirectBoundaryWithPollingUnitFallback($query, $table, 'federal_constituency_id', $scopeType, $scopeId),
            self::LGA => $this->whereDirectBoundaryWithPollingUnitFallback($query, $table, 'lga_id', $scopeType, $scopeId),
            self::WARD => $this->whereDirectBoundaryWithPollingUnitFallback($query, $table, 'ward_id', $scopeType, $scopeId),
            self::POLLING_UNIT => $this->whereColumn($query, $table, 'polling_unit_id', $scopeId),
            default => $query,
        };
    }

    private function applyIncidentScope(EloquentBuilder|QueryBuilder $query, string $scopeType, int $scopeId, string $table): EloquentBuilder|QueryBuilder
    {
        return $this->applyVoteScope($query, $scopeType, $scopeId, $table);
    }

    private function applyPollingUnitScope(EloquentBuilder|QueryBuilder $query, string $scopeType, int $scopeId, string $table): EloquentBuilder|QueryBuilder
    {
        return match ($scopeType) {
            self::REGION => $this->whereRelated($query, 'ward.localGovernmentArea.state', 'region_id', $scopeId, $table, 'states.region_id'),
            self::STATE => $this->whereRelated($query, 'ward.localGovernmentArea', 'state_id', $scopeId, $table, 'local_government_areas.state_id'),
            self::SENATORIAL => $this->wherePollingUnitBoundaryWithLgaFallback($query, $table, 'senatorial_district_id', $scopeId),
            self::FEDERAL => $this->wherePollingUnitBoundaryWithLgaFallback($query, $table, 'federal_constituency_id', $scopeId),
            self::LGA => $this->whereRelated($query, 'ward', 'lga_id', $scopeId, $table, 'wards.lga_id'),
            self::WARD => $this->whereColumn($query, $table, 'ward_id', $scopeId),
            self::POLLING_UNIT => $this->whereColumn($query, $table, 'id', $scopeId),
            default => $query,
        };
    }

    private function applyWardScope(EloquentBuilder|QueryBuilder $query, string $scopeType, int $scopeId, string $table): EloquentBuilder|QueryBuilder
    {
        return match ($scopeType) {
            self::REGION => $this->whereRelated($query, 'localGovernmentArea.state', 'region_id', $scopeId, $table, 'states.region_id'),
            self::STATE => $this->whereRelated($query, 'localGovernmentArea', 'state_id', $scopeId, $table, 'local_government_areas.state_id'),
            self::SENATORIAL => $this->whereRelated($query, 'localGovernmentArea', 'senatorial_district_id', $scopeId, $table, 'local_government_areas.senatorial_district_id'),
            self::FEDERAL => $this->whereRelated($query, 'localGovernmentArea', 'federal_constituency_id', $scopeId, $table, 'local_government_areas.federal_constituency_id'),
            self::LGA => $this->whereColumn($query, $table, 'lga_id', $scopeId),
            self::WARD => $this->whereColumn($query, $table, 'id', $scopeId),
            self::POLLING_UNIT => $this->whereRelated($query, 'pollingUnits', 'id', $scopeId, $table, 'polling_units.id'),
            default => $query,
        };
    }

    private function applyLgaScope(EloquentBuilder|QueryBuilder $query, string $scopeType, int $scopeId, string $table): EloquentBuilder|QueryBuilder
    {
        return match ($scopeType) {
            self::REGION => $this->whereRelated($query, 'state', 'region_id', $scopeId, $table, 'states.region_id'),
            self::STATE => $this->whereColumn($query, $table, 'state_id', $scopeId),
            self::SENATORIAL => $this->whereColumn($query, $table, 'senatorial_district_id', $scopeId),
            self::FEDERAL => $this->whereColumn($query, $table, 'federal_constituency_id', $scopeId),
            self::LGA => $this->whereColumn($query, $table, 'id', $scopeId),
            self::WARD => $this->whereRelated($query, 'wards', 'id', $scopeId, $table, 'wards.id'),
            self::POLLING_UNIT => $this->whereRelated($query, 'wards.pollingUnits', 'id', $scopeId, $table, 'polling_units.id'),
            default => $query,
        };
    }

    private function applyPollingUnitResultScope(EloquentBuilder|QueryBuilder $query, string $scopeType, int $scopeId, string $table): EloquentBuilder|QueryBuilder
    {
        return match ($scopeType) {
            self::REGION => $this->wherePollingUnitScope($query, $table, $scopeType, $scopeId),
            self::STATE => $this->whereDirectBoundaryWithPollingUnitFallback($query, $table, 'state_id', $scopeType, $scopeId),
            self::SENATORIAL => $this->whereDirectBoundaryWithPollingUnitFallback($query, $table, 'senatorial_district_id', $scopeType, $scopeId),
            self::FEDERAL => $this->whereDirectBoundaryWithPollingUnitFallback($query, $table, 'federal_constituency_id', $scopeType, $scopeId),
            self::LGA => $this->whereDirectBoundaryWithPollingUnitFallback($query, $table, 'lga_id', $scopeType, $scopeId),
            self::WARD => $this->whereDirectBoundaryWithPollingUnitFallback($query, $table, 'ward_id', $scopeType, $scopeId),
            self::POLLING_UNIT => $this->whereColumn($query, $table, 'polling_unit_id', $scopeId),
            default => $query,
        };
    }

    private function whereColumn(EloquentBuilder|QueryBuilder $query, ?string $table, string $column, int $value): EloquentBuilder|QueryBuilder
    {
        return $query->where($table ? "{$table}.{$column}" : $column, $value);
    }

    private function whereRelated(EloquentBuilder|QueryBuilder $query, string $relation, string $column, int $value, string $table, string $queryColumn): EloquentBuilder|QueryBuilder
    {
        if ($query instanceof EloquentBuilder) {
            return $query->whereHas($relation, fn ($relatedQuery) => $relatedQuery->where($column, $value));
        }

        return $query->where($queryColumn, $value);
    }

    private function wherePollingUnitBoundary(EloquentBuilder|QueryBuilder $query, string $table, string $boundaryColumn, int $boundaryId): EloquentBuilder|QueryBuilder
    {
        if ($query instanceof EloquentBuilder) {
            return $query->whereHas('pollingUnit', fn ($pollingUnitQuery) => $pollingUnitQuery->where($boundaryColumn, $boundaryId));
        }

        return $query->whereIn("{$table}.polling_unit_id", function ($pollingUnitQuery) use ($boundaryColumn, $boundaryId) {
            $pollingUnitQuery
                ->select('polling_units.id')
                ->from('polling_units')
                ->where($boundaryColumn, $boundaryId);
        });
    }

    private function whereDirectBoundaryWithPollingUnitFallback(EloquentBuilder|QueryBuilder $query, string $table, string $column, string $scopeType, int $scopeId): EloquentBuilder|QueryBuilder
    {
        return $query->where(function ($boundaryQuery) use ($table, $column, $scopeType, $scopeId) {
            $this->whereColumn($boundaryQuery, $table, $column, $scopeId);

            $boundaryQuery->orWhere(function ($fallbackQuery) use ($table, $column, $scopeType, $scopeId) {
                $fallbackQuery->whereNull("{$table}.{$column}");
                $this->wherePollingUnitScope($fallbackQuery, $table, $scopeType, $scopeId);
            });
        });
    }

    private function wherePollingUnitBoundaryWithLgaFallback(EloquentBuilder|QueryBuilder $query, string $table, string $column, int $scopeId): EloquentBuilder|QueryBuilder
    {
        return $query->where(function ($boundaryQuery) use ($table, $column, $scopeId) {
            $this->whereColumn($boundaryQuery, $table, $column, $scopeId);

            $boundaryQuery->orWhere(function ($fallbackQuery) use ($table, $column, $scopeId) {
                $fallbackQuery->whereNull("{$table}.{$column}");

                if ($fallbackQuery instanceof EloquentBuilder) {
                    $fallbackQuery->whereHas('ward.localGovernmentArea', fn ($lgaQuery) => $lgaQuery->where($column, $scopeId));

                    return;
                }

                $fallbackQuery->whereExists(function ($lgaQuery) use ($table, $column, $scopeId) {
                    $lgaQuery
                        ->selectRaw('1')
                        ->from('polling_units as scoped_polling_units')
                        ->join('wards as scoped_wards', 'scoped_polling_units.ward_id', '=', 'scoped_wards.id')
                        ->join('local_government_areas as scoped_lgas', 'scoped_wards.lga_id', '=', 'scoped_lgas.id')
                        ->whereColumn('scoped_polling_units.id', "{$table}.id")
                        ->where("scoped_lgas.{$column}", $scopeId);
                });
            });
        });
    }

    private function wherePollingUnitScope(EloquentBuilder|QueryBuilder $query, string $table, string $scopeType, int $scopeId): EloquentBuilder|QueryBuilder
    {
        if ($query instanceof EloquentBuilder) {
            return $query->whereHas('pollingUnit', function ($pollingUnitQuery) use ($scopeType, $scopeId) {
                $this->applyPollingUnitScope($pollingUnitQuery, $scopeType, $scopeId, 'polling_units');
            });
        }

        return $query->whereIn("{$table}.polling_unit_id", function ($pollingUnitQuery) use ($scopeType, $scopeId) {
            $pollingUnitQuery
                ->select('polling_units.id')
                ->from('polling_units')
                ->leftJoin('wards', 'polling_units.ward_id', '=', 'wards.id')
                ->leftJoin('local_government_areas', 'wards.lga_id', '=', 'local_government_areas.id')
                ->leftJoin('states', 'local_government_areas.state_id', '=', 'states.id');

            $this->applyPollingUnitScope($pollingUnitQuery, $scopeType, $scopeId, 'polling_units');
        });
    }

    private function inferSubject(EloquentBuilder|QueryBuilder $query): ?string
    {
        if ($query instanceof EloquentBuilder) {
            return match ($query->getModel()::class) {
                User::class => 'users',
                PollingUnit::class => 'polling_units',
                Ward::class => 'wards',
                LocalGovernmentArea::class => 'local_government_areas',
                Vote::class => 'votes',
                ElectionIncident::class => 'election_incidents',
                PollingUnitResult::class => 'polling_unit_results',
                default => null,
            };
        }

        return $query->from;
    }

    private function applyLicensedScope(EloquentBuilder|QueryBuilder $query, ?string $subject): EloquentBuilder|QueryBuilder
    {
        return app(LicensedScopeQueryService::class)->applyToSubject($query, $subject);
    }

    private function resolvePollingUnit(object $record): ?PollingUnit
    {
        if ($record instanceof PollingUnit) {
            return $record->loadMissing(['ward.localGovernmentArea.state', 'senatorialDistrict', 'federalConstituency']);
        }

        if (isset($record->pollingUnit) && $record->pollingUnit instanceof PollingUnit) {
            return $record->pollingUnit->loadMissing(['ward.localGovernmentArea.state', 'senatorialDistrict', 'federalConstituency']);
        }

        if (!empty($record->polling_unit_id)) {
            return PollingUnit::with(['ward.localGovernmentArea.state', 'senatorialDistrict', 'federalConstituency'])->find($record->polling_unit_id);
        }

        return null;
    }
}
