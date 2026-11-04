<?php

namespace App\Console\Commands;

use App\Services\Enterprise\EnterpriseBootstrapService;
use Illuminate\Console\Command;

class CampaignEnterpriseBootstrap extends Command
{
    protected $signature = 'campaign:enterprise:bootstrap';
    protected $description = 'Initialize enterprise permissions and synchronize registered add-on status safely';

    public function handle(EnterpriseBootstrapService $bootstrap): int
    {
        try {
            $result = $bootstrap->initialize();
        } catch (\Throwable) {
            $this->error('Enterprise permission setup failed. Retry the official update recovery process; existing customer data is preserved.');
            return self::FAILURE;
        }
        $this->info('Enterprise permissions initialized.');
        if ($result['synchronized']) {
            $this->info('Installation add-on status synchronized.');
        }
        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }
        return self::SUCCESS;
    }
}
