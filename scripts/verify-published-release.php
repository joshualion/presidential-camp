<?php

// Standalone post-publication verification. No app boot or private credentials.
require __DIR__.'/../app/Services/ReleaseManifestSigner.php';

$version = $argv[1] ?? '';
if (! preg_match('/^\d+\.\d+\.\d+$/D', $version)) {
    throw new RuntimeException('Supply the released semantic version.');
}
$key = file_get_contents(__DIR__.'/../config/release-public.pem');
$manifest = json_decode(file_get_contents('release-manifest.json'), true, 64, JSON_THROW_ON_ERROR);
$signer = new App\Services\ReleaseManifestSigner;
if ($signer->verify($manifest, $key) !== true || $manifest['version'] !== $version
    || ! hash_equals($manifest['checksum_sha256'], hash_file('sha256', 'campaign-manager.zip'))
    || $manifest['size_bytes'] !== filesize('campaign-manager.zip')
    || ! in_array('1.1.3', $manifest['upgrade']['supported_sources'], true)
    || ! in_array('1.1.4', $manifest['upgrade']['supported_sources'], true)
    || ! in_array('1.1.5', $manifest['upgrade']['supported_sources'], true)
    || (version_compare($version, '1.1.7', '>=') && ! in_array('1.1.6', $manifest['upgrade']['supported_sources'], true))) {
    throw new RuntimeException('Published application metadata or signature verification failed.');
}
if (version_compare($version, '1.2.0', '>=')) {
    foreach (['1.1.7', '1.1.8', '1.1.9'] as $source) {
        if (! in_array($source, $manifest['upgrade']['supported_sources'], true)) {
            throw new RuntimeException('Published manifest lacks a reviewed upgrade source.');
        }
    }
}
$release = json_decode(file_get_contents('github-release.json'), true, 64, JSON_THROW_ON_ERROR);
if ($release['tag_name'] !== 'v'.$version || $release['draft'] || $release['prerelease']) {
    throw new RuntimeException('The published release is not stable.');
}
foreach ($release['assets'] as $asset) {
    if ($asset['state'] !== 'uploaded' || ! is_file($asset['name'])
        || $asset['size'] !== filesize($asset['name'])
        || $asset['digest'] !== 'sha256:'.hash_file('sha256', $asset['name'])) {
        throw new RuntimeException('Public release asset does not match its uploaded digest.');
    }
}
$bootstrap = new ZipArchive;
if ($bootstrap->open('campaign-manager-updater-bootstrap.zip') !== true) {
    throw new RuntimeException('Cannot open the published bootstrap.');
}
$bundle = json_decode($bootstrap->getFromName('bootstrap-manifest.json'), true, 64, JSON_THROW_ON_ERROR);
$signature = base64_decode($bundle['signature'], true);
unset($bundle['signature']);
if ($bundle['version'] !== $version || ! $signature
    || openssl_verify($signer->canonicalPayload($bundle), $signature, $key, OPENSSL_ALGO_SHA256) !== 1) {
    throw new RuntimeException('Published bootstrap signature verification failed.');
}
foreach ($bundle['checksums'] as $name => $hash) {
    $contents = $bootstrap->getFromName($name);
    if ($contents === false || ! hash_equals($hash, hash('sha256', $contents))) {
        throw new RuntimeException('Published bootstrap file verification failed: '.$name);
    }
}
$bootstrap->close();
echo 'Published '.$version.": public downloads, signatures, checksums, bootstrap file inventory and reviewed source versions verified.\n";
