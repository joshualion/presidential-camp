<?php

use App\Models\SystemSetting;
use App\Services\ReleaseManifestSigner;
use App\Services\Updates\AutomaticUpdateService;
use App\Services\Updates\DatabaseSnapshot;
use App\Services\Updates\UpdatePaths;
use App\Services\Updates\UpdatePolicy;
use App\Services\Updates\UpdatePortalClient;
use App\Services\Updates\UpdateProgress;
use App\Services\Updates\UpdateWorker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->originalBase = base_path();
    $this->originalStorage = storage_path();
    $this->sandbox = sys_get_temp_dir().'/campaign-update-test-'.Str::uuid();
    File::ensureDirectoryExists($this->sandbox.'/public');
    File::ensureDirectoryExists($this->sandbox.'/storage/framework');
    app()->setBasePath($this->sandbox);
    app()->useStoragePath($this->sandbox.'/storage');
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $private);
    $this->updateKey = $private;
    config(['release.signing.public_key' => openssl_pkey_get_details($key)['key'], 'release.identity.version' => '1.0.8']);
});
afterEach(function () {
    app()->setBasePath($this->originalBase);
    app()->useStoragePath($this->originalStorage);
    File::deleteDirectory($this->sandbox);
});
function safetySign(array $manifest, string $private): array
{
    openssl_sign(app(ReleaseManifestSigner::class)->canonicalPayload($manifest), $signature, $private, OPENSSL_ALGO_SHA256);
    $manifest['manifest_signature'] = ['algorithm' => 'rsa-sha256', 'value' => base64_encode($signature)];

    return $manifest;
}
function safetyStage($test, array $migrationFiles = [], array $approved = [], array $sources = ['1.0.8', '1.0.9', '1.1.0'], string $target = '1.1.1'): string
{
    $id = (string) Str::uuid();
    $stage = UpdatePaths::stage($id);
    $files = ['artisan' => '<?php', 'vendor/autoload.php' => '<?php', 'bootstrap/app.php' => '<?php', 'app/Console/Commands/CampaignUpdateHealth.php' => '<?php'] + $migrationFiles;
    $manifest = ['version' => $target, 'checksums' => [], 'upgrade' => ['protocol' => 1, 'supported_sources' => $sources, 'approved_migrations' => $approved, 'created_tables' => []]];
    foreach ($files as $name => $content) {
        File::ensureDirectoryExists(dirname($stage.'/application/'.$name));
        File::put($stage.'/application/'.$name, $content);
        $manifest['checksums'][$name] = hash('sha256', $content);
    }
    File::put($stage.'/application/release-manifest.json', json_encode(safetySign($manifest, $test->updateKey)));
    $outer = safetySign($manifest + ['checksum_sha256' => str_repeat('a', 64), 'size_bytes' => 100], $test->updateKey);
    UpdatePaths::write($stage.'/journal.json', ['id' => $id, 'version' => $target, 'state' => 'prepared', 'manifest' => $outer]);

    return $id;
}
test('supported legacy versions use existing migration history without replaying installed migrations', function () {
    foreach (['1.0.8', '1.0.9', '1.1.0'] as $version) {
        config(['release.identity.version' => $version]);
        $stage = safetyStage($this);
        $plan = app(UpdatePolicy::class)->inspect($stage);
        expect($plan['source_version'])->toBe($version)->and($plan['pending_migrations'])->toBe([]);
    }
});
test('unknown versions and unreviewed pending migrations fail closed', function () {
    $stage = safetyStage($this, ['database/migrations/2099_01_01_pending.php' => '<?php return null;']);
    expect(fn () => app(UpdatePolicy::class)->inspect($stage))->toThrow(RuntimeException::class, 'not approved');
    config(['release.identity.version' => '1.0.7']);
    expect(fn () => app(UpdatePolicy::class)->inspect($stage))->toThrow(RuntimeException::class, 'reviewed automatic upgrade');
});
test('only exact reviewed pending migration hashes are authorized', function () {
    $content = '<?php return null;';
    $name = '2099_01_01_pending';
    $stage = safetyStage($this, ['database/migrations/'.$name.'.php' => $content], [$name => hash('sha256', $content)]);
    expect(app(UpdatePolicy::class)->inspect($stage)['pending_migrations'])->toBe([$name => hash('sha256', $content)]);
    file_put_contents(UpdatePaths::stage($stage).'/application/database/migrations/'.$name.'.php', '<?php echo "changed";');
    expect(fn () => app(UpdatePolicy::class)->inspect($stage))->toThrow(RuntimeException::class, 'changed');
});
test('environment uploads hosting rules and symlink destinations are protected', function () {
    foreach (['.env', 'storage/app/data', 'public/uploads/logo.png', 'public/storage/avatar.png', 'public/.htaccess', 'bootstrap/cache/config.php', 'app/../.env', 'app\\bad.php'] as $path) {
        expect(UpdatePaths::managed($path))->toBeFalse();
    }
    symlink($this->sandbox.'/storage', $this->sandbox.'/app');
    expect(fn () => UpdatePaths::destination('app/Bad.php'))->toThrow(RuntimeException::class, 'symbolic');
});
test('inner and outer release policies must match even when both signatures are valid', function () {
    $stage = safetyStage($this);
    $path = UpdatePaths::stage($stage).'/journal.json';
    $journal = json_decode(file_get_contents($path), true);
    $journal['manifest']['upgrade']['supported_sources'] = ['1.0.8'];
    unset($journal['manifest']['manifest_signature']);
    $journal['manifest'] = safetySign($journal['manifest'], $this->updateKey);
    UpdatePaths::write($path, $journal);
    expect(fn () => app(UpdatePolicy::class)->inspect($stage))->toThrow(RuntimeException::class, 'policies differ');
});
test('file backed SQLite snapshots preserve records and reject tampered backups', function () {
    $original = config('database.connections.sqlite');
    $database = $this->sandbox.'/live.sqlite';
    touch($database);
    config(['database.connections.update_fixture' => ['driver' => 'sqlite', 'database' => $database, 'prefix' => '', 'foreign_key_constraints' => true], 'database.default' => 'update_fixture']);
    DB::purge('update_fixture');
    DB::statement('create table example (id integer primary key, name text)');
    DB::table('example')->insert(['name' => 'Keep this record']);
    File::ensureDirectoryExists($this->sandbox.'/backup');
    try {
        $service = app(DatabaseSnapshot::class);
        $service->preflight();
        $backup = $service->backup($this->sandbox.'/backup');
        DB::table('example')->update(['name' => 'Changed']);
        $service->restore($this->sandbox.'/backup', $backup, []);
        expect(DB::table('example')->value('name'))->toBe('Keep this record');
        file_put_contents($this->sandbox.'/backup/'.$backup['filename'], 'corrupt');
        expect(fn () => $service->restore($this->sandbox.'/backup', $backup, []))->toThrow(RuntimeException::class, 'integrity');
    } finally {
        DB::purge('update_fixture');
        config(['database.default' => 'sqlite']);
    }
});
function recoveryFixture($test): array
{
    $id = (string) Str::uuid();
    $dir = UpdatePaths::job($id);
    File::ensureDirectoryExists($dir.'/backup/files/app');
    File::ensureDirectoryExists($test->sandbox.'/app');
    File::ensureDirectoryExists($test->sandbox.'/bootstrap/cache');
    File::put($test->sandbox.'/app/Example.php', 'new code');
    File::put($dir.'/backup/files/app/Example.php', 'old code');
    File::put($test->sandbox.'/.env', 'PRESERVE=secret');
    File::put($test->sandbox.'/storage/framework/down', 'owned-maintenance');
    $live = $test->sandbox.'/live.sqlite';
    $pdo = new PDO('sqlite:'.$live);
    $pdo->exec('create table customers (name text)');
    $pdo->exec("insert into customers values ('Existing customer')");
    $pdo = null;
    copy($live, $dir.'/backup/database.sqlite');
    $pdo = new PDO('sqlite:'.$live);
    $pdo->exec("update customers set name='Partial migration'");
    $pdo = null;
    $c = ['driver' => 'sqlite', 'database' => $live];
    UpdatePaths::write($dir.'/backup/connection.json', $c);
    $job = ['id' => $id, 'base_path' => $test->sandbox, 'state' => 'recovery_required', 'backup_complete' => true, 'migrations_started' => true, 'maintenance_hash' => hash('sha256', 'owned-maintenance'), 'created_tables' => [], 'files' => ['app/Example.php' => hash('sha256', 'new code')], 'original_files' => ['app/Example.php' => ['exists' => true, 'sha256' => hash('sha256', 'old code'), 'mode' => 0644]], 'database' => ['filename' => 'database.sqlite', 'sha256' => hash_file('sha256', $dir.'/backup/database.sqlite'), 'connection' => hash('sha256', json_encode($c)), 'driver' => 'sqlite', 'tables' => ['customers']]];
    UpdatePaths::write($dir.'/journal.json', $job);
    copy($test->originalBase.'/tools/updater/recover.php', $dir.'/recover.php');

    return [$id, $dir, $live];
}
test('standalone recovery restores pre upgrade records and files without a Laravel boot', function () {
    [$id,$dir,$live] = recoveryFixture($this);
    $p = new Process([PHP_BINARY, $dir.'/recover.php', '--confirm='.$id, '--restore-database']);
    $p->run();
    expect($p->getOutput().$p->getErrorOutput())->toContain('Recovery completed');
    expect($p->getExitCode())->toBe(0)->and(file_get_contents($this->sandbox.'/app/Example.php'))->toBe('old code')->and(file_get_contents($this->sandbox.'/.env'))->toBe('PRESERVE=secret')->and(is_file($this->sandbox.'/storage/framework/down'))->toBeFalse();
    $pdo = new PDO('sqlite:'.$live);
    expect($pdo->query('select name from customers')->fetchColumn())->toBe('Existing customer');
});
test('recovery refuses database restore without consent and refuses a tampered file backup', function () {
    [$id,$dir,$live] = recoveryFixture($this);
    $p = new Process([PHP_BINARY, $dir.'/recover.php', '--confirm='.$id]);
    $p->run();
    expect($p->getExitCode())->toBe(1)->and(file_get_contents($this->sandbox.'/app/Example.php'))->toBe('new code');
    file_put_contents($dir.'/backup/files/app/Example.php', 'tampered');
    $p = new Process([PHP_BINARY, $dir.'/recover.php', '--confirm='.$id, '--restore-database']);
    $p->run();
    expect($p->getExitCode())->toBe(1)->and($p->getErrorOutput())->toContain('integrity');
    $pdo = new PDO('sqlite:'.$live);
    expect($pdo->query('select name from customers')->fetchColumn())->toBe('Partial migration');
});
test('recovery rejects unexpected new tables before overwriting existing data', function () {
    [$id,$dir,$live] = recoveryFixture($this);
    $pdo = new PDO('sqlite:'.$live);
    $pdo->exec('create table unexpected (id integer)');
    $pdo = null;
    $p = new Process([PHP_BINARY, $dir.'/recover.php', '--confirm='.$id, '--restore-database']);
    $p->run();
    expect($p->getExitCode())->toBe(1)->and($p->getErrorOutput())->toContain('Unexpected new tables');
});

test('the initiating response contains live progress and a private raw status cookie', function () {
    $id = (string) Str::uuid();
    $request = Request::create('https://example.test/superadmin/updates/apply', 'POST');
    $response = UpdateProgress::response(['id' => $id, 'status_token' => str_repeat('c', 64)], $request);
    expect($response->getStatusCode())->toBe(200)->and($response->getContent())->toContain('campaign-update-status.php')->and($response->getContent())->not->toContain(str_repeat('c', 64));
    $cookie = $response->headers->getCookies()[0];
    expect($cookie->getName())->toBe('cm_update_'.$id)->and($cookie->getValue())->toBe(str_repeat('c', 64))->and($cookie->isHttpOnly())->toBeTrue()->and($cookie->isSecure())->toBeTrue()->and($cookie->getSameSite())->toBe('strict')->and($cookie->getPath())->toBe('/campaign-update-status.php');
});

test('bootstrap packaging signs every bridge file and refuses tampering before installation', function ($sourceVersion) {
    require_once $this->originalBase.'/tools/updater/build-bootstrap.php';
    app()->setBasePath($this->originalBase);
    try {
        $zip = buildCampaignUpdaterBootstrap($this->sandbox, $this->updateKey, config('release.signing.public_key'), '1.1.7');
    } finally {
        app()->setBasePath($this->sandbox);
    }
    $bundle = $this->sandbox.'/bundle';
    $archive = new ZipArchive;
    $archive->open($zip);
    $archive->extractTo($bundle);
    $archive->close();
    File::ensureDirectoryExists($this->sandbox.'/vendor');
    File::put($this->sandbox.'/vendor/autoload.php', '<?php');
    File::put($this->sandbox.'/.env', 'APP_KEY=preserved');
    File::put($this->sandbox.'/release-manifest.json', json_encode(['version' => $sourceVersion]));
    $path = $bundle.'/engine/UpdatePolicy.php';
    $original = file_get_contents($path);
    file_put_contents($path, $original.'tampered');
    $p = new Process([PHP_BINARY, $bundle.'/bootstrap-install.php', $this->sandbox]);
    $p->run();
    expect($p->getExitCode())->toBe(1)->and(is_dir($this->sandbox.'/storage/app/updates/bootstrap'))->toBeFalse();
    file_put_contents($path, $original);
    $p = new Process([PHP_BINARY, $bundle.'/bootstrap-install.php', $this->sandbox]);
    $p->run();
    expect($p->getOutput().$p->getErrorOutput())->toContain('Bootstrap installed')->and($p->getExitCode())->toBe(0)->and(file_get_contents($this->sandbox.'/.env'))->toBe('APP_KEY=preserved')->and(is_file($this->sandbox.'/public/campaign-update-bootstrap.php'))->toBeTrue();
    $again = new Process([PHP_BINARY, $bundle.'/bootstrap-install.php', $this->sandbox]);
    $again->run();
    expect($again->getExitCode())->toBe(1);
    $replace = new Process([PHP_BINARY, $bundle.'/bootstrap-install.php', $this->sandbox, '--replace-bootstrap']);
    $replace->run();
    expect($replace->getExitCode())->toBe(0)
        ->and(glob($this->sandbox.'/storage/app/updates/bootstrap-history/*/bootstrap'))->toHaveCount(1)
        ->and(file_get_contents($this->sandbox.'/.env'))->toBe('APP_KEY=preserved');

})->with(['1.0.8', '1.1.2', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9', '1.2.0']);

test('bootstrap installation rejects an unreviewed source before changing the installation', function ($sourceVersion) {
    File::ensureDirectoryExists($this->sandbox.'/vendor');
    File::put($this->sandbox.'/vendor/autoload.php', '<?php');
    File::put($this->sandbox.'/.env', 'APP_KEY=preserved');
    File::put($this->sandbox.'/release-manifest.json', json_encode(['version' => $sourceVersion]));
    $process = new Process([PHP_BINARY, $this->originalBase.'/tools/updater/bootstrap-install.php', $this->sandbox]);
    $process->run();
    expect($process->getExitCode())->toBe(1)
        ->and($process->getErrorOutput())->toContain('This bootstrap supports only')
        ->and(is_dir($this->sandbox.'/storage/app/updates/bootstrap'))->toBeFalse()
        ->and(is_file($this->sandbox.'/public/campaign-update-bootstrap.php'))->toBeFalse()
        ->and(file_get_contents($this->sandbox.'/.env'))->toBe('APP_KEY=preserved');
})->with(['1.2.1', '9.9.9']);

test('authorized startup writes a valid private worker and permits cancelling only queued jobs', function () {
    $id = (string) Str::uuid();
    $plan = ['source_version' => '1.0.8', 'target_version' => '1.1.1', 'stage_id' => $id, 'files' => ['app/Test.php' => hash('sha256', 'test')], 'pending_migrations' => [], 'created_tables' => [], 'manifest_hash' => str_repeat('b', 64), 'package_sha256' => str_repeat('a', 64)];
    $policy = Mockery::mock(UpdatePolicy::class);
    $policy->shouldReceive('inspect')->andReturn($plan);
    $database = Mockery::mock(DatabaseSnapshot::class);
    $database->shouldReceive('preflight')->andReturn(0);
    $portal = Mockery::mock(UpdatePortalClient::class);
    $portal->shouldReceive('packageType')->andReturn('presidential');
    $portal->shouldReceive('authorize')->once()->andReturn(['authorized' => true]);
    $portal->shouldReceive('finish')->once()->withArgs(fn ($job, $success) => ! $success);
    config(['queue.default' => 'sync']);
    UpdatePaths::write(UpdatePaths::root().'/check.json', ['latest_version' => '1.1.1', 'manifest' => ['checksum_sha256' => str_repeat('a', 64)]]);
    $service = new class($policy, $database, $portal) extends AutomaticUpdateService
    {
        protected function launch(string $id): void {}
    };
    $job = $service->start($id);
    $dir = UpdatePaths::job($job['id']);
    $p = new Process([PHP_BINARY, '-l', $dir.'/worker.php']);
    $p->run();
    expect($p->getExitCode())->toBe(0)->and(fileperms($dir.'/worker.php') & 0777)->toBe(0600);
    $supervisorLint = new Process([PHP_BINARY, '-l', $dir.'/supervise.php']);
    $supervisorLint->run();
    expect($supervisorLint->getExitCode())->toBe(0)
        ->and(fileperms($dir.'/supervise.php') & 0777)->toBe(0600);
    $service->cancelQueued($job['id']);
    $journal = json_decode(file_get_contents($dir.'/journal.json'), true);
    expect($journal['state'])->toBe('failed');
    expect(fn () => $service->cancelQueued($job['id']))->toThrow(RuntimeException::class, 'Only an update');
});

function workerSafetyFixture($test): array
{
    $stage = (string) Str::uuid();
    $id = (string) Str::uuid();
    $directory = UpdatePaths::job($id);
    $staged = UpdatePaths::stage($stage).'/application';
    File::ensureDirectoryExists($test->sandbox.'/app');
    File::put($test->sandbox.'/app/Existing.php', 'old application');
    File::ensureDirectoryExists($staged.'/app');
    File::put($staged.'/app/Existing.php', 'new application');
    File::ensureDirectoryExists($staged.'/database/migrations');
    File::put($staged.'/database/migrations/2099_01_01_approved.php', '<?php');
    File::ensureDirectoryExists(storage_path('app/updates/bootstrap'));
    copy($test->originalBase.'/tools/updater/recover.php', storage_path('app/updates/bootstrap/recover.php'));
    File::put($test->sandbox.'/artisan', '<?php $base=__DIR__; $command=$argv[1]; if($command==="down"){file_put_contents($base."/storage/framework/down","owned");} elseif($command==="up"){@unlink($base."/storage/framework/down");} elseif($command==="migrate"){$db=new PDO("sqlite:".$base."/live.sqlite");$db->exec("UPDATE customers SET name=\'Partial migration write\'");exit(1);}');
    $plan = ['stage_id' => $stage, 'source_version' => '1.0.8', 'target_version' => '1.1.1', 'manifest_hash' => str_repeat('b', 64), 'package_sha256' => str_repeat('a', 64), 'files' => ['app/Existing.php' => hash('sha256', 'new application')], 'pending_migrations' => ['2099_01_01_approved' => hash('sha256', '<?php')], 'created_tables' => []];
    $job = $plan + ['id' => $id, 'state' => 'queued', 'migrations_started' => false];
    UpdatePaths::write($directory.'/journal.json', $job);

    return [$id, $directory, $plan];
}
test('a migration failure automatically restores the database and application before reopening', function () {
    [$id,$directory,$plan] = workerSafetyFixture($this);
    $live = $this->sandbox.'/live.sqlite';
    touch($live);
    config(['database.default' => 'update_worker_fixture', 'database.connections.update_worker_fixture' => ['driver' => 'sqlite', 'database' => $live, 'prefix' => '', 'foreign_key_constraints' => true], 'queue.default' => 'sync']);
    DB::purge('update_worker_fixture');
    DB::statement('create table customers (name text)');
    DB::table('customers')->insert(['name' => 'Existing customer']);
    $policy = Mockery::mock(UpdatePolicy::class);
    $policy->shouldReceive('inspect')->andReturn($plan);
    $portal = Mockery::mock(UpdatePortalClient::class);
    $portal->shouldReceive('authorize')->andReturn(['authorized' => true]);
    $portal->shouldReceive('finish')->withArgs(fn ($job, $ok) => ! $ok)->once();
    try {
        (new UpdateWorker($policy, app(DatabaseSnapshot::class), $portal))->run($id);
        $journal = json_decode(file_get_contents($directory.'/journal.json'), true);
        $this->assertSame('recovered', $journal['state'], (is_file($directory.'/failure.log') ? file_get_contents($directory.'/failure.log') : '').(is_file(storage_path('app/updates/command-errors.log')) ? file_get_contents(storage_path('app/updates/command-errors.log')) : ''));
        expect($journal['state'])->toBe('recovered')
            ->and($journal['migrations_started'])->toBeTrue()
            ->and($journal['backup_complete'])->toBeTrue()
            ->and(is_file(storage_path('framework/down')))->toBeFalse()
            ->and(file_get_contents($this->sandbox.'/app/Existing.php'))->toBe('old application');
        DB::purge('update_worker_fixture');
        expect(DB::table('customers')->value('name'))->toBe('Existing customer');
    } finally {
        DB::purge('update_worker_fixture');
        config(['database.default' => 'sqlite']);
    }
});
test('a successful worker preserves customer data verifies version and reports completion after reopening', function () {
    [$id, $directory, $plan] = workerSafetyFixture($this);
    $live = $this->sandbox.'/live.sqlite';
    touch($live);
    config(['database.default' => 'update_success_fixture', 'database.connections.update_success_fixture' => ['driver' => 'sqlite', 'database' => $live, 'prefix' => '', 'foreign_key_constraints' => true], 'queue.default' => 'sync']);
    DB::purge('update_success_fixture');
    DB::statement('create table customers (name text)');
    DB::table('customers')->insert(['name' => 'Existing customer']);
    File::put($this->sandbox.'/.env', 'APP_KEY=preserved');
    File::ensureDirectoryExists($this->sandbox.'/public/uploads');
    File::put($this->sandbox.'/public/uploads/customer.txt', 'preserved upload');
    $manifest = json_encode(['version' => '1.1.1']);
    File::put(UpdatePaths::stage($plan['stage_id']).'/application/release-manifest.json', $manifest);
    $plan['files']['release-manifest.json'] = hash('sha256', $manifest);
    UpdatePaths::write($directory.'/journal.json', $plan + ['id' => $id, 'state' => 'queued', 'migrations_started' => false]);
    File::put($this->sandbox.'/artisan', <<<'PHP'
<?php
$base = __DIR__;
$command = $argv[1];
file_put_contents($base.'/commands.jsonl', json_encode($argv)."\n", FILE_APPEND);
if ($command === 'down') file_put_contents($base.'/storage/framework/down', 'owned');
if ($command === 'up') unlink($base.'/storage/framework/down');
if ($command === 'migrate') (new PDO('sqlite:'.$base.'/live.sqlite'))->exec('ALTER TABLE customers ADD COLUMN upgraded INTEGER DEFAULT 1');
if ($command === 'campaign:updates-health') {
    $version = json_decode(file_get_contents($base.'/release-manifest.json'), true)['version'];
    $name = (new PDO('sqlite:'.$base.'/live.sqlite'))->query('SELECT name FROM customers')->fetchColumn();
    if ($version !== $argv[2] || $name !== 'Existing customer') exit(1);
}
PHP);
    $policy = Mockery::mock(UpdatePolicy::class);
    $policy->shouldReceive('inspect')->andReturn($plan);
    $portal = Mockery::mock(UpdatePortalClient::class);
    $portal->shouldReceive('authorize')->once()->andReturn(['authorized' => true]);
    $portal->shouldReceive('finish')->once()->withArgs(fn ($job, $ok) => $ok && ! is_file(storage_path('framework/down')));
    try {
        (new UpdateWorker($policy, app(DatabaseSnapshot::class), $portal))->run($id);
        $journal = json_decode(file_get_contents($directory.'/journal.json'), true);
        expect($journal['state'])->toBe('completed')->and($journal['backup_complete'])->toBeTrue()
            ->and($journal['installation_verified'])->toBeTrue()->and($journal['portal_receipt_pending'])->toBeFalse()
            ->and(app(\App\Services\OfficialUpdateService::class)->installedVersion())->toBe('1.1.1')
            ->and(file_get_contents($this->sandbox.'/.env'))->toBe('APP_KEY=preserved')
            ->and(file_get_contents($this->sandbox.'/public/uploads/customer.txt'))->toBe('preserved upload')
            ->and(file_get_contents($this->sandbox.'/app/Existing.php'))->toBe('new application');
        DB::purge('update_success_fixture');
        expect(DB::table('customers')->value('name'))->toBe('Existing customer')->and((int) DB::table('customers')->value('upgraded'))->toBe(1);
    } finally {
        DB::purge('update_success_fixture');
        config(['database.default' => 'sqlite']);
    }
});

test('backup failure stops the worker before replacing application files', function () {
    [$id,$directory,$plan] = workerSafetyFixture($this);
    $policy = Mockery::mock(UpdatePolicy::class);
    $policy->shouldReceive('inspect')->andReturn($plan);
    $database = Mockery::mock(DatabaseSnapshot::class);
    $database->shouldReceive('backup')->andThrow(new RuntimeException('Simulated export failure'));
    $portal = Mockery::mock(UpdatePortalClient::class);
    $portal->shouldReceive('authorize')->andReturn(['authorized' => true]);
    $portal->shouldReceive('finish')->once();
    config(['queue.default' => 'sync']);
    (new UpdateWorker($policy, $database, $portal))->run($id);
    $journal = json_decode(file_get_contents($directory.'/journal.json'), true);
    expect($journal['state'])->toBe('failed')->and(file_get_contents($this->sandbox.'/app/Existing.php'))->toBe('old application')->and(is_file(storage_path('framework/down')))->toBeFalse();
});

test('compact signed discovery metadata binds the exact full embedded manifest', function () {
    config(['release.identity.version' => '1.1.0']);
    $id = safetyStage($this, [], [], ['1.1.0']);
    $stage = UpdatePaths::stage($id);
    $journal = json_decode(file_get_contents($stage.'/journal.json'), true);
    unset($journal['manifest']['checksums'], $journal['manifest']['manifest_signature']);
    $journal['manifest']['embedded_manifest_sha256'] = hash_file('sha256', $stage.'/application/release-manifest.json');
    $journal['manifest'] = safetySign($journal['manifest'], $this->updateKey);
    UpdatePaths::write($stage.'/journal.json', $journal);
    expect(app(UpdatePolicy::class)->inspect($id)['target_version'])->toBe('1.1.1');
    $inner = json_decode(file_get_contents($stage.'/application/release-manifest.json'), true);
    $inner['changed'] = true;
    unset($inner['manifest_signature']);
    File::put($stage.'/application/release-manifest.json', json_encode(safetySign($inner, $this->updateKey)));
    expect(fn () => app(UpdatePolicy::class)->inspect($id))->toThrow(RuntimeException::class, 'embedded manifest');
});

test('Core requests the Portal price for its installed scope and maps chairmanship to local government', function () {
    SystemSetting::create(['package' => 'chairmanship']);
    config(['app.url' => 'https://campaign.example.test', 'campaign.portal_api_base' => 'https://campaignmanager.ng/api/v2', 'campaign.portal_web_base' => 'https://campaignmanager.ng']);
    File::ensureDirectoryExists(storage_path('app/private'));
    $uuid = (string) Str::uuid();
    File::put(storage_path('app/private/deployment-registration.json'), Crypt::encryptString(json_encode(['id' => $uuid, 'token' => str_repeat('a', 64), 'registered' => true, 'domain' => 'campaign.example.test'])));
    Http::fake(['campaignmanager.ng/*' => fn ($request) => Http::response(['package_type' => 'lga', 'scope_label' => 'Local Government', 'price_minor' => 500, 'currency' => 'USD', 'entitled' => false, 'payment_available' => true, 'checkout_url' => 'https://campaignmanager.ng/clientarea/automatic-update?'.http_build_query(['installation' => $uuid, 'version' => '1.1.1', 'sha256' => str_repeat('a', 64), 'package_type' => 'lga', 'quote_token' => str_repeat('b', 64), 'return_url' => $request['return_url'], 'return_token' => str_repeat('c', 64), 'return_state' => $request['return_state'], 'return_expires' => $request['return_expires']])])]);
    $quote = app(UpdatePortalClient::class)->status('1.1.1', str_repeat('a', 64));
    expect($quote['price_minor'])->toBe(500);
    Http::assertSent(fn ($r) => $r['package_type'] === 'lga' && $r->header('Authorization') === ['Bearer '.str_repeat('a', 64)]);
});
test('Core refuses to authorize a job prepared for another installed scope', function () {
    SystemSetting::create(['package' => 'presidential']);
    config(['app.url' => 'https://campaign.example.test']);
    File::ensureDirectoryExists(storage_path('app/private'));
    File::put(storage_path('app/private/deployment-registration.json'), Crypt::encryptString(json_encode(['id' => (string) Str::uuid(), 'token' => str_repeat('a',64), 'registered' => true, 'domain' => 'campaign.example.test'])));
    expect(fn () => app(UpdatePortalClient::class)->authorize(['package_type' => 'lga']))->toThrow(RuntimeException::class,'scope changed');
    Http::assertNothingSent();
});


test('Portal API redirects are refused without forwarding installation credentials', function () {
    SystemSetting::create(['package' => 'presidential']);
    config(['app.url' => 'https://campaign.example.test']);
    File::ensureDirectoryExists(storage_path('app/private'));
    File::put(storage_path('app/private/deployment-registration.json'), Crypt::encryptString(json_encode(['id' => (string) Str::uuid(), 'token' => str_repeat('a', 64), 'registered' => true, 'domain' => 'campaign.example.test'])));
    Http::fake(['campaignmanager.ng/*' => Http::response('', 302, ['Location' => 'https://evil.test/collect'])]);
    expect(fn () => app(UpdatePortalClient::class)->status('1.1.6', str_repeat('a', 64)))->toThrow(\App\Services\Updates\UpdateException::class);
    Http::assertSentCount(1);
    Http::assertNotSent(fn ($request) => parse_url($request->url(), PHP_URL_HOST) === 'evil.test');
});

test('the proposed immutable 1.1.7 policy supports both affected current source versions', function ($version) {
    config(['release.identity.version' => $version]);
    $stage = safetyStage($this, sources: config('updates.supported_sources'), target: '1.1.7');
    expect(app(UpdatePolicy::class)->inspect($stage)['target_version'])->toBe('1.1.7');
})->with(['1.1.5', '1.1.6']);

test('corrective release accepts each reviewed source without replaying installed migrations', function ($version) {
    config(['release.identity.version' => $version]);
    $stage = safetyStage($this, sources: config('updates.supported_sources'), target: '1.1.3');
    $plan = app(UpdatePolicy::class)->inspect($stage);
    expect($plan['source_version'])->toBe($version)
        ->and($plan['target_version'])->toBe('1.1.3')
        ->and($plan['pending_migrations'])->toBe([]);
})->with(['1.0.8', '1.0.9', '1.1.0', '1.1.1', '1.1.2']);

test('the signed 1.1.4 policy admits 1.1.3 and only the reviewed NDC migration', function () {
    config(['release.identity.version' => '1.1.3']);
    $name = '2026_10_20_000001_add_nigeria_democratic_congress';
    $content = file_get_contents($this->originalBase.'/database/migrations/'.$name.'.php');
    $approved = config('updates.approved_migrations');
    expect($approved[$name])->toBe(hash('sha256', $content));
    DB::table('migrations')->where('migration', $name)->delete();
    $stage = safetyStage($this, ['database/migrations/'.$name.'.php' => $content], [$name => $approved[$name]], config('updates.supported_sources'), '1.1.4');
    $plan = app(UpdatePolicy::class)->inspect($stage);
    expect($plan['source_version'])->toBe('1.1.3')
        ->and($plan['target_version'])->toBe('1.1.4')
        ->and($plan['pending_migrations'])->toBe([$name => $approved[$name]]);
});


test('the independent supervisor recovers a terminated worker without loading Laravel', function () {
    [$id, $dir, $live] = recoveryFixture($this);
    $job = json_decode(file_get_contents($dir.'/journal.json'), true);
    $job['state'] = 'migrating';
    $job['source_version'] = '1.0.8';
    UpdatePaths::write($dir.'/journal.json', $job);
    File::put($this->sandbox.'/artisan', '<?php exit(0);');
    File::put($dir.'/worker.php', '<?php exit(137);');
    File::put($dir.'/supervise.php', '<?php require '.var_export($this->originalBase.'/app/Services/Updates/UpdateSupervisor.php', true).'; App\\Services\\Updates\\UpdateSupervisor::run(__DIR__,PHP_BINARY);');
    $process = new Process([PHP_BINARY, $dir.'/supervise.php']);
    $process->run();
    expect($process->getExitCode())->toBe(0);
    $job = json_decode(file_get_contents($dir.'/journal.json'), true);
    expect($job['state'])->toBe('recovered')
        ->and(is_file($this->sandbox.'/storage/framework/down'))->toBeFalse()
        ->and(file_get_contents($this->sandbox.'/app/Example.php'))->toBe('old code');
    $pdo = new PDO('sqlite:'.$live);
    expect($pdo->query('SELECT name FROM customers')->fetchColumn())->toBe('Existing customer');
});

test('automatic recovery refuses to race a surviving update command', function () {
    [$id, $dir, $live] = recoveryFixture($this);
    $lock = fopen($dir.'/commands.lock', 'c');
    flock($lock, LOCK_EX);
    try {
        $process = new Process([PHP_BINARY, $dir.'/recover.php', '--confirm='.$id, '--restore-database', '--automatic']);
        $process->run();
        expect($process->getExitCode())->toBe(1)
            ->and(file_get_contents($this->sandbox.'/app/Example.php'))->toBe('new code')
            ->and(is_file($this->sandbox.'/storage/framework/down'))->toBeTrue();
        $pdo = new PDO('sqlite:'.$live);
        expect($pdo->query('SELECT name FROM customers')->fetchColumn())->toBe('Partial migration');
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
});

test('automatic recovery keeps maintenance enabled when restored health fails', function () {
    [$id, $dir, $live] = recoveryFixture($this);
    $job = json_decode(file_get_contents($dir.'/journal.json'), true);
    $job['source_version'] = '1.0.8';
    UpdatePaths::write($dir.'/journal.json', $job);
    File::put($this->sandbox.'/artisan', '<?php exit(1);');
    $process = new Process([PHP_BINARY, $dir.'/recover.php', '--confirm='.$id, '--restore-database', '--automatic']);
    $process->run();
    expect($process->getExitCode())->toBe(1)
        ->and(file_get_contents($this->sandbox.'/app/Example.php'))->toBe('old code')
        ->and(is_file($this->sandbox.'/storage/framework/down'))->toBeTrue();
});


test('recovery never rolls back a verified update that may have accepted new writes', function () {
    [$id, $dir, $live] = recoveryFixture($this);
    $job = json_decode(file_get_contents($dir.'/journal.json'), true);
    $job['installation_verified'] = true;
    $job['state'] = 'reopening';
    UpdatePaths::write($dir.'/journal.json', $job);
    $process = new Process([PHP_BINARY, $dir.'/recover.php', '--confirm='.$id, '--restore-database', '--automatic']);
    $process->run();
    expect($process->getExitCode())->toBe(1)
        ->and(file_get_contents($this->sandbox.'/app/Example.php'))->toBe('new code');
    $pdo = new PDO('sqlite:'.$live);
    expect($pdo->query('SELECT name FROM customers')->fetchColumn())->toBe('Partial migration');
    File::put($dir.'/supervise.php', '<?php require '.var_export($this->originalBase.'/app/Services/Updates/UpdateSupervisor.php', true).'; App\\Services\\Updates\\UpdateSupervisor::run(__DIR__,PHP_BINARY,true);');
    $process = new Process([PHP_BINARY, $dir.'/supervise.php']);
    $process->run();
    $job = json_decode(file_get_contents($dir.'/journal.json'), true);
    expect($process->getExitCode())->toBe(0)
        ->and($job['state'])->toBe('completed')
        ->and(is_file($this->sandbox.'/storage/framework/down'))->toBeFalse()
        ->and(file_get_contents($this->sandbox.'/app/Example.php'))->toBe('new code')
        ->and($pdo->query('SELECT name FROM customers')->fetchColumn())->toBe('Partial migration');
});



test('1.2.1 accepts each recent reviewed source through the signed update policy', function ($version) {
    config(['release.identity.version' => $version]);
    $stage = safetyStage($this, sources: config('updates.supported_sources'), target: '1.2.1');
    $plan = app(UpdatePolicy::class)->inspect($stage);
    expect($plan['source_version'])->toBe($version)
        ->and($plan['target_version'])->toBe('1.2.1')
        ->and($plan['pending_migrations'])->toBe([]);
})->with(['1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9', '1.2.0']);
