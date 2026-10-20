<?php

namespace App\Services\Updates;

use App\Services\Updates\UpdateException as RuntimeException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class DatabaseSnapshot
{
    private function connection(): array
    {
        $c = config('database.connections.'.config('database.default'));
        if (! in_array($c['driver'] ?? '', ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Automatic updates currently support MySQL/MariaDB and file-backed SQLite.');
        }
        if (! empty($c['url']) || ! empty($c['options']) || ! empty($c['read']) || ! empty($c['write']) || ! empty($c['prefix'])) {
            throw new RuntimeException('This database connection needs operator-reviewed backup handling.');
        }
        if (($c['driver'] === 'sqlite') && ($c['database'] === ':memory:' || ! is_file($c['database']))) {
            throw new RuntimeException('A persistent SQLite database is required.');
        }

        return $c;
    }

    private function identity(array $c): string
    {
        return hash('sha256', json_encode(array_intersect_key($c, array_flip(['driver', 'database', 'host', 'port', 'unix_socket']))));
    }

    public function preflight(): int
    {
        $c = $this->connection();
        if ($c['driver'] === 'sqlite') {
            return filesize($c['database']);
        }
        try {
            $pdo = MysqlBackup::connect($c);
            $inventory = MysqlBackup::inventory($pdo);
            MysqlBackup::readiness($pdo, $inventory);
        } catch (\Throwable $error) {
            throw new UpdateFlowException('database_storage', 'The database readiness check could not verify backup and recovery permissions or acquire a consistent snapshot. No package has been downloaded and no payment has started. Retry the readiness check.', $error);
        } finally {
            $pdo = null;
        }

        return (int) DB::selectOne('SELECT COALESCE(SUM(DATA_LENGTH + INDEX_LENGTH),0) AS bytes FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?', [$c['database']])->bytes;
    }

    protected function binary(string $key, array $defaults): string
    {
        foreach (array_filter(array_merge([config('updates.'.$key)], $defaults)) as $path) {
            if (is_file($path) && is_executable($path)) {
                return $path;
            }
        }
        throw new RuntimeException('The server needs a configured '.$key.' executable for database backup and recovery.');
    }

    private function defaults(array $c, string $directory): string
    {
        $path = $directory.'/mysql-client-'.bin2hex(random_bytes(6)).'.cnf';
        $lines = ['[client]'];
        foreach (['host' => 'host', 'port' => 'port', 'user' => 'username', 'password' => 'password', 'socket' => 'unix_socket'] as $option => $key) {
            $value = (string) ($c[$key] ?? '');
            if ($value === '') {
                continue;
            }
            if (preg_match('/[\x00-\x1f\x7f]/', $value)) {
                throw new RuntimeException('This database configuration requires manual backup handling.');
            }
            $lines[] = $option.'="'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }
        if (file_put_contents($path, implode("\n", $lines)."\n", LOCK_EX) === false || ! chmod($path, 0600)) {
            throw new RuntimeException('Cannot secure the database backup configuration.');
        }

        return $path;
    }

    public function backup(string $directory): array
    {
        // Each failed attempt starts a new snapshot; partial snapshots are never resumed.
        $last = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return $this->backupAttempt($directory);
            } catch (\Throwable $error) {
                $last = $error;
                foreach (['database.sql.part', 'database.jsonl.part', 'database.sqlite.part'] as $partial) {
                    @unlink($directory.'/'.$partial);
                }
                if ($attempt < 3) {
                    usleep($attempt * 250000);
                }
            }
        }
        throw new RuntimeException('Database backup could not complete after automatic retries. Application files were not changed; your update coverage remains available.', 0, $last);
    }

    private function backupAttempt(string $directory): array
    {
        $c = $this->connection();
        $tables = array_column(Schema::getTables(), 'name');
        $inventory = null;
        $counts = null;
        if ($c['driver'] === 'sqlite') {
            $filename = 'database.sqlite';
            $temporary = $directory.'/'.$filename.'.part';
            $pdo = DB::connection()->getPdo();
            $pdo->exec('VACUUM INTO '.$pdo->quote($temporary));
            $check = new \PDO('sqlite:'.$temporary);
            if ($check->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
                throw new RuntimeException('SQLite backup integrity verification failed.');
            }
            $check = null;
        } else {
            // Dedicated connection: locks/transactions cannot affect Laravel's connection.
            $pdo = MysqlBackup::connect($c);
            $inventory = MysqlBackup::inventory($pdo);
            $tables = $inventory['tables'];
            $filename = 'database.sql';
            $temporary = $directory.'/'.$filename.'.part';
            try {
                $dump = $this->binary('mysql_dump', ['/usr/bin/mysqldump', '/usr/bin/mariadb-dump']);
                $this->binary('mysql_client', ['/usr/bin/mysql', '/usr/bin/mariadb']);
                $defaults = $this->defaults($c, $directory);
                try {
                    MysqlBackup::snapshot($pdo, $inventory, function () use ($dump, $defaults, $c, $temporary, $inventory): void {
                        $args = [$dump, '--defaults-extra-file='.$defaults, '--no-tablespaces', '--quick', '--routines', '--triggers', '--events', '--hex-blob', '--skip-lock-tables', '--skip-add-locks'];
                        if (! MysqlBackup::needsLocks($inventory)) {
                            $args[] = '--single-transaction';
                        }
                        $args = array_merge($args, ['--result-file='.$temporary, '--', $c['database']]);
                        $process = new Process($args, null, null, null, 600);
                        $process->run();
                        if (! $process->isSuccessful() || ! is_file($temporary) || filesize($temporary) < 100) {
                            throw new RuntimeException('Native database export did not complete.');
                        }
                        $file = fopen($temporary, 'rb');
                        try {
                            fseek($file, max(0, filesize($temporary) - 4096));
                            if (! str_contains(stream_get_contents($file), 'Dump completed')) {
                                throw new RuntimeException('Native database export completion could not be verified.');
                            }
                        } finally {
                            fclose($file);
                        }
                    });
                } finally {
                    @unlink($defaults);
                }
            } catch (\Throwable) {
                // Missing/incompatible CLI tools or native export failure: switch
                // automatically to the standalone PDO exporter, with a NEW snapshot.
                @unlink($temporary);
                $pdo = null;
                $pdo = MysqlBackup::connect($c);
                $inventory = MysqlBackup::inventory($pdo);
                $tables = $inventory['tables'];
                $filename = 'database.jsonl';
                $temporary = $directory.'/'.$filename.'.part';
                $counts = MysqlBackup::snapshot($pdo, $inventory, fn () => MysqlBackup::export($pdo, $inventory, $temporary));
            } finally {
                $pdo = null; // Releases table locks even on failure.
            }
        }
        if (! chmod($temporary, 0600) || ! is_file($temporary)) {
            throw new RuntimeException('Database backup could not be secured.');
        }
        $sha256 = hash_file('sha256', $temporary);
        $path = $directory.'/'.$filename;
        if (is_file($path) || ! rename($temporary, $path)) {
            throw new RuntimeException('Database backup could not be finalized.');
        }

        return ['filename' => $filename, 'sha256' => $sha256, 'connection' => $this->identity($c), 'tables' => $tables, 'driver' => $c['driver'], 'inventory' => $inventory, 'counts' => $counts];
    }

    public function restore(string $directory, array $snapshot, array $declaredNewTables): void
    {
        $c = $this->connection();
        if (! in_array($snapshot['filename'] ?? '', ['database.sql', 'database.sqlite', 'database.jsonl'], true)) {
            throw new RuntimeException('Unknown database backup format.');
        }
        $path = $directory.'/'.$snapshot['filename'];
        if ($snapshot['connection'] !== $this->identity($c) || ! is_file($path) || ! hash_equals($snapshot['sha256'], hash_file('sha256', $path))) {
            throw new RuntimeException('Backup identity or integrity verification failed.');
        }
        $new = array_values(array_diff(array_column(Schema::getTables(), 'name'), $snapshot['tables']));
        if (array_diff($new, $declaredNewTables)) {
            throw new RuntimeException('Unexpected new tables require operator-reviewed recovery.');
        }
        if ($c['driver'] === 'sqlite') {
            $database = realpath($c['database']);
            DB::purge();
            $temporary = $database.'.restore-'.bin2hex(random_bytes(6));
            if (! copy($path, $temporary) || ! rename($temporary, $database)) {
                throw new RuntimeException('Database restore failed.');
            }
            @unlink($database.'-wal');
            @unlink($database.'-shm');
        } elseif ($snapshot['filename'] === 'database.jsonl') {
            $pdo = MysqlBackup::connect($c);
            MysqlBackup::restore($pdo, $path, $declaredNewTables);
            $pdo = null;
            DB::purge();
        } else {
            $pdo = MysqlBackup::connect($c);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach ($new as $table) {
                    $pdo->exec('DROP TABLE '.MysqlBackup::quote($table));
                }
            } finally {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
                $pdo = null;
            }
            $defaults = $this->defaults($c, $directory);
            $input = fopen($path, 'rb');
            try {
                $p = new Process([$this->binary('mysql_client', ['/usr/bin/mysql', '/usr/bin/mariadb']), '--defaults-extra-file='.$defaults, '--', $c['database']], null, null, $input, 600);
                $p->run();
                if (! $p->isSuccessful()) {
                    throw new RuntimeException('Database restore did not complete.');
                }
            } finally {
                fclose($input);
                @unlink($defaults);
            }
            DB::purge();
            if (! empty($snapshot['inventory'])) {
                $pdo = MysqlBackup::connect($c);
                if (MysqlBackup::inventory($pdo) !== $snapshot['inventory']) {
                    throw new RuntimeException('Restored database objects or engines do not match the backup.');
                }
                $pdo = null;
            }
        }

        if (array_diff($snapshot['tables'], array_column(Schema::getTables(), 'name'))) {
            throw new RuntimeException('Restored database schema could not be verified.');
        }
    }
}
