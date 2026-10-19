<?php

// Loaded after the application boots and before the release signing key is removed.
function buildCampaignUpdaterBootstrap(string $output, string $privateKey, string $publicKey, string $version): string
{
    $files = [];
    foreach (glob(base_path('app/Services/Updates/*.php')) as $path) {
        $files['engine/'.basename($path)] = file_get_contents($path);
    }
    foreach (['OfficialUpdateService', 'ReleaseManifestSigner', 'ReleaseSigningKeyService', 'DeploymentRegistrationService'] as $name) {
        $files['services/'.$name.'.php'] = file_get_contents(base_path('app/Services/'.$name.'.php'));
    }
    foreach (['entry.php', 'bridge.php', 'screen.blade.php', 'recover.php'] as $name) {
        $files[$name] = file_get_contents(__DIR__.'/'.$name);
    }
    $files['updates.php'] = file_get_contents(config_path('updates.php'));
    $files['status.php'] = file_get_contents(public_path('campaign-update-status.php'));
    $files['trusted.pem'] = $publicKey;
    $manifest = ['product' => 'Campaign Manager updater bootstrap', 'version' => $version, 'checksums' => []];
    foreach ($files as $name => $content) {
        $manifest['checksums'][$name] = hash('sha256', $content);
    }
    $canonical = function (array $v) use (&$canonical): array {
        foreach ($v as $k => $child) {
            if (is_array($child)) {
                $v[$k] = $canonical($child);
            }
        }ksort($v);

        return $v;
    };
    if (! openssl_sign(json_encode($canonical($manifest), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Cannot sign the bootstrap.');
    }
    $manifest['signature'] = base64_encode($signature);
    $files['bootstrap-manifest.json'] = json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    $installer = file_get_contents(__DIR__.'/bootstrap-install.php');
    $files['bootstrap-install.php'] = str_replace("'__CAMPAIGN_BOOTSTRAP_TRUSTED_KEY__'", var_export($publicKey, true), $installer);
    $files['README.txt'] = "Campaign Manager legacy updater\n\nFor existing 1.0.8, 1.0.9, 1.1.0 and 1.1.1 installations only.\nExtract this official bundle OUTSIDE the web document root.\nRun: php bootstrap-install.php /absolute/path/to/existing/core\nSign in as Super Admin and open /campaign-update-bootstrap.php.\nThe bootstrap does not replace Core or run migrations. The paid update performs compatibility and backup checks first.\nBack up your installation before any maintenance. On hosts without PHP CLI or process execution, use the free manual update guide.\nAfter success, remove public/campaign-update-bootstrap.php and storage/app/updates/bootstrap. Keep private update backups securely until you have verified the installation.\n";
    $path = $output.'/campaign-manager-updater-bootstrap.zip';
    $zip = new ZipArchive;
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Cannot create the bootstrap archive.');
    }
    foreach ($files as $name => $content) {
        if (! $zip->addFromString($name, $content)) {
            throw new RuntimeException('Cannot add bootstrap file.');
        }
    }
    $zip->close();
    file_put_contents($path.'.sha256', hash_file('sha256', $path).'  '.basename($path)."\n");

    return $path;
}
