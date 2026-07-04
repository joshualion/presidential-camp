<?php

use App\Http\Middleware\CheckUserOnboarding;
use App\Http\Controllers\ElectionController;
use App\Models\LocalLicense;
use App\Models\User;
use App\Services\LocationScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\SystemSetting;
use App\Http\Middleware\KuyakEyenIno;
use App\Http\Middleware\ValidateLicense;

function containmentTestUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'country_id' => null,
        'region_id' => null,
        'state_id' => null,
        'senatorial_district_id' => null,
        'federal_constituency_id' => null,
        'lga_id' => null,
        'ward_id' => null,
        'polling_unit_id' => null,
        'religion_id' => null,
        'age_grade_id' => null,
    ], $attributes));
}

test('inactive account cannot authenticate', function () {
    $user = containmentTestUser([
        'status' => 'inactive',
        'password' => bcrypt('secret-password'),
    ]);

    $this->post('/login', [
        'login' => $user->email,
        'password' => 'secret-password',
    ]);

    $this->assertGuest();
});

test('an existing inactive session is terminated', function () {
    $user = containmentTestUser(['status' => 'inactive']);

    $this->actingAs($user)
        ->get('/login')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('member without polling unit receives no location scoped users', function () {
    $member = containmentTestUser([
        'access_level' => 'user',
        'polling_unit_id' => null,
    ]);

    $query = User::query();
    app(LocationScopeService::class)->applyScope($query, $member, 'users', 'users');

    expect($query->count())->toBe(0);
});

test('member onboarding requires a polling unit assignment', function () {
    $member = containmentTestUser([
        'access_level' => 'user',
        'firstname' => 'Member',
        'lastname' => 'Without Scope',
        'email' => 'member-without-scope@campaign.test',
        'phone' => '+2348000000001',
        'polling_unit_id' => null,
        'requires_update' => false,
        'password' => bcrypt('secret-password'),
    ]);

    $this->actingAs($member);
    $request = Request::create('/messages', 'GET');
    $request->setUserResolver(fn () => $member);

    $response = app(CheckUserOnboarding::class)->handle($request, fn () => response('allowed'));

    expect($response->isRedirect(route('user.account.complete-profile')))->toBeTrue();
});

test('hidden location-dependent and planned member routes fail closed on direct access', function () {
    SystemSetting::query()->updateOrCreate(['id' => 1], [
        'package' => 'governorship',
        'frontend_registration' => 1,
    ]);
    $member = containmentTestUser([
        'access_level' => 'user',
        'status' => 'active',
        'polling_unit_id' => null,
    ]);

    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);

    foreach (['user.messages.page', 'user.block', 'user.referrals', 'user.notices', 'user.pu.details'] as $routeName) {
        $this->actingAs($member)->get(route($routeName))->assertForbidden();
    }
});

test('member and shared participation routes carry containment middleware', function () {
    $memberProfileMiddleware = Route::getRoutes()->getByName('user.profile')->gatherMiddleware();
    $agentRequestMiddleware = Route::getRoutes()->getByName('user.agents.request')->gatherMiddleware();
    $electionChartMiddleware = Route::getRoutes()->getByName('election.chart.data')->gatherMiddleware();
    $locationMiddleware = Route::getRoutes()->getByName('getPollingUnits')->gatherMiddleware();
    $groupRecipientMiddleware = Route::getRoutes()->getByName('getGroupRecipients')->gatherMiddleware();

    expect($memberProfileMiddleware)->toContain('access_level:user')
        ->and($memberProfileMiddleware)->toContain('check_onboarding')
        ->and($agentRequestMiddleware)->toContain('check_onboarding')
        ->and($agentRequestMiddleware)->toContain('module.enabled:agents')
        ->and($electionChartMiddleware)->toContain('auth')
        ->and($electionChartMiddleware)->toContain('check_onboarding')
        ->and($electionChartMiddleware)->toContain('module.enabled:elections')
        ->and($locationMiddleware)->toContain('auth')
        ->and($groupRecipientMiddleware)->toContain('auth')
        ->and($groupRecipientMiddleware)->toContain('access_level:superadmin,nationaladmin,regionaladmin,stateadmin,senatorialadmin,federaladmin,lgaadmin,wardadmin,puadmin');
});

test('election controller is unavailable when elections module is disabled', function () {
    $license = LocalLicense::query()->create([
        'license_key' => 'CM-PHASE-ONE-ELECTIONS',
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
    ]);

    $license->modules()->create([
        'module_key' => 'elections',
        'enabled' => false,
    ]);

    expect(fn () => app(ElectionController::class))->toThrow(HttpException::class);
});
