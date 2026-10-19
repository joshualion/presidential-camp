<?php

namespace App\Services\Updates;

use App\Services\OfficialUpdateService;
use App\Services\ReleaseManifestSigner;
use App\Services\Updates\UpdateException as RuntimeException;
use Illuminate\Support\Facades\Schema;

class UpdatePolicy
{
    public function inspect(string $stageId): array
    {
        $stage = UpdatePaths::stage($stageId);
        $path = $stage.'/application/release-manifest.json';
        $journal = is_file($stage.'/journal.json') ? json_decode(file_get_contents($stage.'/journal.json'), true) : null;
        if (! $journal || ($journal['state'] ?? null) !== 'prepared' || ! is_file($path)) {
            throw new RuntimeException('Prepare an official update package first.');
        }
        app(OfficialUpdateService::class)->validateManifest($journal['manifest'] ?? [], $journal['version']);
        $manifest = json_decode(file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        if (app(ReleaseManifestSigner::class)->verify($manifest) !== true) {
            throw new RuntimeException('Staged release signature is invalid.');
        }
        $outer = $journal['manifest'];
        $keys = ['version', 'upgrade'];
        if (isset($outer['embedded_manifest_sha256'])) {
            if (! is_string($outer['embedded_manifest_sha256']) || ! preg_match('/^[a-f0-9]{64}$/D', $outer['embedded_manifest_sha256'])
                || ! hash_equals($outer['embedded_manifest_sha256'], hash_file('sha256', $path))) {
                throw new RuntimeException('The embedded manifest does not match the signed discovery metadata.');
            }
        } else {
            $keys[] = 'checksums'; // Existing full manifests remain supported.
        }
        foreach ($keys as $key) {
            if (($manifest[$key] ?? null) !== ($journal['manifest'][$key] ?? null)) {
                throw new RuntimeException('The inner and outer release policies differ.');
            }
        }
        $from = app(OfficialUpdateService::class)->installedVersion();
        $to = $manifest['version'] ?? '';
        $policy = $manifest['upgrade'] ?? [];
        if (($policy['protocol'] ?? null) !== 1 || ! in_array($from, $policy['supported_sources'] ?? [], true) || ! version_compare($to, $from, '>') || $to !== $journal['version']) {
            throw new RuntimeException('This release does not declare a reviewed automatic upgrade from the installed version. Use the documented manual route or a supported intermediate release.');
        }
        if (version_compare(PHP_VERSION, $manifest['minimum_php'] ?? '8.2.0', '<')) {
            throw new RuntimeException('This server needs a newer PHP runtime.');
        }
        foreach ($manifest['requirements']['extensions'] ?? [] as $extension) {
            if (! extension_loaded($extension)) {
                throw new RuntimeException('Required PHP extension is unavailable: '.$extension);
            }
        }
        $files = [];
        foreach ($manifest['checksums'] ?? [] as $name => $hash) {
            if (! preg_match('/^[a-f0-9]{64}$/D', (string) $hash)) {
                throw new RuntimeException('Invalid signed file checksum.');
            }
            if (! UpdatePaths::managed($name)) {
                continue;
            }
            $source = $stage.'/application/'.$name;
            if (! is_file($source) || is_link($source) || ! hash_equals($hash, hash_file('sha256', $source))) {
                throw new RuntimeException('A prepared application file has changed.');
            }
            UpdatePaths::destination($name);
            $files[$name] = $hash;
        }
        if (! isset($files['artisan'], $files['vendor/autoload.php'], $files['bootstrap/app.php'], $files['app/Console/Commands/CampaignUpdateHealth.php'])) {
            throw new RuntimeException('The package does not contain the required application and update health check.');
        }
        // The signed inner manifest is deliberately outside its own checksum list.
        $files['release-manifest.json'] = hash_file('sha256', $path);
        $migrator = app('migrator');
        if (! $migrator->repositoryExists()) {
            throw new RuntimeException('No existing migration history was found. Do not run an upgrade as a fresh installation.');
        }
        $ran = $migrator->getRepository()->getRan();
        $pending = [];
        foreach (glob($stage.'/application/database/migrations/*.php') as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $ran, true)) {
                continue;
            }
            $hash = hash_file('sha256', $file);
            if (($policy['approved_migrations'][$name] ?? null) !== $hash) {
                throw new RuntimeException('A pending migration is not approved for automatic execution: '.$name);
            }
            $pending[$name] = $hash;
        }
        foreach (['users', 'system_settings'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException('The existing application schema is not supported.');
            }
        }

        return ['source_version' => $from, 'target_version' => $to, 'stage_id' => $stageId, 'files' => $files, 'pending_migrations' => $pending, 'created_tables' => $policy['created_tables'] ?? [], 'manifest_hash' => hash_file('sha256', $path), 'package_sha256' => $journal['manifest']['checksum_sha256'] ?? ''];
    }
}
