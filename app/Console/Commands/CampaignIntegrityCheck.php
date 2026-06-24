<?php

namespace App\Console\Commands;

use App\Services\ReleaseIntegrityService;
use Illuminate\Console\Command;

class CampaignIntegrityCheck extends Command
{
    protected $signature = 'campaign:integrity-check {--no-persist : Do not write the integrity status row}';

    protected $description = 'Verify release manifest signature and protected file hashes.';

    public function handle(ReleaseIntegrityService $integrity): int
    {
        $result = $integrity->check(! $this->option('no-persist'));

        $this->line('Campaign Manager release integrity');
        $this->line('Manifest present: '.($result['manifest_present'] ? 'yes' : 'no'));
        $this->line('Signature valid: '.$this->formatNullable($result['manifest_signature_valid']));
        $this->line('Protected files valid: '.$this->formatNullable($result['protected_files_valid']));
        $this->line('Tampered: '.($result['tampered'] ? 'yes' : 'no'));
        $this->line('Update entitlement state: '.$result['update_entitlement_state']);
        $this->line('App version: '.($result['app_version'] ?: 'unknown'));
        $this->line($result['message']);

        if (!empty($result['tamper_details'])) {
            $this->newLine();
            $this->warn('Affected files:');
            foreach ($result['tamper_details'] as $detail) {
                $this->line('- '.$detail['file'].' ['.$detail['status'].']');
            }
        }

        return $result['tampered'] ? self::FAILURE : self::SUCCESS;
    }

    private function formatNullable(?bool $value): string
    {
        return $value === null ? 'not checked' : ($value ? 'yes' : 'no');
    }
}
