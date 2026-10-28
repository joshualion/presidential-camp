<?php

namespace Tests\Unit;

use App\Services\Enterprise\SignedEntitlement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SignedEnterpriseEntitlementTest extends TestCase
{
    private function envelope(array $changes = []): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $claims = array_replace([
            'protocol' => 1, 'installation_id' => 'installation-a', 'domain' => 'campaign.example.test',
            'issued_at' => 1000, 'refresh_after' => 1900, 'valid_until' => 87400,
            'features' => ['advanced_reports' => ['active' => true, 'starts_at' => 900, 'expires_at' => 5000]],
            'catalogue' => [],
        ], $changes);
        $payload = json_encode($claims, JSON_THROW_ON_ERROR);
        openssl_sign($payload, $signature, $key, OPENSSL_ALGO_SHA256);
        return [['payload' => base64_encode($payload), 'signature' => base64_encode($signature)], openssl_pkey_get_details($key)['key']];
    }

    public function test_valid_installation_bound_response_is_accepted(): void
    {
        [$envelope, $key] = $this->envelope();
        $claims = (new SignedEntitlement)->verify($envelope, $key, 'installation-a', 'campaign.example.test', 1100);
        $this->assertTrue($claims['features']['advanced_reports']['active']);
        $this->assertArrayNotHasKey('campaign_finance', $claims['features']);
    }

    public function test_tampered_payload_is_rejected(): void
    {
        [$envelope, $key] = $this->envelope();
        $envelope['payload'] = base64_encode(str_replace('installation-a', 'installation-b', base64_decode($envelope['payload'])));
        $this->expectException(RuntimeException::class);
        (new SignedEntitlement)->verify($envelope, $key, 'installation-b', 'campaign.example.test', 1100);
    }

    public function test_another_installation_cannot_reuse_a_signed_response(): void
    {
        [$envelope, $key] = $this->envelope();
        $this->expectException(RuntimeException::class);
        (new SignedEntitlement)->verify($envelope, $key, 'installation-b', 'campaign.example.test', 1100);
    }

    public function test_domain_changes_invalidate_cache(): void
    {
        [$envelope, $key] = $this->envelope();
        $this->expectException(RuntimeException::class);
        (new SignedEntitlement)->verify($envelope, $key, 'installation-a', 'changed.example.test', 1100);
    }

    public function test_expired_or_excessively_long_cache_is_rejected(): void
    {
        foreach ([['valid_until' => 1100], ['valid_until' => 87401], ['issued_at' => 2000], ['protocol' => 2]] as $changes) {
            [$envelope, $key] = $this->envelope($changes);
            try {
                (new SignedEntitlement)->verify($envelope, $key, 'installation-a', 'campaign.example.test', 1100);
                $this->fail('An invalid lifetime or protocol was accepted.');
            } catch (RuntimeException) { $this->addToAssertionCount(1); }
        }
    }

    public function test_one_expired_feature_does_not_invalidate_other_features(): void
    {
        [$envelope, $key] = $this->envelope();
        $this->assertIsArray((new SignedEntitlement)->verify($envelope, $key, 'installation-a', 'campaign.example.test', 6000));
    }

    public function test_a_different_signing_key_cannot_authorize_access(): void
    {
        [$envelope] = $this->envelope();
        [, $key] = $this->envelope();
        $this->expectException(RuntimeException::class);
        (new SignedEntitlement)->verify($envelope, $key, 'installation-a', 'campaign.example.test', 1100);
    }
}
