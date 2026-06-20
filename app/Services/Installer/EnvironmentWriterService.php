<?php

namespace App\Services\Installer;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class EnvironmentWriterService
{
    public function read(array $keys): array
    {
        $path = base_path('.env');

        if (!File::exists($path) || !File::isReadable($path)) {
            return [];
        }

        $values = [];
        $lines = preg_split('/\R/', (string) File::get($path));

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);

            if (in_array($key, $keys, true)) {
                $values[$key] = $this->decode($value);
            }
        }

        return $values;
    }

    public function write(array $values): void
    {
        $path = base_path('.env');

        if (!File::exists($path)) {
            if (File::exists(base_path('.env.example'))) {
                File::copy(base_path('.env.example'), $path);
            } else {
                File::put($path, '');
            }
        }

        if (!is_writable($path)) {
            throw new RuntimeException('.env is not writable.');
        }

        if (empty($values['APP_KEY'])) {
            $values['APP_KEY'] = config('app.key') ?: 'base64:'.base64_encode(random_bytes(32));
        }

        $contents = File::get($path);

        foreach ($values as $key => $value) {
            $contents = $this->setValue($contents, $key, $value);
        }

        File::put($path, $contents);
        Artisan::call('config:clear');
    }

    private function setValue(string $contents, string $key, mixed $value): string
    {
        $encoded = $this->encode($value);
        $line = $key.'='.$encoded;

        if (preg_match('/^'.preg_quote($key, '/').'=.*/m', $contents)) {
            return preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $contents);
        }

        return rtrim($contents).PHP_EOL.$line.PHP_EOL;
    }

    private function encode(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        if ($value === '' || preg_match('/\s|#|"|\'/', $value)) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }

    private function decode(string $value): string
    {
        $value = trim($value);

        if (
            strlen($value) >= 2
            && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        return str_replace('\"', '"', $value);
    }
}
