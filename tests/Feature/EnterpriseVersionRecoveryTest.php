<?php

use App\Models\SystemSetting;
use App\Services\DeploymentRegistrationService;
use App\Services\OfficialUpdateService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->oldEnterpriseStorage = storage_path();
    $this->enterpriseStorage = sys_get_temp_dir().'/enterprise-version-'.Str::uuid();
    app()->useStoragePath($this->enterpriseStorage);
    config(['app.url' => 'https://campaign.example.test', 'campaign.portal_api_base' => 'https://portal.example.test/api/v2']);
    SystemSetting::create(['package' => 'governorship', 'system_name' => 'Version recovery', 'system_country' => 'Nigeria']);
    $this->enterpriseIdentity = ['registered' => true, 'id' => (string) Str::uuid(), 'domain' => 'campaign.example.test', 'token' => str_repeat('a', 64)];
    File::ensureDirectoryExists(storage_path('app/private'));
    File::put(storage_path('app/private/deployment-registration.json'), Crypt::encryptString(json_encode($this->enterpriseIdentity)));
    $this->mock(OfficialUpdateService::class)->shouldReceive('installedVersion')->andReturn('1.2.5');
    Http::preventStrayRequests();
});
afterEach(function () { app()->useStoragePath($this->oldEnterpriseStorage); File::deleteDirectory($this->enterpriseStorage); });

test('add-on synchronization reports the actual installed version before retrieving checkout readiness', function () {
    Http::fake([
        '*/heartbeat' => Http::response(['accepted' => true]),
        '*/enterprise-scope' => Http::response(['accepted' => true]),
        '*/enterprise-entitlements' => Http::response(['payload' => 'synthetic signed response']),
    ]);
    expect(app(DeploymentRegistrationService::class)->enterpriseEntitlements()->json('payload'))->toBe('synthetic signed response');
    $requests = Http::recorded()->map(fn ($pair) => $pair[0])->all();
    expect(count($requests))->toBe(3);
    expect($requests[0]->url())->toEndWith('/heartbeat');
    expect($requests[0]['installed_version'])->toBe('1.2.5')->and($requests[0]['event'])->toBe('update_checked');
    expect($requests[0]->hasHeader('Authorization', 'Bearer '.$this->enterpriseIdentity['token']))->toBeTrue();
    expect($requests[1]->url())->toEndWith('/enterprise-scope')->and($requests[2]->url())->toEndWith('/enterprise-entitlements');
});

test('a rejected version report cannot proceed to scope binding or entitlement issuance', function () {
    Http::fake(['*/heartbeat' => Http::response(['accepted' => false], 403)]);
    expect(app(DeploymentRegistrationService::class)->enterpriseEntitlements()->status())->toBe(403);
    Http::assertSentCount(1);
});
