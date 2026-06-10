<?php

namespace App\Console\Commands;

use App\Support\InecCsv;
use App\Support\InecNameFormatter;
use App\Support\NigeriaRegionMap;
use Illuminate\Console\Command;
use SplFileObject;

class InecPrepareCsvCommand extends Command
{
    protected $signature = 'inec:prepare-csv
        {path : Path to the raw INEC polling unit CSV}
        {--state= : Limit to one state}
        {--lga= : Limit to one local government area}
        {--ward= : Limit to one ward}
        {--senatorial= : Limit to one senatorial district}
        {--federal-constituency= : Limit to one federal constituency}';

    protected $description = 'Prepare raw INEC polling unit CSV into normalized import-ready CSV files.';

    private array $headers = [
        'states' => ['country_name', 'region_name', 'state_code', 'name'],
        'local_government_areas' => ['state_code', 'state_name', 'lga_code', 'name', 'senatorial_district_name', 'federal_constituency_name'],
        'wards' => ['state_code', 'state_name', 'lga_code', 'lga_name', 'ward_code', 'name'],
        'senatorial_districts' => ['state_code', 'state_name', 'name'],
        'federal_constituencies' => ['state_code', 'state_name', 'senatorial_district_name', 'name'],
        'polling_units' => ['state_code', 'state_name', 'lga_code', 'lga_name', 'ward_code', 'ward_name', 'pu_code', 'inec_full_code', 'name', 'senatorial_district_name', 'federal_constituency_name'],
    ];

    public function handle(): int
    {
        $path = $this->argument('path');
        $outputDirectory = $this->outputDirectory();
        $files = $this->openOutputFiles($outputDirectory);
        $seen = array_fill_keys(array_keys($this->headers), []);
        $counts = array_fill_keys(array_keys($this->headers), 0);
        $lgas = [];
        $conflicts = [];
        $read = 0;

        foreach (InecCsv::readRows($path) as $row) {
            $read++;
            $clean = $this->cleanRow($row);

            if (! $this->matchesFilters($clean)) {
                continue;
            }

            $this->writeUnique($files, $seen, $counts, 'states', $clean['state_code'].'|'.$clean['state_name'], [
                'country_name' => 'Nigeria',
                'region_name' => NigeriaRegionMap::regionFor($clean['state_name']),
                'state_code' => $clean['state_code'],
                'name' => $clean['state_name'],
            ]);

            $this->trackLgaBoundary($lgas, $clean);

            $this->writeUnique($files, $seen, $counts, 'wards', $clean['state_code'].'|'.$clean['lga_code'].'|'.$clean['ward_code'].'|'.$clean['ward_name'], [
                'state_code' => $clean['state_code'],
                'state_name' => $clean['state_name'],
                'lga_code' => $clean['lga_code'],
                'lga_name' => $clean['lga_name'],
                'ward_code' => $clean['ward_code'],
                'name' => $clean['ward_name'],
            ]);

            $this->writeUnique($files, $seen, $counts, 'senatorial_districts', $clean['state_code'].'|'.$clean['senatorial_district_name'], [
                'state_code' => $clean['state_code'],
                'state_name' => $clean['state_name'],
                'name' => $clean['senatorial_district_name'],
            ]);

            $this->writeUnique($files, $seen, $counts, 'federal_constituencies', $clean['state_code'].'|'.$clean['federal_constituency_name'], [
                'state_code' => $clean['state_code'],
                'state_name' => $clean['state_name'],
                'senatorial_district_name' => $clean['senatorial_district_name'],
                'name' => $clean['federal_constituency_name'],
            ]);

            $this->writeUnique($files, $seen, $counts, 'polling_units', $clean['inec_full_code'] ?: implode('|', [$clean['state_code'], $clean['lga_code'], $clean['ward_code'], $clean['pu_code'], $clean['name']]), [
                'state_code' => $clean['state_code'],
                'state_name' => $clean['state_name'],
                'lga_code' => $clean['lga_code'],
                'lga_name' => $clean['lga_name'],
                'ward_code' => $clean['ward_code'],
                'ward_name' => $clean['ward_name'],
                'pu_code' => $clean['pu_code'],
                'inec_full_code' => $clean['inec_full_code'],
                'name' => $clean['name'],
                'senatorial_district_name' => $clean['senatorial_district_name'],
                'federal_constituency_name' => $clean['federal_constituency_name'],
            ]);
        }

        $this->writeLgas($files, $seen, $counts, $lgas, $conflicts);

        foreach ($files as $file) {
            $file = null;
        }

        $this->info("Rows read: {$read}");
        foreach ($counts as $name => $count) {
            $this->info("{$name}.csv: {$count} rows");
        }
        foreach ($conflicts as $conflict) {
            $this->warn($conflict);
        }
        $this->info("Output: {$outputDirectory}");

        return self::SUCCESS;
    }

    private function cleanRow(array $row): array
    {
        return [
            'state_name' => InecNameFormatter::display($row['state'] ?? ''),
            'lga_name' => InecNameFormatter::display($row['lg'] ?? ''),
            'ward_name' => InecNameFormatter::display($row['ward'] ?? ''),
            'state_code' => trim((string) ($row['state_code'] ?? '')),
            'lga_code' => trim((string) ($row['lg_code'] ?? '')),
            'ward_code' => trim((string) ($row['ward_code'] ?? '')),
            'pu_code' => trim((string) ($row['pu_code'] ?? '')),
            'inec_full_code' => trim((string) ($row['code'] ?? '')),
            'name' => InecNameFormatter::display($row['location'] ?? ''),
            'senatorial_district_name' => InecNameFormatter::display($row['senatorial'] ?? ''),
            'federal_constituency_name' => InecNameFormatter::display($row['house_of_rep'] ?? ''),
        ];
    }

    private function matchesFilters(array $row): bool
    {
        $filters = [
            'state' => 'state_name',
            'lga' => 'lga_name',
            'ward' => 'ward_name',
            'senatorial' => 'senatorial_district_name',
            'federal-constituency' => 'federal_constituency_name',
        ];

        foreach ($filters as $option => $field) {
            if ($this->option($option) && InecNameFormatter::key($this->option($option)) !== InecNameFormatter::key($row[$field])) {
                return false;
            }
        }

        return true;
    }

    private function outputDirectory(): string
    {
        $base = storage_path('app/imports/inec/prepared');
        $state = $this->option('state') ? InecNameFormatter::slug($this->option('state')) : null;

        if (! $state) {
            return "{$base}/all";
        }

        $path = "{$base}/{$state}";

        if ($this->option('senatorial')) {
            return $path.'/senatorial/'.InecNameFormatter::slug($this->option('senatorial'));
        }

        if ($this->option('federal-constituency')) {
            return $path.'/federal-constituency/'.InecNameFormatter::slug($this->option('federal-constituency'));
        }

        if ($this->option('lga') && $this->option('ward')) {
            return $path.'/lga/'.InecNameFormatter::slug($this->option('lga')).'/ward/'.InecNameFormatter::slug($this->option('ward'));
        }

        if ($this->option('lga')) {
            return $path.'/lga/'.InecNameFormatter::slug($this->option('lga'));
        }

        return $path;
    }

    private function openOutputFiles(string $outputDirectory): array
    {
        if (! is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0775, true);
        }

        $files = [];

        foreach ($this->headers as $name => $headers) {
            $file = new SplFileObject("{$outputDirectory}/{$name}.csv", 'w');
            $file->fputcsv($headers);
            $files[$name] = $file;
        }

        return $files;
    }

    private function writeUnique(array $files, array &$seen, array &$counts, string $name, string $key, array $row): void
    {
        if ($key === '' || isset($seen[$name][$key])) {
            return;
        }

        $seen[$name][$key] = true;
        $files[$name]->fputcsv(array_map(fn ($header) => $row[$header] ?? '', $this->headers[$name]));
        $counts[$name]++;
    }

    private function trackLgaBoundary(array &$lgas, array $row): void
    {
        $key = $row['state_code'].'|'.$row['lga_code'].'|'.$row['lga_name'];

        if (! isset($lgas[$key])) {
            $lgas[$key] = [
                'row' => [
                    'state_code' => $row['state_code'],
                    'state_name' => $row['state_name'],
                    'lga_code' => $row['lga_code'],
                    'name' => $row['lga_name'],
                ],
                'senatorial' => [],
                'federal' => [],
            ];
        }

        if ($row['senatorial_district_name'] !== '') {
            $lgas[$key]['senatorial'][$row['senatorial_district_name']] = true;
        }

        if ($row['federal_constituency_name'] !== '') {
            $lgas[$key]['federal'][$row['federal_constituency_name']] = true;
        }
    }

    private function writeLgas(array $files, array &$seen, array &$counts, array $lgas, array &$conflicts): void
    {
        foreach ($lgas as $key => $lga) {
            $senatorial = array_keys($lga['senatorial']);
            $federal = array_keys($lga['federal']);
            $hasConflict = count($senatorial) > 1 || count($federal) > 1;
            $isClean = count($senatorial) === 1 && count($federal) === 1 && ! $hasConflict;

            if ($hasConflict) {
                $conflicts[] = sprintf(
                    'LGA boundary conflict: %s has %d senatorial district(s) [%s] and %d federal constituenc(y/ies) [%s]. LGA-level boundary columns left blank.',
                    $lga['row']['name'],
                    count($senatorial),
                    implode(' | ', $senatorial),
                    count($federal),
                    implode(' | ', $federal),
                );
            }

            $this->writeUnique($files, $seen, $counts, 'local_government_areas', $key, [
                ...$lga['row'],
                'senatorial_district_name' => $isClean ? $senatorial[0] : '',
                'federal_constituency_name' => $isClean ? $federal[0] : '',
            ]);
        }
    }
}
