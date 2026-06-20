<?php

namespace App\Console\Commands;

use App\Models\LocalLicense;
use App\Services\LicensedLocationProvisioningService;
use Illuminate\Console\Command;
use RuntimeException;

class CampaignProvisionLocations extends Command
{
    protected $signature = 'campaign:provision-locations
        {--dry-run : Preview the licensed location import without writing data}
        {--force : Write provisioned location records}
        {--summary : Print the final summary as JSON}
        {--source= : Override the prepared CSV source directory}';

    protected $description = 'Recovery/admin command for provisioning licensed Campaign Manager location data from prepared INEC CSV files.';

    public function handle(LicensedLocationProvisioningService $provisioning): int
    {
        $dryRun = !$this->option('force');
        $source = $this->option('source') ?: null;
        $license = LocalLicense::query()->with('scope')->latest('id')->first();

        $this->line('Campaign Manager licensed location provisioning');
        $this->warn('Normal fresh installs should use the browser installer. This command is for already-installed systems, recovery, or developer/admin maintenance.');
        $this->line('Mode: '.($dryRun ? 'dry-run' : 'write'));
        $this->line('Source: '.($source ?: $provisioning->defaultSourcePath()));

        if ($license) {
            $this->line('Package type: '.($license->package_type ?: 'not set'));
            $this->line('Scope type: '.($license->scope?->scope_type ?: 'not set'));
            $this->line('Scope name: '.($license->scope?->scope_name ?: $license->scope?->state_name ?: 'not set'));
        } else {
            $this->line('Local license: missing');
        }

        if (!$this->option('force')) {
            $this->warn('Preview only. Re-run with --force to write location records.');
        }

        try {
            $result = $provisioning->provision($license, $dryRun, $source);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->line('Status: '.$result['status']);
        $this->line('Files:');
        foreach (($result['files'] ?? []) as $file => $info) {
            $this->line(' - '.$file.': '.($info['exists'] ? 'found' : 'missing').' ['.implode(', ', $info['headers'] ?? []).']');
        }

        $this->line('Summary:');
        foreach (($result['summary'] ?? []) as $level => $counts) {
            $this->line(sprintf(
                ' - %s: selected=%d created=%d updated=%d skipped=%d',
                $level,
                $counts['selected'] ?? 0,
                $counts['created'] ?? 0,
                $counts['updated'] ?? 0,
                $counts['skipped'] ?? 0
            ));
        }

        foreach (($result['warnings'] ?? []) as $warning) {
            $this->warn($warning);
        }

        if ($this->option('summary')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        }

        return self::SUCCESS;
    }
}
