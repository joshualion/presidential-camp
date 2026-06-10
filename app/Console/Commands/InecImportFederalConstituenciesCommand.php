<?php

namespace App\Console\Commands;

use App\Support\InecNameFormatter;

class InecImportFederalConstituenciesCommand extends InecImportCommand
{
    protected $signature = 'inec:import-federal-constituencies
        {path : Path to prepared federal_constituencies CSV}
        {--dry-run : Validate without saving changes}
        {--update-existing : Update existing matching records}
        {--skip-existing : Skip existing matching records}
        {--create-missing-parents : Create missing parent state/senatorial rows when required}';

    protected $description = 'Import prepared INEC federal constituency CSV.';

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
            $constituency = $this->findFederalConstituency($row, $state, $district);

            if ($constituency) {
                if ($this->shouldSkipExisting()) {
                    $this->stats['skipped']++;
                    continue;
                }

                $changed = false;
                if ($this->shouldUpdateExisting()) {
                    $constituency->name = $row['name'];
                    $changed = true;
                }

                if ($district && empty($constituency->senatorial_district_id)) {
                    $constituency->senatorial_district_id = $district->id;
                    $changed = true;
                }

                $changed ? $this->saveModel($constituency, false) : $this->stats['skipped']++;
                continue;
            }

            $this->findFederalConstituency($row, $state, $district, true, true);
        }

        return $this->report();
    }
}
