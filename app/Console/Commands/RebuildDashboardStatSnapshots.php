<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DashboardStatSnapshotService;
use Illuminate\Console\Command;

class RebuildDashboardStatSnapshots extends Command
{
    protected $signature = 'dashboard:rebuild-stat-snapshots {--access-level=* : Limit rebuild to one or more access levels}';

    protected $description = 'Rebuild precomputed dashboard statistic snapshots for admin dashboard polling endpoints.';

    public function handle(DashboardStatSnapshotService $snapshotService): int
    {
        $accessLevels = $this->option('access-level') ?: [
            'superadmin',
            'nationaladmin',
            'regionaladmin',
            'stateadmin',
            'senatorialadmin',
            'federaladmin',
            'lgaadmin',
            'wardadmin',
            'puadmin',
        ];

        $rebuilt = 0;

        User::query()
            ->whereIn('access_level', $accessLevels)
            ->orderBy('access_level')
            ->orderBy('id')
            ->get()
            ->unique(fn (User $user) => implode(':', [
                $user->access_level,
                $user->region_id,
                $user->state_id,
                $user->senatorial_district_id,
                $user->federal_constituency_id,
                $user->lga_id,
                $user->ward_id,
                $user->polling_unit_id,
            ]))
            ->each(function (User $user) use ($snapshotService, &$rebuilt) {
                $snapshotService->refreshForUser($user);
                $rebuilt++;
                $this->line("Rebuilt {$user->access_level} snapshot #{$rebuilt}");
            });

        $this->info("Dashboard stat snapshots rebuilt: {$rebuilt}");

        return self::SUCCESS;
    }
}
