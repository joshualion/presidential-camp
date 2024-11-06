<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SeedAndRollbackMembers extends Command
{
    protected $signature = 'members:seed {--rollback}';
    protected $description = 'Seed members and optionally roll back the seeding';

    public function handle()
    {
        if ($this->option('rollback')) {
            $this->info('Rolling back member seeding...');
            User::where('access_level', 'user')->delete();
            $this->info('Rollback complete.');
        } else {
            $this->info('Seeding members...');
            DB::transaction(function () {
                $batchSize = 100;
                $totalUsers = 4000;

                for ($i = 0; $i < $totalUsers; $i += $batchSize) {
                    User::factory($batchSize)->create([
                        'access_level' => 'user',
                    ]);
                }
            });
            $this->info('Seeding complete.');
        }
    }
}
