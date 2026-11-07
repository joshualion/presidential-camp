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

[$script, $root, $archive, $metadata, $report, $bootstrapArchive, $sourceVersion] = $argv;
$targetVersion = json_decode(file_get_contents($metadata), true, 64, JSON_THROW_ON_ERROR)['version'];
$reviewedSources = match ($targetVersion) {
    '1.2.6' => ['1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9', '1.2.0', '1.2.1', '1.2.2', '1.2.3', '1.2.4', '1.2.5'],
    '1.2.5' => ['1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9', '1.2.0', '1.2.1', '1.2.2', '1.2.3', '1.2.4'],
    '1.2.4' => ['1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9', '1.2.0', '1.2.1', '1.2.2', '1.2.3'],
    '1.2.3' => ['1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9', '1.2.0', '1.2.1', '1.2.2'],
    '1.2.2' => ['1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9', '1.2.0', '1.2.1'],
    '1.1.6' => ['1.1.3', '1.1.4', '1.1.5'],
    '1.1.7' => ['1.1.3', '1.1.4', '1.1.5', '1.1.6'],
    '1.1.8' => ['1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7'],
    '1.2.1' => ['1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9', '1.2.0'],
    '1.2.0' => ['1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9'],
    '1.1.9' => ['1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8'],
    default => [],
};
if (! in_array($sourceVersion, $reviewedSources, true)) {
    throw new RuntimeException('This source/target pair has not been reviewed for the isolated rehearsal.');
}
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
// Install the actual signed bootstrap BEFORE loading updater classes, exactly as
// the public bridge does. Merely using the old worker cannot test this fix.
$bundleDirectory = $root.'-bootstrap';
mkdir($bundleDirectory, 0700);
$bundle = new ZipArchive;
if ($bundle->open($bootstrapArchive) !== true || ! $bundle->extractTo($bundleDirectory)) {
    throw new RuntimeException('Cannot extract the signed bootstrap fixture.');
}
$bundle->close();
$installer = proc_open([PHP_BINARY, $bundleDirectory.'/bootstrap-install.php', $root], [0 => ['file', '/dev/null', 'r'], 1 => STDOUT, 2 => STDERR], $pipes);
if (! is_resource($installer) || proc_close($installer) !== 0) {
    throw new RuntimeException('Signed bootstrap installation failed.');
}
$private = $root.'/storage/app/updates/bootstrap';
foreach (glob($private.'/services/*.php') as $file) { require_once $file; }
foreach (glob($private.'/engine/*.php') as $file) { require_once $file; }
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Laravel's standalone exception renderer may return a successful process status.
// Every failed assertion MUST fail CI rather than only printing an error.
set_exception_handler(static function (\Throwable $error): never {
    fwrite(STDERR, (string) $error."\n");
    exit(1);
});

config(['updates' => require $private.'/updates.php', 'release.signing.public_key' => file_get_contents($private.'/trusted.pem')]);
$assert = function (bool $ok, string $message): void {
    if (! $ok) { throw new RuntimeException($message); }
};
$assert(app(OfficialUpdateService::class)->installedVersion() === $sourceVersion, 'Unexpected source archive version.');
$sourceManifest = json_decode(file_get_contents($root.'/release-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$assert(app(ReleaseManifestSigner::class)->verify($sourceManifest, file_get_contents($root.'/config/release-public.pem')) === true, 'Source release signature failed.');
$assert(Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]) === 0, 'Cannot initialize the isolated source schema.');
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
$customRole = DB::table('roles')->insertGetId(['name' => 'Customer upgrade role', 'guard_name' => 'web', 'group_name' => 'user']);
$customPermission = DB::table('permissions')->insertGetId(['name' => 'customer.upgrade.view', 'guard_name' => 'web', 'group_name' => 'user']);
DB::table('role_has_permissions')->insert(['role_id' => $customRole, 'permission_id' => $customPermission]);
DB::table('model_has_roles')->insert(['role_id' => $customRole, 'model_id' => $beforeUser['id'], 'model_type' => App\Models\User::class]);
$beforeFinance = null;
if ($sourceVersion === '1.2.5') {
    $financeAccount = DB::table('campaign_finance_accounts')->insertGetId([
        'name' => 'Existing campaign funds', 'owner_id' => $beforeUser['id'], 'created_by' => $beforeUser['id'],
        'currency' => 'NGN', 'balance_minor' => 500000,
    ]);
    $financeEntry = DB::table('campaign_finance_entries')->insertGetId([
        'account_id' => $financeAccount, 'idempotency_key' => (string) Str::uuid(), 'kind' => 'income',
        'amount_minor' => 500000, 'balance_after_minor' => 500000, 'description' => 'Synthetic legacy receipt',
        'recorded_by' => $beforeUser['id'], 'created_at' => now(),
    ]);
    $beforeFinance = (array) DB::table('campaign_finance_entries')->where('id', $financeEntry)->first();
}
$beforeEnv = hash_file('sha256', $root.'/.env');
$uploads = $root.'/public/uploads/member_images';
if (! is_dir($uploads)) { mkdir($uploads, 0755, true); }
file_put_contents($uploads.'/upgrade-preservation.txt', 'Existing upload must survive.');
$beforeUpload = hash_file('sha256', $uploads.'/upgrade-preservation.txt');

$outer = json_decode(file_get_contents($metadata), true, 512, JSON_THROW_ON_ERROR);
app(OfficialUpdateService::class)->validateManifest($outer, $targetVersion);
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
UpdatePaths::write($stage.'/journal.json', ['id' => $stageId, 'version' => $targetVersion, 'state' => 'prepared', 'manifest' => $outer]);
// Reproduce the customer's blocked state: an obsolete 1.1.4 download exists
// while the latest signed target package is already staged and ready for reuse.
$obsoleteId = (string) Str::uuid();
mkdir(UpdatePaths::stage($obsoleteId), 0700, true);
UpdatePaths::write(UpdatePaths::stage($obsoleteId).'/journal.json', [
    'id' => $obsoleteId, 'version' => '1.1.4', 'state' => 'prepared', 'manifest' => [],
]);
$base = 'https://github.com/joshualion/Campaign-Manager/releases/download/v'.$targetVersion.'/';
\Illuminate\Support\Facades\Http::preventStrayRequests();
\Illuminate\Support\Facades\Http::fake([
    'https://api.github.com/repos/joshualion/Campaign-Manager/releases/latest' => \Illuminate\Support\Facades\Http::response([
        'tag_name' => 'v'.$targetVersion, 'assets' => [
            ['name' => 'release-manifest.json', 'browser_download_url' => $base.'release-manifest.json'],
            ['name' => 'campaign-manager.zip', 'browser_download_url' => $base.'campaign-manager.zip'],
        ],
    ]),
    $base.'release-manifest.json' => \Illuminate\Support\Facades\Http::response($outer),
]);
// Hosting backup readiness is exercised by the real worker below. Only Portal
// coverage is substituted for this preparation/reuse check.
$access = new class extends \App\Services\Updates\UpdateDownloadAccess {
    public function __construct() {}
    public function assertAllowed(array $status): void {}
};
app()->instance(\App\Services\Updates\UpdateDownloadAccess::class, $access);
$reused = app(OfficialUpdateService::class)->prepare(['version' => $targetVersion, 'sha256' => $outer['checksum_sha256']]);
$assert($reused['id'] === $stageId && ! is_dir(UpdatePaths::stage($obsoleteId)), 'The obsolete stage blocked latest-package reuse.');
$assert(\Illuminate\Support\Facades\Http::recorded(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'))->isEmpty(), 'Matching package reuse unexpectedly downloaded an archive.');
$policy = app(UpdatePolicy::class); // The policy from the verified installed bootstrap.
$plan = $policy->inspect($stageId);
$assert($plan['source_version'] === $sourceVersion && $plan['target_version'] === $targetVersion, 'Unexpected upgrade plan.');
$expectedMigrations = $sourceVersion === '1.1.3' ? ['2026_10_20_000001_add_nigeria_democratic_congress'] : [];
if (version_compare($targetVersion, '1.2.0', '>=') && version_compare($sourceVersion, '1.2.0', '<')) {
    $expectedMigrations[] = '2026_10_25_000001_add_managed_campaign_capabilities';
    $expectedMigrations[] = '2026_10_25_000002_add_incident_evidence_metadata';
}
if (version_compare($targetVersion, '1.2.2', '>=') && version_compare($sourceVersion, '1.2.2', '<')) {
    $expectedMigrations[] = '2026_10_28_000001_register_enterprise_capabilities';
}
if (version_compare($targetVersion, '1.2.5', '>=') && version_compare($sourceVersion, '1.2.5', '<')) {
    $expectedMigrations = array_merge($expectedMigrations, ['2026_11_04_000010_create_campaign_finance_tables', '2026_11_04_125003_create_campaign_inventory', '2026_11_04_125004_capture_legacy_sms_access', '2026_11_04_125004_create_enterprise_ai_tables']);
}
if ($targetVersion === '1.2.6') {
    $expectedMigrations = array_merge($expectedMigrations, [
        '2026_11_05_000010_add_campaign_finance_journal',
        '2026_11_05_000011_add_campaign_finance_policies',
        '2026_11_05_000012_add_campaign_finance_reversals',
        '2026_11_06_000013_add_campaign_finance_manual_expenses',
        '2026_11_06_000014_add_campaign_finance_request_routes',
        '2026_11_06_000015_add_campaign_finance_governance',
        '2026_11_06_000016_add_campaign_finance_request_recipients',
        '2026_11_06_000017_add_campaign_finance_funding_proposals',
        '2026_11_06_000018_add_campaign_finance_proposal_workflow',
        '2026_11_06_000019_add_campaign_finance_statement_checks',
        '2026_11_06_000020_add_campaign_finance_period_events',
        '2026_11_06_000021_add_campaign_paystack_sandbox',
        '2026_11_07_000022_add_campaign_paystack_beneficiary_links',
        '2026_11_07_000023_add_campaign_paystack_refund_checks',
        '2026_11_07_000024_add_campaign_finance_statement_evidence',
        '2026_11_07_000025_bind_finance_beneficiaries_to_profiles',
    ]);
}
$actualMigrations = array_keys($plan['pending_migrations']);
sort($expectedMigrations); sort($actualMigrations);
$assert($actualMigrations === $expectedMigrations, 'Unexpected pending migrations. Expected '.json_encode($expectedMigrations).'; received '.json_encode($actualMigrations));
$id = (string) Str::uuid();
UpdatePaths::write(UpdatePaths::job($id).'/journal.json', $plan + ['id' => $id, 'state' => 'queued', 'migrations_started' => false]);
// Only entitlement calls are replaced in this isolated test. Signature checking,
// file replacement, database backup, migrations, subprocesses and health checks are real.
$portal = new class($sourceVersion, $targetVersion) extends UpdatePortalClient {
    public function __construct(private string $source, private string $target) {}
    public bool $finished = false;
    public function authorize(array $job): array {
        if ($job['source_version'] !== $this->source || $job['target_version'] !== $this->target) {
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
$assert(app(OfficialUpdateService::class)->installedVersion() === $targetVersion, 'Installed version did not change.');
if ($beforeFinance !== null) {
    $assert((array) DB::table('campaign_finance_entries')->where('id', $financeEntry)->first() === $beforeFinance, 'Existing finance entry changed.');
    $assert((int) DB::table('campaign_finance_accounts')->where('id', $financeAccount)->value('balance_minor') === 500000, 'Existing finance balance changed.');
    $assert(\Illuminate\Support\Facades\Schema::hasTable('campaign_finance_manual_expenses')
        && \Illuminate\Support\Facades\Schema::hasTable('campaign_finance_request_routes'), 'Finance workflow migrations were not applied.');
}
$afterUser = (array) DB::table('users')->where('username', 'upgrade-fixture')->first();
$assert(array_intersect_key($afterUser, $beforeUser) === $beforeUser, 'Existing user data changed.');
$assert(DB::table('roles')->where('id', $customRole)->value('name') === 'Customer upgrade role', 'Customer role changed.');
$assert(DB::table('permissions')->where('id', $customPermission)->value('name') === 'customer.upgrade.view', 'Customer permission changed.');
$assert(DB::table('role_has_permissions')->where(['role_id' => $customRole, 'permission_id' => $customPermission])->exists(), 'Customer role grant removed.');
$assert(DB::table('model_has_roles')->where(['role_id' => $customRole, 'model_id' => $beforeUser['id'], 'model_type' => App\Models\User::class])->exists(), 'Customer role assignment removed.');
if (version_compare($targetVersion, '1.2.0', '>=')) {
    $assert(array_key_exists('capabilities_managed', $afterUser) && ! (bool) $afterUser['capabilities_managed'], 'Existing user was silently adopted into managed mode.');
    $assert(! (bool) DB::table('roles')->where('id', $customRole)->value('capabilities_managed'), 'Existing role was silently adopted into managed mode.');
}
$enterpriseCapabilitiesRegistered = false;
if (version_compare($targetVersion, '1.2.2', '>=')) {
    $expectedCapabilities = (require $root.'/config/enterprise.php')['permissions'];
    $registeredCapabilities = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $expectedCapabilities)->pluck('name')->all();
    sort($expectedCapabilities); sort($registeredCapabilities);
    $assert($registeredCapabilities === $expectedCapabilities, 'Enterprise capabilities were not registered during the real upgrade.');
    $enterpriseCapabilitiesRegistered = true;
}
$enterpriseInfrastructureInitialized = false;
$automaticEntitlementTrust = false;
if (version_compare($targetVersion, '1.2.5', '>=')) {
    foreach (['campaign_finance_accounts', 'campaign_finance_budgets', 'campaign_finance_requests', 'campaign_finance_entries', 'campaign_finance_audits', 'campaign_finance_documents', 'campaign_material_categories', 'campaign_materials', 'campaign_material_stocks', 'campaign_material_requests', 'campaign_material_request_events', 'campaign_material_movements', 'campaign_enterprise_legacy_access', 'enterprise_ai_settings', 'enterprise_ai_usage', 'enterprise_ai_audits'] as $table) { $assert(\Illuminate\Support\Facades\Schema::hasTable($table), 'Missing enterprise table after update: '.$table); }
    $verification = require $root.'/config/enterprise.php';
    $pem = (string) ($verification['public_key'] ?? '');
    $key = openssl_pkey_get_public($pem);
    $details = $key ? openssl_pkey_get_details($key) : false;
    $assert($details && $details['type'] === OPENSSL_KEYTYPE_RSA && $details['bits'] >= 2048, 'Automatic enterprise verification trust is missing.');
    $assert(hash_file('sha256', $root.'/config/enterprise-public.pem') === hash_file('sha256', __DIR__.'/../config/enterprise-public.pem'), 'Updated trust anchor differs from reviewed release source.');
    $assert(is_file($root.'/app/Console/Commands/CampaignEnterpriseBootstrap.php'), 'Enterprise initializer was not installed.');
    $enterpriseInfrastructureInitialized = true; $automaticEntitlementTrust = true;
}
if ($targetVersion === '1.2.6') {
    $schema = \Illuminate\Support\Facades\Schema::class;
    $assert($schema::hasColumn('users', 'finance_bank_revision'), 'Finance bank revision column was not installed.');
    foreach (['campaign_finance_statement_imports', 'campaign_finance_statement_resolutions',
        'campaign_paystack_settlement_matches', 'campaign_finance_profile_beneficiaries'] as $table) {
        $assert($schema::hasTable($table), 'Missing Finance upgrade table: '.$table);
    }
}
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
    'source' => 'official v'.$sourceVersion.' application ZIP', 'target' => 'signed v'.$targetVersion.' candidate',
    'state' => 'completed', 'obsolete_stage_superseded' => true, 'matching_package_reused' => true, 'signed_bootstrap_engine_used' => true,
    'signatures_verified' => true, 'backup_verified' => true,
    'environment_preserved' => true, 'existing_users_preserved' => true,
    'existing_uploads_preserved' => true, 'custom_role_assignments_preserved' => true, 'custom_parties_preserved' => true,
    'enterprise_capabilities_registered' => $enterpriseCapabilitiesRegistered,
    'enterprise_infrastructure_initialized' => $enterpriseInfrastructureInitialized, 'automatic_entitlement_trust' => $automaticEntitlementTrust,
    'ndc_installed' => true, 'credentials_verified' => true,
    'authorization' => 'isolated entitlement stub; no payment or customer installation used',
];
file_put_contents($report, json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
echo json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";



