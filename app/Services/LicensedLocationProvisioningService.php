<?php

namespace App\Services;

use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\LocalLicense;
use App\Models\LocationProvisioningRun;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class LicensedLocationProvisioningService
{
    private const REQUIRED_FILES = [
        'states.csv',
        'senatorial_districts.csv',
        'federal_constituencies.csv',
        'local_government_areas.csv',
        'wards.csv',
        'polling_units.csv',
    ];

    private array $summary = [];

    private array $warnings = [];

    private string $activeSourcePath = '';

    private array $allowedLgaCache = [];

    public function __construct(private DeploymentModeService $deploymentMode)
    {
    }

    public function defaultSourcePath(): string
    {
        return storage_path('app/imports/inec/prepared/all');
    }

    public function inspectSource(?string $sourcePath = null): array
    {
        $sourcePath ??= $this->defaultSourcePath();

        return collect(self::REQUIRED_FILES)
            ->mapWithKeys(fn (string $file) => [$file => [
                'path' => $sourcePath.DIRECTORY_SEPARATOR.$file,
                'exists' => is_file($sourcePath.DIRECTORY_SEPARATOR.$file),
                'headers' => is_file($sourcePath.DIRECTORY_SEPARATOR.$file) ? $this->headers($sourcePath.DIRECTORY_SEPARATOR.$file) : [],
            ]])
            ->all();
    }

    public function missingSourceFiles(?string $sourcePath = null): array
    {
        $sourcePath ??= $this->defaultSourcePath();

        return array_values(array_filter(
            self::REQUIRED_FILES,
            fn (string $file) => !is_file($sourcePath.DIRECTORY_SEPARATOR.$file)
        ));
    }

    public function provision(?LocalLicense $license = null, bool $dryRun = true, ?string $sourcePath = null): array
    {
        $license ??= LocalLicense::query()->with('scope')->latest('id')->first();
        $sourcePath ??= $this->defaultSourcePath();
        $this->activeSourcePath = $sourcePath;
        $this->allowedLgaCache = [];
        $this->resetSummary();

        if (!$license) {
            if ($this->deploymentMode->isSelfHosted()) {
                throw new RuntimeException('No local license found. Licensed location provisioning requires a local license for self-hosted deployments.');
            }

            return [
                'status' => 'skipped',
                'dry_run' => $dryRun,
                'deployment_mode' => $this->deploymentMode->mode(),
                'message' => 'Managed deployment has no local license yet. Location provisioning skipped and existing fallback behavior is preserved.',
                'source_path' => $sourcePath,
                'files' => $this->inspectSource($sourcePath),
                'summary' => $this->summary,
                'warnings' => ['Managed mode without a local license is intentionally unaffected.'],
            ];
        }

        $license->loadMissing('scope');
        $this->ensureFilesExist($sourcePath);
        $scope = $this->resolveScope($license, $sourcePath);

        $run = $this->startRun($license, $scope, $dryRun);

        try {
            $result = DB::transaction(function () use ($license, $sourcePath, $scope, $dryRun) {
                $context = $this->provisionContext($sourcePath, $scope, $dryRun);

                $this->provisionSenatorialDistricts($sourcePath, $scope, $context, $dryRun);
                $this->provisionFederalConstituencies($sourcePath, $scope, $context, $dryRun);
                $this->provisionLgas($sourcePath, $scope, $context, $dryRun);
                $this->provisionWards($sourcePath, $scope, $context, $dryRun);
                $this->provisionPollingUnits($sourcePath, $scope, $context, $dryRun);

                if (!$dryRun) {
                    $this->syncLocalLicenseScope($license, $scope, $context);
                }

                return [
                    'status' => $dryRun ? 'dry_run' : 'completed',
                    'dry_run' => $dryRun,
                    'deployment_mode' => $this->deploymentMode->mode(),
                    'package_type' => $license->package_type,
                    'scope_type' => $scope['scope_type'],
                    'scope_name' => $scope['scope_name'],
                    'source_path' => $sourcePath,
                    'files' => $this->inspectSource($sourcePath),
                    'summary' => $this->summary,
                    'warnings' => $this->warnings,
                ];
            });

            $this->completeRun($run, $result['status'], $result['summary'], $result['warnings']);

            return $result;
        } catch (RuntimeException $e) {
            $this->failRun($run, $e->getMessage());
            throw $e;
        }
    }

    public function latestRun(): ?LocationProvisioningRun
    {
        if (!Schema::hasTable('location_provisioning_runs')) {
            return null;
        }

        return LocationProvisioningRun::query()->latest('id')->first();
    }

    private function resolveScope(LocalLicense $license, string $sourcePath): array
    {
        $scope = $license->scope;
        if (!$scope) {
            throw new RuntimeException('Local license scope is missing. Cannot provision licensed location data.');
        }

        $scopeType = strtolower((string) ($scope->scope_type ?: $license->package_type));
        $scopeName = $scope->scope_name ?: $scope->state_name ?: $scope->senatorial_district_name ?: $scope->federal_constituency_name ?: $scope->lga_name ?: 'National';

        if (in_array($scopeType, ['presidential', 'national'], true) || $license->package_type === 'presidential') {
            return [
                'scope_type' => 'national',
                'scope_name' => 'National',
                'state' => null,
                'senatorial' => null,
                'federal' => null,
                'lga' => null,
            ];
        }

        if ($scopeType === 'state_constituency') {
            throw new RuntimeException('State Constituency scope is not supported for automated location provisioning yet.');
        }

        $state = $this->findSourceState($sourcePath, $scope->state_name ?: $scopeName, $this->rawValue($scope, 'state_code'));

        if (!$state && in_array($scopeType, ['state', 'governorship'], true)) {
            $state = $this->findSourceState($sourcePath, $scopeName, $this->rawValue($scope, 'state_code'));
        }

        if (!$state) {
            throw new RuntimeException('Unable to map licensed state scope "'.$scopeName.'" to states.csv.');
        }

        $resolved = [
            'scope_type' => match ($scopeType) {
                'governorship' => 'state',
                default => $scopeType,
            },
            'scope_name' => $scopeName,
            'state' => $state,
            'senatorial' => null,
            'federal' => null,
            'lga' => null,
        ];

        if (in_array($resolved['scope_type'], ['state'], true)) {
            $resolved['scope_name'] = $state['name'];
            return $resolved;
        }

        if ($resolved['scope_type'] === 'senatorial_district') {
            $resolved['senatorial'] = $this->findSourceRow(
                $sourcePath,
                'senatorial_districts.csv',
                fn (array $row) => $this->sameState($row, $state)
                    && $this->matchesName($row['name'] ?? null, $scope->senatorial_district_name ?: $scopeName)
            );
            if (!$resolved['senatorial']) {
                throw new RuntimeException('Unable to map licensed senatorial district "'.$scopeName.'" in '.$state['name'].'.');
            }
            $resolved['scope_name'] = $resolved['senatorial']['name'];
        }

        if ($resolved['scope_type'] === 'federal_constituency') {
            $resolved['federal'] = $this->findSourceRow(
                $sourcePath,
                'federal_constituencies.csv',
                fn (array $row) => $this->sameState($row, $state)
                    && $this->matchesName($row['name'] ?? null, $scope->federal_constituency_name ?: $scopeName)
            );
            if (!$resolved['federal']) {
                throw new RuntimeException('Unable to map licensed federal constituency "'.$scopeName.'" in '.$state['name'].'.');
            }
            $resolved['scope_name'] = $resolved['federal']['name'];
        }

        if ($resolved['scope_type'] === 'lga') {
            $resolved['lga'] = $this->findSourceRow(
                $sourcePath,
                'local_government_areas.csv',
                fn (array $row) => $this->sameState($row, $state)
                    && $this->matchesName($row['name'] ?? null, $scope->lga_name ?: $scopeName)
            );
            if (!$resolved['lga']) {
                throw new RuntimeException('Unable to map licensed LGA "'.$scopeName.'" in '.$state['name'].'.');
            }
            $resolved['scope_name'] = $resolved['lga']['name'];
        }

        return $resolved;
    }

    private function provisionContext(string $sourcePath, array $scope, bool $dryRun): array
    {
        $context = [
            'country' => null,
            'regions' => [],
            'states' => [],
            'senatorial' => [],
            'federal' => [],
            'lgas' => [],
            'wards' => [],
        ];

        foreach ($this->readCsv($sourcePath, 'states.csv') as $row) {
            if (!$this->rowInScope($row, $scope, 'states')) {
                $this->count('states', 'skipped');
                continue;
            }

            $this->count('states', 'selected');
            $countryName = $row['country_name'] ?? 'Nigeria';
            $regionName = $row['region_name'] ?? null;

            if (!$regionName) {
                throw new RuntimeException('states.csv row for '.$row['name'].' is missing region_name.');
            }

            $country = $context['country'] ?: $this->persistCountry($countryName, $dryRun);
            $region = $this->persistRegion($country, $regionName, $dryRun);
            $state = $this->persistState($region, $row, $dryRun);

            $context['country'] = $country;
            $context['regions'][$this->norm($regionName)] = $region;
            $context['states'][$row['state_code'] ?: $this->norm($row['name'])] = $state;
        }

        if (!$context['states'] && $scope['scope_type'] !== 'national') {
            throw new RuntimeException('No source state rows matched the licensed scope.');
        }

        return $context;
    }

    private function provisionSenatorialDistricts(string $sourcePath, array $scope, array &$context, bool $dryRun): void
    {
        foreach ($this->readCsv($sourcePath, 'senatorial_districts.csv') as $row) {
            if (!$this->rowInScope($row, $scope, 'senatorial')) {
                $this->count('senatorial_districts', 'skipped');
                continue;
            }

            $state = $this->stateFromContext($context, $row);
            if (!$state) {
                $this->warn('Skipped senatorial district '.$row['name'].' because parent state was not provisioned.');
                $this->count('senatorial_districts', 'skipped');
                continue;
            }

            $this->count('senatorial_districts', 'selected');
            $district = $this->persistSenatorialDistrict($state, $row['name'], $dryRun);
            $context['senatorial'][$this->boundaryKey($row)] = $district;
        }
    }

    private function provisionFederalConstituencies(string $sourcePath, array $scope, array &$context, bool $dryRun): void
    {
        foreach ($this->readCsv($sourcePath, 'federal_constituencies.csv') as $row) {
            if (!$this->rowInScope($row, $scope, 'federal')) {
                $this->count('federal_constituencies', 'skipped');
                continue;
            }

            $state = $this->stateFromContext($context, $row);
            if (!$state) {
                $this->warn('Skipped federal constituency '.$row['name'].' because parent state was not provisioned.');
                $this->count('federal_constituencies', 'skipped');
                continue;
            }

            $senatorial = $this->senatorialFromContext($context, $row);
            $this->count('federal_constituencies', 'selected');
            $federal = $this->persistFederalConstituency($state, $senatorial, $row['name'], $dryRun);
            $context['federal'][$this->boundaryKey($row)] = $federal;
        }
    }

    private function provisionLgas(string $sourcePath, array $scope, array &$context, bool $dryRun): void
    {
        foreach ($this->readCsv($sourcePath, 'local_government_areas.csv') as $row) {
            if (!$this->rowInScope($row, $scope, 'lgas')) {
                $this->count('lgas', 'skipped');
                continue;
            }

            $state = $this->stateFromContext($context, $row);
            if (!$state) {
                $this->warn('Skipped LGA '.$row['name'].' because parent state was not provisioned.');
                $this->count('lgas', 'skipped');
                continue;
            }

            $senatorial = $this->senatorialFromContext($context, $row);
            $federal = $this->federalFromContext($context, $row);
            $this->count('lgas', 'selected');
            $lga = $this->persistLga($state, $senatorial, $federal, $row, $dryRun);
            $context['lgas'][$this->lgaKey($row)] = $lga;
        }
    }

    private function provisionWards(string $sourcePath, array $scope, array &$context, bool $dryRun): void
    {
        foreach ($this->readCsv($sourcePath, 'wards.csv') as $row) {
            if (!$this->rowInScope($row, $scope, 'wards')) {
                $this->count('wards', 'skipped');
                continue;
            }

            $lga = $this->lgaFromContext($context, $row);
            if (!$lga) {
                $this->warn('Skipped ward '.$row['name'].' because parent LGA was not provisioned.');
                $this->count('wards', 'skipped');
                continue;
            }

            $this->count('wards', 'selected');
            $ward = $this->persistWard($lga, $row, $dryRun);
            $context['wards'][$this->wardKey($row)] = $ward;
        }
    }

    private function provisionPollingUnits(string $sourcePath, array $scope, array &$context, bool $dryRun): void
    {
        foreach ($this->readCsv($sourcePath, 'polling_units.csv') as $row) {
            if (!$this->rowInScope($row, $scope, 'polling_units')) {
                $this->count('polling_units', 'skipped');
                continue;
            }

            $ward = $this->wardFromContext($context, $row);
            if (!$ward) {
                $this->warn('Skipped polling unit '.$row['name'].' because parent ward was not provisioned.');
                $this->count('polling_units', 'skipped');
                continue;
            }

            $this->count('polling_units', 'selected');
            $this->persistPollingUnit(
                $ward,
                $this->senatorialFromContext($context, $row),
                $this->federalFromContext($context, $row),
                $row,
                $dryRun
            );
        }
    }

    private function rowInScope(array $row, array $scope, string $level): bool
    {
        if ($scope['scope_type'] === 'national') {
            return true;
        }

        if (!$this->sameState($row, $scope['state'])) {
            return false;
        }

        return match ($scope['scope_type']) {
            'state' => true,
            'senatorial_district' => match ($level) {
                'senatorial' => $this->matchesName($row['name'] ?? null, $scope['senatorial']['name'] ?? null),
                'federal' => true,
                'lgas', 'polling_units' => $this->matchesName($row['senatorial_district_name'] ?? null, $scope['senatorial']['name'] ?? null),
                'wards' => $this->lgaBelongsToBoundary($row, $scope, 'senatorial'),
                default => true,
            },
            'federal_constituency' => match ($level) {
                'federal' => $this->matchesName($row['name'] ?? null, $scope['federal']['name'] ?? null),
                'senatorial' => true,
                'lgas', 'polling_units' => $this->matchesName($row['federal_constituency_name'] ?? null, $scope['federal']['name'] ?? null),
                'wards' => $this->lgaBelongsToBoundary($row, $scope, 'federal'),
                default => true,
            },
            'lga' => match ($level) {
                'lgas' => $this->sameLga($row, $scope['lga']),
                'wards', 'polling_units' => $this->sameLga($row, $scope['lga']),
                'senatorial' => $this->matchesName($row['name'] ?? null, $scope['lga']['senatorial_district_name'] ?? null),
                'federal' => $this->matchesName($row['name'] ?? null, $scope['lga']['federal_constituency_name'] ?? null),
                default => true,
            },
            default => false,
        };
    }

    private function lgaBelongsToBoundary(array $row, array $scope, string $boundary): bool
    {
        $cacheKey = $boundary.'|'.$this->norm($scope['scope_name'] ?? '');

        if (!array_key_exists($cacheKey, $this->allowedLgaCache)) {
            $this->allowedLgaCache[$cacheKey] = [];

            foreach ($this->readCsv($this->activeSourcePath ?: $this->defaultSourcePath(), 'local_government_areas.csv') as $lgaRow) {
                if (!$this->sameState($lgaRow, $scope['state'])) {
                    continue;
                }

                $matches = $boundary === 'senatorial'
                    ? $this->matchesName($lgaRow['senatorial_district_name'] ?? null, $scope['senatorial']['name'] ?? null)
                    : $this->matchesName($lgaRow['federal_constituency_name'] ?? null, $scope['federal']['name'] ?? null);

                if ($matches) {
                    $this->allowedLgaCache[$cacheKey][$this->lgaKey($lgaRow)] = true;
                }
            }
        }

        return $this->allowedLgaCache[$cacheKey][$this->lgaKey($row)] ?? false;
    }

    private function persistCountry(string $name, bool $dryRun): ?Country
    {
        if ($dryRun) {
            $this->count('regions', 'selected');
            return new Country(['name' => $name]);
        }

        return Country::query()->firstOrCreate(
            ['name' => $name],
            ['uuid' => (string) Str::uuid()]
        );
    }

    private function persistRegion(?Country $country, string $name, bool $dryRun): ?Region
    {
        if ($dryRun) {
            $this->count('regions', 'selected');
            return new Region([
                'country_id' => $country?->id,
                'name' => $name,
                'description' => $name,
            ]);
        }

        $region = Region::query()
            ->where('country_id', $country?->id)
            ->get()
            ->first(fn (Region $region) => $this->matchesName($region->name, $name));

        if (!$region) {
            $this->count('regions', 'created');
            return Region::query()->create([
                'country_id' => $country?->id,
                'name' => $name,
                'description' => $name,
            ]);
        }

        $this->count('regions', 'skipped');
        return $region;
    }

    private function persistState(?Region $region, array $row, bool $dryRun): ?State
    {
        if ($dryRun) {
            return new State([
                'region_id' => $region?->id,
                'name' => $row['name'],
                'inec_state_code' => $row['state_code'] ?: null,
            ]);
        }

        $state = State::query()
            ->when($row['state_code'] ?? null, fn ($query, $code) => $query->where('inec_state_code', $code))
            ->when(!($row['state_code'] ?? null), fn ($query) => $query->where('name', $row['name']))
            ->first();

        $payload = [
            'region_id' => $region?->id,
            'name' => $row['name'],
            'inec_state_code' => $row['state_code'] ?: null,
        ];

        return $this->saveModel($state, State::class, $payload, 'states');
    }

    private function persistSenatorialDistrict(?State $state, string $name, bool $dryRun): ?SenatorialDistrict
    {
        if ($dryRun) {
            return new SenatorialDistrict([
                'state_id' => $state?->id,
                'name' => $name,
                'normalized_name' => $this->norm($name),
            ]);
        }

        $normalized = $this->norm($name);
        $district = SenatorialDistrict::query()
            ->where('state_id', $state?->id)
            ->where('normalized_name', $normalized)
            ->first();

        return $this->saveModel($district, SenatorialDistrict::class, [
            'state_id' => $state?->id,
            'name' => $name,
            'normalized_name' => $normalized,
        ], 'senatorial_districts');
    }

    private function persistFederalConstituency(?State $state, ?SenatorialDistrict $senatorial, string $name, bool $dryRun): ?FederalConstituency
    {
        if ($dryRun) {
            return new FederalConstituency([
                'state_id' => $state?->id,
                'senatorial_district_id' => $senatorial?->id,
                'name' => $name,
                'normalized_name' => $this->norm($name),
            ]);
        }

        $normalized = $this->norm($name);
        $federal = FederalConstituency::query()
            ->where('state_id', $state?->id)
            ->where('normalized_name', $normalized)
            ->first();

        return $this->saveModel($federal, FederalConstituency::class, [
            'state_id' => $state?->id,
            'senatorial_district_id' => $senatorial?->id,
            'name' => $name,
            'normalized_name' => $normalized,
        ], 'federal_constituencies');
    }

    private function persistLga(?State $state, ?SenatorialDistrict $senatorial, ?FederalConstituency $federal, array $row, bool $dryRun): ?LocalGovernmentArea
    {
        if ($dryRun) {
            return new LocalGovernmentArea([
                'state_id' => $state?->id,
                'senatorial_district_id' => $senatorial?->id,
                'federal_constituency_id' => $federal?->id,
                'inec_lga_code' => $row['lga_code'] ?: null,
                'name' => $row['name'],
            ]);
        }

        $lga = LocalGovernmentArea::query()
            ->where('state_id', $state?->id)
            ->when($row['lga_code'] ?? null, fn ($query, $code) => $query->where('inec_lga_code', $code))
            ->when(!($row['lga_code'] ?? null), fn ($query) => $query->where('name', $row['name']))
            ->first();

        return $this->saveModel($lga, LocalGovernmentArea::class, [
            'state_id' => $state?->id,
            'senatorial_district_id' => $senatorial?->id,
            'federal_constituency_id' => $federal?->id,
            'inec_lga_code' => $row['lga_code'] ?: null,
            'name' => $row['name'],
        ], 'lgas');
    }

    private function persistWard(?LocalGovernmentArea $lga, array $row, bool $dryRun): ?Ward
    {
        if ($dryRun) {
            return new Ward([
                'lga_id' => $lga?->id,
                'inec_ward_code' => $row['ward_code'] ?: null,
                'name' => $row['name'],
            ]);
        }

        $ward = Ward::query()
            ->where('lga_id', $lga?->id)
            ->when($row['ward_code'] ?? null, fn ($query, $code) => $query->where('inec_ward_code', $code))
            ->when(!($row['ward_code'] ?? null), fn ($query) => $query->where('name', $row['name']))
            ->first();

        return $this->saveModel($ward, Ward::class, [
            'lga_id' => $lga?->id,
            'inec_ward_code' => $row['ward_code'] ?: null,
            'name' => $row['name'],
        ], 'wards');
    }

    private function persistPollingUnit(?Ward $ward, ?SenatorialDistrict $senatorial, ?FederalConstituency $federal, array $row, bool $dryRun): ?PollingUnit
    {
        if ($dryRun) {
            return new PollingUnit([
                'ward_id' => $ward?->id,
                'senatorial_district_id' => $senatorial?->id,
                'federal_constituency_id' => $federal?->id,
                'inec_pu_code' => $row['pu_code'] ?: null,
                'inec_full_code' => $row['inec_full_code'] ?: null,
                'name' => $row['name'],
            ]);
        }

        $unit = PollingUnit::query()
            ->when($row['inec_full_code'] ?? null, fn ($query, $code) => $query->where('inec_full_code', $code))
            ->when(!($row['inec_full_code'] ?? null) && ($row['pu_code'] ?? null), fn ($query) => $query->where('ward_id', $ward?->id)->where('inec_pu_code', $row['pu_code']))
            ->when(!($row['inec_full_code'] ?? null) && !($row['pu_code'] ?? null), fn ($query) => $query->where('ward_id', $ward?->id)->where('name', $row['name']))
            ->first();

        return $this->saveModel($unit, PollingUnit::class, [
            'ward_id' => $ward?->id,
            'senatorial_district_id' => $senatorial?->id,
            'federal_constituency_id' => $federal?->id,
            'inec_pu_code' => $row['pu_code'] ?: null,
            'inec_full_code' => $row['inec_full_code'] ?: null,
            'name' => $row['name'],
        ], 'polling_units');
    }

    private function saveModel($model, string $class, array $payload, string $counter)
    {
        if (!$model) {
            $this->count($counter, 'created');
            return $class::query()->create($payload);
        }

        $changed = collect($payload)->contains(fn ($value, $key) => $model->{$key} != $value);
        if ($changed) {
            $model->fill($payload)->save();
            $this->count($counter, 'updated');
        } else {
            $this->count($counter, 'skipped');
        }

        return $model;
    }

    private function syncLocalLicenseScope(LocalLicense $license, array $scope, array $context): void
    {
        $localScope = $license->scope;
        if (!$localScope) {
            return;
        }

        $state = $scope['state'] ? $this->stateFromContext($context, $scope['state']) : null;
        $senatorial = $scope['senatorial'] ? $this->senatorialFromContext($context, $scope['senatorial']) : null;
        $federal = $scope['federal'] ? $this->federalFromContext($context, $scope['federal']) : null;
        $lga = $scope['lga'] ? $this->lgaFromContext($context, $scope['lga']) : null;

        $localScope->fill([
            'scope_type' => $scope['scope_type'],
            'scope_name' => $scope['scope_name'],
            'state_id' => $state?->id,
            'state_name' => $state?->name,
            'senatorial_district_id' => $senatorial?->id,
            'senatorial_district_name' => $senatorial?->name,
            'federal_constituency_id' => $federal?->id,
            'federal_constituency_name' => $federal?->name,
            'lga_id' => $lga?->id,
            'lga_name' => $lga?->name,
        ])->save();
    }

    private function stateFromContext(array $context, array $row): ?State
    {
        $key = $row['state_code'] ?: $this->norm($row['state_name'] ?? $row['name'] ?? '');

        return $context['states'][$key] ?? null;
    }

    private function senatorialFromContext(array $context, array $row): ?SenatorialDistrict
    {
        return $context['senatorial'][$this->boundaryKey([
            'state_code' => $row['state_code'] ?? null,
            'state_name' => $row['state_name'] ?? null,
            'name' => $row['senatorial_district_name'] ?? $row['name'] ?? null,
        ])] ?? null;
    }

    private function federalFromContext(array $context, array $row): ?FederalConstituency
    {
        return $context['federal'][$this->boundaryKey([
            'state_code' => $row['state_code'] ?? null,
            'state_name' => $row['state_name'] ?? null,
            'name' => $row['federal_constituency_name'] ?? $row['name'] ?? null,
        ])] ?? null;
    }

    private function lgaFromContext(array $context, array $row): ?LocalGovernmentArea
    {
        return $context['lgas'][$this->lgaKey($row)] ?? null;
    }

    private function wardFromContext(array $context, array $row): ?Ward
    {
        return $context['wards'][$this->wardKey($row)] ?? null;
    }

    private function findSourceState(string $sourcePath, ?string $name, ?string $code): ?array
    {
        return $this->findSourceRow(
            $sourcePath,
            'states.csv',
            fn (array $row) => ($code && ($row['state_code'] ?? null) === $code) || $this->matchesName($row['name'] ?? null, $name)
        );
    }

    private function findSourceRow(string $sourcePath, string $file, callable $matches): ?array
    {
        foreach ($this->readCsv($sourcePath, $file) as $row) {
            if ($matches($row)) {
                return $row;
            }
        }

        return null;
    }

    private function readCsv(string $sourcePath, string $file): \Generator
    {
        $path = $sourcePath.DIRECTORY_SEPARATOR.$file;
        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new RuntimeException("Unable to read {$file}.");
        }

        $headers = null;
        while (($row = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn ($header) => trim((string) $header), $row);
                continue;
            }

            if (count(array_filter($row, fn ($value) => $value !== null && $value !== '')) === 0) {
                continue;
            }

            $values = array_pad($row, count($headers), null);
            yield array_map(fn ($value) => is_string($value) ? trim($value) : $value, array_combine($headers, array_slice($values, 0, count($headers))));
        }

        fclose($handle);
    }

    private function headers(string $path): array
    {
        $handle = fopen($path, 'rb');
        $headers = $handle ? fgetcsv($handle) : [];
        if ($handle) {
            fclose($handle);
        }

        return array_map(fn ($header) => trim((string) $header), $headers ?: []);
    }

    private function ensureFilesExist(string $sourcePath): void
    {
        $missing = $this->missingSourceFiles($sourcePath);

        if ($missing) {
            throw new RuntimeException('Location data files are missing. Please upload the complete Campaign Manager package or contact support. Missing: '.implode(', ', $missing).'.');
        }
    }

    private function sameState(array $row, ?array $state): bool
    {
        if (!$state) {
            return false;
        }

        return (($row['state_code'] ?? null) && ($row['state_code'] ?? null) === ($state['state_code'] ?? null))
            || $this->matchesName($row['state_name'] ?? $row['name'] ?? null, $state['name'] ?? null);
    }

    private function sameLga(array $row, ?array $lga): bool
    {
        if (!$lga) {
            return false;
        }

        return (($row['lga_code'] ?? null) && ($row['lga_code'] ?? null) === ($lga['lga_code'] ?? null))
            || $this->matchesName($row['lga_name'] ?? $row['name'] ?? null, $lga['name'] ?? null);
    }

    private function matchesName(?string $left, ?string $right): bool
    {
        return $left !== null && $right !== null && $this->norm($left) === $this->norm($right);
    }

    private function boundaryKey(array $row): string
    {
        return ($row['state_code'] ?? $this->norm($row['state_name'] ?? '')).'|'.$this->norm($row['name'] ?? '');
    }

    private function lgaKey(array $row): string
    {
        return ($row['state_code'] ?? $this->norm($row['state_name'] ?? '')).'|'.(($row['lga_code'] ?? null) ?: $this->norm($row['lga_name'] ?? $row['name'] ?? ''));
    }

    private function wardKey(array $row): string
    {
        return $this->lgaKey($row).'|'.(($row['ward_code'] ?? null) ?: $this->norm($row['ward_name'] ?? $row['name'] ?? ''));
    }

    private function rawValue($scope, string $key): ?string
    {
        $raw = $scope->raw_payload ?: [];

        return $raw[$key] ?? $raw[Str::camel($key)] ?? null;
    }

    private function norm(?string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower((string) $value)));
    }

    private function count(string $level, string $type): void
    {
        $this->summary[$level][$type] = ($this->summary[$level][$type] ?? 0) + 1;
    }

    private function warn(string $message): void
    {
        if (count($this->warnings) < 50) {
            $this->warnings[] = $message;
        }
    }

    private function resetSummary(): void
    {
        $this->warnings = [];
        $this->summary = collect(['regions', 'states', 'senatorial_districts', 'federal_constituencies', 'lgas', 'wards', 'polling_units'])
            ->mapWithKeys(fn (string $level) => [$level => ['selected' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0]])
            ->all();
    }

    private function startRun(LocalLicense $license, array $scope, bool $dryRun): ?LocationProvisioningRun
    {
        if (!Schema::hasTable('location_provisioning_runs')) {
            return null;
        }

        return LocationProvisioningRun::query()->create([
            'local_license_id' => $license->id,
            'deployment_mode' => $this->deploymentMode->mode(),
            'package_type' => $license->package_type,
            'scope_type' => $scope['scope_type'],
            'scope_name' => $scope['scope_name'],
            'status' => $dryRun ? 'dry_run_running' : 'running',
            'started_at' => now(),
        ]);
    }

    private function completeRun(?LocationProvisioningRun $run, string $status, array $summary, array $warnings): void
    {
        if (!$run) {
            return;
        }

        $run->update([
            'status' => $status,
            'completed_at' => now(),
            'summary' => $summary,
            'warnings' => $warnings,
        ]);
    }

    private function failRun(?LocationProvisioningRun $run, string $message): void
    {
        if (!$run) {
            return;
        }

        $run->update([
            'status' => 'failed',
            'completed_at' => now(),
            'summary' => $this->summary,
            'warnings' => $this->warnings,
            'error_message' => $message,
        ]);
    }
}
