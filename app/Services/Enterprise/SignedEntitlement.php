<?php

namespace App\Services\Enterprise;

use RuntimeException;

/** Versioned Portal contract. Validate before persisting or using any claim. */
class SignedEntitlement
{
    public function verify(array $envelope, string $publicKey, string $installation, string $domain, int $now, ?string $expectedPackage = null): array
    {
        $payload = $envelope['payload'] ?? null;
        $signature = $envelope['signature'] ?? null;
        if (! is_string($payload) || ! is_string($signature) || strlen($payload) > 65536 || strlen($signature) > 2048) {
            throw new RuntimeException('Invalid entitlement envelope.');
        }
        $bytes = base64_decode($payload, true);
        $proof = base64_decode($signature, true);
        $key = openssl_pkey_get_public($publicKey);
        $details = $key ? openssl_pkey_get_details($key) : false;
        if ($bytes === false || $proof === false || ! $details || $details['type'] !== OPENSSL_KEYTYPE_RSA
            || $details['bits'] < 2048 || openssl_verify($bytes, $proof, $key, OPENSSL_ALGO_SHA256) !== 1) {
            throw new RuntimeException('Entitlement signature could not be verified.');
        }
        $claims = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR);
        if (! is_array($claims) || ($claims['protocol'] ?? null) !== 1
            || ($claims['installation_id'] ?? null) !== $installation || ($claims['domain'] ?? null) !== $domain
            || ! is_int($claims['issued_at'] ?? null) || ! is_int($claims['refresh_after'] ?? null)
            || ! is_int($claims['valid_until'] ?? null) || $claims['issued_at'] > $now + 60
            || $claims['refresh_after'] < $claims['issued_at'] || $claims['refresh_after'] > $claims['issued_at'] + 900
            || $claims['valid_until'] <= $now || $claims['valid_until'] <= $claims['refresh_after']
            || $claims['valid_until'] > $claims['issued_at'] + 86400
            || ! is_array($claims['features'] ?? null) || ! is_array($claims['catalogue'] ?? null)) {
            throw new RuntimeException('Entitlement identity or validity is invalid.');
        }
        if ($expectedPackage !== null && (! in_array($expectedPackage, ['presidential', 'governorship', 'senatorial', 'federal_constituency', 'lga'], true)
            || ($claims['package_type'] ?? null) !== $expectedPackage)) {
            throw new RuntimeException('Entitlement campaign scope does not match this installation. Refresh the licence status.');
        }
        foreach ($claims['features'] as $feature => $grant) {
            if (! is_string($feature) || ! is_array($grant) || ($grant['active'] ?? null) !== true
                || ! is_int($grant['starts_at'] ?? null) || $grant['starts_at'] > $now
                || (isset($grant['expires_at']) && (! is_int($grant['expires_at']) || $grant['expires_at'] <= $grant['starts_at']))) {
                throw new RuntimeException('Invalid feature grant.');
            }
        }

        return $claims;
    }
}
