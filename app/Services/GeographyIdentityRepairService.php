<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GeographyIdentityRepairService
{
    private array $stateChildMerges = [
        'senatorial_districts' => 0,
        'federal_constituencies' => 0,
        'local_government_areas' => 0,
    ];

    public function repair(bool $dryRun = false): array
    {
        return DB::transaction(function () use ($dryRun) {
            $this->stateChildMerges = [
                'senatorial_districts' => 0,
                'federal_constituencies' => 0,
                'local_government_areas' => 0,
            ];

            $summary = [
                'states' => $this->repairStates($dryRun),
                'senatorial_districts' => $this->stateChildMerges['senatorial_districts'] + $this->repairBoundary(
                    'senatorial_districts',
                    'inec_senatorial_district_code',
                    $this->senatorialReferences(),
                    $dryRun
                ),
                'federal_constituencies' => $this->stateChildMerges['federal_constituencies'] + $this->repairBoundary(
                    'federal_constituencies',
                    'inec_federal_constituency_code',
                    $this->federalReferences(),
                    $dryRun
                ),
                'local_government_areas' => $this->stateChildMerges['local_government_areas'] + $this->repairBoundary(
                    'local_government_areas',
                    'inec_lga_code',
                    $this->lgaReferences(),
                    $dryRun
                ),
            ];

            $summary['total_merged'] = array_sum($summary);

            return $summary;
        });
    }

    private function repairStates(bool $dryRun): int
    {
        if (! $this->has('states', 'inec_state_code')) {
            return 0;
        }

        $pairs = DB::table('states as blank')
            ->join('states as coded', function ($join) {
                $join->whereRaw('LOWER(TRIM(blank.name)) = LOWER(TRIM(coded.name))')
                    ->whereNull('blank.inec_state_code')
                    ->whereNotNull('coded.inec_state_code')
                    ->whereColumn('blank.id', '<>', 'coded.id');
            })
            ->select([
                'blank.id as canonical_id',
                'coded.id as duplicate_id',
                'coded.inec_state_code as code',
                'coded.region_id as region_id',
            ])
            ->orderBy('blank.id')
            ->get();

        $count = 0;
        foreach ($pairs as $pair) {
            $this->mergeState((int) $pair->canonical_id, (int) $pair->duplicate_id, (string) $pair->code, (int) $pair->region_id, $dryRun);
            $count++;
        }

        return $count;
    }

    private function mergeState(int $canonicalId, int $duplicateId, string $code, int $regionId, bool $dryRun): void
    {
        if (! $dryRun) {
            DB::table('states')->where('id', $canonicalId)->update([
                'inec_state_code' => $code,
                'region_id' => $regionId,
                'updated_at' => now(),
            ]);
        }

        $this->mergeStateChildBoundaries($canonicalId, $duplicateId, 'senatorial_districts', 'inec_senatorial_district_code', $this->senatorialReferences(), $dryRun);
        $this->mergeStateChildBoundaries($canonicalId, $duplicateId, 'federal_constituencies', 'inec_federal_constituency_code', $this->federalReferences(), $dryRun);
        $this->mergeStateChildBoundaries($canonicalId, $duplicateId, 'local_government_areas', 'inec_lga_code', $this->lgaReferences(), $dryRun);

        foreach ([
            ['senatorial_districts', 'state_id'],
            ['federal_constituencies', 'state_id'],
            ['local_government_areas', 'state_id'],
            ['users', 'state_id'],
            ['votes', 'state_id'],
            ['polling_unit_results', 'state_id'],
            ['election_incidents', 'state_id'],
            ['system_settings', 'campaign_state_id'],
            ['local_license_scopes', 'state_id'],
        ] as [$table, $column]) {
            $this->remap($table, $column, $duplicateId, $canonicalId, $dryRun);
        }

        $this->deleteDuplicate('states', $duplicateId, $dryRun);
    }

    private function mergeStateChildBoundaries(int $canonicalStateId, int $duplicateStateId, string $table, string $codeColumn, array $references, bool $dryRun): void
    {
        if (! $this->has($table, $codeColumn) || ! $this->has($table, 'state_id')) {
            return;
        }

        $pairs = DB::table("{$table} as blank")
            ->join("{$table} as coded", function ($join) use ($codeColumn, $canonicalStateId, $duplicateStateId) {
                $join->where('blank.state_id', $canonicalStateId)
                    ->where('coded.state_id', $duplicateStateId)
                    ->whereRaw('LOWER(TRIM(blank.name)) = LOWER(TRIM(coded.name))')
                    ->whereNull("blank.{$codeColumn}")
                    ->whereNotNull("coded.{$codeColumn}");
            })
            ->select([
                'blank.id as canonical_id',
                'coded.id as duplicate_id',
                "coded.{$codeColumn} as code",
            ])
            ->orderBy('blank.id')
            ->get();

        foreach ($pairs as $pair) {
            $this->mergeBoundaryPair($table, $codeColumn, $references, (int) $pair->canonical_id, (int) $pair->duplicate_id, (string) $pair->code, $dryRun);
            $this->stateChildMerges[$table]++;
        }
    }

    private function repairBoundary(string $table, string $codeColumn, array $references, bool $dryRun): int
    {
        if (! $this->has($table, $codeColumn) || ! $this->has($table, 'state_id')) {
            return 0;
        }

        $pairs = DB::table("{$table} as blank")
            ->join("{$table} as coded", function ($join) use ($codeColumn) {
                $join->whereColumn('blank.state_id', 'coded.state_id')
                    ->whereRaw('LOWER(TRIM(blank.name)) = LOWER(TRIM(coded.name))')
                    ->whereNull("blank.{$codeColumn}")
                    ->whereNotNull("coded.{$codeColumn}")
                    ->whereColumn('blank.id', '<>', 'coded.id');
            })
            ->select([
                'blank.id as canonical_id',
                'coded.id as duplicate_id',
                "coded.{$codeColumn} as code",
            ])
            ->orderBy('blank.id')
            ->get();

        $count = 0;
        foreach ($pairs as $pair) {
            $this->mergeBoundaryPair($table, $codeColumn, $references, (int) $pair->canonical_id, (int) $pair->duplicate_id, (string) $pair->code, $dryRun);
            $count++;
        }

        return $count;
    }

    private function mergeBoundaryPair(string $table, string $codeColumn, array $references, int $canonicalId, int $duplicateId, string $code, bool $dryRun): void
    {
        if (! $dryRun) {
            DB::table($table)->where('id', $canonicalId)->update([
                $codeColumn => $code,
                'updated_at' => now(),
            ]);
        }

        foreach ($references as [$referenceTable, $column]) {
            $this->remap($referenceTable, $column, $duplicateId, $canonicalId, $dryRun);
        }

        $this->deleteDuplicate($table, $duplicateId, $dryRun);
    }

    private function senatorialReferences(): array
    {
        return [
            ['federal_constituencies', 'senatorial_district_id'],
            ['local_government_areas', 'senatorial_district_id'],
            ['polling_units', 'senatorial_district_id'],
            ['users', 'senatorial_district_id'],
            ['votes', 'senatorial_district_id'],
            ['polling_unit_results', 'senatorial_district_id'],
            ['election_incidents', 'senatorial_district_id'],
            ['system_settings', 'campaign_senatorial_district_id'],
            ['local_license_scopes', 'senatorial_district_id'],
        ];
    }

    private function federalReferences(): array
    {
        return [
            ['local_government_areas', 'federal_constituency_id'],
            ['polling_units', 'federal_constituency_id'],
            ['users', 'federal_constituency_id'],
            ['votes', 'federal_constituency_id'],
            ['polling_unit_results', 'federal_constituency_id'],
            ['election_incidents', 'federal_constituency_id'],
            ['system_settings', 'campaign_federal_constituency_id'],
            ['local_license_scopes', 'federal_constituency_id'],
        ];
    }

    private function lgaReferences(): array
    {
        return [
            ['wards', 'lga_id'],
            ['users', 'lga_id'],
            ['votes', 'lga_id'],
            ['polling_unit_results', 'lga_id'],
            ['election_incidents', 'lga_id'],
            ['system_settings', 'campaign_lga_id'],
            ['local_license_scopes', 'lga_id'],
        ];
    }

    private function remap(string $table, string $column, int $from, int $to, bool $dryRun): void
    {
        if (! $this->has($table, $column) || $dryRun) {
            return;
        }

        DB::table($table)->where($column, $from)->update([$column => $to]);
    }

    private function deleteDuplicate(string $table, int $id, bool $dryRun): void
    {
        if ($dryRun || ! Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->where('id', $id)->delete();
    }

    private function has(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }
}