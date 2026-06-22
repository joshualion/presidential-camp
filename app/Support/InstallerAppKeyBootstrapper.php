<?php

namespace App\Support;

use RuntimeException;

class InstallerAppKeyBootstrapper
{
    public static function bootstrap(string $basePath): void
    {
        $envPath = $basePath.DIRECTORY_SEPARATOR.'.env';
        $examplePath = $basePath.DIRECTORY_SEPARATOR.'.env.example';
        $lockPath = $basePath.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'installed.lock';

        if (!is_file($envPath) && is_file($examplePath) && self::envValue(file_get_contents($examplePath) ?: '', 'DEPLOYMENT_MODE') === 'self_hosted') {
            if (!is_writable($basePath)) {
                throw new RuntimeException('Campaign Manager installer cannot create .env. Copy .env.example to .env and make it writable, then reload the installer.');
            }

            copy($examplePath, $envPath);
        }

        if (!is_file($envPath)) {
            return;
        }

        $contents = file_get_contents($envPath);
        if ($contents === false) {
            throw new RuntimeException('Campaign Manager installer cannot read .env. Check file permissions and reload the installer.');
        }

        if (is_file($lockPath)
            || self::envValue($contents, 'DEPLOYMENT_MODE') !== 'self_hosted'
            || !filter_var(self::envValue($contents, 'INSTALLER_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN)
            || self::envValue($contents, 'APP_KEY') !== ''
        ) {
            return;
        }

        if (!is_writable($envPath)) {
            throw new RuntimeException('Campaign Manager installer cannot generate APP_KEY because .env is not writable. Make .env writable or run php artisan key:generate, then reload the installer.');
        }

        $key = 'base64:'.base64_encode(random_bytes(32));
        $updated = preg_match('/^APP_KEY=.*$/m', $contents)
            ? preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents, 1)
            : rtrim($contents).PHP_EOL.'APP_KEY='.$key.PHP_EOL;

        file_put_contents($envPath, $updated, LOCK_EX);
        putenv('APP_KEY='.$key);
        $_ENV['APP_KEY'] = $key;
        $_SERVER['APP_KEY'] = $key;
    }

    private static function envValue(string $contents, string $key): string
    {
        if (!preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches)) {
            return '';
        }

        return trim(trim($matches[1]), "\"'");
    }
}
