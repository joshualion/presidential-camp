<?php

namespace App\Services\Updates;

use PDO;
use RuntimeException;

/** Dependency-free streaming backup codec, copied into the private recovery kit. */
class MysqlBackup
{
    public static function connect(array $c): PDO
    {
        $dsn = ! empty($c['unix_socket'])
            ? 'mysql:unix_socket='.$c['unix_socket']
            : 'mysql:host='.$c['host'].';port='.($c['port'] ?? 3306);
        $pdo = new PDO($dsn.';dbname='.$c['database'].';charset=utf8mb4', $c['username'], $c['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => true,
        ]);
        $pdo->exec("SET SESSION time_zone='+00:00'");
        $pdo->exec('SET SESSION lock_wait_timeout=3');
        $pdo->exec('SET SESSION innodb_lock_wait_timeout=3');

        return $pdo;
    }

    public static function quote(string $name): string
    {
        return chr(96).str_replace(chr(96), chr(96).chr(96), $name).chr(96);
    }

    public static function inventory(PDO $pdo): array
    {
        $tables = [];
        $views = [];
        foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as $row) {
            if ($row[1] === 'BASE TABLE') {
                $tables[] = $row[0];
            } elseif ($row[1] === 'VIEW') {
                $views[] = $row[0];
            } else {
                throw new RuntimeException('This database contains an unsupported table object.');
            }
        }
        sort($tables);
        sort($views);
        $engines = [];
        foreach ($pdo->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'")->fetchAll(PDO::FETCH_NUM) as [$name, $engine]) {
            if (! in_array(strtoupper((string) $engine), ['INNODB', 'MYISAM', 'ARIA', 'MEMORY', 'CSV', 'ARCHIVE'], true)) {
                throw new RuntimeException('No verified local backup strategy for table '.$name.' ('.$engine.').');
            }
            $engines[$name] = $engine;
        }
        ksort($engines);
        $objects = ['FUNCTION' => [], 'PROCEDURE' => [], 'TRIGGER' => [], 'EVENT' => []];
        foreach ($pdo->query("SELECT ROUTINE_NAME, ROUTINE_TYPE FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE() ORDER BY ROUTINE_NAME")->fetchAll(PDO::FETCH_NUM) as [$name, $type]) {
            $objects[$type][] = $name;
        }
        foreach ($pdo->query('SHOW TRIGGERS')->fetchAll(PDO::FETCH_ASSOC) as $trigger) {
            $objects['TRIGGER'][] = $trigger['Trigger'];
        }
        foreach ($pdo->query('SHOW EVENTS')->fetchAll(PDO::FETCH_ASSOC) as $event) {
            if ($event['Status'] === 'ENABLED') {
                throw new RuntimeException('An active database event needs a verified pause strategy before automatic updating.');
            }
            $objects['EVENT'][] = $event['Name'];
        }
        foreach ($objects as &$names) {
            sort($names);
        }
        unset($names);

        return compact('tables', 'views', 'engines', 'objects');
    }

    public static function definition(PDO $pdo, string $type, string $name): array
    {
        $row = $pdo->query('SHOW CREATE '.$type.' '.self::quote($name))->fetch(PDO::FETCH_ASSOC);
        $key = $type === 'TRIGGER' ? 'SQL Original Statement' : 'Create '.ucfirst(strtolower($type));
        if (! is_array($row) || ! is_string($row[$key] ?? null) || $row[$key] === '') {
            throw new RuntimeException('Database object definition is not readable: '.$name);
        }

        return ['sql' => $row[$key], 'mode' => $row['sql_mode'] ?? null, 'collation' => $row['collation_connection'] ?? null];
    }

    /** Real capability probe before checkout; touches only randomly named probe objects. */
    public static function readiness(PDO $pdo, array $inventory): void
    {
        $name = '_cm_update_probe_'.bin2hex(random_bytes(8));
        $table = self::quote($name);
        $created = false;
        $extras = [];
        try {
            $pdo->exec('CREATE TABLE '.$table.' (id INT PRIMARY KEY, value VARCHAR(20)) ENGINE=InnoDB');
            $created = true;
            $pdo->exec('INSERT INTO '.$table." VALUES (1,'probe')");
            $pdo->exec('ALTER TABLE '.$table.' ADD COLUMN checked INT NULL');
            $pdo->exec('UPDATE '.$table.' SET checked=1 WHERE id=1');
            if ((int) $pdo->query('SELECT checked FROM '.$table)->fetchColumn() !== 1) {
                throw new RuntimeException('Database recovery capability verification failed.');
            }
            foreach (['VIEW', 'TRIGGER', 'PROCEDURE', 'FUNCTION', 'EVENT'] as $type) {
                $present = $type === 'VIEW' ? $inventory['views'] : $inventory['objects'][$type];
                if ($present === []) {
                    continue;
                }
                $suffix = ['VIEW' => '_v', 'TRIGGER' => '_t', 'PROCEDURE' => '_p', 'FUNCTION' => '_f', 'EVENT' => '_e'][$type];
                $object = self::quote($name.$suffix);
                $sql = match ($type) {
                    'VIEW' => 'CREATE VIEW '.$object.' AS SELECT id FROM '.$table,
                    'TRIGGER' => 'CREATE TRIGGER '.$object.' BEFORE INSERT ON '.$table.' FOR EACH ROW SET NEW.checked=1',
                    'PROCEDURE' => 'CREATE PROCEDURE '.$object.'() SELECT 1',
                    'FUNCTION' => 'CREATE FUNCTION '.$object.'() RETURNS INT DETERMINISTIC NO SQL RETURN 1',
                    'EVENT' => 'CREATE EVENT '.$object.' ON SCHEDULE EVERY 1 DAY DISABLE DO SELECT 1',
                };
                $pdo->exec($sql);
                $extras[] = [$type, $name.$suffix];
            }
        } finally {
            foreach (array_reverse($extras) as [$type, $object]) {
                $pdo->exec('DROP '.$type.' IF EXISTS '.self::quote($object));
            }
            if ($created) {
                $pdo->exec('DROP TABLE '.$table);
            }
        }
        foreach ($inventory['tables'] as $name) {
            self::definition($pdo, 'TABLE', $name);
            $pdo->query('SELECT * FROM '.self::quote($name).' LIMIT 0')->closeCursor();
        }
        foreach ($inventory['views'] as $name) {
            self::definition($pdo, 'VIEW', $name);
        }
        foreach ($inventory['objects'] as $type => $names) {
            foreach ($names as $name) {
                self::definition($pdo, $type, $name);
            }
        }
        if (self::needsLocks($inventory)) {
            self::snapshot($pdo, $inventory, fn () => null);
        }
    }

    public static function needsLocks(array $inventory): bool
    {
        foreach ($inventory['engines'] as $engine) {
            if (strtoupper($engine) !== 'INNODB') {
                return true;
            }
        }

        return false;
    }

    public static function snapshot(PDO $pdo, array $inventory, callable $export, bool $forceLocks = false): mixed
    {
        $locked = false;
        $transaction = false;
        try {
            if (($forceLocks || self::needsLocks($inventory)) && $inventory['tables'] !== []) {
                $pdo->exec('SET autocommit=0');
                // READ prevents concurrent MyISAM inserts; READ LOCAL does not.
                $pdo->exec('LOCK TABLES '.implode(', ', array_map(fn ($t) => self::quote($t).' READ', $inventory['tables'])));
                $locked = true;
            } else {
                $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
                $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
                $transaction = true;
            }

            return $export();
        } finally {
            if ($locked) {
                $pdo->exec('UNLOCK TABLES');
                $pdo->exec('ROLLBACK');
                $pdo->exec('SET autocommit=1');
            } elseif ($transaction) {
                $pdo->exec('ROLLBACK');
            }
        }
    }

    private static function write($file, array $record): void
    {
        $bytes = json_encode($record, JSON_THROW_ON_ERROR)."\n";
        $offset = 0;
        while ($offset < strlen($bytes)) {
            $written = fwrite($file, substr($bytes, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Insufficient storage while writing the database backup.');
            }
            $offset += $written;
        }
    }

    public static function export(PDO $pdo, array $inventory, string $path): array
    {
        $file = fopen($path, 'xb');
        if (! $file || ! chmod($path, 0600)) {
            if (is_resource($file)) {
                fclose($file);
            }
            throw new RuntimeException('Cannot create a private database backup.');
        }
        $counts = [];
        $statements = 0;
        $emit = function (string $sql, ?string $mode = null, ?string $collation = null) use ($file, &$statements): void {
            self::write($file, ['sql' => base64_encode($sql), 'mode' => $mode, 'collation' => $collation]);
            $statements++;
        };
        try {
            self::write($file, ['format' => 'campaign-mysql-pdo-v1', 'inventory' => $inventory]);
            $mode = (string) $pdo->query('SELECT @@SESSION.sql_mode')->fetchColumn();
            $emit("SET SESSION time_zone='+00:00'");
            $emit("SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'");
            $emit('SET FOREIGN_KEY_CHECKS=0');
            foreach ($inventory['views'] as $name) {
                $emit('DROP VIEW IF EXISTS '.self::quote($name));
            }
            foreach ($inventory['objects'] as $type => $names) {
                if ($type === 'TRIGGER') {
                    continue;
                }
                foreach ($names as $name) {
                    $emit('DROP '.$type.' IF EXISTS '.self::quote($name));
                }
            }
            foreach (array_reverse($inventory['tables']) as $name) {
                $emit('DROP TABLE IF EXISTS '.self::quote($name));
            }
            foreach ($inventory['tables'] as $name) {
                $emit(self::definition($pdo, 'TABLE', $name)['sql'], $mode);
            }
            foreach ($inventory['tables'] as $name) {
                $columns = $pdo->query('SHOW FULL COLUMNS FROM '.self::quote($name))->fetchAll(PDO::FETCH_ASSOC);
                $columns = array_values(array_filter($columns, fn ($c) => ! preg_match('/(?:VIRTUAL|STORED|PERSISTENT) GENERATED/i', $c['Extra'])));
                $names = array_column($columns, 'Field');
                $select = $names === [] ? '1' : implode(',', array_map(function ($column) {
                    $name = self::quote($column['Field']);
                    return str_starts_with(strtolower($column['Type']), 'bit(') ? 'HEX('.$name.')' : $name;
                }, $columns));
                $insert = 'INSERT INTO '.self::quote($name).' ('.implode(',', array_map([self::class, 'quote'], $names)).') VALUES (';
                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                $rows = $pdo->query('SELECT '.$select.' FROM '.self::quote($name));
                $count = 0;
                try {
                    while (($row = $rows->fetch(PDO::FETCH_NUM)) !== false) {
                        $values = [];
                        foreach ($columns as $index => $column) {
                            $values[] = self::literal($row[$index], $column['Type']);
                        }
                        $emit($insert.implode(',', $values).')', 'NO_AUTO_VALUE_ON_ZERO');
                        $count++;
                    }
                } finally {
                    $rows->closeCursor();
                    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                }
                $counts[$name] = $count;
            }
            foreach (['FUNCTION', 'PROCEDURE'] as $type) {
                foreach ($inventory['objects'][$type] as $name) {
                    $definition = self::definition($pdo, $type, $name);
                    $emit($definition['sql'], $definition['mode'], $definition['collation']);
                }
            }
            foreach ($inventory['views'] as $name) {
                $definition = self::definition($pdo, 'VIEW', $name);
                self::write($file, ['view' => $name, 'sql' => base64_encode($definition['sql']), 'mode' => $definition['mode'], 'collation' => $definition['collation']]);
                $statements++;
            }
            foreach (['TRIGGER', 'EVENT'] as $type) {
                foreach ($inventory['objects'][$type] as $name) {
                    $definition = self::definition($pdo, $type, $name);
                    $emit($definition['sql'], $definition['mode'], $definition['collation']);
                }
            }
            $emit('SET FOREIGN_KEY_CHECKS=1');
            self::write($file, ['complete' => true, 'statements' => $statements, 'counts' => $counts]);
            if (! fflush($file) || (function_exists('fsync') && ! fsync($file))) {
                throw new RuntimeException('Database backup could not be flushed to storage.');
            }
        } finally {
            fclose($file);
        }
        self::validate($path);

        return $counts;
    }

    private static function literal(mixed $value, string $type): string
    {
        if ($value === null) {
            return 'NULL';
        }
        $value = (string) $value;
        $type = strtolower($type);
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|numeric|float|double|real|year)\b/', $type)) {
            if (! preg_match('/^-?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?$/D', $value)) {
                throw new RuntimeException('A numeric database value could not be exported safely.');
            }

            return $value;
        }
        if (str_starts_with($type, 'bit(')) {
            if (! preg_match('/^[a-fA-F0-9]+$/D', $value)) {
                throw new RuntimeException('A bit value could not be exported safely.');
            }

            return "X'".(strlen($value) % 2 ? '0' : '').$value."'";
        }
        if (preg_match('/^(geometry|point|linestring|polygon|multipoint|multilinestring|multipolygon|geometrycollection)\b/', $type)) {
            if (strlen($value) < 5) {
                throw new RuntimeException('Invalid spatial database value.');
            }
            $srid = unpack('V', substr($value, 0, 4))[1];

            return "ST_GeomFromWKB(X'".bin2hex(substr($value, 4))."', ".$srid.')';
        }
        $hex = "X'".bin2hex($value)."'";
        if (preg_match('/^(binary|varbinary|tinyblob|blob|mediumblob|longblob)\b/', $type)) {
            return $hex;
        }

        return 'CONVERT('.$hex.' USING utf8mb4)';
    }

    public static function validate(string $path): array
    {
        $file = fopen($path, 'rb');
        if (! $file) {
            throw new RuntimeException('Database backup cannot be read.');
        }
        try {
            $header = json_decode((string) fgets($file), true, 512, JSON_THROW_ON_ERROR);
            if (($header['format'] ?? '') !== 'campaign-mysql-pdo-v1' || ! is_array($header['inventory']['tables'] ?? null)) {
                throw new RuntimeException('Unknown database backup format.');
            }
            $count = 0;
            $footer = null;
            while (($line = fgets($file)) !== false) {
                $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (isset($record['complete'])) {
                    $footer = $record;
                    if (fgets($file) !== false) {
                        throw new RuntimeException('Unexpected data after backup completion.');
                    }
                    break;
                }
                if (! is_string($record['sql'] ?? null) || base64_decode($record['sql'], true) === false) {
                    throw new RuntimeException('Invalid database backup record.');
                }
                $count++;
            }
            if (($footer['complete'] ?? false) !== true || ($footer['statements'] ?? -1) !== $count || array_keys($footer['counts'] ?? []) !== $header['inventory']['tables']) {
                throw new RuntimeException('Incomplete database backup.');
            }

            return $header + ['counts' => $footer['counts']];
        } finally {
            fclose($file);
        }
    }

    public static function restore(PDO $pdo, string $path, array $declaredNewTables = []): void
    {
        // Validate every record before executing any destructive statement.
        $metadata = self::validate($path);
        $current = self::inventory($pdo);
        $new = array_diff($current['tables'], $metadata['inventory']['tables']);
        if (array_diff($new, $declaredNewTables)) {
            throw new RuntimeException('Unexpected tables prevent automatic database recovery.');
        }
        if (array_diff($current['views'], $metadata['inventory']['views'])) {
            throw new RuntimeException('Unexpected views prevent automatic database recovery.');
        }
        foreach ($current['objects'] as $type => $names) {
            if (array_diff($names, $metadata['inventory']['objects'][$type])) {
                throw new RuntimeException('Unexpected database objects prevent automatic recovery.');
            }
        }
        $mode = (string) $pdo->query('SELECT @@SESSION.sql_mode')->fetchColumn();
        $views = [];
        $file = fopen($path, 'rb');
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach ($new as $name) {
                $pdo->exec('DROP TABLE '.self::quote($name));
            }
            fgets($file);
            while (($line = fgets($file)) !== false) {
                $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (isset($record['complete'])) {
                    break;
                }
                if (isset($record['view'])) {
                    $views[] = $record;
                    continue;
                }
                self::execute($pdo, $record);
            }
            while ($views !== []) {
                $remaining = [];
                $last = null;
                foreach ($views as $record) {
                    try {
                        self::execute($pdo, $record);
                    } catch (\PDOException $error) {
                        $remaining[] = $record;
                        $last = $error;
                    }
                }
                if (count($remaining) === count($views)) {
                    throw new RuntimeException('Database views could not be restored.', 0, $last);
                }
                $views = $remaining;
            }
            foreach ($metadata['counts'] as $name => $expected) {
                if ((int) $pdo->query('SELECT COUNT(*) FROM '.self::quote($name))->fetchColumn() !== $expected) {
                    throw new RuntimeException('Restored database row count does not match the backup.');
                }
            }
            if (self::inventory($pdo) !== $metadata['inventory']) {
                throw new RuntimeException('Restored database objects or storage engines do not match the backup.');
            }
        } finally {
            fclose($file);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $pdo->exec('SET SESSION sql_mode='.$pdo->quote($mode));
        }
    }

    private static function execute(PDO $pdo, array $record): void
    {
        if (is_string($record['mode'] ?? null)) {
            $pdo->exec('SET SESSION sql_mode='.$pdo->quote($record['mode']));
        }
        if (is_string($record['collation'] ?? null)) {
            $pdo->exec('SET SESSION collation_connection='.$pdo->quote($record['collation']));
            $pdo->exec("SET SESSION character_set_client='utf8mb4'");
        }
        $pdo->exec(base64_decode($record['sql'], true));
    }
}
