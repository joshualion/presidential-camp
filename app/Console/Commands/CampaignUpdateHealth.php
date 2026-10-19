<?php

namespace App\Console\Commands;

use App\Services\OfficialUpdateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CampaignUpdateHealth extends Command
{
    protected $signature = 'campaign:updates-health {version}';

    protected $description = 'Verify upgraded application boot, version and database availability.';

    public function handle(OfficialUpdateService $updates): int
    {
        if ($updates->installedVersion() !== $this->argument('version')) {
            return self::FAILURE;
        }
        DB::select('SELECT 1');
        foreach (['users', 'system_settings', 'migrations'] as $table) {
            if (! Schema::hasTable($table)) {
                return self::FAILURE;
            }
        }
        $this->info('Upgraded application boot and database health verified.');

        return self::SUCCESS;
    }
}
