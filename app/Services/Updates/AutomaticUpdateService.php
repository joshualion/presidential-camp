<?php

namespace App\Services\Updates;

use App\Services\Updates\UpdateException as RuntimeException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AutomaticUpdateService
{
    public function __construct(private UpdatePolicy $policy, private DatabaseSnapshot $database, private UpdatePortalClient $portal) {}

    public function readiness(): int
    {
        if (PHP_SAPI !== 'cli' && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN) && ! filter_var(ini_get('opcache.validate_timestamps'), FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException('This host requires a PHP worker restart after file changes. Use an operator-assisted deployment.');
        }
        if (realpath(storage_path()) !== realpath(base_path('storage'))) {
            throw new RuntimeException('A custom storage directory needs an operator-reviewed update configuration.');
        }
        if (is_file(storage_path('framework/down'))) {
            throw new RuntimeException('An installation already in maintenance needs operator review first.');
        }
        if (PHP_OS_FAMILY === 'Windows' || ! is_executable('/bin/sh') || ! is_executable('/usr/bin/nohup')) {
            throw new RuntimeException('This host cannot run background automatic updates. Free manual upgrades remain available.');
        }
        UpdateProcess::php();
        $size = $this->database->preflight();
        foreach (glob(UpdatePaths::root().'/jobs/*/journal.json') as $path) {
            $j = json_decode(file_get_contents($path), true);
            if (in_array($j['state'] ?? '', ['queued', 'checking', 'maintenance', 'backing_up', 'applying', 'migrating', 'verifying', 'reopening', 'recovering', 'recovery_required'], true)) {
                throw new RuntimeException('Finish or recover the current update before starting another.');
            }
        }
        if (! in_array(config('queue.default'), ['database', 'sync', 'null'], true)) {
            throw new RuntimeException('This queue backend needs an operator-reviewed worker pause before automatic updates.');
        }
        if (config('queue.default') === 'database' && Schema::hasTable('jobs') && DB::table('jobs')->whereNotNull('reserved_at')->exists()) {
            throw new RuntimeException('Wait for active queue jobs to finish before updating.');
        }
        if (disk_free_space(UpdatePaths::root()) < 67108864 + 8 * $size) {
            throw new RuntimeException('Insufficient disk space for verified update backups.');
        }

        return $size;
    }

    public function preflight(string $stage): array
    {
        $size = $this->readiness();
        $plan = $this->policy->inspect($stage);
        $needed = 67108864 + 8 * $size;
        foreach ($plan['files'] as $name => $hash) {
            $destination = UpdatePaths::destination($name);
            $parent = dirname($destination);
            while (! is_dir($parent)) {
                $parent = dirname($parent);
            }
            if (! is_writable($parent) || (is_file($destination) && ! is_writable($destination))) {
                throw new RuntimeException('Application file permissions do not allow automatic updates.');
            }
            if (is_file($destination)) {
                $needed += filesize($destination);
            }
        }
        if (disk_free_space(UpdatePaths::root()) < $needed) {
            throw new RuntimeException('Insufficient disk space for verified update backups.');
        }
        if (config('queue.default') === 'database' && Schema::hasTable('jobs') && DB::table('jobs')->whereNotNull('reserved_at')->exists()) {
            throw new RuntimeException('Wait for active queue jobs to finish before updating.');
        }
        if (! in_array(config('queue.default'), ['database', 'sync', 'null'], true)) {
            throw new RuntimeException('This queue backend needs an operator-reviewed worker pause before automatic updates.');
        }

        return $plan;
    }

    public function start(string $stage): array
    {
        $lock = fopen(UpdatePaths::root().'/apply.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Another update operation is running.');
        }
        try {
            $plan = $this->preflight($stage);
            $id = (string) Str::uuid();
            $dir = UpdatePaths::job($id);
            if (! mkdir($dir, 0700, true)) {
                throw new RuntimeException('Cannot create the update job.');
            }
            $job = $plan + ['id' => $id, 'package_sha256' => $plan['package_sha256'], 'state' => 'queued', 'message' => 'Preparing the automatic update.', 'created_at' => now()->toIso8601String(), 'migrations_started' => false, 'base_path' => realpath(base_path()), 'php_cli' => UpdateProcess::php()];
            $job['package_type'] = $this->portal->packageType();
            $job['authorization'] = $this->portal->authorize($job);
            $token = bin2hex(random_bytes(32));
            $job['status_token_hash'] = hash('sha256', $token);
            UpdatePaths::write($dir.'/journal.json', $job);
            mkdir($dir.'/engine', 0700);
            foreach (glob(__DIR__.'/*.php') as $source) {
                copy($source, $dir.'/engine/'.basename($source));
                chmod($dir.'/engine/'.basename($source), 0600);
            }
            $worker = '<?php umask(0077); set_time_limit(0); $base='.var_export(base_path(), true).'; require $base."/vendor/autoload.php"; foreach(glob($base."/storage/app/updates/bootstrap/services/*.php") as $file) require_once $file; foreach(glob(__DIR__."/engine/*.php") as $file) require_once $file; $app=require $base."/bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); if(is_file($base."/storage/app/updates/bootstrap/updates.php")){config(["updates"=>require $base."/storage/app/updates/bootstrap/updates.php", "release.signing.public_key"=>file_get_contents($base."/storage/app/updates/bootstrap/trusted.pem")]);} try {$app->make(App\\Services\\Updates\\UpdateWorker::class)->run($argv[1]);} catch(Throwable $e){fwrite(STDERR,"Updater stopped; review private journal.\\n");exit(1);}';
            file_put_contents($dir.'/worker.php', str_replace('\\\\', '\\', $worker));
            chmod($dir.'/worker.php', 0600);
            $supervisor = '<?php umask(0077); require __DIR__."/engine/UpdateSupervisor.php"; App\\Services\\Updates\\UpdateSupervisor::run(__DIR__, PHP_BINARY, in_array("--recover-only", $argv, true));';
            if (file_put_contents($dir.'/supervise.php', $supervisor) === false || ! chmod($dir.'/supervise.php', 0600)) {
                throw new RuntimeException('Cannot create the independent update supervisor.');
            }
            try {
                $this->launch($id);
            } catch (\Throwable $e) {
                $job['state'] = 'failed';
                $job['message'] = 'The update worker could not start. Your purchase can be retried.';
                UpdatePaths::write($dir.'/journal.json', $job);
                try {
                    $this->portal->finish($job, false);
                } catch (\Throwable) {
                } throw $e;
            }

            return ['id' => $id, 'status_token' => $token];
        } catch (\Throwable $e) {
            if (! empty($job['authorization'])) {
                $job['state'] = 'failed';
                $job['message'] = 'Update startup stopped before application changes. Your purchase remains available for retry.';
                try {
                    UpdatePaths::write($dir.'/journal.json', $job);
                } catch (\Throwable) {
                }
                try {
                    $this->portal->finish($job, false);
                } catch (\Throwable) {
                }
            }
            throw $e;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    protected function launch(string $id): void
    {
        UpdateProcess::launch($id);
    }

    public function cancelQueued(string $id): void
    {
        $lock = fopen(UpdatePaths::root().'/apply.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('An update worker is active.');
        }
        try {
            $path = UpdatePaths::job($id).'/journal.json';
            $job = json_decode(file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
            if ($job['state'] !== 'queued') {
                throw new RuntimeException('Only an update that has not started can be cancelled. Recover an interrupted running update instead.');
            }
            $this->portal->finish($job, false);
            $job['state'] = 'failed';
            $job['message'] = 'The queued update was cancelled before changing the application. Your purchase can be retried.';
            UpdatePaths::write($path, $job);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function discard(string $stage): void
    {
        $lock = fopen(UpdatePaths::root().'/apply.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('An update is running.');
        }
        try {
            foreach (glob(UpdatePaths::root().'/jobs/*/journal.json') as $p) {
                $j = json_decode(file_get_contents($p), true);
                if (! is_array($j) || ! is_string($j['state'] ?? null)) {
                    throw new RuntimeException('An unreadable update journal needs recovery review before removing packages.');
                }
                if (($j['stage_id'] ?? null) === $stage && ! in_array($j['state'] ?? '', ['completed', 'failed', 'recovered'], true)) {
                    throw new RuntimeException('This package belongs to an active or unrecovered update.');
                }
            }
            $directory = UpdatePaths::stage($stage);
            if (is_dir($directory) && (! File::deleteDirectory($directory) || is_dir($directory))) {
                throw new RuntimeException('The unused prepared package could not be removed. Check private update storage permissions and retry.');
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
