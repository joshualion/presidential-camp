<?php

namespace App\Services\Installer;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class InstallerStateStore
{
    public function path(): string
    {
        return storage_path('app/installer/state.json');
    }

    public function all(): array
    {
        if (!File::exists($this->path())) {
            return [];
        }

        $decoded = json_decode((string) File::get($this->path()), true);

        return is_array($decoded) ? $decoded : [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    public function put(string $key, mixed $value): void
    {
        $state = $this->all();
        Arr::set($state, $key, $this->sanitize($key, $value));
        $this->write($state);
    }

    public function merge(array $values): void
    {
        $state = array_replace_recursive($this->all(), $this->sanitizeArray($values));
        $this->write($state);
    }

    public function markCompleted(string $step): void
    {
        $completed = $this->get('completed_steps', []);
        $completed[] = $step;

        $this->merge([
            'completed_steps' => array_values(array_unique($completed)),
            'current_step' => $step,
        ]);
    }

    public function clear(): void
    {
        if (File::exists($this->path())) {
            File::delete($this->path());
        }
    }

    private function write(array $state): void
    {
        File::ensureDirectoryExists(dirname($this->path()));
        File::replace($this->path(), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), 0600);
    }

    private function sanitizeArray(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                unset($values[$key]);
                continue;
            }

            $values[$key] = $this->sanitize((string) $key, $value);
        }

        return $values;
    }

    private function sanitize(string $key, mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->sanitizeArray($value);
        }

        if ($this->isSensitiveKey($key)) {
            return null;
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        return in_array(strtoupper($key), ['DB_PASSWORD', 'MAIL_PASSWORD', 'PASSWORD', 'PASSWORD_CONFIRMATION'], true);
    }
}

