<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class ReleaseVersionState
{
    public function current(): string
    {
        $stateVersion = $this->stateVersion();

        if ($stateVersion !== null) {
            return $stateVersion;
        }

        $configured = config('release.github.last_released_version')
            ?: config('release.identity.version', config('app.version', '0.0.0'));

        return $this->normalize((string) $configured);
    }

    public function persist(string $version, string $repository, string $tag, string $asset): void
    {
        $version = $this->normalize($version);

        File::ensureDirectoryExists(dirname($this->path()));
        File::put($this->path(), json_encode([
            'last_released_version' => $version,
            'repository' => $repository,
            'tag' => $tag,
            'asset' => $asset,
            'published_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    public function path(): string
    {
        $path = (string) config('release.github.version_state_path', 'release-state.json');

        if (preg_match('/^[A-Za-z]:[\/\\\\]/', $path) || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function stateVersion(): ?string
    {
        if (! File::isFile($this->path())) {
            return null;
        }

        $state = json_decode((string) File::get($this->path()), true);

        if (! is_array($state) || empty($state['last_released_version'])) {
            return null;
        }

        return $this->normalize((string) $state['last_released_version']);
    }

    private function normalize(string $version): string
    {
        $version = ltrim(trim($version), 'vV');

        if (! preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $matches)) {
            throw new RuntimeException('Release version must use semantic version format, for example v1.0.6.');
        }

        return ((int) $matches[1]).'.'.((int) $matches[2]).'.'.((int) $matches[3]);
    }
}
