<?php

use App\Services\ReleaseManifestSigner;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Disposable release gate. Never accepts an existing installation or a production path.
[$script, $root, $archive, $report] = $argv;
$root = realpath($root);
$temporary = realpath(getenv('RUNNER_TEMP') ?: sys_get_temp_dir());
if (! $root || ! $temporary || ! str_starts_with($root, $temporary.DIRECTORY_SEPARATOR) || is_file($root.'/.env')) {
    throw new RuntimeException('A fresh extracted official release in temporary storage is required.');
}
$assert = static function (bool $condition, string $message): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};
$environment = [
    'APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_URL' => 'https://customer.example',
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)), 'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => $root.'/database/bootstrap-http.sqlite', 'SESSION_DRIVER' => 'file',
    'SESSION_SECURE_COOKIE' => 'true', 'CACHE_STORE' => 'file', 'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'log', 'CAMPAIGN_DEPLOYMENT_MODE' => 'self_hosted',
];
foreach ($environment as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
file_put_contents($root.'/.env', implode("\n", array_map(fn ($k, $v) => $k.'='.$v, array_keys($environment), $environment))."\n");
touch($environment['DB_DATABASE']);
$bundle = $root.'-http-bootstrap';
mkdir($bundle, 0700);
$zip = new ZipArchive;
$assert($zip->open($archive) === true && $zip->extractTo($bundle), 'Cannot extract bootstrap.');
$zip->close();
$runInstaller = static function (array $options = []) use ($bundle, $root): int {
    $process = proc_open([PHP_BINARY, $bundle.'/bootstrap-install.php', $root, ...$options], [0 => ['file', '/dev/null', 'r'], 1 => ['file', $root.'/storage/logs/bootstrap-install.log', 'a'], 2 => ['file', $root.'/storage/logs/bootstrap-install.log', 'a']], $pipes);

    return is_resource($process) ? proc_close($process) : -1;
};
$assert($runInstaller() === 0, 'Actual signed bootstrap installer failed.');
$assert($runInstaller() !== 0, 'Existing bootstrap was overwritten without explicit replacement.');
$assert($runInstaller(['--replace-bootstrap']) === 0, 'Safe explicit bootstrap replacement failed.');
chdir($root);
require $root.'/vendor/autoload.php';
foreach (glob($root.'/storage/app/updates/bootstrap/services/*.php') as $file) {
    require_once $file;
}
foreach (glob($root.'/storage/app/updates/bootstrap/engine/*.php') as $file) {
    require_once $file;
}
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, (string) $error."\n");
    exit(1);
});
$manifest = json_decode(file_get_contents($root.'/release-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$assert(app(ReleaseManifestSigner::class)->verify($manifest, file_get_contents($root.'/config/release-public.pem')) === true, 'Official source signature failed.');
$assert(Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]) === 0, 'Disposable schema initialization failed.');
DB::table('system_settings')->insert(['package' => 'presidential', 'system_name' => 'Bootstrap HTTP acceptance']);
$cookies = [];
$tokens = [];
foreach (['superadmin', 'puadmin'] as $role) {
    $id = DB::table('users')->insertGetId([
        'uuid' => (string) Str::uuid(), 'username' => 'bootstrap-'.$role,
        'firstname' => 'Bootstrap', 'lastname' => 'Fixture', 'email' => $role.'@customer.example',
        'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT), 'status' => 'active', 'access_level' => $role,
    ]);
    $session = app('session')->driver();
    $session->setId(Str::random(40));
    $session->start();
    $tokens[$role] = Str::random(40);
    $session->put(app('auth')->guard('web')->getName(), $id);
    $session->put('_token', $tokens[$role]);
    $session->save();
    $name = config('session.cookie');
    $value = CookieValuePrefix::create($name, app('encrypter')->getKey()).$session->getId();
    $cookies[$role] = $name.'='.rawurlencode(app('encrypter')->encrypt($value, false));
}
file_put_contents($root.'/storage/app/installed.lock', json_encode(['deployment_mode' => 'self_hosted', 'installation_mode' => 'community', 'remote_validation_required' => false, 'installed_at' => date(DATE_ATOM)]));
$before = ['env' => hash_file('sha256', $root.'/.env'), 'version' => hash_file('sha256', $root.'/release-manifest.json')];
$router = $root.'-http-router.php';
// Emulate an HTTPS reverse proxy with a symlinked document root. Only public files are served.
$documentRoot = $root.'-public';
symlink($root.'/public', $documentRoot);
file_put_contents($router, '<?php $public='.var_export($documentRoot, true).'; '.<<<'PHP'
$_SERVER['HTTPS']='on'; $_SERVER['SERVER_PORT']=443; $_SERVER['HTTP_HOST']='customer.example';
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); $mount=str_starts_with($path,'/campaign/')?'/campaign':'';
$relative=substr($path,strlen($mount)); $file=realpath($public.$relative); $base=realpath($public);
if($relative!=='/' && $file && str_starts_with($file,$base.'/') && is_file($file) && pathinfo($file,PATHINFO_EXTENSION)!=='php'){header('Content-Type: '.(str_ends_with($file,'.css')?'text/css':'application/javascript'));readfile($file);return;}
$_SERVER['SCRIPT_NAME']=$mount.'/index.php';$_SERVER['PHP_SELF']=$_SERVER['SCRIPT_NAME'];$_SERVER['SCRIPT_FILENAME']=$public.'/index.php';
if($relative==='/campaign-update-bootstrap.php'){$_SERVER['SCRIPT_NAME']=$mount.'/public/campaign-update-bootstrap.php';$_SERVER['PHP_SELF']=$_SERVER['SCRIPT_NAME'];$_SERVER['SCRIPT_FILENAME']=$public.'/campaign-update-bootstrap.php';require $public.'/campaign-update-bootstrap.php';return;}
require $public.'/index.php';
PHP);
$listener = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
$assert($listener !== false, 'Cannot allocate HTTP test port.');
$address = stream_socket_get_name($listener, false);
fclose($listener);
$server = proc_open([PHP_BINARY, '-S', $address, $router], [0 => ['file', '/dev/null', 'r'], 1 => ['file', $root.'/storage/logs/bootstrap-http.log', 'a'], 2 => ['file', $root.'/storage/logs/bootstrap-http.log', 'a']], $pipes, $root);
$assert(is_resource($server), 'Cannot start isolated HTTP server.');
$request = static function (string $path, ?string $cookie = null, ?array $post = null) use ($address): array {
    $curl = curl_init('http://'.$address.$path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15, CURLOPT_PROXY => '', CURLOPT_HTTPHEADER => $cookie ? ['Cookie: '.$cookie] : []]);
    if ($post !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    return [$status, (string) $response];
};
try {
    for ($i = 0; $i < 50; $i++) {
        if (@fsockopen(explode(':', $address)[0], (int) explode(':', $address)[1], $errno, $error, 0.1)) {
            break;
        }
        usleep(100000);
    }
    foreach (['', '/campaign'] as $mount) {
        [$status, $body] = $request($mount.'/campaign-update-bootstrap.php');
        $assert($status === 302 && stripos($body, 'Location: https://customer.example'.$mount.'/login') !== false, 'Guest login redirect is malformed: '.$status);
        [$status, $body] = $request($mount.'/login');
        $assert($status === 200 && !str_contains($body, 'campaign-update-bootstrap.php/assets'), 'Normal login page failed: '.$status);
        [$status, $body] = $request($mount.'/campaign-update-bootstrap.php', $cookies['superadmin']);
        $assert($status === 200 && str_contains($body, 'Official Updates'), 'Authenticated bootstrap failed: '.$status);
        $assert(str_contains($body, 'https://customer.example'.$mount.'/superadmin/dashboard'), 'Dashboard URL is malformed.');
        $assert(! str_contains($body, 'campaign-update-bootstrap.php/login') && ! str_contains($body, 'campaign-update-bootstrap.php/assets'), 'Secondary entry leaked into generated URLs.');
        [$status] = $request($mount.'/campaign-update-bootstrap.php', $cookies['puadmin']);
        $assert($status === 403, 'Polling-unit administrator was allowed to execute bootstrap.');
        [$status] = $request($mount.'/campaign-update-bootstrap.php', $cookies['superadmin'], ['action' => 'unknown']);
        $assert($status === 419, 'Missing CSRF was accepted.');
        [$status] = $request($mount.'/campaign-update-bootstrap.php', $cookies['superadmin'], ['action' => 'unknown', '_token' => $tokens['superadmin']]);
        $assert($status === 422, 'Valid session/CSRF did not reach action validation.');
        foreach (['/assets/dist/css/adminlte.min.css', '/assets/plugins/jquery/jquery.min.js'] as $asset) {
            [$status] = $request($mount.$asset);
            $assert($status === 200, 'Public asset failed: '.$asset);
        }
        [$status] = $request($mount.'/storage/app/updates/bootstrap/trusted.pem');
        $assert($status !== 200, 'Private bootstrap storage was exposed.');
    }
    $assert($before['env'] === hash_file('sha256', $root.'/.env') && $before['version'] === hash_file('sha256', $root.'/release-manifest.json'), 'Browsing bootstrap changed installation configuration/version.');
    $assert(! glob($root.'/storage/app/updates/staged/*'), 'Browsing bootstrap downloaded a package.');
    file_put_contents($report, json_encode(['source_version' => $manifest['version'], 'passed' => true, 'signed_source_verified' => true, 'actual_packaged_bootstrap' => true, 'root_and_subdirectory' => true, 'symlinked_document_root' => true, 'authentication_csrf_session_assets' => true, 'existing_bootstrap_replacement' => true, 'no_upgrade_on_get' => true], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo 'Packaged bootstrap HTTP checks passed for '.$manifest['version']."\n";
} finally {
    proc_terminate($server);
    proc_close($server);
}
