<?php

namespace App\Services;

use App\Models\ElectionIncident;
use App\Models\PollingUnitResult;
use App\Models\User;
use App\Support\SafeDatabase;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LicensedScopeQueryService
{
    public function __construct(private PackageScopeService $scopeService)
    {
    }

    public function applies(): bool
    {
        try {
            return $this->scopeService->isSelfHosted()
                && $this->scopeService->hasCampaignScope()
                && !$this->isNationalScope();
        } catch (Throwable) {
            return false;
        }
    }

    public function applyToStatesQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $this->applyLocationQuery($query, 'states');
    }

    public function applyToRegionsQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        if (!$this->applies()) {
            return $query;
        }

        $stateId = $this->licensedStateId();

        if (!$stateId || !SafeDatabase::hasTable('states')) {
            return $this->disallow($query);
        }

        $table = $this->tableFor($query, 'regions');

        return $query->whereIn("{$table}.id", function ($stateQuery) use ($stateId) {
            $stateQuery
                ->select('states.region_id')
                ->from('states')
                ->where('states.id', $stateId);
        });
    }

    public function applyToSenatorialDistrictsQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $this->applyLocationQuery($query, 'senatorial_districts');
    }

    public function applyToFederalConstituenciesQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $this->applyLocationQuery($query, 'federal_constituencies');
    }

    public function applyToLgasQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $this->applyLocationQuery($query, 'local_government_areas');
    }

    public function applyToWardsQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $this->applyLocationQuery($query, 'wards');
    }

    public function applyToPollingUnitsQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $this->applyLocationQuery($query, 'polling_units');
    }

    public function applyToUsersQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $this->applyDirectBoundaryQuery($query, $this->tableFor($query, 'users'), true);
    }

    public function applyToMembersQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $this->applyToUsersQuery($query);
    }

    public function applyToAgentsQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $this->applyToUsersQuery($query);
    }

    public function applyToResultsQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $this->applyDirectBoundaryQuery($query, $this->tableFor($query, 'polling_unit_results'), true);
    }

    public function applyToVotesQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $this->applyDirectBoundaryQuery($query, $this->tableFor($query, 'votes'), true);
    }

    public function applyToIncidentsQuery(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $this->applyDirectBoundaryQuery($query, $this->tableFor($query, 'election_incidents'), true);
    }

    public function applyToEvidenceQuery(EloquentBuilder|QueryBuilder $query, string $incidentForeignKey = 'election_incident_id'): EloquentBuilder|QueryBuilder
    {
        if (!$this->applies()) {
            return $query;
        }

        if (!SafeDatabase::hasTable('election_incidents')) {
            return $this->disallow($query);
        }

        return $query->whereIn($incidentForeignKey, function ($incidentQuery) {
            $incidentQuery->select('election_incidents.id')->from('election_incidents');
            $this->applyToIncidentsQuery($incidentQuery);
        });
    }

    public function applyToSubject(EloquentBuilder|QueryBuilder $query, ?string $subject): EloquentBuilder|QueryBuilder
    {
        return match ($subject) {
            'regions' => $this->applyToRegionsQuery($query),
            'states' => $this->applyToStatesQuery($query),
            'senatorial_districts' => $this->applyToSenatorialDistrictsQuery($query),
            'federal_constituencies' => $this->applyToFederalConstituenciesQuery($query),
            'lgas', 'local_government_areas' => $this->applyToLgasQuery($query),
            'wards' => $this->applyToWardsQuery($query),
            'polling_units' => $this->applyToPollingUnitsQuery($query),
            'users', 'members' => $this->applyToUsersQuery($query),
            'agents' => $this->applyToAgentsQuery($query),
            'votes' => $this->applyToVotesQuery($query),
            'incidents', 'election_incidents' => $this->applyToIncidentsQuery($query),
            'polling_unit_results', 'election_results', 'results' => $this->applyToResultsQuery($query),
            'picture_evidences', 'video_evidences', 'evidence' => $this->applyToEvidenceQuery($query),
            default => $query,
        };
    }

    public function enforcementSummary(): array
    {
        $scope = $this->scopeService->current();

        return [
            'available' => true,
            'active' => $this->applies(),
            'scope_type' => $scope->scope_type,
            'state_id' => $this->licensedStateId(),
            'senatorial_district_id' => $scope->senatorial_district_id,
            'federal_constituency_id' => $scope->federal_constituency_id,
            'lga_id' => $scope->lga_id,
            'tables' => [
                'states' => SafeDatabase::hasTable('states'),
                'senatorial_districts' => SafeDatabase::hasTable('senatorial_districts'),
                'federal_constituencies' => SafeDatabase::hasTable('federal_constituencies'),
                'local_government_areas' => SafeDatabase::hasTable('local_government_areas'),
                'wards' => SafeDatabase::hasTable('wards'),
                'polling_units' => SafeDatabase::hasTable('polling_units'),
            ],
        ];
    }

    public function allowsPayload(array $payload): bool
    {
        return $this->payloadErrors($payload) === [];
    }

    public function payloadErrors(array $payload): array
    {
        if (!$this->applies()) {
            return [];
        }

        $checks = [
            'state_id' => ['method' => 'allowsState', 'label' => 'state'],
            'senatorial_district_id' => ['method' => 'allowsSenatorialDistrict', 'label' => 'senatorial district'],
            'federal_constituency_id' => ['method' => 'allowsFederalConstituency', 'label' => 'federal constituency'],
            'lga_id' => ['method' => 'allowsLga', 'label' => 'LGA'],
            'ward_id' => ['method' => 'allowsWard', 'label' => 'ward'],
            'polling_unit_id' => ['method' => 'allowsPollingUnit', 'label' => 'polling unit'],
            'pu_id' => ['method' => 'allowsPollingUnit', 'label' => 'polling unit'],
        ];

        $errors = [];

        foreach ($checks as $key => $check) {
            if (!array_key_exists($key, $payload) || $payload[$key] === null || $payload[$key] === '') {
                continue;
            }

            $allowed = $this->scopeService->{$check['method']}((int) $payload[$key]);

            if (!$allowed) {
                $errors[$key] = 'The selected '.$check['label'].' is outside the configured campaign scope.';
            }
        }

        return $errors;
    }

    public function assertPayloadWithinScope(array $payload): void
    {
        $errors = $this->payloadErrors($payload);

        if ($errors !== []) {
            abort(403, reset($errors));
        }
    }

    public function abortIfStateNotAllowed(?int $stateId): void
    {
        $this->abortUnless($this->scopeService->allowsState($stateId));
    }

    public function abortIfRegionNotAllowed(?int $regionId): void
    {
        if (!$this->applies() || !$regionId) {
            return;
        }

        $query = app('db')->table('regions')->where('regions.id', $regionId);
        $this->applyToRegionsQuery($query);

        $this->abortUnless($query->exists());
    }

    public function abortIfSenatorialDistrictNotAllowed(?int $id): void
    {
        $this->abortUnless($this->scopeService->allowsSenatorialDistrict($id));
    }

    public function abortIfFederalConstituencyNotAllowed(?int $id): void
    {
        $this->abortUnless($this->scopeService->allowsFederalConstituency($id));
    }

    public function abortIfLgaNotAllowed(?int $id): void
    {
        $this->abortUnless($this->scopeService->allowsLga($id));
    }

    public function abortIfWardNotAllowed(?int $id): void
    {
        $this->abortUnless($this->scopeService->allowsWard($id));
    }

    public function abortIfPollingUnitNotAllowed(?int $id): void
    {
        $this->abortUnless($this->scopeService->allowsPollingUnit($id));
    }

    public function abortIfUserNotAllowed(User $user): void
    {
        if (!$this->applies()) {
            return;
        }

        $this->abortUnless($this->recordIsAllowed($user, 'users'));
    }

    public function abortIfResultNotAllowed(PollingUnitResult $result): void
    {
        if (!$this->applies()) {
            return;
        }

        $this->abortUnless($this->recordIsAllowed($result, 'polling_unit_results'));
    }

    public function abortIfIncidentNotAllowed(ElectionIncident $incident): void
    {
        if (!$this->applies()) {
            return;
        }

        $this->abortUnless($this->recordIsAllowed($incident, 'election_incidents'));
    }

    public function abortIfEvidenceNotAllowed(Model $evidence): void
    {
        if (!$this->applies()) {
            return;
        }

        $incidentId = $evidence->getAttribute('election_incident_id');

        if (!$incidentId || !SafeDatabase::hasTable('election_incidents')) {
            $this->abortUnless(false);
        }

        $incident = ElectionIncident::query()->find($incidentId);
        $this->abortUnless($incident && $this->recordIsAllowed($incident, 'election_incidents'));
    }

    private function applyLocationQuery(EloquentBuilder|QueryBuilder $query, string $table): EloquentBuilder|QueryBuilder
    {
        if (!$this->applies()) {
            return $query;
        }

        return match ($table) {
            'states' => $this->applyStates($query),
            'senatorial_districts' => $this->applySenatorialDistricts($query),
            'federal_constituencies' => $this->applyFederalConstituencies($query),
            'local_government_areas' => $this->applyLgas($query),
            'wards' => $this->applyWards($query),
            'polling_units' => $this->applyPollingUnits($query),
            default => $this->disallow($query),
        };
    }

    private function applyStates(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        $scope = $this->scopeService->current();
        $stateId = $this->licensedStateId();

        return match ($scope->scope_type) {
            'state', 'senatorial_district', 'federal_constituency', 'lga' => $stateId
                ? $this->whereColumn($query, $this->tableFor($query, 'states'), 'id', $stateId)
                : $this->disallow($query),
            default => $this->disallow($query),
        };
    }

    private function applySenatorialDistricts(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        $scope = $this->scopeService->current();
        $table = $this->tableFor($query, 'senatorial_districts');

        return match ($scope->scope_type) {
            'state' => $scope->state_id ? $this->whereColumn($query, $table, 'state_id', $scope->state_id) : $this->disallow($query),
            'senatorial_district' => $scope->senatorial_district_id ? $this->whereColumn($query, $table, 'id', $scope->senatorial_district_id) : $this->disallow($query),
            'federal_constituency' => $scope->senatorial_district_id
                ? $this->whereColumn($query, $table, 'id', $scope->senatorial_district_id)
                : $this->constrainByRelatedColumn($query, $table, 'id', 'federal_constituencies', 'senatorial_district_id', 'id', $scope->federal_constituency_id),
            'lga' => $scope->senatorial_district_id
                ? $this->whereColumn($query, $table, 'id', $scope->senatorial_district_id)
                : $this->constrainByRelatedColumn($query, $table, 'id', 'local_government_areas', 'senatorial_district_id', 'id', $scope->lga_id),
            default => $this->disallow($query),
        };
    }

    private function applyFederalConstituencies(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        $scope = $this->scopeService->current();
        $table = $this->tableFor($query, 'federal_constituencies');

        return match ($scope->scope_type) {
            'state' => $scope->state_id ? $this->whereColumn($query, $table, 'state_id', $scope->state_id) : $this->disallow($query),
            'senatorial_district' => $scope->senatorial_district_id ? $this->whereColumn($query, $table, 'senatorial_district_id', $scope->senatorial_district_id) : $this->disallow($query),
            'federal_constituency' => $scope->federal_constituency_id ? $this->whereColumn($query, $table, 'id', $scope->federal_constituency_id) : $this->disallow($query),
            'lga' => $scope->federal_constituency_id
                ? $this->whereColumn($query, $table, 'id', $scope->federal_constituency_id)
                : $this->constrainByRelatedColumn($query, $table, 'id', 'local_government_areas', 'federal_constituency_id', 'id', $scope->lga_id),
            default => $this->disallow($query),
        };
    }

    private function applyLgas(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        $scope = $this->scopeService->current();
        $table = $this->tableFor($query, 'local_government_areas');

        return match ($scope->scope_type) {
            'state' => $scope->state_id ? $this->whereColumn($query, $table, 'state_id', $scope->state_id) : $this->disallow($query),
            'senatorial_district' => $scope->senatorial_district_id ? $this->whereColumn($query, $table, 'senatorial_district_id', $scope->senatorial_district_id) : $this->disallow($query),
            'federal_constituency' => $scope->federal_constituency_id ? $this->whereColumn($query, $table, 'federal_constituency_id', $scope->federal_constituency_id) : $this->disallow($query),
            'lga' => $scope->lga_id ? $this->whereColumn($query, $table, 'id', $scope->lga_id) : $this->disallow($query),
            default => $this->disallow($query),
        };
    }

    private function applyWards(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        $scope = $this->scopeService->current();
        $table = $this->tableFor($query, 'wards');

        if ($scope->scope_type === 'lga') {
            return $scope->lga_id ? $this->whereColumn($query, $table, 'lga_id', $scope->lga_id) : $this->disallow($query);
        }

        return $this->constrainByRelatedColumn(
            $query,
            $table,
            'lga_id',
            'local_government_areas',
            'id',
            $this->locationBoundaryColumn(),
            $this->locationBoundaryValue()
        );
    }

    private function applyPollingUnits(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        $scope = $this->scopeService->current();
        $table = $this->tableFor($query, 'polling_units');

        if (in_array($scope->scope_type, ['senatorial_district', 'federal_constituency'], true)) {
            $column = $scope->scope_type === 'senatorial_district' ? 'senatorial_district_id' : 'federal_constituency_id';
            $value = $scope->scope_type === 'senatorial_district' ? $scope->senatorial_district_id : $scope->federal_constituency_id;

            if ($value && $this->hasColumn('polling_units', $column)) {
                return $this->whereColumn($query, $table, $column, $value);
            }
        }

        if ($scope->scope_type === 'lga') {
            return $this->constrainByRelatedColumn($query, $table, 'ward_id', 'wards', 'id', 'lga_id', $scope->lga_id);
        }

        return $this->constrainPollingUnitsThroughLga($query, $table, $this->locationBoundaryColumn(), $this->locationBoundaryValue());
    }

    private function applyDirectBoundaryQuery(EloquentBuilder|QueryBuilder $query, string $table, bool $pollingUnitFallback = false): EloquentBuilder|QueryBuilder
    {
        if (!$this->applies()) {
            return $query;
        }

        $scope = $this->scopeService->current();
        $column = $this->directBoundaryColumn();
        $value = $this->directBoundaryValue();

        if (!$column || !$value) {
            return $this->disallow($query);
        }

        if ($this->hasColumn($this->baseTable($table), $column)) {
            if ($pollingUnitFallback) {
                return $query->where(function ($scopeQuery) use ($query, $table, $column, $value) {
                    $this->whereColumn($scopeQuery, $table, $column, $value);
                    $this->orWhereIndirectBoundaryFallbacks($scopeQuery, $table, $column);
                });
            }

            return $this->whereColumn($query, $table, $column, $value);
        }

        if ($pollingUnitFallback && $this->hasColumn($this->baseTable($table), 'polling_unit_id')) {
            return $query->whereIn("{$table}.polling_unit_id", function ($pollingUnitQuery) use ($scope) {
                $pollingUnitQuery->select('polling_units.id')->from('polling_units');
                $this->applyToPollingUnitsQuery($pollingUnitQuery);
            });
        }

        if ($scope->scope_type === 'state' && $this->hasColumn($this->baseTable($table), 'state_id')) {
            return $this->whereColumn($query, $table, 'state_id', (int) $scope->state_id);
        }

        return $this->disallow($query);
    }

    private function orWhereIndirectBoundaryFallbacks(EloquentBuilder|QueryBuilder $query, string $table, string $directColumn): void
    {
        $baseTable = $this->baseTable($table);

        if ($this->hasColumn($baseTable, 'lga_id')) {
            $query->orWhere(function ($fallbackQuery) use ($table, $directColumn) {
                $fallbackQuery->whereNull("{$table}.{$directColumn}")
                    ->whereIn("{$table}.lga_id", function ($lgaQuery) {
                        $lgaQuery->select('local_government_areas.id')->from('local_government_areas');
                        $this->applyToLgasQuery($lgaQuery);
                    });
            });
        }

        if ($this->hasColumn($baseTable, 'ward_id')) {
            $query->orWhere(function ($fallbackQuery) use ($table, $directColumn) {
                $fallbackQuery->whereNull("{$table}.{$directColumn}")
                    ->whereIn("{$table}.ward_id", function ($wardQuery) {
                        $wardQuery->select('wards.id')->from('wards');
                        $this->applyToWardsQuery($wardQuery);
                    });
            });
        }

        if ($this->hasColumn($baseTable, 'polling_unit_id')) {
            $query->orWhere(function ($fallbackQuery) use ($table, $directColumn) {
                $fallbackQuery->whereNull("{$table}.{$directColumn}")
                    ->whereIn("{$table}.polling_unit_id", function ($pollingUnitQuery) {
                        $pollingUnitQuery->select('polling_units.id')->from('polling_units');
                        $this->applyToPollingUnitsQuery($pollingUnitQuery);
                    });
            });
        }
    }

    private function constrainPollingUnitsThroughLga(EloquentBuilder|QueryBuilder $query, string $table, ?string $column, ?int $value): EloquentBuilder|QueryBuilder
    {
        if (!$column || !$value) {
            return $this->disallow($query);
        }

        return $query->whereIn("{$table}.ward_id", function ($wardQuery) use ($column, $value) {
            $wardQuery
                ->select('wards.id')
                ->from('wards')
                ->join('local_government_areas', 'wards.lga_id', '=', 'local_government_areas.id')
                ->where("local_government_areas.{$column}", $value);
        });
    }

    private function constrainByRelatedColumn(
        EloquentBuilder|QueryBuilder $query,
        string $table,
        string $localColumn,
        string $relatedTable,
        string $relatedKey,
        ?string $relatedColumn,
        ?int $relatedValue
    ): EloquentBuilder|QueryBuilder {
        if (!$relatedColumn || !$relatedValue || !SafeDatabase::hasTable($relatedTable) || !$this->hasColumn($relatedTable, $relatedColumn)) {
            return $this->disallow($query);
        }

        return $query->whereIn("{$table}.{$localColumn}", function ($relatedQuery) use ($relatedTable, $relatedKey, $relatedColumn, $relatedValue) {
            $relatedQuery
                ->select("{$relatedTable}.{$relatedKey}")
                ->from($relatedTable)
                ->where("{$relatedTable}.{$relatedColumn}", $relatedValue);
        });
    }

    private function whereColumn(EloquentBuilder|QueryBuilder $query, string $table, string $column, int $value): EloquentBuilder|QueryBuilder
    {
        return $query->where("{$table}.{$column}", $value);
    }

    private function disallow(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        return $query->whereRaw('1 = 0');
    }

    private function abortUnless(bool $allowed): void
    {
        abort_unless(!$this->applies() || $allowed, 403, 'This record is outside the configured campaign scope.');
    }

    private function recordIsAllowed(Model $record, string $subject): bool
    {
        $query = $record::query()->whereKey($record->getKey());
        $this->applyToSubject($query, $subject);

        return $query->exists();
    }

    private function tableFor(EloquentBuilder|QueryBuilder $query, string $default): string
    {
        if ($query instanceof EloquentBuilder) {
            return $query->getModel()->getTable();
        }

        return is_string($query->from) ? $query->from : $default;
    }

    private function baseTable(string $table): string
    {
        return trim(explode(' ', $table)[0]);
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return SafeDatabase::hasTable($table) && Schema::hasColumn($table, $column);
        } catch (Throwable) {
            return false;
        }
    }

    private function isNationalScope(): bool
    {
        $scope = $this->scopeService->current();

        return $scope->scope_type === 'national' || $this->scopeService->isPresidential();
    }

    private function licensedStateId(): ?int
    {
        $scope = $this->scopeService->current();

        if ($scope->state_id) {
            return $scope->state_id;
        }

        if ($scope->senatorial_district_id && SafeDatabase::hasTable('senatorial_districts')) {
            return (int) app('db')->table('senatorial_districts')->where('id', $scope->senatorial_district_id)->value('state_id') ?: null;
        }

        if ($scope->federal_constituency_id && SafeDatabase::hasTable('federal_constituencies')) {
            return (int) app('db')->table('federal_constituencies')->where('id', $scope->federal_constituency_id)->value('state_id') ?: null;
        }

        if ($scope->lga_id && SafeDatabase::hasTable('local_government_areas')) {
            return (int) app('db')->table('local_government_areas')->where('id', $scope->lga_id)->value('state_id') ?: null;
        }

        return null;
    }

    private function directBoundaryColumn(): ?string
    {
        return match ($this->scopeService->current()->scope_type) {
            'state' => 'state_id',
            'senatorial_district' => 'senatorial_district_id',
            'federal_constituency' => 'federal_constituency_id',
            'lga' => 'lga_id',
            default => null,
        };
    }

    private function directBoundaryValue(): ?int
    {
        $scope = $this->scopeService->current();

        return match ($scope->scope_type) {
            'state' => $scope->state_id,
            'senatorial_district' => $scope->senatorial_district_id,
            'federal_constituency' => $scope->federal_constituency_id,
            'lga' => $scope->lga_id,
            default => null,
        };
    }

    private function locationBoundaryColumn(): ?string
    {
        return match ($this->scopeService->current()->scope_type) {
            'state' => 'state_id',
            'senatorial_district' => 'senatorial_district_id',
            'federal_constituency' => 'federal_constituency_id',
            'lga' => 'id',
            default => null,
        };
    }

    private function locationBoundaryValue(): ?int
    {
        return $this->directBoundaryValue();
    }
}
