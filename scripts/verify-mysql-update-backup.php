<?php

// Disposable CI database only. This script refuses any customer database.
if (getenv('CI') !== 'true' || getenv('DB_DATABASE') !== 'campaign_updater_ci' || getenv('DB_HOST') !== '127.0.0.1') {
    throw new RuntimeException('This check is restricted to the disposable CI database.');
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Updates\DatabaseSnapshot;
use App\Services\Updates\UpdateFlowException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

$assert = function (bool $ok, string $message): void { if (! $ok) { throw new RuntimeException($message); } };
$assert(Schema::getTables() === [], 'The disposable database is not empty.');
DB::statement('CREATE TABLE supporters (id INT PRIMARY KEY, name VARCHAR(100)) ENGINE=InnoDB');
DB::statement('CREATE TABLE legacy_supporters (id INT PRIMARY KEY, name VARCHAR(100)) ENGINE=MyISAM');
DB::statement('CREATE VIEW supporter_names AS SELECT name FROM supporters');
DB::table('supporters')->insert(['id' => 1, 'name' => 'Existing supporter']);
DB::table('legacy_supporters')->insert(['id' => 1, 'name' => 'Legacy supporter']);
$snapshot = app(DatabaseSnapshot::class);
$directory = sys_get_temp_dir().'/campaign-mysql-backup-'.bin2hex(random_bytes(6));
File::ensureDirectoryExists($directory, 0700);
try {
    foreach (['readiness', 'backup'] as $operation) {
        $blocked = false;
        try {
            $operation === 'readiness' ? $snapshot->preflight() : $snapshot->backup($directory);
        } catch (UpdateFlowException $error) {
            $blocked = $error->reason === 'database_storage' && str_contains($error->getMessage(), 'legacy_supporters (MyISAM)');
        }
        $assert($blocked, 'Mixed-engine database was not rejected with actionable table details.');
    }
    $assert(! is_file($directory.'/database.sql'), 'An inconsistent export was created.');
    // Only this disposable test table is converted; production code never changes engines.
    DB::statement('ALTER TABLE legacy_supporters ENGINE=InnoDB');
    $assert($snapshot->preflight() >= 0, 'A supported InnoDB database was rejected.');
    $backup = $snapshot->backup($directory);
    $assert($backup['driver'] === 'mysql' && hash_equals($backup['sha256'], hash_file('sha256', $directory.'/database.sql')), 'Backup checksum verification failed.');
    DB::table('supporters')->where('id', 1)->update(['name' => 'Changed after backup']);
    $snapshot->restore($directory, $backup, []);
    $assert(DB::table('supporters')->where('id', 1)->value('name') === 'Existing supporter', 'MySQL recovery did not restore the supporter.');
    $assert(DB::table('legacy_supporters')->where('id', 1)->value('name') === 'Legacy supporter', 'MySQL recovery did not preserve legacy data.');
    echo "MySQL passed: mixed engines blocked before export; table-specific guidance; InnoDB backup, checksum and real restore verified.\n";
} finally {
    File::deleteDirectory($directory);
}
