<?php

use App\Models\User;
use App\Services\DeploymentRegistrationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    File::delete(storage_path('app/private/deployment-registration.json'));
    config(['app.url' => 'https://campaign.example.test', 'campaign.portal_api_base' => 'https://portal.test/api/v2']);
});

afterEach(function () {
    File::delete(storage_path('app/private/deployment-registration.json'));
});

test('unregistered installations send no Portal telemetry', function () {
    Http::fake();
    $service = app(DeploymentRegistrationService::class);
    $service->sync();
    expect($service->summary()['registered'])->toBeFalse();
    Http::assertNothingSent();
});

test('registration sends only disclosed fields and encrypts its local credential', function () {
    Http::fake([
        'https://portal.test/api/v2/campaign-manager/deployments/register' => Http::response(['registered' => true, 'contact_verified' => false], 201),
        'https://portal.test/api/v2/campaign-manager/deployments/*/preferences' => Http::response(['updated' => true]),
    ]);
    $admin = User::factory()->create(['access_level' => 'superadmin']);
    $service = app(DeploymentRegistrationService::class);
    $service->register($admin, false);
    $summary = $service->summary();
    expect($summary['registered'])->toBeTrue()->and($summary['marketing_opt_in'])->toBeFalse()->and($summary)->not->toHaveKey('token');
    Http::assertSent(function ($request) use ($admin) {
        if (!str_ends_with($request->url(), '/register')) {
            return false;
        }
        $data = $request->data();
        expect(array_keys($data))->toBe(['deployment_id', 'token', 'domain', 'contact_email', 'installed_version', 'installation_status', 'registration_consent', 'marketing_opt_in']);
        return $data['contact_email'] === $admin->email && $data['domain'] === 'campaign.example.test' && $data['registration_consent'] === true;
    });
    expect(File::get(storage_path('app/private/deployment-registration.json')))->not->toContain($admin->email)->not->toContain('campaign.example.test');
});

test('revocation stops local reporting even while the Portal is offline', function () {
    Http::fake([
        'https://portal.test/api/v2/campaign-manager/deployments/register' => Http::response(['registered' => true], 201),
        'https://portal.test/api/v2/campaign-manager/deployments/*/preferences' => Http::response(['updated' => true]),
    ]);
    $service = app(DeploymentRegistrationService::class);
    $service->register(User::factory()->create(['access_level' => 'superadmin']), false);
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Offline test fixture'));
    expect(fn () => $service->revoke())->toThrow(\Illuminate\Http\Client\ConnectionException::class);
    expect($service->summary()['registered'])->toBeFalse()->and($service->summary()['pending_revoke'])->toBeTrue();
});

test('a regular member cannot register the installation or access its updater', function () {
    Http::fake();
    $member = User::factory()->create(['access_level' => 'user']);
    expect(fn () => app(DeploymentRegistrationService::class)->register($member, true))->toThrow(RuntimeException::class);
    $this->actingAs($member)->get(route('superadmin.updates'))->assertForbidden();
    Http::assertNothingSent();
});

test('Super Admin can open update and registration controls without contacting the Portal', function () {
    Http::fake();
    \App\Models\SystemSetting::query()->create(['package' => 'presidential', 'system_name' => 'Test Campaign', 'system_country' => 'Nigeria']);
    $admin = User::factory()->create(['access_level' => 'superadmin']);
    $this->actingAs($admin)->get(route('superadmin.updates'))->assertOk()->assertSee('Optional deployment registration')->assertSee('Official Updates');
    Http::assertNothingSent();
});
