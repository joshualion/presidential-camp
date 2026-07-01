<?php

namespace App\Services;

use App\Models\LocationProvisioningBatch;
use App\Models\LocalLicense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class LocationProvisioningImportService
{
    public const DATASETS = [
        'states',
        'senatorial_districts',
        'federal_constituencies',
        'local_government_areas',
        'wards',
        'polling_units',
    ];

    public function __construct(private LocationProvisioningClient $client)
    {
    }

    public function latestRun(): ?LocationProvisioningBatch
    {
        if (!Schema::hasTable('location_provisioning_batches')) {
            return null;
        }

        return LocationProvisioningBatch::query()->latest('id')->first();
    }

    public function startOrResume(?LocalLicense $license = null, int $batchSize = 500): LocationProvisioningBatch
    {
        $this->ensureTrackerTable();

        $license ??= LocalLicense::query()->with('scope')->latest('id')->first();
        if (!$license) {
            throw new RuntimeException('No active local license was found for location provisioning.');
        }

        if (!in_array(strtolower((string) $license->status), ['active', 'valid'], true)) {
            throw new RuntimeException('Location provisioning requires an active local license.');
        }

        if (!$license->activation_id) {
            throw new RuntimeException('License activation is incomplete. Retry activation before provisioning locations.');
        }

        $run = LocationProvisioningBatch::query()
            ->where('license_key', $license->license_key)
            ->where('activation_id', $license->activation_id)
            ->whereIn('status', ['pending', 'running', 'failed'])
            ->latest('id')
            ->first();

        if ($run) {
            if (!$run->provisioning_session_id || !$run->session_token) {
                $run = $this->createPortalSession($license, $run, $batchSize);
            }

            $run->update([
                'status' => 'running',
                'error_message' => null,
            ]);

            return $run->fresh();
        }

        $run = LocationProvisioningBatch::query()->create([
            'license_key' => $license->license_key,
            'activation_id' => $license->activation_id,
            'package_type' => $license->package_type,
            'release_profile' => $this->releaseProfile($license),
            'scope_payload' => $license->scope?->raw_payload ?: $license->scope?->toArray(),
            'status' => 'pending',
            'current_dataset' => self::DATASETS[0],
            'current_cursor' => 0,
            'total_records' => 0,
            'imported_records' => 0,
            'summary' => $this->emptySummary(),
            'started_at' => now(),
        ]);

        return $this->createPortalSession($license, $run, $batchSize);
    }

    public function processNextBatch(?LocationProvisioningBatch $run = null, int $batchSize = 500): array
    {
        $run ??= $this->latestRun();
        if (!$run) {
            throw new RuntimeException('Location provisioning has not started.');
        }

        if ($run->status === 'completed') {
            return $this->response($run->fresh(), 'complete');
        }

        $license = LocalLicense::query()
            ->with('scope')
            ->where('license_key', $run->license_key)
            ->latest('id')
            ->first();

        if (!$license) {
            throw new RuntimeException('Local license record is missing. Restart license activation or contact support.');
        }

        $batchSize = max(1, $batchSize);
        $dataset = $run->current_dataset ?: self::DATASETS[0];
        $cursor = (int) $run->current_cursor;

        try {
            $batch = $this->client->fetchBatch([
                'provisioning_session_id' => $run->provisioning_session_id,
                'session_token' => $run->session_token,
                'dataset' => $dataset,
                'cursor' => $cursor,
                'batch_size' => $batchSize,
            ]);

            $records = $this->records($batch);
            $imported = DB::transaction(fn () => $this->importRecords($dataset, $records));
            $nextCursor = (int) data_get($batch, 'next_cursor', data_get($batch, 'cursor', $cursor + count($records)));
            $hasMore = (bool) data_get($batch, 'has_more', count($records) >= $batchSize);
            $availableDatasets = $this->availableDatasets($batch, $run);
            $nextDataset = $hasMore ? $dataset : $this->nextDataset($dataset, $availableDatasets);

            $summary = $run->summary ?: $this->emptySummary();
            $summary[$dataset]['imported'] = ($summary[$dataset]['imported'] ?? 0) + $imported;
            $summary[$dataset]['batches'] = ($summary[$dataset]['batches'] ?? 0) + 1;

            $run->fill([
                'location_data_version' => data_get($batch, 'location_data_version', $run->location_data_version),
                'package_type' => data_get($batch, 'package_type', $run->package_type),
                'release_profile' => data_get($batch, 'release_profile', $run->release_profile),
                'scope_payload' => data_get($batch, 'scope_payload', $run->scope_payload),
                'status' => $nextDataset ? 'running' : 'completed',
                'current_dataset' => $nextDataset,
                'current_cursor' => $nextDataset === $dataset ? $nextCursor : 0,
                'total_records' => max((int) $run->total_records, (int) data_get($batch, 'total_records', 0)),
                'imported_records' => (int) $run->imported_records + $imported,
                'error_message' => null,
                'completed_at' => $nextDataset ? null : now(),
                'summary' => $summary,
            ])->save();

            if (!$nextDataset) {
                $this->client->complete([
                    'provisioning_session_id' => $run->provisioning_session_id,
                    'session_token' => $run->session_token,
                    'license_key' => $run->license_key,
                    'activation_id' => $run->activation_id,
                ]);
            }

            return $this->response($run->fresh(), $nextDataset ? 'continue' : 'complete');
        } catch (LocationProvisioningException|RuntimeException $e) {
            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function provision(?LocalLicense $license = null, int $batchSize = 500, ?callable $progress = null): array
    {
        $run = $this->startOrResume($license, $batchSize);

        do {
            $result = $this->processNextBatch($run->fresh(), $batchSize);
            $run = LocationProvisioningBatch::query()->find($run->id);
            $progress && $progress($result);
        } while (($result['status'] ?? null) !== 'completed');

        return $result;
    }

    private function createPortalSession(LocalLicense $license, LocationProvisioningBatch $run, int $batchSize): LocationProvisioningBatch
    {
        $session = $this->client->createSession([
            'license_key' => $license->license_key,
            'activation_id' => $license->activation_id,
            'deployment_mode' => 'self_hosted',
            'batch_size' => $batchSize,
        ]);

        $datasets = $this->normalizeDatasets(data_get($session, 'datasets', self::DATASETS));
        if ($datasets === []) {
            throw new RuntimeException('The portal did not return any licensed location datasets.');
        }

        $run->update([
            'provisioning_session_id' => data_get($session, 'provisioning_session_id', data_get($session, 'session.id')),
            'session_token' => data_get($session, 'session_token', data_get($session, 'token')),
            'location_data_version' => data_get($session, 'location_data_version'),
            'package_type' => data_get($session, 'package_type', $license->package_type),
            'release_profile' => data_get($session, 'release_profile', $this->releaseProfile($license)),
            'scope_payload' => data_get($session, 'scope_payload', $run->scope_payload),
            'current_dataset' => $datasets[0],
            'current_cursor' => 0,
            'status' => 'running',
            'summary' => array_replace_recursive($run->summary ?: $this->emptySummary(), [
                '_available_datasets' => $datasets,
            ]),
        ]);

        if (!$run->provisioning_session_id || !$run->session_token) {
            throw new RuntimeException('The portal did not return a valid location provisioning session.');
        }

        return $run->fresh();
    }

    private function importRecords(string $dataset, array $records): int
    {
        $imported = 0;

        foreach ($records as $record) {
            $record = is_array($record) ? $record : [];

            match ($dataset) {
                'states' => $this->importState($record),
                'senatorial_districts' => $this->importSenatorialDistrict($record),
                'federal_constituencies' => $this->importFederalConstituency($record),
                'local_government_areas' => $this->importLga($record),
                'wards' => $this->importWard($record),
                'polling_units' => $this->importPollingUnit($record),
                default => throw new RuntimeException("Unsupported location dataset [{$dataset}]."),
            };

            $imported++;
        }

        return $imported;
    }

    private function importState(array $row): void
    {
        $countryId = $this->countryId((string) data_get($row, 'country_name', 'Nigeria'));
        $regionId = $this->regionId($countryId, (string) data_get($row, 'region_name', 'Unknown Region'));
        $code = $this->value($row, ['state_code', 'inec_state_code', 'code']);
        $name = $this->name($row);

        DB::table('states')->updateOrInsert(
            $code ? ['inec_state_code' => $code] : ['name' => $name],
            [
                'uuid' => $this->existingUuid('states', $code ? ['inec_state_code' => $code] : ['name' => $name]),
                'name' => $name,
                'inec_state_code' => $code,
                'region_id' => $regionId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function importSenatorialDistrict(array $row): void
    {
        $stateId = $this->stateId($row);
        $name = $this->name($row);
        $normalized = $this->normalized($name);

        DB::table('senatorial_districts')->updateOrInsert(
            ['state_id' => $stateId, 'normalized_name' => $normalized],
            [
                'uuid' => $this->existingUuid('senatorial_districts', ['state_id' => $stateId, 'normalized_name' => $normalized]),
                'name' => $name,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function importFederalConstituency(array $row): void
    {
        $stateId = $this->stateId($row);
        $name = $this->name($row);
        $normalized = $this->normalized($name);
        $senatorialId = $this->senatorialId($row, false);

        DB::table('federal_constituencies')->updateOrInsert(
            ['state_id' => $stateId, 'normalized_name' => $normalized],
            [
                'uuid' => $this->existingUuid('federal_constituencies', ['state_id' => $stateId, 'normalized_name' => $normalized]),
                'senatorial_district_id' => $senatorialId,
                'name' => $name,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function importLga(array $row): void
    {
        $stateId = $this->stateId($row);
        $code = $this->value($row, ['lga_code', 'inec_lga_code', 'code']);
        $name = $this->name($row);

        DB::table('local_government_areas')->updateOrInsert(
            $code ? ['state_id' => $stateId, 'inec_lga_code' => $code] : ['state_id' => $stateId, 'name' => $name],
            [
                'uuid' => $this->existingUuid('local_government_areas', $code ? ['state_id' => $stateId, 'inec_lga_code' => $code] : ['state_id' => $stateId, 'name' => $name]),
                'name' => $name,
                'inec_lga_code' => $code,
                'senatorial_district_id' => $this->senatorialId($row, false),
                'federal_constituency_id' => $this->federalId($row, false),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function importWard(array $row): void
    {
        $lgaId = $this->lgaId($row);
        $code = $this->value($row, ['ward_code', 'inec_ward_code', 'code']);
        $name = $this->name($row);

        DB::table('wards')->updateOrInsert(
            $code ? ['lga_id' => $lgaId, 'inec_ward_code' => $code] : ['lga_id' => $lgaId, 'name' => $name],
            [
                'uuid' => $this->existingUuid('wards', $code ? ['lga_id' => $lgaId, 'inec_ward_code' => $code] : ['lga_id' => $lgaId, 'name' => $name]),
                'name' => $name,
                'inec_ward_code' => $code,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function importPollingUnit(array $row): void
    {
        $wardId = $this->wardId($row);
        $fullCode = $this->value($row, ['inec_full_code', 'full_code']);
        $puCode = $this->value($row, ['pu_code', 'inec_pu_code', 'code']);
        $name = $this->name($row);

        DB::table('polling_units')->updateOrInsert(
            $fullCode ? ['inec_full_code' => $fullCode] : ['ward_id' => $wardId, 'inec_pu_code' => $puCode, 'name' => $name],
            [
                'uuid' => $this->existingUuid('polling_units', $fullCode ? ['inec_full_code' => $fullCode] : ['ward_id' => $wardId, 'inec_pu_code' => $puCode, 'name' => $name]),
                'name' => Str::limit($name, 255, ''),
                'ward_id' => $wardId,
                'inec_pu_code' => $puCode,
                'inec_full_code' => $fullCode,
                'senatorial_district_id' => $this->senatorialId($row, false),
                'federal_constituency_id' => $this->federalId($row, false),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function countryId(string $name): int
    {
        $name = $name ?: 'Nigeria';

        DB::table('countries')->updateOrInsert(['name' => $name], [
            'uuid' => $this->existingUuid('countries', ['name' => $name]),
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        return (int) DB::table('countries')->where('name', $name)->value('id');
    }

    private function regionId(int $countryId, string $name): int
    {
        $name = $name ?: 'Unknown Region';

        DB::table('regions')->updateOrInsert(['country_id' => $countryId, 'name' => $name], [
            'uuid' => $this->existingUuid('regions', ['country_id' => $countryId, 'name' => $name]),
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        return (int) DB::table('regions')->where('country_id', $countryId)->where('name', $name)->value('id');
    }

    private function stateId(array $row): int
    {
        $code = $this->value($row, ['state_code', 'inec_state_code']);
        $name = $this->value($row, ['state_name', 'state']);

        $query = DB::table('states');
        $code ? $query->where('inec_state_code', $code) : $query->where('name', $name);
        $id = $query->value('id');

        if (!$id) {
            throw new RuntimeException('Portal returned child location before its parent state was imported.');
        }

        return (int) $id;
    }

    private function lgaId(array $row): int
    {
        $stateId = $this->stateId($row);
        $code = $this->value($row, ['lga_code', 'inec_lga_code']);
        $name = $this->value($row, ['lga_name', 'local_government_area_name']);

        $query = DB::table('local_government_areas')->where('state_id', $stateId);
        $code ? $query->where('inec_lga_code', $code) : $query->where('name', $name);
        $id = $query->value('id');

        if (!$id) {
            throw new RuntimeException('Portal returned ward data before its parent LGA was imported.');
        }

        return (int) $id;
    }

    private function wardId(array $row): int
    {
        $lgaId = $this->lgaId($row);
        $code = $this->value($row, ['ward_code', 'inec_ward_code']);
        $name = $this->value($row, ['ward_name']);

        $query = DB::table('wards')->where('lga_id', $lgaId);
        $code ? $query->where('inec_ward_code', $code) : $query->where('name', $name);
        $id = $query->value('id');

        if (!$id) {
            throw new RuntimeException('Portal returned polling unit data before its parent ward was imported.');
        }

        return (int) $id;
    }

    private function senatorialId(array $row, bool $required = true): ?int
    {
        $name = $this->value($row, ['senatorial_district_name', 'senatorial_name']);
        if (!$name) {
            return $required ? throw new RuntimeException('Missing senatorial district name in portal batch.') : null;
        }

        $id = DB::table('senatorial_districts')
            ->where('state_id', $this->stateId($row))
            ->where('normalized_name', $this->normalized($name))
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function federalId(array $row, bool $required = true): ?int
    {
        $name = $this->value($row, ['federal_constituency_name', 'federal_name']);
        if (!$name) {
            return $required ? throw new RuntimeException('Missing federal constituency name in portal batch.') : null;
        }

        $id = DB::table('federal_constituencies')
            ->where('state_id', $this->stateId($row))
            ->where('normalized_name', $this->normalized($name))
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function existingUuid(string $table, array $where): string
    {
        $query = DB::table($table);
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }

        return (string) ($query->value('uuid') ?: (string) Str::uuid());
    }

    private function records(array $batch): array
    {
        $records = data_get($batch, 'records', data_get($batch, 'data.records', data_get($batch, 'items', [])));

        return is_array($records) ? array_values($records) : [];
    }

    private function availableDatasets(array $batch, LocationProvisioningBatch $run): array
    {
        return $this->normalizeDatasets(data_get($batch, 'datasets', data_get($run->summary ?: [], '_available_datasets', self::DATASETS)));
    }

    private function normalizeDatasets(mixed $datasets): array
    {
        return collect(is_array($datasets) ? $datasets : [])
            ->map(fn ($dataset) => is_array($dataset) ? (string) data_get($dataset, 'name') : (string) $dataset)
            ->filter(fn (string $dataset) => in_array($dataset, self::DATASETS, true))
            ->unique()
            ->sortBy(fn (string $dataset) => array_search($dataset, self::DATASETS, true))
            ->values()
            ->all();
    }

    private function nextDataset(string $dataset, array $availableDatasets): ?string
    {
        $index = array_search($dataset, $availableDatasets, true);

        return $index === false ? null : ($availableDatasets[$index + 1] ?? null);
    }

    private function response(LocationProvisioningBatch $run, string $nextAction): array
    {
        $percent = $run->total_records > 0
            ? min(100, (int) floor(($run->imported_records / $run->total_records) * 100))
            : ($run->status === 'completed' ? 100 : 0);

        return [
            'ok' => true,
            'status' => $run->status,
            'next_action' => $nextAction,
            'message' => $run->status === 'completed'
                ? 'Location provisioning completed.'
                : 'Provisioning licensed location data from the Campaign Manager portal.',
            'stage_label' => $run->current_dataset ? Str::headline($run->current_dataset) : 'Complete',
            'percent' => $percent,
            'imported_records' => (int) $run->imported_records,
            'total_records' => (int) $run->total_records,
            'package_type' => $run->package_type,
            'release_profile' => $run->release_profile,
            'scope_payload' => $run->scope_payload ?: [],
            'summary' => $run->summary ?: [],
        ];
    }

    private function emptySummary(): array
    {
        return collect(self::DATASETS)
            ->mapWithKeys(fn (string $dataset) => [$dataset => ['imported' => 0, 'batches' => 0]])
            ->all();
    }

    private function value(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($row, $key);
            if ($value !== null && $value !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function name(array $row): string
    {
        $name = $this->value($row, ['name']);
        if (!$name) {
            throw new RuntimeException('Portal returned a location record without a name.');
        }

        return $name;
    }

    private function normalized(string $value): string
    {
        return Str::of($value)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }

    private function releaseProfile(LocalLicense $license): string
    {
        return strtolower((string) ($license->raw_payload['release_profile'] ?? $license->raw_payload['profile'] ?? $license->package_type ?? 'licensed'));
    }

    private function ensureTrackerTable(): void
    {
        if (!Schema::hasTable('location_provisioning_batches')) {
            throw new RuntimeException('Location provisioning tracking table is missing. Run installer migrations first.');
        }
    }
}
