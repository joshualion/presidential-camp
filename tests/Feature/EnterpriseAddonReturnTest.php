<?php

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Enterprise\EntitlementService;

function addonReturnFixture(array $features = [], array $catalogue = []): array
{
    SystemSetting::create(['package' => 'governorship', 'system_name' => 'Campaign', 'system_country' => 'Nigeria']);
    $user = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    return [$user, ['installation_id' => 'installation-a', 'features' => $features, 'catalogue' => $catalogue]];
}

test('checkout return refreshes server authorization before displaying active module access', function () {
    [$user, $claims] = addonReturnFixture(['advanced_reports' => ['active' => true, 'starts_at' => time() - 100, 'expires_at' => time() + 500]]);
    $this->withoutMiddleware(\App\Http\Middleware\CheckUserOnboarding::class);
    $this->mock(EntitlementService::class, function ($mock) use ($claims) {
        $mock->shouldReceive('refresh')->once()->andReturn($claims);
        $mock->shouldReceive('cached')->andReturn($claims);
        $mock->shouldReceive('active')->andReturn(false);
    });
    $this->actingAs($user)->get(route('superadmin.addons.index', ['payment_status' => 'paid']))
        ->assertOk()->assertSee('Active')->assertSee('Open module')->assertSee('Activation date:');
});

test('browser payment claims cannot activate a module when server verification fails', function () {
    [$user] = addonReturnFixture();
    $this->withoutMiddleware(\App\Http\Middleware\CheckUserOnboarding::class);
    $this->mock(EntitlementService::class, function ($mock) {
        $mock->shouldReceive('refresh')->once()->andThrow(new RuntimeException('Unavailable'));
        $mock->shouldReceive('cached')->andReturn(null);
        $mock->shouldReceive('active')->andReturn(false);
    });
    $this->actingAs($user)->get(route('superadmin.addons.index', ['payment_status' => 'paid']))
        ->assertOk()->assertSee('Locked')->assertDontSee('Open module')->assertSee('could not be reached');
});

test('expired licences offer renewal without displaying operational module links', function () {
    config(['campaign.portal_web_base' => 'https://campaignmanager.ng', 'campaign.portal_checkout_origins' => ['https://campaignmanager.ng']]);
    [$user, $claims] = addonReturnFixture(['advanced_reports' => ['active' => true, 'starts_at' => time() - 1000, 'expires_at' => time() - 1]], [
        'advanced_reports' => ['purchasable' => true, 'prices' => [['scope' => 'governorship', 'currency' => 'NGN', 'amount' => '100', 'billing_label' => 'Annual']]],
    ]);
    $this->withoutMiddleware(\App\Http\Middleware\CheckUserOnboarding::class);
    $this->mock(EntitlementService::class, function ($mock) use ($claims) {
        $mock->shouldReceive('refresh')->once()->andReturn($claims);
        $mock->shouldReceive('cached')->andReturn($claims);
        $mock->shouldReceive('active')->andReturn(false);
    });
    $this->actingAs($user)->get(route('superadmin.addons.index'))->assertOk()
        ->assertSee('Review price and renew')->assertDontSee('Open module');
});
