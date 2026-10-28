<?php

namespace App\Support;

/** Shared by the web installer and the pre-bootstrap, CLI-only hosting check. */
class InstallationRuntime
{
    public static function requiredExtensions(): array
    {
        return ['bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'gd', 'hash', 'iconv', 'json', 'libxml', 'mbstring', 'openssl', 'pcre', 'pdo', 'pdo_mysql', 'session', 'simplexml', 'tokenizer', 'xml', 'xmlreader', 'xmlwriter', 'zip', 'zlib'];
    }

    public static function missingExtensions(?callable $loaded = null): array
    {
        $loaded ??= extension_loaded(...);

        return array_values(array_filter(self::requiredExtensions(), static fn (string $extension) => !$loaded($extension)));
    }
}
