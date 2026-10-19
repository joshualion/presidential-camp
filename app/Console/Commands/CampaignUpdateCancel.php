<?php

namespace App\Console\Commands;

use App\Services\Updates\AutomaticUpdateService;
use Illuminate\Console\Command;

class CampaignUpdateCancel extends Command
{
    protected $signature = 'campaign:updates-cancel {job : UUID of an update still queued}';

    protected $description = 'Release a queued update reservation before its worker changes application files';

    public function handle(AutomaticUpdateService $updates): int
    {
        try {
            $updates->cancelQueued((string) $this->argument('job'));
            $this->info('Queued update cancelled. The purchase can be retried.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Cancellation stopped. Verify the private journal and active worker; use recovery for an interrupted running update.');

            return self::FAILURE;
        }
    }
}
