<?php

// Isolated release validation only. Never point this script at a customer installation.
use App\Services\OfficialUpdateService;
use App\Services\ReleaseManifestSigner;
use App\Services\Updates\DatabaseSnapshot;
use App\Services\Updates\UpdatePaths;
use App\Services\Updates\UpdatePolicy;
use App\Services\Updates\UpdatePortalClient;
use App\Services\Updates\UpdateWorker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

[$script, $root, $archive, $metadata, $report] = $argv;
$root = realpath($root);
$temporaryRoot = realpath(getenv('RUNNER_TEMP') ?: sys_get_temp_dir());
if (! $root || ! $temporaryRoot || ! str_starts_with($root, $temporaryRoot.DIRECTORY_SEPARATOR) || is_file($root.'/.env')) {
    throw new RuntimeException('Only a fresh temporary CI installation is accepted.');
}
$env = [
    'APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'APP_URL' => 'https://upgrade-test.invalid',
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $root.'/database/upgrade-smoke.sqlite',
    'CACHE_STORE' => 'file', 'SESSION_DRIVER' => 'file', 'QUEUE_CONNECTION' => 'sync',
    'CAMPAIGN_DEPLOYMENT_MODE' => 'self_hosted', 'MAIL_MAILER' => 'log',
];
foreach ($env as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
file_put_contents($root.'/.env', implode("\n", array_map(fn ($key, $value) => $key.'='.$value, array_keys($env), $env))."\n");
touch($env['DB_DATABASE']);
chdir($root);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$assert = function (bool $ok, string $message): void {
    if (! $ok) { throw new RuntimeException($message); }
};
$assert(app(OfficialUpdateService::class)->installedVersion() === '1.1.3', 'Source archive is not 1.1.3.');
$sourceManifest = json_decode(file_get_contents($root.'/release-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$assert(app(ReleaseManifestSigner::class)->verify($sourceManifest) === true, 'Source release signature failed.');
$assert(Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]) === 0, 'Cannot initialize the isolated 1.1.3 schema.');
DB::table('system_settings')->insert(['package' => 'presidential', 'system_name' => 'Isolated upgrade fixture']);
DB::table('users')->insert([
    'uuid' => (string) Str::uuid(), 'username' => 'upgrade-fixture',
    'firstname' => 'Upgrade', 'lastname' => 'Fixture', 'email' => 'upgrade@example.invalid',
    'password' => password_hash('isolated-upgrade-password', PASSWORD_BCRYPT),
    'access_level' => 'superadmin', 'status' => 'active',
    'country_id' => null, 'region_id' => null, 'state_id' => null, 'lga_id' => null,
    'ward_id' => null, 'polling_unit_id' => null, 'religion_id' => null, 'age_grade_id' => null,
]);
DB::table('political_parties')->insert([
    'uuid' => (string) Str::uuid(), 'name' => 'Owner custom party', 'acronym' => 'OWN',
    'logo' => 'owner-custom-logo.png', 'created_at' => now(), 'updated_at' => now(),
]);
$beforeUser = (array) DB::table('users')->where('username', 'upgrade-fixture')->first();
$beforeEnv = hash_file('sha256', $root.'/.env');
$uploads = $root.'/public/uploads/member_images';
if (! is_dir($uploads)) { mkdir($uploads, 0755, true); }
file_put_contents($uploads.'/upgrade-preservation.txt', 'Existing upload must survive.');
$beforeUpload = hash_file('sha256', $uploads.'/upgrade-preservation.txt');

$outer = json_decode(file_get_contents($metadata), true, 512, JSON_THROW_ON_ERROR);
app(OfficialUpdateService::class)->validateManifest($outer, '1.1.4');
$assert(hash_equals($outer['checksum_sha256'], hash_file('sha256', $archive)), 'Candidate archive hash mismatch.');
$stageId = (string) Str::uuid();
$stage = UpdatePaths::stage($stageId);
mkdir($stage.'/application', 0700, true);
$zip = new ZipArchive;
$assert($zip->open($archive) === true, 'Cannot open the signed candidate.');
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    $assert(! str_starts_with($name, '/') && ! str_contains($name, '\\') && ! in_array('..', explode('/', $name), true), 'Unsafe archive path.');
}
$assert($zip->extractTo($stage.'/application'), 'Cannot stage the candidate.');
$zip->close();
$inner = json_decode(file_get_contents($stage.'/application/release-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$assert(app(ReleaseManifestSigner::class)->verify($inner) === true, 'Embedded signature failed.');
UpdatePaths::write($stage.'/journal.json', ['id' => $stageId, 'version' => '1.1.4', 'state' => 'prepared', 'manifest' => $outer]);
$policy = app(UpdatePolicy::class); // The actual installed 1.1.3 policy, not candidate code.
$plan = $policy->inspect($stageId);
$assert($plan['source_version'] === '1.1.3' && $plan['target_version'] === '1.1.4', 'Unexpected upgrade plan.');
$assert(array_keys($plan['pending_migrations']) === ['2026_10_20_000001_add_nigeria_democratic_congress'], 'Unexpected pending migrations.');
$id = (string) Str::uuid();
UpdatePaths::write(UpdatePaths::job($id).'/journal.json', $plan + ['id' => $id, 'state' => 'queued', 'migrations_started' => false]);
// Only entitlement calls are replaced in this isolated test. Signature checking,
// file replacement, database backup, migrations, subprocesses and health checks are real.
$portal = new class extends UpdatePortalClient {
    public bool $finished = false;
    public function authorize(array $job): array {
        if ($job['source_version'] !== '1.1.3' || $job['target_version'] !== '1.1.4') {
            throw new RuntimeException('Unexpected isolated authorization.');
        }
        return ['authorized' => true];
    }
    public function finish(array $job, bool $successful): void { $this->finished = $successful; }
};
(new UpdateWorker($policy, app(DatabaseSnapshot::class), $portal))->run($id);
$journal = json_decode(file_get_contents(UpdatePaths::job($id).'/journal.json'), true, 512, JSON_THROW_ON_ERROR);
if ($journal['state'] !== 'completed') {
    foreach (['failure.log', '../../command-errors.log'] as $relative) {
        $path = UpdatePaths::job($id).'/'.$relative;
        if (is_file($path)) { fwrite(STDERR, file_get_contents($path)); }
    }
    throw new RuntimeException('Isolated upgrade did not complete: '.$journal['state']);
}
DB::purge();
$assert(app(OfficialUpdateService::class)->installedVersion() === '1.1.4', 'Installed version did not change.');
$assert((array) DB::table('users')->where('username', 'upgrade-fixture')->first() === $beforeUser, 'Existing user data changed.');
$assert(hash_file('sha256', $root.'/.env') === $beforeEnv, 'Environment or APP_KEY changed.');
$assert(hash_file('sha256', $uploads.'/upgrade-preservation.txt') === $beforeUpload, 'Existing upload changed.');
$assert(DB::table('political_parties')->where('acronym', 'OWN')->value('logo') === 'owner-custom-logo.png', 'Custom party changed.');
$assert(DB::table('political_parties')->where('acronym', 'NDC')->count() === 1, 'NDC was not added.');
$logo = DB::table('political_parties')->where('acronym', 'NDC')->value('logo');
$assert(is_file(public_path('uploads/system_images/politicalparty/'.$logo)), 'Installed NDC logo is missing.');
$assert($journal['backup_complete'] && is_file(UpdatePaths::job($id).'/backup/environment.env'), 'Verified backup is missing.');
$assert(! is_file(storage_path('framework/down')) && $portal->finished, 'Upgrade did not leave the app healthy.');
// Confirm authentication credentials still work after replacement.
$assert(password_verify('isolated-upgrade-password', DB::table('users')->where('username', 'upgrade-fixture')->value('password')), 'Existing credentials no longer verify.');
$result = [
    'source' => 'official v1.1.3 application ZIP', 'target' => 'signed v1.1.4 candidate',
    'state' => 'completed', 'old_engine_used' => true,
    'signatures_verified' => true, 'backup_verified' => true,
    'environment_preserved' => true, 'existing_users_preserved' => true,
    'existing_uploads_preserved' => true, 'custom_parties_preserved' => true,
    'ndc_installed' => true, 'credentials_verified' => true,
    'authorization' => 'isolated entitlement stub; no payment or customer installation used',
];
file_put_contents($report, json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
echo json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
