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
        $this->binary('mysql_dump', ['/usr/bin/mysqldump', '/usr/bin/mariadb-dump']);
        $this->binary('mysql_client', ['/usr/bin/mysql', '/usr/bin/mariadb']);
        $tables = DB::select('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ?', [$c['database'], 'BASE TABLE']);
        foreach ($tables as $table) {
            if (strtoupper((string) $table->ENGINE) !== 'INNODB') {
                throw new RuntimeException('Automatic backups require InnoDB tables for a consistent database snapshot.');
            }
        }

        return (int) DB::selectOne('SELECT COALESCE(SUM(DATA_LENGTH + INDEX_LENGTH),0) AS bytes FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?', [$c['database']])->bytes;
    }

    private function binary(string $key, array $defaults): string
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
        $c = $this->connection();
        $path = $directory.'/database.'.($c['driver'] === 'sqlite' ? 'sqlite' : 'sql');
        $tables = array_column(Schema::getTables(), 'name');
        if ($c['driver'] === 'sqlite') {
            $pdo = DB::connection()->getPdo();
            $pdo->exec('VACUUM INTO '.$pdo->quote($path));
            $check = new \PDO('sqlite:'.$path);
            if ($check->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
                throw new RuntimeException('SQLite backup integrity verification failed.');
            }
            $check = null;
        } else {
            $defaults = $this->defaults($c, $directory);
            try {
                $p = new Process([$this->binary('mysql_dump', ['/usr/bin/mysqldump', '/usr/bin/mariadb-dump']), '--defaults-extra-file='.$defaults, '--single-transaction', '--no-tablespaces', '--quick', '--routines', '--triggers', '--events', '--hex-blob', '--result-file='.$path, '--', $c['database']], null, null, null, 600);
                $p->run();
                if (! $p->isSuccessful() || ! is_file($path) || filesize($path) < 100) {
                    throw new RuntimeException('The database export failed; application files were not changed.');
                }
                $f = fopen($path, 'rb');
                fseek($f, max(0, filesize($path) - 4096));
                $tail = stream_get_contents($f);
                fclose($f);
                if (! str_contains($tail, 'Dump completed')) {
                    throw new RuntimeException('Database export completion could not be verified.');
                }
            } finally {
                @unlink($defaults);
            }
        }
        chmod($path, 0600);

        return ['filename' => basename($path), 'sha256' => hash_file('sha256', $path), 'connection' => $this->identity($c), 'tables' => $tables, 'driver' => $c['driver']];
    }

    public function restore(string $directory, array $snapshot, array $declaredNewTables): void
    {
        $c = $this->connection();
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
        } else {
            foreach ($new as $table) {
                Schema::drop($table);
            } // Only declared tables created by this failed upgrade, never existing tables.
            $defaults = $this->defaults($c, $directory);
            $input = fopen($path, 'rb');
            try {
                $p = new Process([$this->binary('mysql_client', ['/usr/bin/mysql', '/usr/bin/mariadb']), '--defaults-extra-file='.$defaults, '--', $c['database']], null, null, $input, 600);
                $p->run();
                if (! $p->isSuccessful()) {
                    throw new RuntimeException('Database restore requires operator attention.');
                }
            } finally {
                fclose($input);
                @unlink($defaults);
            }
            DB::purge();
        }
        if (array_diff($snapshot['tables'], array_column(Schema::getTables(), 'name'))) {
            throw new RuntimeException('Restored database schema could not be verified.');
        }
    }
}
