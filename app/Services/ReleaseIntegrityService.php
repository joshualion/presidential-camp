<?php

namespace App\Services;

use App\Models\IntegrityStatus;
use App\Support\SafeDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ReleaseIntegrityService
{
    public function __construct(
        private ReleaseManifestSigner $signer,
        private ?string $basePath = null,
    ) {
        $this->basePath ??= base_path();
    }

    public function check(bool $persist = true): array
    {
        $manifestPath = $this->path('release-manifest.json');

        if (!File::isFile($manifestPath)) {
            return $this->finish([
                'manifest_present' => false,
                'manifest_signature_valid' => null,
                'protected_files_valid' => null,
                'tampered' => false,
                'tamper_details' => [],
                'app_version' => config('release.identity.version', config('app.version')),
                'update_entitlement_state' => 'manifest_missing',
                'message' => 'release-manifest.json is missing. Integrity check skipped for local/development install.',
            ], $persist);
        }

        $manifest = json_decode(File::get($manifestPath), true);
        if (!is_array($manifest)) {
            return $this->finish([
                'manifest_present' => true,
                'manifest_signature_valid' => false,
                'protected_files_valid' => false,
                'tampered' => true,
                'tamper_details' => [['file' => 'release-manifest.json', 'status' => 'invalid_json']],
                'app_version' => config('release.identity.version', config('app.version')),
                'update_entitlement_state' => 'signature_invalid',
                'message' => 'release-manifest.json is invalid.',
            ], $persist);
        }

        $signatureValid = $this->signer->verify($manifest);
        $details = [];

        foreach (($manifest['protected_files'] ?? []) as $file => $expectedHash) {
            $path = $this->path($file);
            if (!File::isFile($path)) {
                $details[] = ['file' => $file, 'status' => 'missing'];
                continue;
            }

            $actual = hash_file('sha256', $path);
            if (!hash_equals((string) $expectedHash, $actual)) {
                $details[] = [
                    'file' => $file,
                    'status' => 'hash_mismatch',
                    'expected' => $expectedHash,
                    'actual' => $actual,
                ];
            }
        }

        $protectedFilesValid = $details === [];
        $signatureInvalid = $signatureValid === false;
        $tampered = $signatureInvalid || !$protectedFilesValid;

        return $this->finish([
            'manifest_present' => true,
            'manifest_signature_valid' => $signatureValid,
            'protected_files_valid' => $protectedFilesValid,
            'tampered' => $tampered,
            'tamper_details' => $details,
            'app_version' => (string) ($manifest['version'] ?? config('release.identity.version', config('app.version'))),
            'update_entitlement_state' => match (true) {
                $signatureInvalid => 'signature_invalid',
                !$protectedFilesValid => 'tampered',
                default => 'clean',
            },
            'message' => $tampered ? 'Release integrity check found protected file issues.' : 'Release integrity check passed.',
        ], $persist);
    }

    public function latestStatus(): ?IntegrityStatus
    {
        if (!SafeDatabase::hasTable('integrity_statuses')) {
            return null;
        }

        return IntegrityStatus::query()->latest('id')->first();
    }

    private function finish(array $result, bool $persist): array
    {
        $result['last_checked_at'] = now();

        if ($persist && SafeDatabase::canConnect() && Schema::hasTable('integrity_statuses')) {
            IntegrityStatus::query()->create([
                'last_checked_at' => $result['last_checked_at'],
                'manifest_present' => $result['manifest_present'],
                'manifest_signature_valid' => $result['manifest_signature_valid'],
                'protected_files_valid' => $result['protected_files_valid'],
                'tampered' => $result['tampered'],
                'tamper_details' => $result['tamper_details'],
                'app_version' => $result['app_version'],
            ]);
        }

        return $result;
    }

    private function path(string $relativePath): string
    {
        return rtrim($this->basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    }
}
