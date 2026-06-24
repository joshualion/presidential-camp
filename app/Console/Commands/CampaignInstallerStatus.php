<?php

namespace App\Console\Commands;

use App\Services\DeploymentModeService;
use App\Services\InstallationStateService;
use App\Services\LocationProvisioningImportService;
use App\Support\SafeDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

class CampaignInstallerStatus extends Command
{
    protected $signature = 'campaign:installer-status {--check-portal : Attempt a non-sensitive portal reachability check}';

    protected $description = 'Show non-sensitive installer readiness diagnostics.';

    public function handle(DeploymentModeService $deploymentMode, InstallationStateService $installationState, LocationProvisioningImportService $locationProvisioning): int
    {
        $this->line('Campaign Manager installer diagnostics');
        $this->line('App URL: '.config('app.url'));
        $this->line('APP_KEY: '.(config('app.key') ? 'present' : 'missing'));
        $this->line('SESSION_DRIVER: '.config('session.driver'));
        $this->line('CACHE_STORE: '.config('cache.default'));
        $this->line('QUEUE_CONNECTION: '.config('queue.default'));
        $this->line('.env readable: '.(File::isReadable(base_path('.env')) ? 'yes' : 'no'));
        $this->line('Deployment mode: '.$deploymentMode->mode());
        $this->line('Installer enabled: '.($deploymentMode->installerEnabled() ? 'yes' : 'no'));
        $this->line('Installed lock: '.($installationState->installed() ? 'present' : 'missing'));
        $this->line('Activation domain: '.(parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'not detected'));
        $this->line('Storage writable: '.(is_writable(storage_path('app')) ? 'yes' : 'no'));
        $databaseReachable = SafeDatabase::canConnect();
        $this->line('Database configured: '.$this->databaseConfigured());
        $this->line('Database reachable: '.($databaseReachable ? 'yes' : 'no'));
        $this->line('Latest location setup: '.($databaseReachable ? $this->latestProvisioningRun($locationProvisioning) : 'not checked; database is unreachable'));

        foreach (['local_licenses', 'local_license_scopes', 'local_license_modules', 'license_check_logs'] as $table) {
            $this->line("Table {$table}: ".($databaseReachable ? (SafeDatabase::hasTable($table) ? 'present' : 'missing') : 'not checked; database is unreachable'));
        }

        if ($this->option('check-portal')) {
            $this->line('Portal API base: '.config('campaign.portal_api_base'));
            $this->line('Portal reachable: '.$this->portalReachable());
        }

        return self::SUCCESS;
    }

    private function portalReachable(): string
    {
        try {
            $response = Http::timeout(8)->acceptJson()->get(config('campaign.portal_api_base'));

            return $response->status() > 0 ? 'yes, HTTP '.$response->status() : 'no';
        } catch (Throwable $e) {
            return 'no';
        }
    }

    private function databaseConfigured(): string
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}", []);

        return $connection
            && !empty($config['host'])
            && array_key_exists('database', $config)
            && $config['database'] !== ''
            && array_key_exists('username', $config)
            ? 'yes'
            : 'no';
    }

    private function latestProvisioningRun(LocationProvisioningImportService $locationProvisioning): string
    {
        $run = $locationProvisioning->latestRun();
        if (!$run) {
            return 'none recorded';
        }

        return trim(sprintf(
            '#%d %s dataset=%s imported=%s/%s%s',
            $run->id,
            $run->status,
            $run->current_dataset ?: 'complete',
            $run->imported_records ?? 0,
            $run->total_records ?? 'unknown',
            $run->error_message ? ' error='.$this->shortError($run->error_message) : ''
        ));
    }

    private function shortError(string $message): string
    {
        $message = preg_replace('/\s+/', ' ', $message) ?: $message;
        $message = preg_replace('/SQLSTATE\[[^\]]+\].*/', 'Database write failed during location setup.', $message) ?: $message;

        return mb_strlen($message) > 180 ? mb_substr($message, 0, 177).'...' : $message;
    }
}
