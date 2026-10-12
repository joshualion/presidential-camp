<?php

namespace App\Console\Commands;

use App\Services\DeploymentRegistrationService;
use Illuminate\Console\Command;

class CampaignPortalSync extends Command
{
    protected $signature = 'campaign:portal-sync';
    protected $description = 'Refresh messages only for deployments with explicit registration consent.';

    public function handle(DeploymentRegistrationService $registration): int
    {
        try {
            $registration->sync();
            $this->info('Optional Portal synchronization completed.');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Optional Portal synchronization could not complete. The application remains available.');
            return self::FAILURE;
        }
    }
}
