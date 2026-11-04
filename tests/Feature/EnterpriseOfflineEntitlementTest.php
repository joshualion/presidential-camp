<?php

use App\Services\DeploymentRegistrationService;
use App\Services\Enterprise\EntitlementService;
use App\Services\Enterprise\SignedEntitlement;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function () {
    \App\Models\SystemSetting::create(['package' => 'governorship', 'system_name' => 'Scope test', 'system_country' => 'Nigeria']);
    $this->enterpriseTestDirectory = storage_path('framework/testing/enterprise-'.Str::uuid());
});
afterEach(function () { File::deleteDirectory($this->enterpriseTestDirectory); });

function enterpriseOfflineFixture(string $directory, array $changes = []): array
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    config(['enterprise.public_key' => openssl_pkey_get_details($key)['key'], 'app.url' => 'https://campaign.example.test',
        'app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
    $registration = Mockery::mock(DeploymentRegistrationService::class);
    $registration->shouldReceive('summary')->andReturn(['registered' => true, 'id' => 'installation-a', 'domain' => 'campaign.example.test']);
    $registration->shouldReceive('domain')->andReturn('campaign.example.test');
    $claims = array_replace(['protocol' => 1, 'installation_id' => 'installation-a', 'domain' => 'campaign.example.test',
        'issued_at' => time() - 1000, 'refresh_after' => time() - 100, 'valid_until' => time() + 1000,
        'features' => ['advanced_reports' => ['active' => true, 'starts_at' => time() - 5000, 'expires_at' => time() + 500]],
        'catalogue' => [], 'package_type' => 'governorship'], $changes);
    $payload = json_encode($claims, JSON_THROW_ON_ERROR); openssl_sign($payload, $signature, $key, OPENSSL_ALGO_SHA256);
    $response = new Response(new \GuzzleHttp\Psr7\Response(200, [], json_encode(['payload' => base64_encode($payload), 'signature' => base64_encode($signature)])));
    $service = new class($registration, new SignedEntitlement, $directory.'/cache.json') extends EntitlementService {
        public function __construct($registration, $verifier, private string $testPath) { parent::__construct($registration, $verifier); }
        protected function path(): string { return $this->testPath; }
    };
    return [$service, $registration, $response];
}

test('an outage permits only previously signed unexpired features during the bounded grace period', function () {
    [$service, $registration, $response] = enterpriseOfflineFixture($this->enterpriseTestDirectory);
    $registration->shouldReceive('enterpriseEntitlements')->once()->ordered()->andReturn($response);
    $service->refresh();
    $registration->shouldReceive('enterpriseEntitlements')->twice()->ordered()->andThrow(new ConnectionException('Offline'));
    expect($service->active('advanced_reports'))->toBeTrue();
    expect($service->active('campaign_finance'))->toBeFalse();
});

test('online authorization cannot fall back to offline grace', function () {
    [$service, $registration, $response] = enterpriseOfflineFixture($this->enterpriseTestDirectory);
    $registration->shouldReceive('enterpriseEntitlements')->once()->ordered()->andReturn($response);
    $service->refresh();
    $registration->shouldReceive('enterpriseEntitlements')->once()->ordered()->andThrow(new ConnectionException('Offline'));
    expect($service->active('advanced_reports', true))->toBeFalse();
});

test('an explicit authentication rejection removes the cache and denies existing grants', function () {
    [$service, $registration, $response] = enterpriseOfflineFixture($this->enterpriseTestDirectory);
    $registration->shouldReceive('enterpriseEntitlements')->once()->ordered()->andReturn($response);
    $service->refresh();
    $registration->shouldReceive('enterpriseEntitlements')->once()->ordered()->andReturn(new Response(new \GuzzleHttp\Psr7\Response(403)));
    expect($service->active('advanced_reports'))->toBeFalse()->and($service->cached())->toBeNull();
});

test('expiry is enforced independently for each feature during an outage', function () {
    [$service, $registration, $response] = enterpriseOfflineFixture($this->enterpriseTestDirectory, ['features' => [
        'advanced_reports' => ['active' => true, 'starts_at' => time() - 5000, 'expires_at' => time() - 1],
        'campaign_finance' => ['active' => true, 'starts_at' => time() - 5000, 'expires_at' => time() + 500],
    ]]);
    $registration->shouldReceive('enterpriseEntitlements')->once()->ordered()->andReturn($response);
    $service->refresh();
    $registration->shouldReceive('enterpriseEntitlements')->twice()->ordered()->andThrow(new ConnectionException('Offline'));
    expect($service->active('advanced_reports'))->toBeFalse()->and($service->active('campaign_finance'))->toBeTrue();
});

test('cached entitlements cannot survive a change in the installation campaign scope', function () {
    [$service, $registration, $response] = enterpriseOfflineFixture($this->enterpriseTestDirectory);
    $registration->shouldReceive('enterpriseEntitlements')->once()->andReturn($response);
    $service->refresh();
    expect($service->cached())->not->toBeNull();
    \App\Models\SystemSetting::query()->update(['package' => 'presidential']);
    expect($service->cached())->toBeNull();
});
