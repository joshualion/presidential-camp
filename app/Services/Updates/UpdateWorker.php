<?php

namespace App\Services\Updates;

use App\Services\Updates\UpdateException as RuntimeException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class UpdateWorker
{
    private array $job;

    private string $directory;

    public function __construct(private UpdatePolicy $policy, private DatabaseSnapshot $database, private UpdatePortalClient $portal) {}

    private function progress(string $state, string $message): void
    {
        $this->job['state'] = $state;
        $this->job['message'] = $message;
        $this->job['updated_at'] = now()->toIso8601String();
        UpdatePaths::write($this->directory.'/journal.json', $this->job);
    }

    public function run(string $id): void
    {
        umask(0077);
        set_time_limit(0);
        $this->directory = UpdatePaths::job($id);
        $lock = fopen(UpdatePaths::root().'/apply.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX)) {
            throw new RuntimeException('Cannot acquire update lock.');
        }
        $previousJobDirectory = getenv('CAMPAIGN_UPDATE_JOB_DIR');
        putenv('CAMPAIGN_UPDATE_JOB_DIR='.$this->directory);
        try {
            $this->job = json_decode(file_get_contents($this->directory.'/journal.json'), true, 64, JSON_THROW_ON_ERROR);
            if ($this->job['state'] !== 'queued') {
                return;
            }
            $this->progress('checking', 'Verifying the package and paid update authorisation.');
            $plan = $this->policy->inspect($this->job['stage_id']);
            foreach (['source_version', 'target_version', 'manifest_hash', 'files', 'pending_migrations'] as $key) {
                if ($plan[$key] !== $this->job[$key]) {
                    throw new RuntimeException('The prepared update plan changed.');
                }
            }
            $this->portal->authorize($this->job);
            if (is_file(storage_path('framework/down'))) {
                throw new RuntimeException('Maintenance mode was enabled by another operation.');
            }
            $this->progress('maintenance', 'Pausing the application for a consistent update.');
            UpdateProcess::artisan(['down', '--retry=5']);
            $this->job['maintenance_hash'] = hash_file('sha256', storage_path('framework/down'));
            $this->progress('backing_up', 'Backing up and verifying the database and affected application files.');
            // Maintenance prevents new normal jobs; allow existing jobs to drain.
            $deadline = microtime(true) + 30;
            while (config('queue.default') === 'database' && Schema::hasTable('jobs') && DB::table('jobs')->whereNotNull('reserved_at')->exists() && microtime(true) < $deadline) {
                usleep(250000);
            }
            if (config('queue.default') === 'database' && Schema::hasTable('jobs') && DB::table('jobs')->whereNotNull('reserved_at')->exists()) {
                throw new RuntimeException('A queue job is still active.');
            }
            $this->backup();
            $this->progress('applying', 'Applying verified application files. Configuration and uploads are preserved.');
            foreach ($this->job['files'] as $name => $hash) {
                $this->replaceFile($name, $hash);
            }
            $migrationDir = $this->directory.'/approved-migrations';
            File::ensureDirectoryExists($migrationDir, 0700);
            foreach ($this->job['pending_migrations'] as $name => $hash) {
                $source = UpdatePaths::stage($this->job['stage_id']).'/application/database/migrations/'.$name.'.php';
                if (! hash_equals($hash, hash_file('sha256', $source)) || ! copy($source, $migrationDir.'/'.$name.'.php')) {
                    throw new RuntimeException('Approved migration verification failed.');
                }
            }
            UpdateProcess::artisan(['optimize:clear']);
            if ($this->job['pending_migrations']) {
                $this->job['migrations_started'] = true;
                $this->progress('migrating', 'Running only reviewed, pending upgrade migrations.');
                UpdateProcess::artisan(['migrate', '--force', '--no-interaction', '--realpath', '--path='.$migrationDir]);
            }
            UpdateProcess::artisan(['campaign:enterprise:bootstrap']);
            $this->progress('verifying', 'Checking the upgraded application, database and installed version.');
            UpdateProcess::artisan(['campaign:updates-health', $this->job['target_version']]);
            UpdateProcess::artisan(['config:cache']);
            UpdateProcess::artisan(['view:cache']);
            // Commit the verified outcome BEFORE reopening. A crash after this
            // point must never roll back a database that has accepted new writes.
            $this->job['installation_verified'] = true;
            $this->progress('reopening', 'The update passed verification. Reopening the application.');
            $this->releaseMaintenance();
            try {
                UpdateProcess::artisan(['queue:restart']);
            } catch (\Throwable) {
                $this->job['worker_notice'] = 'Restart queue workers if your hosting requires it.';
            }
            try {
                $this->portal->finish($this->job, true);
                $this->job['portal_receipt_pending'] = false;
            } catch (\Throwable) {
                $this->job['portal_receipt_pending'] = true;
            }
            File::deleteDirectory(UpdatePaths::stage($this->job['stage_id']));
            $bridge = public_path('campaign-update-bootstrap.php');
            $privateBridge = storage_path('app/updates/bootstrap/entry.php');
            if (is_file($bridge) && is_file($privateBridge) && hash_equals(hash_file('sha256', $privateBridge), hash_file('sha256', $bridge))) {
                File::delete($bridge);
                File::deleteDirectory(storage_path('app/updates/bootstrap'));
            }
            $this->progress('completed', 'Update completed. Version '.$this->job['target_version'].' is installed.');
        } catch (\Throwable $exception) {
            file_put_contents($this->directory.'/failure.log', (string) $exception);
            chmod($this->directory.'/failure.log', 0600);
            $this->job['failure_phase'] = $this->job['state'];
            $this->job['error_class'] = get_class($exception); // No credentials or raw command output in browser-readable progress.
            if (! empty($this->job['installation_verified'])) {
                try {
                    $this->releaseMaintenance();
                    $this->progress('completed', 'The verified update is installed. Some cleanup or receipt reporting will be retried.');
                    try {
                        $this->portal->finish($this->job, true);
                    } catch (\Throwable) {
                        $this->job['portal_receipt_pending'] = true;
                        UpdatePaths::write($this->directory.'/journal.json', $this->job);
                    }
                } catch (\Throwable) {
                    $this->progress('recovery_required', 'The update passed verification, but maintenance ownership needs review. No database rollback was attempted.');
                }
                return;
            }
            if (! empty($this->job['backup_complete'])) {
                try {
                    $this->progress('recovering', 'Restoring the verified pre-update application and database.');
                    // Validate all file backups before changing either files or database.
                    $this->validateFileBackups();
                    if (! empty($this->job['migrations_started'])) {
                        $this->database->restore($this->directory.'/backup', $this->job['database'], $this->job['created_tables']);
                    }
                    $this->restoreFiles();
                    $this->clearGeneratedCaches();
                    UpdateProcess::artisan(['campaign:updates-health', $this->job['source_version']]);
                    $this->releaseMaintenance();
                    $this->progress('recovered', 'The update stopped and the previous application was restored automatically. Your coverage remains available for retry.');
                } catch (\Throwable $recoveryError) {
                    file_put_contents($this->directory.'/recovery-failure.log', (string) $recoveryError);
                    chmod($this->directory.'/recovery-failure.log', 0600);
                    $this->progress('recovery_required', 'Automatic recovery could not be verified. The application remains protected in maintenance with its private backups.');
                }
            } else {
                try {
                    $this->releaseMaintenance();
                    $this->progress('failed', 'The backup stopped before application changes. Your coverage remains available for retry.');
                } catch (\Throwable) {
                    $this->progress('recovery_required', 'Maintenance ownership changed. Application files were not updated.');
                }
            }
            try {
                $this->portal->finish($this->job, false);
            } catch (\Throwable) {
                $this->job['portal_receipt_pending'] = true;
                UpdatePaths::write($this->directory.'/journal.json', $this->job);
            }
        } finally {
            $previousJobDirectory === false ? putenv('CAMPAIGN_UPDATE_JOB_DIR') : putenv('CAMPAIGN_UPDATE_JOB_DIR='.$previousJobDirectory);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function backup(): void
    {
        $backup = $this->directory.'/backup';
        File::ensureDirectoryExists($backup.'/files', 0700);
        $this->job['database'] = $this->database->backup($backup);
        $this->job['original_files'] = [];
        foreach ($this->job['files'] as $name => $hash) {
            $path = UpdatePaths::destination($name);
            $entry = ['exists' => is_file($path)];
            if ($entry['exists']) {
                File::ensureDirectoryExists(dirname($backup.'/files/'.$name), 0700);
                $entry += ['sha256' => hash_file('sha256', $path), 'mode' => fileperms($path) & 0777];
                if (! copy($path, $backup.'/files/'.$name) || ! hash_equals($entry['sha256'], hash_file('sha256', $backup.'/files/'.$name))) {
                    throw new RuntimeException('Application backup verification failed.');
                }
            }
            $this->job['original_files'][$name] = $entry;
        }
        if (is_file(base_path('.env')) && ! copy(base_path('.env'), $backup.'/environment.env')) {
            throw new RuntimeException('Environment backup failed.');
        }
        $connection = config('database.connections.'.config('database.default'));
        $connection = array_intersect_key($connection, array_flip(['driver', 'database', 'host', 'port', 'username', 'password', 'unix_socket', 'options', 'charset', 'collation']));
        UpdatePaths::write($backup.'/connection.json', $connection);
        $recovery = is_file(storage_path('app/updates/bootstrap/recover.php')) ? storage_path('app/updates/bootstrap/recover.php') : base_path('tools/updater/recover.php');
        if (! is_file($recovery) || ! copy($recovery, $this->directory.'/recover.php')) {
            throw new RuntimeException('A standalone recovery kit is required.');
        } chmod($this->directory.'/recover.php', 0600);
        $this->job['base_path'] = realpath(base_path());
        $this->job['mysql_client'] = config('updates.mysql_client') ?: (is_executable('/usr/bin/mysql') ? '/usr/bin/mysql' : '/usr/bin/mariadb');
        $this->job['backup_complete'] = true;
        $this->progress('backing_up', 'Verified backup completed.');
    }

    private function replaceFile(string $name, string $hash): void
    {
        $source = UpdatePaths::stage($this->job['stage_id']).'/application/'.$name;
        $destination = UpdatePaths::destination($name);
        if (! is_file($source) || ! hash_equals($hash, hash_file('sha256', $source))) {
            throw new RuntimeException('Prepared application checksum changed.');
        }
        File::ensureDirectoryExists(dirname($destination));
        $tmp = $destination.'.update-'.$this->job['id'];
        if (! copy($source, $tmp) || ! hash_equals($hash, hash_file('sha256', $tmp))) {
            throw new RuntimeException('Application replacement verification failed.');
        }
        chmod($tmp, $this->job['original_files'][$name]['mode'] ?? 0644);
        if (! rename($tmp, $destination)) {
            throw new RuntimeException('Application replacement failed.');
        }
    }

    private function validateFileBackups(): void
    {
        foreach ($this->job['original_files'] as $name => $entry) {
            if ($entry['exists']) {
                $source = $this->directory.'/backup/files/'.$name;
                if (! is_file($source) || ! hash_equals($entry['sha256'], hash_file('sha256', $source))) {
                    throw new RuntimeException('Application backup checksum mismatch.');
                }
            } else {
                $destination = UpdatePaths::destination($name);
                if (is_file($destination) && ! hash_equals($this->job['files'][$name], hash_file('sha256', $destination))) {
                    throw new RuntimeException('An added application file changed.');
                }
            }
        }
    }

    private function restoreFiles(): void
    {
        foreach ($this->job['original_files'] as $name => $entry) {
            $destination = UpdatePaths::destination($name);
            if (! $entry['exists']) {
                if (is_file($destination) && ! hash_equals($this->job['files'][$name], hash_file('sha256', $destination))) {
                    throw new RuntimeException('A newly added file changed; operator recovery is required.');
                }
                if (is_file($destination) && ! unlink($destination)) {
                    throw new RuntimeException('Cannot remove an added update file.');
                }

                continue;
            }
            $source = $this->directory.'/backup/files/'.$name;
            if (! is_file($source) || ! hash_equals($entry['sha256'], hash_file('sha256', $source))) {
                throw new RuntimeException('Backup checksum mismatch.');
            }
            $temporary = $destination.'.restore-'.$this->job['id'];
            if (! copy($source, $temporary) || ! chmod($temporary, $entry['mode']) || ! rename($temporary, $destination)) {
                throw new RuntimeException('Application restore failed.');
            }
        }
    }

    private function clearGeneratedCaches(): void
    {
        foreach (['config.php', 'events.php', 'routes-v7.php', 'packages.php', 'services.php'] as $name) {
            File::delete(base_path('bootstrap/cache/'.$name));
        }
        foreach (glob(storage_path('framework/views/*.php')) as $path) {
            File::delete($path);
        }
    }

    private function releaseMaintenance(): void
    {
        $path = storage_path('framework/down');
        if (! empty($this->job['maintenance_hash']) && is_file($path)) {
            if (! hash_equals($this->job['maintenance_hash'], hash_file('sha256', $path))) {
                throw new RuntimeException('Maintenance ownership changed; operator review is required.');
            }
            UpdateProcess::artisan(['up']);
        }
    }
}
