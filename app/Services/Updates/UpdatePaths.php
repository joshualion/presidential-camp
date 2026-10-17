<?php

namespace App\Services\Updates;

use App\Services\Updates\UpdateException as RuntimeException;

class UpdatePaths
{
    public static function root(): string
    {
        $root = storage_path('app/updates');
        if (! is_dir($root) && ! mkdir($root, 0700, true) && ! is_dir($root)) {
            throw new RuntimeException('Cannot create private updater storage.');
        }
        $real = realpath($root);
        $public = realpath(public_path());
        if (! $real || ($public && ($real === $public || str_starts_with($real, $public.DIRECTORY_SEPARATOR)))) {
            throw new RuntimeException('Updater storage must be outside the public directory.');
        }
        $documentRoot = PHP_SAPI !== 'cli' && ! empty($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
        if ($documentRoot && ($real === $documentRoot || str_starts_with($real, $documentRoot.DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('Private update storage is inside the web document root. Correct the hosting document root before automatic updating.');
        }
        chmod($real, 0700);

        return $real;
    }

    public static function job(string $id): string
    {
        if (! preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/D', $id)) {
            throw new RuntimeException('Invalid updater job.');
        }

        return self::root().'/jobs/'.$id;
    }

    public static function stage(string $id): string
    {
        self::job($id);

        return self::root().'/staged/'.$id;
    }

    public static function write(string $path, array $value): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        $tmp = $path.'.'.bin2hex(random_bytes(6)).'.tmp';
        if (file_put_contents($tmp, json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), LOCK_EX) === false || ! chmod($tmp, 0600) || ! rename($tmp, $path)) {
            throw new RuntimeException('Cannot persist updater progress.');
        }
    }

    public static function managed(string $name): bool
    {
        if ($name === '' || str_starts_with($name, '/') || str_contains($name, '\\') || preg_match('/[\x00-\x1f\x7f:]/', $name) || array_intersect(explode('/', $name), ['.', '..', ''])) {
            return false;
        }
        if (str_starts_with($name, '.env') || str_starts_with($name, 'storage/') || str_starts_with($name, 'bootstrap/cache/') || str_starts_with($name, 'public/uploads/') || $name === 'public/storage' || str_starts_with($name, 'public/storage/') || basename($name) === '.htaccess' || str_contains($name, '/.well-known/')) {
            return false;
        }

        return preg_match('#^(app|bootstrap|config|database|resources|routes|vendor|public|scripts|tools)/#D', $name) === 1 || in_array($name, ['artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'release-manifest.json', 'checksums.txt', 'LICENSE.txt', 'EULA.txt', 'campaign-update-bootstrap.php'], true);
    }

    public static function destination(string $name): string
    {
        if (! self::managed($name)) {
            throw new RuntimeException('Protected or unsupported application path.');
        }
        $base = realpath(base_path());
        $path = $base;
        foreach (explode('/', $name) as $part) {
            $path .= '/'.$part;
            if (is_link($path)) {
                throw new RuntimeException('Managed application paths cannot be symbolic links.');
            }
        }

        return $path;
    }
}
