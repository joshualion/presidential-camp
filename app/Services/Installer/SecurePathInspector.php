<?php

namespace App\Services\Installer;

class SecurePathInspector
{
    public function inspect(): array
    {
        $public = realpath(public_path()) ?: public_path();
        $base = realpath(base_path()) ?: base_path();
        $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: null;
        $warnings = [];

        if ($documentRoot && $this->samePath($documentRoot, $base)) {
            $warnings[] = 'The Laravel core appears to be the document root. For best security, place the Laravel core application outside the public web root and expose only the public directory.';
        } elseif ($documentRoot && str_starts_with(strtolower($base), strtolower($documentRoot))) {
            $warnings[] = 'The Laravel core appears to be inside the public web root. For best security, place the Laravel core application outside the public web root and expose only the public directory.';
        }

        foreach (['.env', 'artisan', 'composer.json', 'app', 'vendor', 'storage'] as $target) {
            if ($documentRoot && file_exists($documentRoot.DIRECTORY_SEPARATOR.$target) && !$this->samePath($public, $documentRoot)) {
                $warnings[] = "Possible public exposure detected for {$target}.";
            }
        }

        return array_values(array_unique($warnings));
    }

    private function samePath(string $a, string $b): bool
    {
        return rtrim(strtolower($a), '\\/') === rtrim(strtolower($b), '\\/');
    }
}
