<?php

// Run from an extracted official bootstrap bundle: php bootstrap-install.php /path/to/core
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
umask(0077);
try {
    $base = realpath($argv[1] ?? '');
    if (! $base || ! is_file($base.'/.env') || ! is_file($base.'/vendor/autoload.php') || ! is_file($base.'/release-manifest.json')) {
        throw new RuntimeException('Supply the existing installed Core directory, not a new installation.');
    }
    $installed = json_decode(file_get_contents($base.'/release-manifest.json'), true, 64, JSON_THROW_ON_ERROR);
    if (! in_array($installed['version'] ?? '', ['1.0.8', '1.0.9', '1.1.0', '1.1.1', '1.1.2', '1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9', '1.2.0', '1.2.1', '1.2.2', '1.2.3', '1.2.4'], true)) {
        throw new RuntimeException('This bootstrap supports only 1.0.8, 1.0.9, 1.1.0, 1.1.1, 1.1.2, 1.1.3, 1.1.4, 1.1.5, 1.1.6, 1.1.7, 1.1.8, 1.1.9, 1.2.0, 1.2.1, 1.2.2, 1.2.3 and 1.2.4.');
    }
    $bundle = json_decode(file_get_contents(__DIR__.'/bootstrap-manifest.json'), true, 64, JSON_THROW_ON_ERROR);
    $signature = base64_decode($bundle['signature'] ?? '', true);
    unset($bundle['signature']);
    $canonical = function (array $v) use (&$canonical): array {
        foreach ($v as $k => $child) {
            if (is_array($child)) {
                $v[$k] = $canonical($child);
            }
        } ksort($v);

        return $v;
    };
    // This key is embedded by the release builder, not read from an untrusted bundle file.
    $trustedKey = '__CAMPAIGN_BOOTSTRAP_TRUSTED_KEY__';
    if (! $signature || openssl_verify(json_encode($canonical($bundle), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $signature, $trustedKey, OPENSSL_ALGO_SHA256) !== 1) {
        throw new RuntimeException('Official bootstrap signature verification failed.');
    }
    foreach ($bundle['checksums'] as $name => $hash) {
        if (str_starts_with($name, '/') || str_contains($name, '\\') || array_intersect(explode('/', $name), ['.', '..', '']) || ! is_file(__DIR__.'/'.$name) || is_link(__DIR__.'/'.$name) || ! hash_equals($hash, hash_file('sha256', __DIR__.'/'.$name))) {
            throw new RuntimeException('Bootstrap file verification failed.');
        }
    }
    $private = $base.'/storage/app/updates/bootstrap';
    $replacing = is_dir($private) || is_file($base.'/public/campaign-update-bootstrap.php');
    if ($replacing && ! in_array('--replace-bootstrap', $argv, true)) {
        throw new RuntimeException('A bootstrap exists. After confirming no update is active, use --replace-bootstrap to archive and replace it.');
    }
    if (is_link($base.'/storage') || str_starts_with(realpath($base.'/storage'), realpath($base.'/public').DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('Bootstrap storage must be private.');
    }
    if (! is_dir($base.'/storage/app/updates') && ! mkdir($base.'/storage/app/updates', 0700, true)) {
        throw new RuntimeException('Cannot create private update storage.');
    }
    $applyLock = fopen($base.'/storage/app/updates/apply.lock', 'c');
    if (! $applyLock || ! flock($applyLock, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException('An update is active. Finish it before installing the bootstrap.');
    }
    if ($replacing) {
        if (is_file($base.'/storage/framework/down') || is_link($private)) {
            throw new RuntimeException('Cannot replace a bootstrap during maintenance or through a symlink.');
        }
        foreach (glob($base.'/storage/app/updates/jobs/*/journal.json') as $journal) {
            $job = json_decode(file_get_contents($journal), true);
            if (! is_array($job) || ! in_array($job['state'] ?? '', ['completed', 'recovered', 'failed'], true)) {
                throw new RuntimeException('Recover or finish existing update jobs before replacing the bootstrap.');
            }
        }
        $history = $base.'/storage/app/updates/bootstrap-history/'.gmdate('YmdHis').'-'.bin2hex(random_bytes(6));
        if (! mkdir($history, 0700, true)) {
            throw new RuntimeException('Cannot create bootstrap history.');
        }
        if (is_dir($private) && ! rename($private, $history.'/bootstrap')) {
            throw new RuntimeException('Cannot archive the previous bootstrap.');
        }
        foreach (['campaign-update-bootstrap.php', 'campaign-update-status.php'] as $name) {
            if (is_file($base.'/public/'.$name) && ! rename($base.'/public/'.$name, $history.'/'.$name)) {
                throw new RuntimeException('Cannot archive the previous bootstrap entry point.');
            }
        }
    }
    mkdir($private, 0700, true);
    foreach ($bundle['checksums'] as $name => $hash) {
        if (! is_dir(dirname($private.'/'.$name))) {
            mkdir(dirname($private.'/'.$name), 0700, true);
        }
        if (! copy(__DIR__.'/'.$name, $private.'/'.$name)) {
            throw new RuntimeException('Cannot copy verified bootstrap.');
        }
        chmod($private.'/'.$name, 0600);
    }
    copy($private.'/entry.php', $base.'/public/campaign-update-bootstrap.php');
    chmod($base.'/public/campaign-update-bootstrap.php', 0644);
    if (! is_file($base.'/public/campaign-update-status.php')) {
        copy($private.'/status.php', $base.'/public/campaign-update-status.php');
        chmod($base.'/public/campaign-update-status.php', 0644);
    }
    echo "Bootstrap installed. No application files or database records were upgraded. Sign in as Super Admin and open /campaign-update-bootstrap.php.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
}

