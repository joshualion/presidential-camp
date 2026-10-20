<?php

namespace App\Services\Updates;

/** Standalone supervisor: no application/vendor boot is needed for recovery. */
class UpdateSupervisor
{
    private const TERMINAL = ['completed', 'failed', 'recovered', 'recovery_required'];

    public static function kick(string $directory): void
    {
        $journal = $directory.'/journal.json';
        $job = is_file($journal) ? json_decode(file_get_contents($journal), true) : null;
        if (! $job || in_array($job['state'] ?? '', self::TERMINAL, true)
            || time() - (strtotime($job['updated_at'] ?? $job['created_at'] ?? '') ?: time()) < 90
            || ! is_file($directory.'/supervise.php') || ! is_executable($job['php_cli'] ?? '')) {
            return;
        }
        foreach ([dirname($directory, 2).'/apply.lock', $directory.'/commands.lock'] as $path) {
            $lock = fopen($path, 'c');
            if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
                if ($lock) {
                    fclose($lock);
                }
                return;
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        if (! function_exists('proc_open')) {
            return;
        }
        $command = ['/bin/sh', '-c', 'nohup "$1" "$2" --recover-only >"$3" 2>&1 </dev/null &',
            'campaign-recovery', $job['php_cli'], $directory.'/supervise.php', $directory.'/supervisor.log'];
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']], $pipes);
        if (is_resource($process)) {
            proc_close($process);
        }
    }

    public static function run(string $directory, string $php, bool $recoverOnly = false): void
    {
        umask(0077);
        set_time_limit(0);
        $lock = fopen($directory.'/supervisor.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            return;
        }
        try {
            $job = self::read($directory);
            if (in_array($job['state'], self::TERMINAL, true)) {
                return;
            }
            if (! $recoverOnly) {
                self::process([$php, $directory.'/worker.php', $job['id']], $directory.'/worker.log');
            }
            $job = self::read($directory);
            if (in_array($job['state'], self::TERMINAL, true)) {
                return;
            }
            // A worker may die while an artisan child survives. Wait for its
            // independent lease before attempting any recovery.
            $apply = fopen(dirname($directory, 2).'/apply.lock', 'c');
            $command = fopen($directory.'/commands.lock', 'c');
            $acquired = false;
            for ($attempt = 0; $attempt < 120; $attempt++) {
                if ($apply && $command && flock($apply, LOCK_EX | LOCK_NB)) {
                    if (flock($command, LOCK_EX | LOCK_NB)) {
                        $acquired = true;
                        break;
                    }
                    flock($apply, LOCK_UN);
                }
                usleep(500000);
            }
            if (! $acquired) {
                // Leave the journal active: a later progress poll/scheduler
                // can retry after the surviving command has finished.
                return;
            }
            $job = self::read($directory);
            if (in_array($job['state'], self::TERMINAL, true)) {
                flock($command, LOCK_UN);
                flock($apply, LOCK_UN);
                return;
            }
            if (! empty($job['installation_verified'])) {
                // Never restore an old snapshot after the verified application
                // may have reopened and accepted new client data.
                $base = realpath($job['base_path']);
                $valid = $base && realpath(dirname($directory, 2)) === realpath($base.'/storage/app/updates');
                foreach ($job['files'] as $name => $hash) {
                    $path = $base.'/'.$name;
                    if (! is_file($path) || is_link($path) || ! hash_equals($hash, hash_file('sha256', $path))) {
                        $valid = false;
                    }
                }
                $down = $base.'/storage/framework/down';
                if ($valid && is_file($down)) {
                    $valid = hash_equals($job['maintenance_hash'] ?? '', hash_file('sha256', $down)) && unlink($down);
                }
                $job['state'] = $valid ? 'completed' : 'recovery_required';
                $job['message'] = $valid ? 'The verified update is installed. Receipt reporting will be retried.' : 'The verified update needs a final installation review. No database rollback was attempted.';
                $job['portal_receipt_pending'] = true;
                self::save($directory, $job);
                flock($command, LOCK_UN);
                flock($apply, LOCK_UN);
                return;
            }
            if (empty($job['backup_complete'])) {
                // No application files/migrations may start before this flag
                // is durably journaled by the worker.
                $down = $job['base_path'].'/storage/framework/down';
                $owned = ! is_file($down) || (! empty($job['maintenance_hash']) && hash_equals($job['maintenance_hash'], hash_file('sha256', $down)));
                if ($owned && is_file($down)) {
                    $owned = unlink($down);
                }
                $job['state'] = $owned ? 'failed' : 'recovery_required';
                $job['message'] = $owned ? 'The interrupted backup stopped before application changes. Your coverage remains available for retry.' : 'Maintenance ownership changed; recovery needs review.';
                $job['portal_receipt_pending'] = true;
                self::save($directory, $job);
                flock($command, LOCK_UN);
                flock($apply, LOCK_UN);
                return;
            }
            // The recovery script independently reacquires both leases and
            // validates every backup and maintenance ownership before restoring.
            flock($command, LOCK_UN);
            flock($apply, LOCK_UN);
            $code = self::process([$php, $directory.'/recover.php', '--confirm='.$job['id'], '--restore-database', '--automatic'], $directory.'/recovery.log');
            if ($code !== 0) {
                $job = self::read($directory);
                $job['state'] = 'recovery_required';
                $job['message'] = 'Automatic recovery could not be verified. The application remains protected in maintenance with its private backups.';
                $job['portal_receipt_pending'] = true;
                self::save($directory, $job);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function process(array $arguments, string $log): int
    {
        $process = proc_open($arguments, [0 => ['file', '/dev/null', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes);
        return is_resource($process) ? proc_close($process) : 1;
    }

    private static function read(string $directory): array
    {
        return json_decode(file_get_contents($directory.'/journal.json'), true, 64, JSON_THROW_ON_ERROR);
    }

    private static function save(string $directory, array $job): void
    {
        $job['updated_at'] = gmdate('c');
        $path = $directory.'/journal.supervisor.tmp';
        if (file_put_contents($path, json_encode($job, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false || ! rename($path, $directory.'/journal.json')) {
            throw new \RuntimeException('Cannot persist recovery state.');
        }
    }
}
