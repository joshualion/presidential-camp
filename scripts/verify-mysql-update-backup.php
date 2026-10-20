<?php

// This test creates, mutates and restores disposable fixtures; never run on client data.
if (getenv('CI') !== 'true' || getenv('DB_DATABASE') !== 'campaign_updater_ci' || getenv('DB_HOST') !== '127.0.0.1') {
    throw new RuntimeException('This check is restricted to the disposable CI database.');
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Laravel's standalone exception renderer may return a successful process status.
// Every failed assertion MUST fail CI rather than only printing an error.
set_exception_handler(static function (\Throwable $error): never {
    fwrite(STDERR, (string) $error."\n");
    exit(1);
});


use App\Services\Updates\DatabaseSnapshot;
use App\Services\Updates\MysqlBackup;
use App\Services\Updates\UpdateFlowException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

$assert = function (bool $ok, string $message): void { if (! $ok) { throw new RuntimeException($message); } };
$assert(Schema::getTables() === [], 'The disposable database is not empty.');
$c = config('database.connections.mysql');
$pdo = MysqlBackup::connect($c);
$pdo->exec("CREATE TABLE supporters (
    id BIGINT UNSIGNED PRIMARY KEY,
    name VARCHAR(100) CHARACTER SET utf8mb4,
    latin_name VARCHAR(100) CHARACTER SET latin1,
    amount DECIMAL(22,8), negative_value DOUBLE, note TEXT,
    payload LONGBLOB, flags BIT(9), happened_at TIMESTAMP(6) NULL,
    computed BIGINT GENERATED ALWAYS AS (id + 10) STORED,
    hidden_value VARCHAR(40) INVISIBLE, position POINT NULL
) ENGINE=InnoDB");
$pdo->exec('CREATE TABLE legacy_supporters (id INT PRIMARY KEY, name VARCHAR(100)) ENGINE=MyISAM');
$pdo->exec('CREATE TABLE cached_supporters (id INT PRIMARY KEY, name VARCHAR(100)) ENGINE=MEMORY');
$pdo->exec('CREATE TABLE audit_rows (id INT AUTO_INCREMENT PRIMARY KEY, supporter_id BIGINT) ENGINE=MyISAM');
$pdo->exec('CREATE TABLE duplicate_rows (name VARCHAR(100)) ENGINE=InnoDB');
$pdo->exec('CREATE TABLE empty_rows (id INT PRIMARY KEY) ENGINE=InnoDB');
$odd = MysqlBackup::quote('odd'.chr(96).'table');
$pdo->exec('CREATE TABLE '.$odd.' (id INT PRIMARY KEY, value VARBINARY(30)) ENGINE=InnoDB');
$pdo->exec('CREATE VIEW z_supporter_names AS SELECT id, name FROM supporters');
$pdo->exec('CREATE VIEW a_dependent_names AS SELECT name FROM z_supporter_names');
$pdo->exec('CREATE TRIGGER supporters_audit AFTER INSERT ON supporters FOR EACH ROW INSERT INTO audit_rows(supporter_id) VALUES (NEW.id)');
$pdo->exec('CREATE FUNCTION fixture_constant() RETURNS INT DETERMINISTIC NO SQL RETURN 7');
$pdo->exec('CREATE PROCEDURE fixture_count() SELECT COUNT(*) AS total FROM supporters');
$pdo->exec('CREATE EVENT fixture_disabled_event ON SCHEDULE EVERY 1 DAY DISABLE DO SELECT 1');
if (str_contains(strtolower($pdo->query('SELECT VERSION()')->fetchColumn()), 'mariadb')) {
    $pdo->exec('CREATE TABLE aria_supporters (id INT PRIMARY KEY, name VARCHAR(100)) ENGINE=Aria');
    $pdo->exec("INSERT INTO aria_supporters VALUES (1,'Aria supporter')");
}
$insert = $pdo->prepare('INSERT INTO supporters (id,name,latin_name,amount,negative_value,note,payload,flags,happened_at,hidden_value,position) VALUES (?,?,?,?,?,?,?,b\'100000001\',?,?,ST_GeomFromText(\'POINT(3 7)\'))');
$insert->execute([1, 'Existing supporter 😀', 'Café', '12345678901234.12345678', '-0.00003125', "line one\nline two ' \\ ; DELIMITER $$", "\x00\xff\x01binary", '2026-10-20 12:34:56.123456', 'invisible preserved']);
$pdo->exec("INSERT INTO legacy_supporters VALUES (1,'Legacy supporter')");
$pdo->exec("INSERT INTO cached_supporters VALUES (1,'Cached supporter')");
$pdo->exec("INSERT INTO duplicate_rows VALUES ('duplicate'),('duplicate')");
$pdo->exec('INSERT INTO '.$odd." VALUES (1,X'00FF5C')");
$query = 'SELECT id,name,latin_name,amount,negative_value,note,HEX(payload),HEX(flags),happened_at,computed,hidden_value,ST_AsText(position) FROM supporters';
$baseline = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
$inventory = MysqlBackup::inventory($pdo);
$directory = sys_get_temp_dir().'/campaign-mysql-backup-'.bin2hex(random_bytes(6));
File::ensureDirectoryExists($directory, 0700);
$noCli = new class extends DatabaseSnapshot {
    protected function binary(string $key, array $defaults): string { throw new RuntimeException('Simulated missing CLI.'); }
};
$brokenCli = new class extends DatabaseSnapshot {
    protected function binary(string $key, array $defaults): string { return '/usr/bin/false'; }
};
$checkRestored = function () use ($c, $baseline, $query, $inventory, $assert): void {
    $check = MysqlBackup::connect($c);
    $assert($check->query($query)->fetchAll(PDO::FETCH_ASSOC) === $baseline, 'Restored data types, precision, binary, Unicode, generated or invisible columns differ.');
    $assert(MysqlBackup::inventory($check) === $inventory, 'Engines or objects changed.');
    $assert((int) $check->query('SELECT COUNT(*) FROM audit_rows')->fetchColumn() === 1, 'Triggers ran during restoration.');
    $assert((int) $check->query('SELECT COUNT(*) FROM duplicate_rows')->fetchColumn() === 2, 'Duplicate rows lost.');
    $assert((int) $check->query('SELECT COUNT(*) FROM a_dependent_names')->fetchColumn() === 1, 'Dependent views lost.');
    $assert((int) $check->query('SELECT fixture_constant()')->fetchColumn() === 7, 'Stored function lost.');
    $assert($check->query('SELECT name FROM legacy_supporters WHERE id=1')->fetchColumn() === 'Legacy supporter', 'MyISAM row lost.');
    $assert($check->query('SELECT name FROM cached_supporters WHERE id=1')->fetchColumn() === 'Cached supporter', 'MEMORY row lost.');
};
try {
    $snapshot = app(DatabaseSnapshot::class);
    $assert($snapshot->preflight() >= 0, 'Mixed engines failed readiness.');
    $assert(MysqlBackup::inventory($pdo) === $inventory, 'Readiness left probe objects behind.');
    foreach (['native' => $snapshot, 'missing-cli' => $noCli, 'broken-cli' => $brokenCli] as $label => $service) {
        $dir = $directory.'/'.$label;
        File::ensureDirectoryExists($dir, 0700);
        if ($label === 'missing-cli') { file_put_contents($dir.'/database.jsonl.part', '{"partial":true}'); }
        $backup = $service->backup($dir);
        $assert(hash_equals($backup['sha256'], hash_file('sha256', $dir.'/'.$backup['filename'])), 'Checksum mismatch.');
        $assert(($label === 'native') === ($backup['filename'] === 'database.sql'), 'Unexpected method: '.$label);
        $assert(glob($dir.'/*.part') === [] && glob($dir.'/*.cnf') === [], 'Partial backup or credentials left behind.');
        $pdo->exec("UPDATE supporters SET name='Changed after backup', amount=0");
        $pdo->exec("UPDATE legacy_supporters SET name='Changed legacy row'");
        $pdo->exec('DELETE FROM duplicate_rows');
        $pdo->exec('DELETE FROM cached_supporters');
        $pdo->exec('CREATE TABLE approved_new_table (id INT PRIMARY KEY) ENGINE=InnoDB');
        $service->restore($dir, $backup, ['approved_new_table']);
        $checkRestored();
        $assert(! Schema::hasTable('approved_new_table'), 'Declared migration table remains.');
        echo $label.": real mixed-engine backup and restore passed.\n";
    }
    $path = $directory.'/missing-cli/database.jsonl';
    $original = file_get_contents($path);
    $lines = explode("\n", rtrim($original, "\n"));
    array_pop($lines);
    file_put_contents($path, implode("\n", $lines)."\n");
    $pdo->exec("UPDATE supporters SET name='Do not alter on invalid backup'");
    $blocked = false;
    try { MysqlBackup::restore($pdo, $path); } catch (Throwable) { $blocked = true; }
    $assert($blocked && $pdo->query('SELECT name FROM supporters')->fetchColumn() === 'Do not alter on invalid backup', 'Truncated backup mutated live data.');
    file_put_contents($path, $original);
    MysqlBackup::restore($pdo, $path);
    $checkRestored();
    $pdo->exec('CREATE TABLE unexpected_table (id INT PRIMARY KEY) ENGINE=InnoDB');
    $blocked = false;
    try { MysqlBackup::restore($pdo, $path); } catch (Throwable) { $blocked = true; }
    $assert($blocked && Schema::hasTable('unexpected_table'), 'Recovery removed an undeclared table.');
    $pdo->exec('DROP TABLE unexpected_table');

    $writerFile = $directory.'/writer.php';
    $configFile = $directory.'/writer-config.json';
    file_put_contents($configFile, json_encode($c, JSON_THROW_ON_ERROR));
    chmod($configFile, 0600);
    file_put_contents($writerFile, '<?php require '.var_export(__DIR__.'/../app/Services/Updates/MysqlBackup.php', true).'; $c=json_decode(file_get_contents($argv[1]),true); $pdo=App\\Services\\Updates\\MysqlBackup::connect($c); $pdo->exec("INSERT INTO legacy_supporters VALUES (2,\'Concurrent writer\')");');
    $writer = null;
    MysqlBackup::snapshot($pdo, $inventory, function () use (&$writer, $writerFile, $configFile, $assert, $directory, $pdo, $inventory): void {
        $writer = new Process([PHP_BINARY, $writerFile, $configFile]);
        $writer->start();
        usleep(300000);
        $assert($writer->isRunning(), 'Concurrent MyISAM insert was not blocked.');
        MysqlBackup::export($pdo, $inventory, $directory.'/concurrent.jsonl');
    });
    $writer->wait();
    $assert($writer->isSuccessful(), 'Writer did not resume after releasing locks.');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM legacy_supporters')->fetchColumn() === 2, 'Concurrent write lost.');
    MysqlBackup::restore($pdo, $directory.'/concurrent.jsonl');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM legacy_supporters')->fetchColumn() === 1, 'Snapshot included a write after locking.');
    try { MysqlBackup::snapshot($pdo, $inventory, function (): void { throw new RuntimeException('Interrupted export'); }); } catch (RuntimeException) {}
    $other = MysqlBackup::connect($c);
    $other->exec("INSERT INTO legacy_supporters VALUES (3,'After failed backup')");
    $other->exec('DELETE FROM legacy_supporters WHERE id=3');
    $other = null;

    $pdo->exec("CREATE USER 'campaign_backup_limited'@'%' IDENTIFIED BY 'disposable-test-only'");
    $pdo->exec("GRANT SELECT ON campaign_updater_ci.* TO 'campaign_backup_limited'@'%'");
    $limitedConfig = array_replace($c, ['username' => 'campaign_backup_limited', 'password' => 'disposable-test-only']);
    $limited = MysqlBackup::connect($limitedConfig);
    $blocked = false;
    try { MysqlBackup::snapshot($limited, $inventory, fn () => null); } catch (Throwable) { $blocked = true; }
    $assert($blocked, 'A user without LOCK TABLES acquired a mixed-engine snapshot.');
    config(['database.connections.mysql' => $limitedConfig]);
    try {
        $blocked = false;
        try { $snapshot->preflight(); } catch (UpdateFlowException) { $blocked = true; }
        $assert($blocked, 'Missing restore permissions were not detected before checkout.');
    } finally {
        config(['database.connections.mysql' => $c]);
        $limited = null;
        $pdo->exec("DROP USER 'campaign_backup_limited'@'%'");
    }
    // Isolate the transaction strategy in its own disposable database so the
    // complete inventory remains authoritative while excluding mixed engines.
    $pdo->exec('CREATE DATABASE campaign_updater_innodb_ci');
    try {
        $transactionConfig = array_replace($c, ['database' => 'campaign_updater_innodb_ci']);
        $transaction = MysqlBackup::connect($transactionConfig);
        $transaction->exec('CREATE TABLE supporters (id INT PRIMARY KEY, name VARCHAR(100)) ENGINE=InnoDB');
        $transaction->exec("INSERT INTO supporters VALUES (1,'Original snapshot')");
        $innodb = MysqlBackup::inventory($transaction);
        MysqlBackup::snapshot($transaction, $innodb, function () use ($transaction, $transactionConfig, $assert): void {
            $before = $transaction->query('SELECT name FROM supporters WHERE id=1')->fetchColumn();
            $writer = MysqlBackup::connect($transactionConfig);
            $writer->exec("UPDATE supporters SET name='Concurrent InnoDB write' WHERE id=1");
            $assert($transaction->query('SELECT name FROM supporters WHERE id=1')->fetchColumn() === $before, 'InnoDB snapshot changed.');
        });
        $transaction = null;
    } finally {
        $pdo->exec('DROP DATABASE campaign_updater_innodb_ci');
    }
    $checkRestored();
    echo "Passed: preserved engines/objects; concurrency; lock cleanup; truncation; unexpected tables; permissions; transaction snapshot.\n";
} finally {
    $pdo = null;
    File::deleteDirectory($directory);
}
