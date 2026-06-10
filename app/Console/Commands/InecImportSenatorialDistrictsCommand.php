<?php

namespace App\Console\Commands;

use App\Support\InecNameFormatter;

class InecImportSenatorialDistrictsCommand extends InecImportCommand
{
    protected $signature = 'inec:import-senatorial-districts
        {path : Path to prepared senatorial_districts CSV}
        {--dry-run : Validate without saving changes}
        {--update-existing : Update existing matching records}
        {--skip-existing : Skip existing matching records}
        {--create-missing-parents : Create missing parent state rows when required}';

    protected $description = 'Import prepared INEC senatorial district CSV.';

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

            $district = $this->findSenatorialDistrict($row, $state);
            if ($district) {
                if ($this->shouldSkipExisting()) {
                    $this->stats['skipped']++;
                    continue;
                }

                if ($this->shouldUpdateExisting() && $district->name !== $row['name']) {
                    $district->name = $row['name'];
                    $this->saveModel($district, false);
                } else {
                    $this->stats['skipped']++;
                }
                continue;
            }

            $this->findSenatorialDistrict($row, $state, true, true);
        }

        return $this->report();
    }
}
