<?php

use App\Models\User;
use App\Services\CampaignCapabilityService;
use App\Services\Enterprise\EntitlementService;
use App\Http\Middleware\EnsureEnterpriseFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

test('enterprise permissions are never inherited from geographic administrative rank', function (string $level) {
    $user = User::factory()->create(['access_level' => $level, 'status' => 'active']);
    foreach (config('enterprise.permissions') as $name) {
        expect(app(CampaignCapabilityService::class)->allows($user, $name))->toBeFalse();
    }
})->with(['nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user']);

test('explicit enterprise grants reuse existing restrictions', function () {
    $user = User::factory()->create(['access_level' => 'stateadmin', 'status' => 'active']);
    $user->givePermissionTo(Permission::findOrCreate('finance.view', 'web'));
    expect(app(CampaignCapabilityService::class)->allows($user, 'finance.view'))->toBeTrue();
    DB::table('campaign_permission_restrictions')->insert(['user_id' => $user->id, 'permission_name' => 'finance.view']);
    expect((new CampaignCapabilityService)->allows($user->fresh(), 'finance.view'))->toBeFalse();
});

test('even a permitted user needs an installation entitlement at the server boundary', function () {
    $user = User::factory()->create(['access_level' => 'stateadmin', 'status' => 'active']);
    $user->givePermissionTo(Permission::findOrCreate('finance.view', 'web'));
    $request = Request::create('/synthetic-finance'); $request->setUserResolver(fn () => $user);
    $this->mock(EntitlementService::class)->shouldReceive('active')->with('campaign_finance')->once()->andReturn(false);
    try {
        (new EnsureEnterpriseFeature)->handle($request, fn () => throw new RuntimeException('Should not execute'), 'campaign_finance', 'finance.view');
        $this->fail('Unpaid request was accepted.');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) { expect($exception->getStatusCode())->toBe(402); }
});

test('SMS entitlement gate stays backward compatible until explicitly enabled', function () {
    config(['enterprise.sms_enforcement_enabled' => false]);
    $access = new \App\Services\Sms\SmsAddonAccess;
    expect($access->allowed())->toBeTrue();
});

test('both hosted and direct SMS routes carry the staged entitlement middleware', function () {
    foreach (['superadmin.sms.compose.send', 'superadmin.sms.settings.index', 'superadmin.direct-sms.send'] as $name) {
        $route = app('router')->getRoutes()->getByName($name);
        expect($route)->not->toBeNull();
        expect($route->gatherMiddleware())->toContain('sms.addon');
    }
});

test('SMS enforcement refuses installation access without an active entitlement', function () {
    config(['enterprise.sms_enforcement_enabled' => true]);
    $this->mock(EntitlementService::class)->shouldReceive('active')
        ->with('sms_messaging')->once()->andReturn(false);
    $request = Request::create('/synthetic-sms');
    try {
        (new \App\Http\Middleware\EnsureSmsAddon)->handle($request, fn () => throw new RuntimeException('Unlicensed SMS passed'));
        $this->fail('Unlicensed SMS passed.');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
        expect($exception->getStatusCode())->toBe(402);
    }
});

test('premium scope resolves chairmanship to Portal LGA rather than the environment default', function () {
    \App\Models\SystemSetting::create([
        'package' => 'chairmanship', 'system_name' => 'LGA Campaign', 'system_country' => 'Nigeria',
    ]);
    $resolved = app(\App\Services\PackageScopeService::class)->current();
    expect($resolved->package_type)->toBe('chairmanship');
    expect($resolved->fallback_used)->toBeFalse();
    $portalScope = $resolved->package_type === 'chairmanship' ? 'lga' : $resolved->package_type;
    expect($portalScope)->toBe('lga');
});

test('SMS premium entitlement restores route access without changing existing SMS permissions', function () {
    config(['enterprise.sms_enforcement_enabled' => true]);
    $this->mock(EntitlementService::class)->shouldReceive('active')
        ->with('sms_messaging')->once()->andReturn(true);
    $middleware = new \App\Http\Middleware\EnsureSmsAddon;
    $called = false;
    $response = $middleware->handle(Request::create('/synthetic-sms'), function () use (&$called) {
        $called = true;
        return new \Illuminate\Http\Response('licensed');
    });
    expect($called)->toBeTrue();
    expect($response->getContent())->toBe('licensed');
});

test('the Add-ons catalogue routes are Super Admin only', function () {
    foreach (['superadmin.addons.index', 'superadmin.addons.refresh'] as $name) {
        $route = app('router')->getRoutes()->getByName($name);
        expect($route)->not->toBeNull();
        expect($route->gatherMiddleware())->toContain('auth', 'access_level:superadmin');
    }
});

test('an existing permission schema can register enterprise capabilities with cached preceding-release configuration', function () {
    $names = config('enterprise.permissions');
    Permission::whereIn('name', $names)->delete();
    $legacy = Permission::findOrCreate('customer.legacy.reporting', 'web');
    $role = \Spatie\Permission\Models\Role::create(['name' => 'Existing customer role', 'guard_name' => 'web', 'group_name' => 'user']);
    $role->givePermissionTo($legacy);
    config(['enterprise.permissions' => []]);
    $migration = require database_path('migrations/2026_10_28_000001_register_enterprise_capabilities.php');
    $migration->up(); $migration->up();
    expect(Permission::whereIn('name', $names)->count())->toBe(count($names));
    expect(Permission::whereIn('name', $names)->pluck('group_name')->unique()->all())->toBe(['user']);
    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['customer.legacy.reporting']);
});

test('the Add-ons catalogue renders a useful locked screen without Portal access', function (string $brand) {
    \App\Models\SystemSetting::create(['package' => 'presidential', 'system_name' => 'Campaign Test', 'system_country' => 'Nigeria', 'dark_theme_color' => $brand]);
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $admin = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $response = $this->actingAs($admin)->get(route('superadmin.addons.index'))->assertOk()
        ->assertSee('Advanced Reports and AI Intelligence')->assertSee('SMS Messaging')->assertSee('Locked')->assertSee('Refresh licence status');
    if ($path = getenv('ENTERPRISE_BROWSER_OUTPUT')) {
        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname(base_path($path)));
        file_put_contents(base_path(str_replace('.html', '-'.ltrim($brand, '#').'.html', $path)), $response->getContent());
        // Exercise the real dashboard partial alongside the Add-ons page in browser regressions.
        $widgets = view('backend.shared.dashboard-metrics-cards', [
            'profileData' => $admin,
            'dashboardMetrics' => ['active_election' => ['uuid' => 'theme-fixture-election']],
            'expandedMetricsCollapsed' => false,
        ])->render();
        file_put_contents(base_path(str_replace('.html', '-widgets-'.ltrim($brand, '#').'.html', $path)), $widgets);
    }
})->with(['#008751', '#0d6efd', '#ffffff', '#ffc107', '#6f42c1', '#000000']);

test('changing the saved brand is reflected on the next page render', function () {
    $settings = \App\Models\SystemSetting::create(['package' => 'presidential', 'system_name' => 'Campaign Test', 'system_country' => 'Nigeria', 'dark_theme_color' => '#008751']);
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $admin = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $this->actingAs($admin)->get(route('superadmin.addons.index'))->assertOk()->assertSee('--cm-primary: #008751;', false);
    $settings->update(['dark_theme_color' => '#0d6efd']);
    $this->get(route('superadmin.addons.index'))->assertOk()->assertSee('--cm-primary: #0d6efd;', false)->assertDontSee('--cm-primary: #008751;', false);
});

test('ordinary administrators cannot access the Add-ons catalogue by URL', function (string $level) {
    \App\Models\SystemSetting::create(['package' => 'presidential', 'system_name' => 'Campaign Test', 'system_country' => 'Nigeria']);
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $this->actingAs(User::factory()->create(['access_level' => $level, 'status' => 'active']))
        ->get(route('superadmin.addons.index'))->assertForbidden();
    $this->post(route('superadmin.addons.refresh'))->assertForbidden();
})->with(['nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user']);

