<?php

namespace App\Console\Commands;

use App\Support\InecNameFormatter;

class InecImportStatesCommand extends InecImportCommand
{
    protected $signature = 'inec:import-states
        {path : Path to prepared states CSV}
        {--dry-run : Validate without saving changes}
        {--update-existing : Update existing matching records}
        {--skip-existing : Skip existing matching records}
        {--create-missing-parents : Create missing country/region rows when required}';

    protected $description = 'Import prepared INEC states CSV.';

    public function handle(): int
    {
        foreach ($this->rows() as $row) {
            $this->stats['read']++;
            $row['name'] = InecNameFormatter::display($row['name'] ?? '');

            $country = $this->findCountry($row['country_name'] ?? 'Nigeria', $this->mayCreateParents());
            if (! $country) {
                $this->failRow($row, 'Country not found');
                continue;
            }

            $region = $this->findRegion($row['region_name'] ?? 'Unknown', $country, $this->mayCreateParents());
            if (! $region) {
                $this->failRow($row, 'Region not found');
                continue;
            }

            $state = $this->findState($row);
            if ($state) {
                if ($this->shouldSkipExisting()) {
                    $this->stats['skipped']++;
                    continue;
                }

                $changed = $this->fillMissingCodes($state, ['inec_state_code' => trim((string) ($row['state_code'] ?? ''))]);
                if ($this->shouldUpdateExisting()) {
                    $state->name = $row['name'];
                    $state->region_id = $region->id;
                    $changed = true;
                }

                $changed ? $this->saveModel($state, false) : $this->stats['skipped']++;
                continue;
            }

            $state = new \App\Models\State([
                'name' => $row['name'],
                'inec_state_code' => trim((string) ($row['state_code'] ?? '')) ?: null,
                'region_id' => $region->id,
            ]);
            $this->saveModel($state, true);
        }

        return $this->report();
    }
}
