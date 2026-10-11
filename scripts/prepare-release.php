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
if (file_put_contents($keyPath, $key, LOCK_EX) === false || !chmod($keyPath, 0600)) {
    throw new RuntimeException('Private signing material could not be secured.');
}
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
    file_put_contents(base_path('builds/candidate/campaign-manager.zip.sha256'), $result['checksum_sha256']."  campaign-manager.zip\n");
    echo "Signed private candidate prepared. No GitHub release has been published.\n";
} finally {
    if (is_file($keyPath)) {
        unlink($keyPath);
    }
}
