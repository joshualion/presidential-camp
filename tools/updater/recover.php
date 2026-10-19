<?php

/** Standalone recovery: deliberately independent of application/vendor boot. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
umask(0077);
function stopRecovery(string $message): never
{
    fwrite(STDERR, $message."\n");
    exit(1);
}
function saveRecovery(array $job): void
{
    $tmp = __DIR__.'/journal.tmp';
    if (file_put_contents($tmp, json_encode($job, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false || ! rename($tmp, __DIR__.'/journal.json')) {
        stopRecovery('Could not save recovery progress.');
    }
}
try {
    $job = json_decode(file_get_contents(__DIR__.'/journal.json'), true, 64, JSON_THROW_ON_ERROR);
    if (! preg_match('/^[a-f0-9-]{36}$/D', $job['id'] ?? '') || ! in_array('--confirm='.$job['id'], $argv, true)) {
        stopRecovery('Pass --confirm=<job UUID>. Add --restore-database when migrations have started; this restores the verified pre-update snapshot.');
    }
    if (! in_array($job['state'], ['recovery_required', 'applying', 'migrating', 'verifying', 'maintenance', 'backing_up'], true) || empty($job['backup_complete'])) {
        stopRecovery('This job has no complete recoverable backup or is already finished.');
    }
    if (! empty($job['migrations_started']) && ! in_array('--restore-database', $argv, true)) {
        stopRecovery('Database recovery requires the explicit --restore-database flag.');
    }
    $base = realpath($job['base_path']);
    if (! $base || realpath(dirname(__DIR__, 2)) !== realpath($base.'/storage/app/updates')) {
        stopRecovery('Recovery directory does not match the application.');
    }
    $lock = fopen(dirname(__DIR__, 2).'/apply.lock', 'c');
    if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
        stopRecovery('An update worker is still active.');
    }
    $down = $base.'/storage/framework/down';
    if (! is_file($down) || ! hash_equals($job['maintenance_hash'] ?? '', hash_file('sha256', $down))) {
        stopRecovery('Maintenance ownership changed; review with an operator.');
    }
    $backup = __DIR__.'/backup';
    $database = $backup.'/'.$job['database']['filename'];
    if (! in_array($job['database']['filename'], ['database.sql', 'database.sqlite'], true) || ! is_file($database) || ! hash_equals($job['database']['sha256'], hash_file('sha256', $database))) {
        stopRecovery('Database backup integrity check failed. No restore attempted.');
    }
    $paths = [];
    foreach ($job['original_files'] as $name => $entry) {
        if (str_starts_with($name, '/') || str_contains($name, '\\') || preg_match('/[\x00-\x1f\x7f:]/', $name) || array_intersect(explode('/', $name), ['.', '..', '']) || ! preg_match('#^(app|bootstrap|config|database|resources|routes|vendor|public|scripts|tools)/|^(artisan|composer\.json|composer\.lock|package\.json|package-lock\.json|release-manifest\.json|checksums\.txt|LICENSE\.txt|EULA\.txt|campaign-update-bootstrap\.php)$#D', $name) || str_starts_with($name, 'bootstrap/cache/') || str_starts_with($name, 'public/uploads/') || str_starts_with($name, 'public/storage') || basename($name) === '.htaccess') {
            stopRecovery('Unsafe recovery path.');
        }
        $dest = $base;
        foreach (explode('/', $name) as $part) {
            $dest .= '/'.$part;
            if (is_link($dest)) {
                stopRecovery('A recovery destination is now a symbolic link.');
            }
        }
        if ($entry['exists']) {
            if (! is_file($backup.'/files/'.$name) || ! hash_equals($entry['sha256'], hash_file('sha256', $backup.'/files/'.$name))) {
                stopRecovery('Application backup integrity check failed. No restore attempted.');
            }
        } elseif (is_file($dest) && ! hash_equals($job['files'][$name], hash_file('sha256', $dest))) {
            stopRecovery('An added file changed after the update; operator review is required.');
        }
        $paths[$name] = $dest;
    }
    // Validate every backup before restoring either application or database.
    if (! empty($job['migrations_started'])) {
        $c = json_decode(file_get_contents($backup.'/connection.json'), true, 64, JSON_THROW_ON_ERROR);
        $fingerprint = hash('sha256', json_encode(array_intersect_key($c, array_flip(['driver', 'database', 'host', 'port', 'unix_socket']))));
        if (! hash_equals($job['database']['connection'], $fingerprint)) {
            stopRecovery('Database connection fingerprint changed.');
        }
        if ($c['driver'] === 'sqlite') {
            $pdo = new PDO('sqlite:'.$database);
            if ($pdo->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
                stopRecovery('SQLite backup integrity check failed.');
            }
            $pdo = null;
            $live = realpath($c['database']);
            if (! $live) {
                stopRecovery('Live SQLite database cannot be found.');
            }
            $pdo = new PDO('sqlite:'.$live);
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($c['driver'] === 'mysql') {
            $dsn = ! empty($c['unix_socket']) ? 'mysql:unix_socket='.$c['unix_socket'] : 'mysql:host='.$c['host'].';port='.($c['port'] ?? 3306);
            $pdo = new PDO($dsn.';dbname='.$c['database'], $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            stopRecovery('Unsupported recovery database.');
        }
        $new = array_diff($tables, $job['database']['tables']);
        if (array_diff($new, $job['created_tables'])) {
            stopRecovery('Unexpected new tables require operator-reviewed recovery.');
        }
        if ($c['driver'] === 'sqlite') {
            $pdo = null;
            if (! copy($database, $live.'.recover') || ! rename($live.'.recover', $live)) {
                stopRecovery('SQLite restore failed. Application stays in maintenance.');
            }
            @unlink($live.'-wal');
            @unlink($live.'-shm');
        } else {
            $client = $job['mysql_client'];
            if (! is_file($client) || ! is_executable($client)) {
                stopRecovery('MySQL recovery client is unavailable.');
            }
            $cnf = $backup.'/recovery-client.cnf';
            $lines = ['[client]'];
            foreach (['host' => 'host', 'port' => 'port', 'user' => 'username', 'password' => 'password', 'socket' => 'unix_socket'] as $option => $key) {
                $value = (string) ($c[$key] ?? '');
                if ($value === '') {
                    continue;
                }
                if (preg_match('/[\x00-\x1f\x7f]/', $value)) {
                    stopRecovery('Unsupported database credential format.');
                }
                $lines[] = $option.'="'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
            }
            if (file_put_contents($cnf, implode("\n", $lines)."\n") === false || ! chmod($cnf, 0600)) {
                stopRecovery('Cannot secure recovery credentials.');
            }
            try {
                // Only signed, declared new tables are removed; existing tables are restored from the verified snapshot.
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
                foreach ($new as $table) {
                    $pdo->exec('DROP TABLE `'.str_replace('`', '``', $table).'`');
                }
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
                $pdo = null;
                $process = proc_open([$client, '--defaults-extra-file='.$cnf, '--', $c['database']], [0 => ['file', $database, 'r'], 1 => ['file', __DIR__.'/recovery.log', 'a'], 2 => ['file', __DIR__.'/recovery.log', 'a']], $pipes);
                if (! is_resource($process) || proc_close($process) !== 0) {
                    stopRecovery('Database import failed. Application stays in maintenance; review the private recovery log.');
                }
            } finally {
                @unlink($cnf);
            }
        }
    }
    foreach ($paths as $name => $dest) {
        $entry = $job['original_files'][$name];
        if (! $entry['exists']) {
            if (is_file($dest) && ! unlink($dest)) {
                stopRecovery('Cannot remove an update-added file.');
            }

continue;
        }
        if (! is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }
        if (! copy($backup.'/files/'.$name, $dest.'.recover') || ! chmod($dest.'.recover', $entry['mode']) || ! rename($dest.'.recover', $dest)) {
            stopRecovery('Application restore failed. Application stays in maintenance.');
        }
    }
    foreach (['config.php', 'events.php', 'routes-v7.php', 'packages.php', 'services.php'] as $cache) {
        if (is_file($base.'/bootstrap/cache/'.$cache)) {
            unlink($base.'/bootstrap/cache/'.$cache);
        }
    }
    foreach (glob($base.'/storage/framework/views/*.php') as $view) {
        unlink($view);
    }
    if (! hash_equals($job['maintenance_hash'], hash_file('sha256', $down)) || ! unlink($down)) {
        stopRecovery('Files restored; maintenance requires operator review.');
    }
    $job['state'] = 'recovered';
    $job['message'] = 'The verified pre-update application and database were restored.';
    $job['updated_at'] = gmdate('c');
    $job['portal_receipt_pending'] = true;
    saveRecovery($job);
    fwrite(STDOUT, "Recovery completed. Open Official Updates to confirm the installation and retry without another payment.\n");
} catch (Throwable $error) {
    stopRecovery('Recovery stopped safely; review the private job files with your operator. Error: '.get_class($error));
}
