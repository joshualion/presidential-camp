<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallationStateService
{
    public function lockPath(): string
    {
        return storage_path('app/installed.lock');
    }

    public function installed(): bool
    {
        return File::exists($this->lockPath());
    }

    public function metadata(): array
    {
        if (!$this->installed()) {
            return [];
        }

        $decoded = json_decode((string) File::get($this->lockPath()), true);

        return is_array($decoded) ? $decoded : [];
    }

    public function create(array $metadata): void
    {
        File::ensureDirectoryExists(dirname($this->lockPath()));

        $metadata = array_merge([
            'installed_at' => now()->toIso8601String(),
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'app_version' => $this->appVersion(),
            'deployment_mode' => config('campaign.deployment_mode'),
        ], $metadata);

        File::put($this->lockPath(), json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function remove(): bool
    {
        if (!$this->installed()) {
            return false;
        }

        File::delete($this->lockPath());

        return true;
    }

    public function maskLicenseKey(?string $licenseKey): ?string
    {
        if (!$licenseKey) {
            return null;
        }

        return Str::mask($licenseKey, '*', 4, max(strlen($licenseKey) - 8, 0));
    }

    private function appVersion(): ?string
    {
        return config('app.version') ?? env('APP_VERSION');
    }
}
