<?php

// Private artifact preparation only. This script never publishes or creates tags.
require __DIR__.'/../vendor/autoload.php';

$version = getenv('CANDIDATE_VERSION');
if (!is_string($version) || !preg_match('/^\d+\.\d+\.\d+$/D', $version)) {
    throw new RuntimeException('An explicitly approved semantic version is required.');
}
$installed = json_decode(file_get_contents(__DIR__.'/../vendor/composer/installed.json'), true, 512, JSON_THROW_ON_ERROR);
if (($installed['dev'] ?? true) !== false) {
    throw new RuntimeException('Candidate packaging requires composer install --no-dev.');
}
$key = getenv('CAMPAIGN_RELEASE_PRIVATE_KEY');
$keyPath = getenv('CAMPAIGN_RELEASE_PRIVATE_KEY_PATH');
if (!$key || !$keyPath || !is_file(__DIR__.'/../config/release-public.pem')) {
    throw new RuntimeException('Owner-managed private signing key and committed trusted public key are required.');
}
$private = openssl_pkey_get_private($key);
$public = openssl_pkey_get_public(file_get_contents(__DIR__.'/../config/release-public.pem'));
if (!$private || !$public || openssl_pkey_get_details($private)['type'] !== OPENSSL_KEYTYPE_RSA || openssl_pkey_get_details($private)['bits'] < 2048 || !hash_equals(openssl_pkey_get_details($public)['key'], openssl_pkey_get_details($private)['key'])) {
    throw new RuntimeException('Signing material does not match the established trusted public key.');
}
if (file_put_contents($keyPath, $key, LOCK_EX) === false || !chmod($keyPath, 0600)) {
    throw new RuntimeException('Private signing material could not be secured.');
}
$failure = null;
try {
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $result = $app->make(App\Services\ReleaseBuildService::class)->build([
        'profile' => 'standard', 'version' => $version, 'channel' => 'stable',
        'zip' => true, 'output' => base_path('builds/candidate'),
    ]);
    // Updater looks for this exact public asset name. The builder's versioned report is retained locally.
    if (!copy($result['manifest_path'], base_path('builds/candidate/release-manifest.json'))) {
        throw new RuntimeException('Canonical updater manifest could not be written.');
    }
    $metadata = file_get_contents(base_path('builds/candidate/release-manifest.json'));
    if (strlen($metadata) > 1048576) {
        throw new RuntimeException('Release metadata exceeds the legacy updater 1 MiB limit. Publication stopped.');
    }
    $app->make(App\Services\OfficialUpdateService::class)->validateManifest(
        json_decode($metadata, true, 64, JSON_THROW_ON_ERROR), $version
    );
    file_put_contents(base_path('builds/candidate/campaign-manager.zip.sha256'), $result['checksum_sha256']."  campaign-manager.zip\n");
    require base_path('tools/updater/build-bootstrap.php');
    buildCampaignUpdaterBootstrap(base_path('builds/candidate'), $key, file_get_contents(config_path('release-public.pem')), $version);
    echo "Signed private candidate prepared. No GitHub release has been published.\n";
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    if (is_file($keyPath)) {
        unlink($keyPath);
    }
}


if ($failure) {
    fwrite(STDERR, 'Release preparation failed: '.$failure->getMessage().PHP_EOL);
    exit(1);
}
