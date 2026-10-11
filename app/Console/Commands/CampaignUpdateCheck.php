<?php

namespace App\Console\Commands;

use App\Services\OfficialUpdateService;
use Illuminate\Console\Command;

class CampaignUpdateCheck extends Command
{
    protected $signature = 'campaign:updates-check';
    protected $description = 'Check the official release feed without sending installation or campaign data.';

    public function handle(OfficialUpdateService $updates): int
    {
        try {
            $result = $updates->check();
            $this->info('Latest official version: '.$result['latest_version']);
            $this->line($result['message']);
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('The official update check could not be completed or verified.');
            return self::FAILURE;
        }
    }
}
