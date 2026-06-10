<?php

namespace App\Console\Commands;

use App\Models\PollingUnit;

class InecImportPollingUnitsCommand extends InecImportCommand
{
    protected $signature = 'inec:import-polling-units
        {path : Path to prepared polling_units CSV}
        {--dry-run : Validate without saving changes}
        {--update-existing : Update existing matching records}
        {--skip-existing : Skip existing matching records}
        {--create-missing-parents : Create missing parent state/LGA/ward/electoral rows when required}';

    protected $description = 'Import prepared INEC polling unit CSV.';

    public function handle(): int
    {
        foreach ($this->rows() as $row) {
            $this->stats['read']++;
            $row = $this->trimRow($row);

            if ($reason = $this->missingRequiredField($row)) {
                $this->failRowWithNumber($row, $reason);
                continue;
            }

            $state = $this->findState($row, $this->mayCreateParents());
            $lga = $state ? $this->findLga($row, $state, $this->mayCreateParents()) : null;
            $ward = $lga ? $this->findWard($row, $lga, $this->mayCreateParents()) : null;

            if (! $state || ! $lga || ! $ward) {
                $this->failRowWithNumber($row, ! $state ? 'State not found' : (! $lga ? 'LGA not found' : 'Ward not found'));
                continue;
            }

            $district = $this->findSenatorialDistrict($row, $state, $this->mayCreateParents());
            $constituency = $this->findFederalConstituency($row, $state, $district, $this->mayCreateParents());
            $pollingUnit = PollingUnit::where('inec_full_code', $row['inec_full_code'])->first();

            if ($pollingUnit) {
                if ($this->shouldSkipExisting()) {
                    $this->logSkippedRow($row, 'Polling unit already exists');
                    $this->stats['skipped']++;
                    continue;
                }

                $pollingUnit->fill($this->pollingUnitAttributes($row, $ward->id, $district?->id, $constituency?->id));

                if ($pollingUnit->isDirty()) {
                    $this->saveModel($pollingUnit, false);
                } else {
                    $this->logSkippedRow($row, 'Polling unit already up to date');
                    $this->stats['skipped']++;
                }

                continue;
            }

            $this->saveModel(new PollingUnit([
                'inec_full_code' => trim((string) ($row['inec_full_code'] ?? '')) ?: null,
                ...$this->pollingUnitAttributes($row, $ward->id, $district?->id, $constituency?->id),
            ]), true);
        }

        return $this->report();
    }

    private function trimRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = trim($value);
            }
        }

        return $row;
    }

    private function missingRequiredField(array $row): ?string
    {
        foreach (['state_code', 'lga_code', 'ward_code', 'pu_code', 'inec_full_code', 'name'] as $field) {
            if (($row[$field] ?? '') === '') {
                return "Missing required field: {$field}";
            }
        }

        return null;
    }

    private function pollingUnitAttributes(array $row, int $wardId, ?int $districtId, ?int $constituencyId): array
    {
        return [
            'ward_id' => $wardId,
            'name' => $row['name'],
            'inec_pu_code' => str_pad($row['pu_code'], 3, '0', STR_PAD_LEFT),
            'senatorial_district_id' => $districtId,
            'federal_constituency_id' => $constituencyId,
        ];
    }

    private function failRowWithNumber(array $row, string $reason): void
    {
        $row['row_number'] = $row['__row_number'] ?? null;
        unset($row['__row_number']);

        $this->warn("Skipping row {$row['row_number']}: {$reason}");
        $this->failRow($row, $reason);
    }

    private function logSkippedRow(array $row, string $reason): void
    {
        $rowNumber = $row['__row_number'] ?? 'unknown';

        $this->line("Skipping row {$rowNumber}: {$reason}");
    }
}
