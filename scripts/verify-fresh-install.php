<?php
// Isolated synthetic rehearsal. Never use a customer application or database.
[$script, $root, $mode, $report] = $argv;
$temporary = realpath(getenv('RUNNER_TEMP') ?: sys_get_temp_dir());
$root = realpath($root);
if (!$root || !$temporary || !str_starts_with($root, $temporary.DIRECTORY_SEPARATOR) || is_file($root.'/.env') || is_file($root.'/storage/app/installed.lock')) {
    throw new RuntimeException('A fresh isolated application directory is required.');
}
$db = getenv('FRESH_TEST_DATABASE');
$port = getenv('FRESH_TEST_PORT');
$password = getenv('FRESH_TEST_PASSWORD') ?: '';
if (!is_string($port) || !ctype_digit($port) || (int)$port < 1 || (int)$port > 65535) { throw new RuntimeException('An isolated local database service port is required.'); }
if (!is_string($db) || !preg_match('/^campaign_fresh_[a-z0-9_]+$/D', $db)) { throw new RuntimeException('Only a synthetic fresh-install test database is allowed.'); }
$pdo = new PDO('mysql:host=127.0.0.1;port='.getenv('FRESH_TEST_PORT').';dbname='.$db, 'root', $password, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if ($pdo->query('SHOW TABLES')->fetch()) { throw new RuntimeException('The synthetic database must be empty.'); }
require $root.'/vendor/autoload.php';
$request = Illuminate\Http\Request::create('https://candidate.example.test/install/run', 'POST');
$app = require $root.'/bootstrap/app.php';
$app->instance('request', $request);
Illuminate\Support\Facades\Facade::clearResolvedInstance('request');
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\Installer\InstallerService;
use App\Services\InstallationStateService;
use App\Services\DeploymentRegistrationService;
use App\Models\User;
use App\Models\SystemSetting;
config(['app.url'=>'https://candidate.example.test', 'campaign.portal_api_base'=>'https://portal.example.test/api/v2', 'cache.default'=>'file', 'session.driver'=>'file', 'queue.default'=>'sync']);
Http::preventStrayRequests();
$offline = $mode === 'registration-retry';
$identities = [];
Http::fake(function ($request) use (&$offline, &$identities, $mode) {
    if (str_ends_with($request->url(), '/register')) {
        $identities[] = [$request['deployment_id'], hash('sha256', $request['token'])];
        if ($offline) { throw new Illuminate\Http\Client\ConnectionException('Synthetic registration outage'); }
        return Http::response(['registered'=>true, 'contact_verified'=>false, 'receipt'=>['installation_id'=>$request['deployment_id'], 'domain'=>$request['domain'], 'contact_email'=>$request['contact_email'], 'terms_version'=>$request['terms_version'], 'registered_at'=>'2026-10-28T00:00:00Z']], 201);
    }
    if (str_ends_with($request->url(), '/heartbeat')) {
        return Http::response(['accepted'=>true], $mode === 'status-outage' ? 503 : 200);
    }
    throw new RuntimeException('Unexpected HTTP request in manual fresh-install rehearsal.');
});
$password = 'Synthetic-Candidate-Setup-579!';
$state = [
    'environment'=>['APP_NAME'=>'Candidate Campaign', 'APP_URL'=>'https://candidate.example.test', 'APP_ENV'=>'production', 'APP_DEBUG'=>false, 'APP_TIMEZONE'=>'Africa/Lagos', 'DEPLOYMENT_MODE'=>'self_hosted'],
    'database'=>['DB_CONNECTION'=>'mysql', 'DB_HOST'=>'127.0.0.1', 'DB_PORT'=>getenv('FRESH_TEST_PORT'), 'DB_DATABASE'=>$db, 'DB_USERNAME'=>'root', 'DB_PASSWORD'=>getenv('FRESH_TEST_PASSWORD') ?: ''],
    'mail'=>['MAIL_MAILER'=>'log', 'MAIL_FROM_ADDRESS'=>'noreply@example.test', 'MAIL_FROM_NAME'=>'Candidate Campaign'],
    'campaign_scope'=>['package_type'=>'federal_constituency', 'scope_type'=>'federal_constituency', 'scope_name'=>'Selected Federal Constituency'],
    'campaign_geography'=>['scope_name'=>'Calabar Municipal/Odukpani', 'state_name'=>'Cross River', 'region_name'=>'South South', 'country_name'=>'Nigeria', 'portal_state_id'=>10, 'portal_federal_constituency_id'=>201, 'state_code'=>'9', 'federal_constituency_code'=>'9-01', 'federal_constituency_name'=>'Calabar Municipal/Odukpani'],
    'campaign_identity'=>['system_name'=>'Independent House of Representatives Campaign', 'campaign_slogan'=>'Public service'],
    'geography_setup'=>['method'=>'manual'],
    'admin'=>['name'=>'Independent Candidate Administrator', 'email'=>'candidate-admin@example.test', 'password'=>$password],
    'registration'=>['terms_version'=>'2026-10-12', 'accepted_at'=>'2026-10-28T00:00:00Z', 'marketing_opt_in'=>false],
];
$first = app(InstallerService::class)->run($state);
if ($offline) {
    if (($first['stage'] ?? null) !== 'registration' || ($first['ok'] ?? true) || app(InstallationStateService::class)->installed() || $pdo->query('SHOW TABLES')->fetch()) { throw new RuntimeException('Registration outage must leave the database untouched.'); }
    $offline = false;
    $result = app(InstallerService::class)->run($state);
} else { $result = $first; }
if (($result['ok'] ?? false) !== true || !app(InstallationStateService::class)->installed()) {
    fwrite(STDERR, json_encode($result, JSON_PRETTY_PRINT).PHP_EOL);
    fwrite(STDERR, Illuminate\Support\Facades\Artisan::output().PHP_EOL);
    throw new RuntimeException('Fresh installation did not complete.');
}
$admin = User::where('email', 'candidate-admin@example.test')->firstOrFail();
$settings = SystemSetting::firstOrFail();
if (!\Illuminate\Support\Facades\Auth::guard('web')->attempt(['email'=>'candidate-admin@example.test', 'password'=>$password]) || !Hash::check($password, $admin->password) || $admin->access_level !== 'superadmin' || $settings->package !== 'federal_constituency' || !$settings->campaign_federal_constituency_id) { throw new RuntimeException('The fresh federal campaign identity, credentials or scope were not preserved.'); }
if ($mode === 'registration-retry' && ($identities[0] ?? null) !== ($identities[1] ?? null)) { throw new RuntimeException('Registration retry changed the installation identity.'); }
$receipt = app(DeploymentRegistrationService::class)->summary();
if (($receipt['registered'] ?? false) !== true || ($receipt['contact_email'] ?? null) !== 'candidate-admin@example.test') { throw new RuntimeException('Registration receipt does not match this independent customer.'); }
$summary = ['passed'=>true, 'mode'=>$mode, 'fresh_empty_database'=>true, 'real_migrations_and_seeders'=>true, 'package'=>'federal_constituency', 'house_of_representatives_scope'=>true, 'independent_super_admin_login_credentials'=>true, 'registration_required'=>true, 'registration_retry_preserves_identity'=>$mode==='registration-retry', 'status_outage_preserves_installation'=>$mode==='status-outage', 'no_paid_provisioning_or_live_financial_calls'=>true];
file_put_contents($report, json_encode($summary, JSON_PRETTY_PRINT));
echo json_encode($summary).PHP_EOL;
