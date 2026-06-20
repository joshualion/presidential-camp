<?php

namespace App\Console\Commands;

use App\Services\InstallationStateService;
use Illuminate\Console\Command;

class CampaignInstallerReset extends Command
{
    protected $signature = 'campaign:installer-reset {--force : Remove the install lock without confirmation}';

    protected $description = 'Remove the self-hosted installer lock without modifying database tables or users.';

    public function handle(InstallationStateService $installationState): int
    {
        if (!$installationState->installed()) {
            $this->info('No installed.lock file exists.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Remove storage/app/installed.lock? Database tables and users will not be changed.')) {
            $this->warn('Installer reset cancelled.');
            return self::SUCCESS;
        }

        $installationState->remove();
        $this->info('Removed storage/app/installed.lock only.');

        return self::SUCCESS;
    }
}
