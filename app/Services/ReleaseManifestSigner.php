<?php

namespace App\Services;

use RuntimeException;

class ReleaseManifestSigner
{
    public function sign(array $manifest, ?array $signingConfig = null): array
    {
        $signingConfig ??= config('release.signing', []);
        $keys = new ReleaseSigningKeyService($signingConfig['_base_path'] ?? base_path(), $signingConfig);
        $privateKey = $keys->readPrivateKey();

        unset($manifest['manifest_signature']);

        $payload = $this->canonicalPayload($manifest);
        if (!openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign release manifest with configured private key.');
        }

        $manifest['manifest_signature'] = [
            'algorithm' => 'rsa-sha256',
            'key_id' => (string) ($signingConfig['public_key_id'] ?? 'campaign-manager-release-v1'),
            'value' => base64_encode($signature),
        ];

        return [
            'manifest' => $manifest,
            'signed' => true,
            'warning' => null,
        ];
    }

    public function verify(array $manifest, ?string $publicKey = null, ?array $signingConfig = null): ?bool
    {
        $signaturePayload = $manifest['manifest_signature'] ?? null;
        $signature = is_array($signaturePayload) ? ($signaturePayload['value'] ?? null) : null;
        if (!$signature) {
            return null;
        }

        $publicKey = $publicKey ?: $this->configuredPublicKey($signingConfig ?? config('release.signing', []));
        if (!$publicKey) {
            return false;
        }

        $unsigned = $manifest;
        unset($unsigned['manifest_signature']);

        $decoded = base64_decode((string) $signature, true);
        if ($decoded === false) {
            return false;
        }

        return openssl_verify($this->canonicalPayload($unsigned), $decoded, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    public function canonicalPayload(array $manifest): string
    {
        $manifest = $this->sortRecursive($manifest);

        return json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function sortRecursive(array $value): array
    {
        foreach ($value as $key => $child) {
            if (is_array($child)) {
                $value[$key] = $this->sortRecursive($child);
            }
        }

        ksort($value);

        return $value;
    }

    private function configuredPublicKey(array $signingConfig): ?string
    {
        if (!empty($signingConfig['public_key'])) {
            return (string) $signingConfig['public_key'];
        }

        try {
            return (new ReleaseSigningKeyService($signingConfig['_base_path'] ?? base_path(), $signingConfig))->readPublicKey();
        } catch (RuntimeException) {
            return null;
        }
    }
}
