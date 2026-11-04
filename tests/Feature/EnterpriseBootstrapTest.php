<?php

use App\Services\DeploymentRegistrationService;
use App\Services\Enterprise\EnterpriseBootstrapService;
use App\Services\Enterprise\EntitlementService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function enterpriseBootstrapTrustFixture(): void
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    config(['enterprise.public_key' => openssl_pkey_get_details($key)['key']]);
}

test('enterprise bootstrap is idempotent and preserves existing customer role grants', function () {
    enterpriseBootstrapTrustFixture();
    $registration = $this->mock(DeploymentRegistrationService::class);
    $registration->shouldReceive('summary')->twice()->andReturn(['registered' => false]);
    $this->mock(EntitlementService::class)->shouldNotReceive('refresh');
    $legacy = Permission::firstOrCreate(['name' => 'customer.previous.permission', 'guard_name' => 'web'], ['group_name' => 'user']);
    $role = Role::create(['name' => 'Existing customer role', 'guard_name' => 'web', 'group_name' => 'user']);
    $role->givePermissionTo($legacy);
    $bootstrap = app(EnterpriseBootstrapService::class);
    $first = $bootstrap->initialize();
    $count = Permission::count();
    $second = $bootstrap->initialize();
    expect($first['permissions_ready'])->toBeTrue()->and($second['synchronized'])->toBeFalse()
        ->and(Permission::count())->toBe($count)
        ->and($role->fresh()->permissions->pluck('name')->all())->toBe(['customer.previous.permission']);
    Http::assertNothingSent();
});

test('fresh and unverified installations initialize without an entitlement request', function (array $state) {
    enterpriseBootstrapTrustFixture();
    $this->mock(DeploymentRegistrationService::class)->shouldReceive('summary')->once()->andReturn($state);
    $this->mock(EntitlementService::class)->shouldNotReceive('refresh');
    $result = app(EnterpriseBootstrapService::class)->initialize();
    expect($result['permissions_ready'])->toBeTrue()->and($result['synchronized'])->toBeFalse()
        ->and($result['warnings'])->not->toBeEmpty();
    Http::assertNothingSent();
})->with([
    'fresh' => [['registered' => false]],
    'unverified' => [['registered' => true, 'contact_verified' => false]],
    'revoking' => [['registered' => true, 'contact_verified' => true, 'pending_revoke' => true]],
]);

test('registered verified installations synchronize using the entitlement verifier', function () {
    enterpriseBootstrapTrustFixture();
    $this->mock(DeploymentRegistrationService::class)->shouldReceive('summary')->once()
        ->andReturn(['registered' => true, 'contact_verified' => true]);
    $this->mock(EntitlementService::class)->shouldReceive('refresh')->once()->andReturn(['features' => []]);
    $result = app(EnterpriseBootstrapService::class)->initialize();
    expect($result['synchronized'])->toBeTrue()->and($result['warnings'])->toBe([]);
});

test('a Portal outage is recoverable and bootstrap never deletes the entitlement cache', function () {
    enterpriseBootstrapTrustFixture();
    $this->mock(DeploymentRegistrationService::class)->shouldReceive('summary')->once()
        ->andReturn(['registered' => true, 'contact_verified' => true]);
    $this->mock(EntitlementService::class)->shouldReceive('refresh')->once()->andThrow(new ConnectionException('Synthetic outage'));
    $result = app(EnterpriseBootstrapService::class)->initialize();
    expect($result['permissions_ready'])->toBeTrue()->and($result['synchronized'])->toBeFalse()
        ->and($result['warnings'][0])->toContain('Open Add-ons');
});

test('missing trust fails closed without generating keys or contacting the Portal', function () {
    config(['enterprise.public_key' => null]);
    $this->mock(DeploymentRegistrationService::class)->shouldNotReceive('summary');
    $this->mock(EntitlementService::class)->shouldNotReceive('refresh');
    $this->artisan('campaign:enterprise:bootstrap')
        ->expectsOutputToContain('Enterprise permissions initialized.')
        ->expectsOutputToContain('Add-on verification trust is unavailable')
        ->assertSuccessful();
    Http::assertNothingSent();
});

test('local setup failures stop the upgrade with a sanitized recovery message', function () {
    $this->mock(EnterpriseBootstrapService::class)->shouldReceive('initialize')->once()
        ->andThrow(new RuntimeException('synthetic database password'));
    $code = \Illuminate\Support\Facades\Artisan::call('campaign:enterprise:bootstrap');
    expect($code)->toBe(1)
        ->and(\Illuminate\Support\Facades\Artisan::output())->toContain('official update recovery process')
        ->not->toContain('synthetic database password');
});
