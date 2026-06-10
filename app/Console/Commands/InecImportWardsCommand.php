<?php

namespace App\Console\Commands;

use App\Models\Ward;
use App\Support\InecNameFormatter;

class InecImportWardsCommand extends InecImportCommand
{
    protected $signature = 'inec:import-wards
        {path : Path to prepared wards CSV}
        {--dry-run : Validate without saving changes}
        {--update-existing : Update existing matching records}
        {--skip-existing : Skip existing matching records}
        {--create-missing-parents : Create missing parent state/LGA rows when required}';

    protected $description = 'Import prepared INEC wards CSV.';

    public function handle(): int
    {
        foreach ($this->rows() as $row) {
            $this->stats['read']++;
            $row['name'] = InecNameFormatter::display($row['name'] ?? '');
            $state = $this->findState($row, $this->mayCreateParents());
            $lga = $state ? $this->findLga($row, $state, $this->mayCreateParents()) : null;

            if (! $state || ! $lga) {
                $this->failRow($row, ! $state ? 'State not found' : 'LGA not found');
                continue;
            }

            $wardCode = trim((string) ($row['ward_code'] ?? ''));

            if ($wardCode === '') {
                $this->failRow($row, 'Ward code is required');
                continue;
            }

            $ward = Ward::where('lga_id', $lga->id)
                ->where('inec_ward_code', $wardCode)
                ->first();

            if ($ward) {
                if ($this->shouldSkipExisting()) {
                    $this->stats['skipped']++;
                    continue;
                }

                $ward->name = $row['name'];
                $ward->lga_id = $lga->id;
                $ward->inec_ward_code = $wardCode;

                $ward->isDirty() ? $this->saveModel($ward, false) : $this->stats['skipped']++;
                continue;
            }

            $this->saveModel(new Ward([
                'lga_id' => $lga->id,
                'name' => $row['name'],
                'inec_ward_code' => $wardCode,
            ]), true);
        }

        return $this->report();
    }
}
