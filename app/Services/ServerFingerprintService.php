<?php

namespace App\Services;

class ServerFingerprintService
{
    public function make(): string
    {
        $parts = [
            parse_url((string) config('app.url'), PHP_URL_HOST),
            base_path(),
            hash('sha256', (string) config('app.key')),
            PHP_VERSION,
            php_uname('s'),
        ];

        return hash('sha256', implode('|', array_filter($parts)));
    }
}
