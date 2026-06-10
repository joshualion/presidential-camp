<?php

namespace App\Console\Commands;

use App\Models\LocalGovernmentArea;
use App\Support\InecNameFormatter;

class InecImportLgasCommand extends InecImportCommand
{
    protected $signature = 'inec:import-lgas
        {path : Path to prepared local_government_areas CSV}
        {--dry-run : Validate without saving changes}
        {--update-existing : Update existing matching records}
        {--skip-existing : Skip existing matching records}
        {--create-missing-parents : Create missing parent state rows when required}';

    protected $description = 'Import prepared INEC local government area CSV.';

    public function handle(): int
    {
        foreach ($this->rows() as $row) {
            $this->stats['read']++;
            $row['name'] = InecNameFormatter::display($row['name'] ?? '');
            $state = $this->findState($row, $this->mayCreateParents());

            if (! $state) {
                $this->failRow($row, 'State not found');
                continue;
            }

            $district = $this->findSenatorialDistrict($row, $state, $this->mayCreateParents());
            $constituency = $this->findFederalConstituency($row, $state, $district, $this->mayCreateParents());
            $lga = $this->findLga($row, $state);
            if ($lga) {
                if ($this->shouldSkipExisting()) {
                    $this->stats['skipped']++;
                    continue;
                }

                $changed = $this->fillMissingCodes($lga, ['inec_lga_code' => trim((string) ($row['lga_code'] ?? ''))]);
                if ($district && empty($lga->senatorial_district_id)) {
                    $lga->senatorial_district_id = $district->id;
                    $changed = true;
                }

                if ($constituency && empty($lga->federal_constituency_id)) {
                    $lga->federal_constituency_id = $constituency->id;
                    $changed = true;
                }

                if ($this->shouldUpdateExisting()) {
                    $lga->name = $row['name'];
                    $lga->state_id = $state->id;
                    $lga->senatorial_district_id = $district?->id;
                    $lga->federal_constituency_id = $constituency?->id;
                    $changed = true;
                }

                $changed ? $this->saveModel($lga, false) : $this->stats['skipped']++;
                continue;
            }

            $this->saveModel(new LocalGovernmentArea([
                'state_id' => $state->id,
                'name' => $row['name'],
                'inec_lga_code' => trim((string) ($row['lga_code'] ?? '')) ?: null,
                'senatorial_district_id' => $district?->id,
                'federal_constituency_id' => $constituency?->id,
            ]), true);
        }

        return $this->report();
    }
}
