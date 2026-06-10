<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\Ward;
use App\Support\InecCsv;
use App\Support\InecNameFormatter;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SplFileObject;

abstract class InecImportCommand extends Command
{
    protected array $stats = [
        'read' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    protected array $failures = [];

    private int $dryRunId = -1;

    protected function rows(): iterable
    {
        return InecCsv::readRows($this->argument('path'));
    }

    protected function optionsSignature(): string
    {
        return '
            {path : Path to prepared CSV}
            {--dry-run : Validate without saving changes}
            {--update-existing : Update existing matching records}
            {--skip-existing : Skip existing matching records}
            {--create-missing-parents : Create missing parent rows when required}';
    }

    protected function report(): int
    {
        $this->info("Total rows read: {$this->stats['read']}");
        $this->info("Rows created: {$this->stats['created']}");
        $this->info("Rows updated: {$this->stats['updated']}");
        $this->info("Rows skipped: {$this->stats['skipped']}");
        $this->info("Rows failed: {$this->stats['failed']}");

        if ($this->failures !== []) {
            $path = $this->writeFailureReport();
            $this->warn("Failure report: {$path}");
        }

        return self::SUCCESS;
    }

    protected function failRow(array $row, string $reason): void
    {
        $this->stats['failed']++;
        $row['failure_reason'] = $reason;
        $this->failures[] = $row;
    }

    protected function writeFailureReport(): string
    {
        $directory = storage_path('app/imports/inec/reports');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $name = Str::of($this->getName())->after('inec:import-')->replace('-', '_');
        $path = $directory.'/failed_'.$name.'_'.now()->format('Y_m_d_His').'.csv';
        $headers = array_keys($this->failures[0]);
        $file = new SplFileObject($path, 'w');
        $file->fputcsv($headers);

        foreach ($this->failures as $failure) {
            $file->fputcsv(array_map(fn ($header) => $failure[$header] ?? '', $headers));
        }

        return $path;
    }

    protected function shouldSkipExisting(): bool
    {
        return (bool) $this->option('skip-existing');
    }

    protected function shouldUpdateExisting(): bool
    {
        return (bool) $this->option('update-existing');
    }

    protected function dryRun(): bool
    {
        return (bool) $this->option('dry-run');
    }

    protected function mayCreateParents(): bool
    {
        return (bool) $this->option('create-missing-parents');
    }

    protected function saveModel(Model $model, bool $created, bool $count = true): void
    {
        if ($this->dryRun()) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), $this->dryRunId--);
            }
        } else {
            $model->save();
        }

        if ($count) {
            $this->stats[$created ? 'created' : 'updated']++;
        }
    }

    protected function findCountry(string $name, bool $create = false): ?Country
    {
        $name = InecNameFormatter::display($name ?: 'Nigeria');
        $country = Country::whereRaw('LOWER(name) = ?', [InecNameFormatter::key($name)])->first();

        if (! $country && $create) {
            $country = new Country(['uuid' => (string) Str::uuid(), 'name' => $name]);
            $this->saveModel($country, true, false);
        }

        return $country;
    }

    protected function findRegion(string $name, Country $country, bool $create = false): ?Region
    {
        $name = InecNameFormatter::display($name ?: 'Unknown');
        $region = Region::where('country_id', $country->id)
            ->whereRaw('LOWER(name) = ?', [InecNameFormatter::key($name)])
            ->first();

        if (! $region && $create) {
            $region = new Region(['country_id' => $country->id, 'name' => $name]);
            $this->saveModel($region, true, false);
        }

        return $region;
    }

    protected function findState(array $row, bool $create = false): ?State
    {
        $code = trim((string) ($row['state_code'] ?? ''));
        $name = InecNameFormatter::display($row['state_name'] ?? $row['name'] ?? '');

        $state = $code !== '' ? State::where('inec_state_code', $code)->first() : null;
        $state ??= State::whereRaw('LOWER(name) = ?', [InecNameFormatter::key($name)])->first();

        if (! $state && $create) {
            $country = $this->findCountry($row['country_name'] ?? 'Nigeria', true);
            $region = $this->findRegion($row['region_name'] ?? 'Unknown', $country, true);
            $state = new State(['name' => $name, 'inec_state_code' => $code ?: null, 'region_id' => $region->id]);
            $this->saveModel($state, true, false);
        }

        return $state;
    }

    protected function findLga(array $row, State $state, bool $create = false): ?LocalGovernmentArea
    {
        $code = trim((string) ($row['lga_code'] ?? ''));
        $name = InecNameFormatter::display($row['lga_name'] ?? $row['name'] ?? '');

        $lga = $code !== '' ? LocalGovernmentArea::where('state_id', $state->id)->where('inec_lga_code', $code)->first() : null;
        $lga ??= LocalGovernmentArea::where('state_id', $state->id)->whereRaw('LOWER(name) = ?', [InecNameFormatter::key($name)])->first();

        if (! $lga && $create) {
            $lga = new LocalGovernmentArea(['state_id' => $state->id, 'name' => $name, 'inec_lga_code' => $code ?: null]);
            $this->saveModel($lga, true, false);
        }

        return $lga;
    }

    protected function findWard(array $row, LocalGovernmentArea $lga, bool $create = false): ?Ward
    {
        $code = trim((string) ($row['ward_code'] ?? ''));
        $name = InecNameFormatter::display($row['ward_name'] ?? $row['name'] ?? '');

        $ward = $code !== '' ? Ward::where('lga_id', $lga->id)->where('inec_ward_code', $code)->first() : null;
        $ward ??= Ward::where('lga_id', $lga->id)->whereRaw('LOWER(name) = ?', [InecNameFormatter::key($name)])->first();

        if (! $ward && $create) {
            $ward = new Ward(['lga_id' => $lga->id, 'name' => $name, 'inec_ward_code' => $code ?: null]);
            $this->saveModel($ward, true, false);
        }

        return $ward;
    }

    protected function findSenatorialDistrict(array $row, State $state, bool $create = false, bool $count = false): ?SenatorialDistrict
    {
        $name = InecNameFormatter::display($row['senatorial_district_name'] ?? $row['name'] ?? '');
        $normalized = InecNameFormatter::key($name);

        if ($normalized === '') {
            return null;
        }

        $district = SenatorialDistrict::where('state_id', $state->id)->where('normalized_name', $normalized)->first();

        if (! $district && $create) {
            $district = new SenatorialDistrict(['state_id' => $state->id, 'name' => $name, 'normalized_name' => $normalized]);
            $this->saveModel($district, true, $count);
        }

        return $district;
    }

    protected function findFederalConstituency(array $row, State $state, ?SenatorialDistrict $district = null, bool $create = false, bool $count = false): ?FederalConstituency
    {
        $name = InecNameFormatter::display($row['federal_constituency_name'] ?? $row['name'] ?? '');
        $normalized = InecNameFormatter::key($name);

        if ($normalized === '') {
            return null;
        }

        $constituency = FederalConstituency::where('state_id', $state->id)->where('normalized_name', $normalized)->first();

        if (! $constituency && $create) {
            $constituency = new FederalConstituency([
                'state_id' => $state->id,
                'senatorial_district_id' => $district?->id,
                'name' => $name,
                'normalized_name' => $normalized,
            ]);
            $this->saveModel($constituency, true, $count);
        }

        return $constituency;
    }

    protected function fillMissingCodes(Model $model, array $attributes): bool
    {
        $changed = false;

        foreach ($attributes as $key => $value) {
            if ($value !== '' && empty($model->{$key})) {
                $model->{$key} = $value;
                $changed = true;
            }
        }

        return $changed;
    }
}
