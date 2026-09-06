<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class ReleaseSigningKeyService
{
    public function __construct(
        private ?string $basePath = null,
        private ?array $signingConfig = null,
    ) {
        $this->basePath ??= base_path();
        $this->signingConfig ??= config('release.signing', []);
    }

    public function generate(bool $force = false): array
    {
        $this->ensureDirectory();

        $privatePath = $this->privateKeyPath();
        $publicPath = $this->publicKeyPath();
        File::ensureDirectoryExists(dirname($privatePath));
        File::ensureDirectoryExists(dirname($publicPath));
        $privateExists = File::isFile($privatePath);
        $publicExists = File::isFile($publicPath);

        if (($privateExists || $publicExists) && !$force) {
            return [
                'generated' => false,
                'message' => 'Release signing keys already exist. Use --force to regenerate deliberately.',
                'status' => $this->status(),
            ];
        }

        if (!function_exists('openssl_pkey_new')) {
            throw new RuntimeException('PHP OpenSSL extension is required to generate release signing keys.');
        }

        $keyConfig = [
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 4096,
        ];
        if ($configPath = $this->opensslConfigPath()) {
            $keyConfig['config'] = $configPath;
        }

        $resource = openssl_pkey_new($keyConfig);

        if (!$resource) {
            throw new RuntimeException('Unable to generate release signing RSA key pair. '.$this->opensslErrors());
        }

        $privatePem = '';
        if (!openssl_pkey_export($resource, $privatePem, null, $keyConfig)) {
            throw new RuntimeException('Unable to export release signing private key. '.$this->opensslErrors());
        }

        $details = openssl_pkey_get_details($resource);
        $publicPem = $details['key'] ?? null;
        if (!$publicPem) {
            throw new RuntimeException('Unable to export release signing public key.');
        }

        File::put($privatePath, $privatePem);
        File::put($publicPath, $publicPem);
        @chmod($privatePath, 0600);
        @chmod($publicPath, 0644);

        $status = $this->status();
        if (!$status['valid']) {
            throw new RuntimeException('Generated release signing keys failed validation: '.implode(' ', $status['errors']));
        }

        return [
            'generated' => true,
            'message' => 'Release signing keys generated.',
            'status' => $status,
        ];
    }

    public function ensureForBuild(): array
    {
        $status = $this->status();

        if (!$status['private_exists'] && !$status['public_exists']) {
            $generated = $this->generate(false);

            return array_merge($generated['status'], [
                'generated' => true,
                'message' => 'Release signing keys were generated for this build.',
            ]);
        }

        if (!$status['valid']) {
            throw new RuntimeException('Release signing keys are invalid: '.implode(' ', $status['errors']));
        }

        return array_merge($status, [
            'generated' => false,
            'message' => 'Release signing keys are valid.',
        ]);
    }

    public function status(): array
    {
        $privatePath = $this->privateKeyPath();
        $publicPath = $this->publicKeyPath();
        $privateExists = File::isFile($privatePath);
        $publicExists = File::isFile($publicPath);
        $errors = [];

        if (!$privateExists) {
            $errors[] = 'Private signing key is missing.';
        }

        if (!$publicExists) {
            $errors[] = 'Public signing key is missing.';
        }

        $privateUsable = false;
        $publicUsable = false;
        $pairMatches = false;

        if ($privateExists) {
            $privateUsable = File::isReadable($privatePath) && openssl_pkey_get_private(File::get($privatePath)) !== false;
            if (!$privateUsable) {
                $errors[] = 'Private signing key is unreadable or invalid.';
            }
        }

        if ($publicExists) {
            $publicUsable = File::isReadable($publicPath) && openssl_pkey_get_public(File::get($publicPath)) !== false;
            if (!$publicUsable) {
                $errors[] = 'Public signing key is unreadable or invalid.';
            }
        }

        if ($privateUsable && $publicUsable) {
            $verification = $this->verifyTestPayload();
            $pairMatches = $verification['ok'];
            if (!$pairMatches) {
                $errors[] = $verification['message'];
            }
        }

        return [
            'keys_dir' => $this->keysDirectory(),
            'private_key_path' => $privatePath,
            'public_key_path' => $publicPath,
            'private_exists' => $privateExists,
            'public_exists' => $publicExists,
            'private_usable' => $privateUsable,
            'public_usable' => $publicUsable,
            'pair_matches' => $pairMatches,
            'valid' => $privateExists && $publicExists && $privateUsable && $publicUsable && $pairMatches,
            'errors' => array_values(array_unique($errors)),
        ];
    }

    public function verifyTestPayload(): array
    {
        $payload = 'Campaign Manager release signing verification payload';
        $signature = $this->signPayload($payload);
        $verified = $this->verifyPayload($payload, $signature);

        return [
            'ok' => $verified,
            'message' => $verified ? 'Release signing key pair verified.' : 'Private and public signing keys do not match or cannot verify signatures.',
        ];
    }

    public function signPayload(string $payload): string
    {
        $privatePem = $this->readPrivateKey();

        if (!openssl_sign($payload, $signature, $privatePem, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign payload with release private key.');
        }

        return base64_encode($signature);
    }

    public function verifyPayload(string $payload, string $signature): bool
    {
        $publicPem = $this->readPublicKey();
        $decoded = base64_decode($signature, true);

        if ($decoded === false) {
            return false;
        }

        return openssl_verify($payload, $decoded, $publicPem, OPENSSL_ALGO_SHA256) === 1;
    }

    public function readPrivateKey(): string
    {
        $path = $this->privateKeyPath();
        if (!File::isReadable($path)) {
            throw new RuntimeException('Release private signing key is missing or unreadable.');
        }

        $pem = File::get($path);
        if (openssl_pkey_get_private($pem) === false) {
            throw new RuntimeException('Release private signing key is invalid.');
        }

        return $pem;
    }

    public function readPublicKey(): string
    {
        $path = $this->publicKeyPath();
        if (!File::isReadable($path)) {
            throw new RuntimeException('Release public signing key is missing or unreadable.');
        }

        $pem = File::get($path);
        if (openssl_pkey_get_public($pem) === false) {
            throw new RuntimeException('Release public signing key is invalid.');
        }

        return $pem;
    }

    public function publicKeyForRelease(): string
    {
        return $this->readPublicKey();
    }

    private function ensureDirectory(): void
    {
        File::ensureDirectoryExists($this->keysDirectory());
    }

    private function keysDirectory(): string
    {
        return $this->absolutePath($this->signingConfig['keys_dir'] ?? 'storage/app/release-keys');
    }

    private function privateKeyPath(): string
    {
        return $this->absolutePath($this->signingConfig['private_key_path'] ?? 'storage/app/release-keys/campaign_release_private.pem');
    }

    private function publicKeyPath(): string
    {
        return $this->absolutePath($this->signingConfig['public_key_path'] ?? 'storage/app/release-keys/campaign_release_public.pem');
    }

    private function absolutePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\/\\\\]/', $path) || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return rtrim($this->basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function opensslConfigPath(): ?string
    {
        $candidates = array_filter([
            getenv('OPENSSL_CONF') ?: null,
            'C:\xampp\apache\conf\openssl.cnf',
            'C:\xampp\php\extras\ssl\openssl.cnf',
            '/etc/ssl/openssl.cnf',
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && str_ends_with(strtolower(str_replace('\\', '/', $candidate)), 'openssl.cnf')) {
                return $candidate;
            }
        }

        return null;
    }

    private function opensslErrors(): string
    {
        $errors = [];
        while ($error = openssl_error_string()) {
            $errors[] = $error;
        }

        return $errors ? implode(' ', array_unique($errors)) : '';
    }
}
