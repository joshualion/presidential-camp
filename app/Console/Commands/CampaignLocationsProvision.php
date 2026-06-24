<?php

namespace App\Console\Commands;

use App\Models\LocalLicense;
use App\Services\LocationProvisioningException;
use App\Services\LocationProvisioningImportService;
use Illuminate\Console\Command;
use RuntimeException;

class CampaignLocationsProvision extends Command
{
    protected $signature = 'campaign:locations:provision
        {--batch-size=500 : Number of portal records to request per batch}
        {--summary : Print the final summary as JSON}';

    protected $description = 'Provision licensed Campaign Manager location data from the portal API.';

    public function handle(LocationProvisioningImportService $provisioning): int
    {
        $license = LocalLicense::query()->with('scope')->latest('id')->first();
        $batchSize = max(1, (int) $this->option('batch-size'));

        $this->line('Campaign Manager portal location provisioning');
        $this->line('Batch size: '.$batchSize);

        if ($license) {
            $this->line('Package type: '.($license->package_type ?: 'not set'));
            $this->line('Activation ID: '.($license->activation_id ?: 'missing'));
            $this->line('Scope: '.($license->scope?->scope_name ?: $license->scope?->state_name ?: 'not set'));
        } else {
            $this->error('Local license: missing');

            return self::FAILURE;
        }

        try {
            $result = $provisioning->provision($license, $batchSize, function (array $progress): void {
                $this->line(sprintf(
                    '%s: %s/%s records (%s%%)',
                    $progress['stage_label'] ?? 'Provisioning',
                    $progress['imported_records'] ?? 0,
                    $progress['total_records'] ?? 0,
                    $progress['percent'] ?? 0
                ));
            });
        } catch (LocationProvisioningException|RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Location provisioning completed.');

        foreach (($result['summary'] ?? []) as $dataset => $counts) {
            if (str_starts_with((string) $dataset, '_')) {
                continue;
            }

            $this->line(sprintf(
                ' - %s: imported=%d batches=%d',
                $dataset,
                $counts['imported'] ?? 0,
                $counts['batches'] ?? 0
            ));
        }

        if ($this->option('summary')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        }

        return self::SUCCESS;
    }
}
