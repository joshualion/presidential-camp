<?php

// CLI only. Does not bootstrap Laravel, connect to a database, modify files or contact Portal.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__, 2);
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    echo 'Campaign Manager requires PHP 8.2 or newer. This CLI uses PHP '.PHP_VERSION.'. Select the correct PHP binary for this domain.'.PHP_EOL;
    exit(1);
}
require $root.'/app/Support/InstallationRuntime.php';
$checks = [];
$add = static function (string $name, bool $passed, bool $required = true) use (&$checks): void {
    $checks[] = ['check' => $name, 'status' => $passed ? 'pass' : ($required ? 'fail' : 'warning')];
};
$add('PHP 8.2 or newer', version_compare(PHP_VERSION, '8.2.0', '>='));
foreach (\App\Support\InstallationRuntime::requiredExtensions() as $extension) {
    $add('PHP extension: '.$extension, extension_loaded($extension));
}
foreach (['artisan', 'vendor/autoload.php', 'public/index.php', 'public/build/manifest.json', 'config/release-public.pem'] as $file) {
    $add('Package file: '.$file, is_file($root.'/'.$file) && is_readable($root.'/'.$file));
}
foreach (['storage', 'storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'] as $directory) {
    $add('Writable: '.$directory, is_dir($root.'/'.$directory) && is_writable($root.'/'.$directory));
}
$env = $root.'/.env';
$installed = is_file($root.'/storage/app/installed.lock');
$add('Environment file writable or creatable', is_file($env) ? is_writable($env) : is_writable($root));
$contents = is_readable($env) ? file_get_contents($env) : '';
$hasKey = preg_match('/^APP_KEY\s*=\s*[\'\"]?[^\s\'\"\r\n]+/m', $contents) === 1;
$add('Application key configured'.($installed ? ' (existing installation)' : ' (generated on first installer visit)'), $hasKey, $installed);
$add('No stale configuration cache', !is_file($root.'/bootstrap/cache/config.php'), false);
$manifest = is_readable($root.'/release-manifest.json') ? json_decode(file_get_contents($root.'/release-manifest.json'), true) : [];
$report = ['passed' => !in_array('fail', array_column($checks, 'status'), true), 'php' => PHP_VERSION, 'sapi' => PHP_SAPI, 'release' => $manifest['version'] ?? 'source checkout', 'installed_lock_present' => $installed, 'checks' => $checks,
    'notes' => ['Checks are read-only. No credentials, environment values or setup tokens are printed.', 'CLI PHP and website PHP may load different extensions. Check the same PHP version in the web installer.', 'The domain document root should point to this application\'s public directory. Do not change PHP for unrelated domains.', 'Preserve APP_KEY, .env, registration, uploads and the database. Do not remove lock files or restart an existing installation to repair it.']];
if (in_array('--json', $argv, true)) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
} else {
    echo 'Campaign Manager installation check — PHP '.$report['php'].' / '.$report['sapi'].PHP_EOL;
    foreach ($checks as $check) { echo strtoupper($check['status']).' '.$check['check'].PHP_EOL; }
    foreach ($report['notes'] as $note) { echo $note.PHP_EOL; }
}
exit($report['passed'] ? 0 : 1);
