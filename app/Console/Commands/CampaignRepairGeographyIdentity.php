<?php

namespace App\Console\Commands;

use App\Services\GeographyIdentityRepairService;
use Illuminate\Console\Command;

class CampaignRepairGeographyIdentity extends Command
{
    protected $signature = 'campaign:geography:repair-identity {--dry-run : Report duplicate merges without changing data}';

    protected $description = 'Repair duplicate geography rows created when installer scope records missed INEC identity codes.';

    public function handle(GeographyIdentityRepairService $repair): int
    {
        $summary = $repair->repair((bool) $this->option('dry-run'));
        $total = array_sum(array_map('intval', $summary));

        $this->line(($this->option('dry-run') ? 'Dry-run geography identity repair' : 'Geography identity repair').' complete.');
        foreach ($summary as $table => $count) {
            $this->line("{$table}: {$count}");
        }
        $this->line("Total duplicate groups: {$total}");

        return self::SUCCESS;
    }
}
