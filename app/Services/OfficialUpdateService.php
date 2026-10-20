<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/** Public update discovery is independent of software activation and registration. */
class OfficialUpdateService
{
    public function __construct(private ReleaseManifestSigner $signer)
    {
    }

    public function installedVersion(): string
    {
        $manifest = File::isFile(base_path('release-manifest.json'))
            ? json_decode(File::get(base_path('release-manifest.json')), true) : [];

        return ltrim((string) ($manifest['version'] ?? config('release.identity.version', '0.0.0')), 'vV');
    }

    public function status(): array
    {
        $path = storage_path('app/updates/check.json');

        $status = File::isFile($path) ? (json_decode(File::get($path), true) ?: []) : [];
        if (isset($status['latest_version'])) {
            $status['installed_version'] = $this->installedVersion();
            $status['available'] = version_compare($status['latest_version'], $status['installed_version'], '>');
        }

        return $status;
    }

    public function check(): array
    {
        if (!config('updates.enabled')) {
            throw new RuntimeException('Update checks are disabled by the administrator.');
        }

        $repository = config('updates.repository');
        $release = Http::acceptJson()->withHeaders(['Cache-Control' => 'no-cache'])->connectTimeout(5)->timeout(config('updates.timeout'))
            ->get('https://api.github.com/repos/'.$repository.'/releases/latest')->throw()->json();
        $version = ltrim((string) ($release['tag_name'] ?? ''), 'vV');
        if (!preg_match('/^\d+\.\d+\.\d+$/D', $version) || !empty($release['draft']) || !empty($release['prerelease'])) {
            throw new RuntimeException('The official release response is not a stable version.');
        }

        $base = 'https://github.com/'.$repository.'/releases/download/v'.$version.'/';
        $assets = collect($release['assets'] ?? []);
        $metadata = $assets->firstWhere('name', 'release-manifest.json');
        $package = $assets->firstWhere('name', 'campaign-manager.zip');
        $result = [
            'checked_at' => now()->toIso8601String(),
            'installed_version' => $this->installedVersion(),
            'latest_version' => $version,
            'available' => version_compare($version, $this->installedVersion(), '>'),
            'release_url' => 'https://github.com/'.$repository.'/releases/tag/v'.$version,
            'verified' => false,
            'message' => 'Manual download is available. This release does not provide verified updater metadata.',
        ];

        if ($metadata && $package) {
            if (($metadata['browser_download_url'] ?? '') !== $base.'release-manifest.json'
                || ($package['browser_download_url'] ?? '') !== $base.'campaign-manager.zip') {
                throw new RuntimeException('The release assets are not hosted at the official repository.');
            }
            $response = Http::withHeaders(['Cache-Control' => 'no-cache'])->connectTimeout(5)->timeout(config('updates.timeout'))->get($base.'release-manifest.json')->throw();
            // Production manifests include vendor file hashes and exceed 1 MiB.
            // Keep a finite bound while retaining mandatory signature verification.
            if (strlen($response->body()) > 8388608) {
                throw new RuntimeException('Release metadata exceeds the allowed size.');
            }
            $manifest = json_decode($response->body(), true, 64, JSON_THROW_ON_ERROR);
            $this->validateManifest($manifest, $version);
            $result += ['manifest' => $manifest, 'package_url' => $base.'campaign-manager.zip'];
            $result['verified'] = true;
            $result['message'] = 'Official release signature verified. You can prepare this update for review.';
        }

        File::ensureDirectoryExists(storage_path('app/updates'));
        File::replace(storage_path('app/updates/check.json'), json_encode($result, JSON_THROW_ON_ERROR), 0600);

        return $result;
    }

    public function validateManifest(array $manifest, string $version): void
    {
        if ($this->signer->verify($manifest) !== true) {
            throw new RuntimeException('Release signature could not be verified against the installed trusted key.');
        }
        if (($manifest['version'] ?? null) !== $version
            || !preg_match('/^[a-f0-9]{64}$/D', (string) ($manifest['checksum_sha256'] ?? ''))
            || !is_int($manifest['size_bytes'] ?? null)
            || $manifest['size_bytes'] < 1
            || $manifest['size_bytes'] > config('updates.maximum_package_bytes')) {
            throw new RuntimeException('Release version, package size or checksum is invalid.');
        }
        if (version_compare(PHP_VERSION, (string) ($manifest['minimum_php'] ?? '8.2.0'), '<')) {
            throw new RuntimeException('This release requires a newer PHP version than the installed server provides.');
        }
    }

    public function prepare(?array $expected = null): array
    {
        File::ensureDirectoryExists(storage_path('app/updates'), 0700);
        $lock = fopen(storage_path('app/updates/prepare.lock'), 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            throw new RuntimeException('Another update preparation is already running.');
        }
        try {
            return $this->prepareUnlocked($expected);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function prepareUnlocked(?array $expected = null): array
    {
        $status = $this->check();
        if (empty($status['available']) || empty($status['verified'])) {
            throw new RuntimeException('No newer verified release is available for preparation.');
        }
        $manifest = $status['manifest'];
        if ($expected && (($expected['version'] ?? null) !== $status['latest_version'] ||
            ! hash_equals($manifest['checksum_sha256'], (string) ($expected['sha256'] ?? '')))) {
            throw new \App\Services\Updates\UpdateFlowException('release', 'The available release changed. Review it again before updating.');
        }
        if (($manifest['upgrade']['protocol'] ?? null) !== 1 ||
            ! in_array($this->installedVersion(), $manifest['upgrade']['supported_sources'] ?? [], true)) {
            throw new \App\Services\Updates\UpdateFlowException('release', 'This release does not support automatic updating from your installed version. Use the official manual upgrade guide.');
        }
        // Coverage and signed target binding precede any removal or archive request.
        app(\App\Services\Updates\UpdateDownloadAccess::class)->assertAllowed($status);
        $matching = null;
        $obsolete = [];
        foreach (File::isDirectory(storage_path('app/updates/staged')) ? File::directories(storage_path('app/updates/staged')) : [] as $directory) {
            $path = $directory.'/journal.json';
            $stage = File::isFile($path) ? json_decode(File::get($path), true) : null;
            $id = basename($directory);
            if (! Str::isUuid($id) || is_link($directory) || ! is_array($stage) ||
                ($stage['id'] ?? null) !== $id || ($stage['state'] ?? null) !== 'prepared' ||
                ! preg_match('/^\d+\.\d+\.\d+$/D', (string) ($stage['version'] ?? ''))) {
                throw new RuntimeException('An incomplete or unrecognized prepared package needs review before retrying. No package was removed.');
            }
            if ($stage['version'] === $status['latest_version'] &&
                ($stage['manifest']['checksum_sha256'] ?? null) === $manifest['checksum_sha256']) {
                $matching = $stage;
            } elseif (version_compare($stage['version'], $status['latest_version'], '<')) {
                $obsolete[] = $id;
            } else {
                throw new RuntimeException('A conflicting or newer package is already prepared. Check the official release before retrying. No package was removed.');
            }
        }
        foreach ($obsolete as $id) {
            // discard acquires apply.lock and refuses active or unrecovered jobs.
            app(\App\Services\Updates\AutomaticUpdateService::class)->discard($id);
        }
        if ($matching) {
            return $matching;
        }
        $id = (string) Str::uuid();
        $directory = storage_path('app/updates/staged/'.$id);
        File::ensureDirectoryExists($directory, 0700);
        $packagePath = $directory.'/package.zip';

        try {
            $limit = $manifest['size_bytes'];
            Http::connectTimeout(10)->timeout(300)->withOptions([
                'sink' => $packagePath,
                'progress' => function ($total, $downloaded) use ($limit) {
                    if ($downloaded > $limit || $total > $limit) {
                        throw new RuntimeException('The package download exceeded its signed size.');
                    }
                },
            ])->get($status['package_url'])->throw();
            if (File::size($packagePath) !== $limit || !hash_equals($manifest['checksum_sha256'], hash_file('sha256', $packagePath))) {
                throw new RuntimeException('The downloaded package does not match its signed checksum and size.');
            }
            $this->extractVerifiedPackage($packagePath, $directory.'/application', $status['latest_version']);
            $journal = ['id' => $id, 'state' => 'prepared', 'version' => $status['latest_version'], 'created_at' => now()->toIso8601String(), 'manifest' => $manifest];
            File::replace($directory.'/journal.json', json_encode($journal, JSON_THROW_ON_ERROR), 0600);

            return $journal;
        } catch (\Throwable $exception) {
            File::deleteDirectory($directory);
            throw $exception;
        }
    }

    public function extractVerifiedPackage(string $packagePath, string $destination, ?string $expectedVersion = null): void
    {
        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== true) {
            throw new RuntimeException('The update package is not a valid ZIP.');
        }
        try {
            if ($zip->numFiles > config('updates.maximum_files')) {
                throw new RuntimeException('The update package contains too many files.');
            }
            $expanded = 0;
            $seen = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];
                $parts = explode('/', rtrim($name, '/'));
                $zip->getExternalAttributesIndex($i, $system, $attributes);
                if ($name === '' || str_starts_with($name, '/') || str_contains($name, '\\')
                    || preg_match('/[\x00-\x1f\x7f:]/', $name)
                    || array_intersect($parts, ['..', '.', ''])
                    || isset($seen[strtolower(rtrim($name, '/'))])
                    || (($attributes >> 16) & 0170000) === 0120000
                    || in_array(strtolower($parts[0]), ['.env', '.git', '.github', 'node_modules'], true)) {
                    throw new RuntimeException('The update package contains an unsafe path or link.');
                }
                $seen[strtolower(rtrim($name, '/'))] = $name;
                $expanded += $stat['size'];
                if ($expanded > config('updates.maximum_expanded_bytes')) {
                    throw new RuntimeException('The expanded package exceeds its safe size limit.');
                }
            }
            File::ensureDirectoryExists($destination, 0700);
            if (!$zip->extractTo($destination)) {
                throw new RuntimeException('The update package could not be staged.');
            }
            $innerPath = $destination.'/release-manifest.json';
            $inner = File::isFile($innerPath) ? json_decode(File::get($innerPath), true) : null;
            if (!is_array($inner) || $this->signer->verify($inner) !== true || empty($inner['checksums'])
                || ($expectedVersion !== null && ($inner['version'] ?? null) !== $expectedVersion)) {
                throw new RuntimeException('The staged package manifest cannot be authenticated.');
            }
            foreach ($inner['checksums'] as $name => $hash) {
                if (!isset($seen[strtolower($name)]) || !File::isFile($destination.'/'.$name)
                    || !hash_equals((string) $hash, hash_file('sha256', $destination.'/'.$name))) {
                    throw new RuntimeException('A staged application file failed checksum verification.');
                }
            }
            $listed = array_change_key_case($inner['checksums'], CASE_LOWER);
            foreach ($seen as $canonical => $name) {
                if (str_ends_with($name, '/')) {
                    continue;
                }
                if (File::isFile($destination.'/'.$name) && !in_array($canonical, ['release-manifest.json', 'checksums.txt'], true)
                    && !array_key_exists($canonical, $listed)) {
                    throw new RuntimeException('The staged package contains an unlisted application file.');
                }
            }
        } finally {
            $zip->close();
        }
    }
}
