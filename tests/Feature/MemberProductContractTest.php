<?php

use App\Models\LocalLicense;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\MemberCapabilityService;
use App\Services\PublicRegistrationService;
use App\Services\AccessLevelRouteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

function contractMember(array $attributes = []): User
{
    return (new User())->forceFill(array_merge([
        'id' => 100,
        'firstname' => 'Contract',
        'lastname' => 'Member',
        'access_level' => 'user',
        'status' => 'active',
        'polling_unit_id' => 10,
    ], $attributes));
}

function contractPublicRegistration(bool $enabled, string $package = 'governorship'): void
{
    SystemSetting::query()->updateOrCreate(['id' => 1], [
        'package' => $package,
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'frontend_community' => 0,
        'frontend_registration' => $enabled,
        'require_bank_details' => 0,
        'enable_local_storage' => 1,
        'enable_s3_storage' => 0,
    ]);
}

test('member product contract declares current and planned capabilities', function () {
    contractPublicRegistration(true);
    $service = app(MemberCapabilityService::class);
    $statuses = collect($service->catalog())->pluck('status')->unique()->sort()->values()->all();

    expect($statuses)->toBe(['available', 'planned'])
        ->and($service->allows(contractMember(), MemberCapabilityService::DASHBOARD))->toBeTrue()
        ->and($service->allows(contractMember(), MemberCapabilityService::VOTING_BLOCK))->toBeTrue()
        ->and($service->allows(contractMember(), MemberCapabilityService::REFERRALS))->toBeTrue()
        ->and($service->allows(contractMember(), MemberCapabilityService::NOTICE_BOARD))->toBeTrue()
        ->and($service->allows(contractMember(), MemberCapabilityService::ELECTION_REPORTS))->toBeTrue()
        ->and($service->allows(contractMember(), MemberCapabilityService::ELECTION_SUBMISSION))->toBeFalse();
});

test('member location-dependent capabilities behave consistently for every supported package', function (string $package) {
    contractPublicRegistration(true, $package);
    $service = app(MemberCapabilityService::class);
    $complete = contractMember();
    $missingLocation = contractMember(['polling_unit_id' => null]);

    foreach ([
        MemberCapabilityService::MESSAGING,
        MemberCapabilityService::VOTING_BLOCK,
        MemberCapabilityService::REFERRALS,
        MemberCapabilityService::NOTICE_BOARD,
    ] as $capability) {
        expect($service->allows($complete, $capability))->toBeTrue(
            "{$capability} should be available for {$package} with a polling unit."
        );
        expect($service->allows($missingLocation, $capability))->toBeFalse(
            "{$capability} must fail closed for {$package} without a polling unit."
        );
    }
})->with([
    'presidential' => 'presidential',
    'governorship' => 'governorship',
    'senatorial' => 'senatorial',
    'federal constituency' => 'federal_constituency',
    'chairmanship' => 'chairmanship',
]);

test('missing member location hides protected facilities from navigation', function () {
    contractPublicRegistration(true);
    $html = View::make('backend.template.userMenu', [
        'profileData' => contractMember(['polling_unit_id' => null]),
    ])->render();

    expect($html)->not->toContain('Internal Communication')
        ->and($html)->not->toContain('My Voting Bloc')
        ->and($html)->not->toContain('Direct Referrals')
        ->and($html)->not->toContain('Notice Board')
        ->and($html)->not->toContain('Election Results')
        ->and($html)->not->toContain('Polling Unit Details');
});

test('every member facility route declares its matching capability middleware', function () {
    $routes = [
        'user.dashboard' => MemberCapabilityService::DASHBOARD,
        'user.profile' => MemberCapabilityService::PROFILE,
        'user.change.password' => MemberCapabilityService::ACCOUNT_SECURITY,
        'user.campaign.notifications.index' => MemberCapabilityService::NOTIFICATIONS,
        'user.messages.page' => MemberCapabilityService::MESSAGING,
        'timeline' => MemberCapabilityService::COMMUNITY,
        'user.agents.index' => MemberCapabilityService::AGENTS,
        'user.block' => MemberCapabilityService::VOTING_BLOCK,
        'user.referrals' => MemberCapabilityService::REFERRALS,
        'user.pu.details' => MemberCapabilityService::POLLING_UNIT_DETAILS,
        'user.notices' => MemberCapabilityService::NOTICE_BOARD,
        'user.elections' => MemberCapabilityService::ELECTION_REPORTS,
        'user.elections.workspace' => MemberCapabilityService::ELECTION_SUBMISSION,
    ];

    foreach ($routes as $routeName => $capability) {
        expect(Route::getRoutes()->getByName($routeName))->not->toBeNull()
            ->and(Route::getRoutes()->getByName($routeName)->gatherMiddleware())
            ->toContain('member.capability:'.$capability);
    }
});

test('member capability contract enforces location and licensed module requirements', function () {
    contractPublicRegistration(true);
    $license = LocalLicense::query()->create([
        'license_key' => 'CM-MEMBER-CONTRACT',
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
    ]);

    $module = $license->modules()->create([
        'module_key' => 'agents',
        'enabled' => false,
    ]);

    $service = app(MemberCapabilityService::class);

    expect($service->allows(contractMember(), MemberCapabilityService::AGENTS))->toBeFalse()
        ->and($service->allows(contractMember(['polling_unit_id' => null]), MemberCapabilityService::MESSAGING))->toBeFalse()
        ->and($service->allows(contractMember(['polling_unit_id' => null]), MemberCapabilityService::VOTING_BLOCK))->toBeFalse()
        ->and($service->allows(contractMember(['polling_unit_id' => null]), MemberCapabilityService::REFERRALS))->toBeFalse();

    $module->update(['enabled' => true]);

    expect($service->allows(contractMember(), MemberCapabilityService::AGENTS))->toBeTrue();
});

test('community member capability requires both license and runtime setting', function () {
    $license = LocalLicense::query()->create([
        'license_key' => 'CM-MEMBER-COMMUNITY',
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
    ]);
    $license->modules()->create(['module_key' => 'community', 'enabled' => true]);

    SystemSetting::query()->updateOrCreate(['id' => 1], [
        'package' => 'governorship',
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'frontend_community' => 0,
        'require_bank_details' => 0,
        'enable_local_storage' => 1,
        'enable_s3_storage' => 0,
    ]);

    $service = app(MemberCapabilityService::class);
    expect($service->allows(contractMember(), MemberCapabilityService::COMMUNITY))->toBeFalse();

    SystemSetting::query()->whereKey(1)->update(['frontend_community' => 1]);

    expect($service->allows(contractMember(), MemberCapabilityService::COMMUNITY))->toBeTrue();
});

test('member facing routes use the executable capability contract', function () {
    expect(Route::getRoutes()->getByName('user.dashboard')->uri())->toBe('member/dashboard')
        ->and(Route::getRoutes()->getByName('dashboard')->uri())->toBe('dashboard')
        ->and(Route::getRoutes()->getByName('user.dashboard')->gatherMiddleware())
        ->toContain('member.capability:dashboard.view')
        ->and(Route::getRoutes()->getByName('user.referrals')->gatherMiddleware())
        ->toContain('member.capability:referrals.view')
        ->and(Route::getRoutes()->getByName('user.block')->gatherMiddleware())
        ->toContain('member.capability:voting_block.view')
        ->and(Route::getRoutes()->getByName('user.notices')->gatherMiddleware())
        ->toContain('member.capability:announcements.board')
        ->and(Route::getRoutes()->getByName('user.messages.page')->uri())->toBe('member/messages')
        ->and(Route::getRoutes()->getByName('user.messages.page')->gatherMiddleware())
        ->toContain('member.capability:messaging.use')
        ->and(Route::getRoutes()->getByName('user.campaign.notifications.index')->uri())->toBe('member/notifications')
        ->and(Route::getRoutes()->getByName('stateadmin.campaign.notifications.index')->uri())->toBe('state/notifications')
        ->and(Route::getRoutes()->getByName('user.account.complete-profile')->uri())->toBe('member/complete-profile')
        ->and(Route::getRoutes()->getByName('stateadmin.account.update-credentials')->uri())->toBe('state/update-credentials')
        ->and(Route::getRoutes()->getByName('timeline')->gatherMiddleware())
        ->toContain('member.capability:community.use')
        ->and(Route::getRoutes()->getByName('election.report.print')->gatherMiddleware())
        ->toContain('election.report.access')
        ->and(Route::getRoutes()->getByName('register')->gatherMiddleware())
        ->toContain('public.registration');
});

test('dashboard routing maps every access level to its canonical named route and prefix', function () {
    $routes = app(AccessLevelRouteService::class);

    expect($routes->prefixFor('user'))->toBe('member')
        ->and($routes->dashboardRouteFor('user'))->toBe('user.dashboard')
        ->and($routes->prefixFor('stateadmin'))->toBe('state')
        ->and($routes->dashboardRouteFor('stateadmin'))->toBe('stateadmin.dashboard')
        ->and($routes->dashboardRouteFor('unknown'))->toBeNull();
});

test('member menu exposes available facilities and hides planned facilities', function () {
    contractPublicRegistration(true);
    $html = View::make('backend.template.userMenu', [
        'profileData' => contractMember(),
    ])->render();

    expect($html)->toContain('Dashboard')
        ->and($html)->toContain('Internal Communication')
        ->and($html)->toContain('Manage Profile')
        ->and($html)->toContain('My Voting Bloc')
        ->and($html)->toContain('Direct Referrals')
        ->and($html)->toContain('Notice Board')
        ->and($html)->toContain('Election Results')
        ->and($html)->not->toContain('Feature coming soon')
        ->and($html)->not->toContain('/pu/elections');
});

test('superadmin public registration setting controls voting bloc and referral facilities', function () {
    contractPublicRegistration(false);
    $capabilities = app(MemberCapabilityService::class);
    $registration = app(PublicRegistrationService::class);
    $member = contractMember();

    $closedMenu = View::make('backend.template.userMenu', ['profileData' => $member])->render();

    expect($registration->enabled())->toBeFalse()
        ->and($capabilities->allows($member, MemberCapabilityService::VOTING_BLOCK))->toBeFalse()
        ->and($capabilities->allows($member, MemberCapabilityService::REFERRALS))->toBeFalse()
        ->and($closedMenu)->not->toContain('My Voting Bloc')
        ->and($closedMenu)->not->toContain('Direct Referrals');

    DB::table('system_settings')->where('id', 1)->update(['frontend_registration' => 1]);
    $openMenu = View::make('backend.template.userMenu', ['profileData' => $member])->render();

    expect($registration->enabled())->toBeTrue()
        ->and($capabilities->allows($member, MemberCapabilityService::VOTING_BLOCK))->toBeTrue()
        ->and($capabilities->allows($member, MemberCapabilityService::REFERRALS))->toBeTrue()
        ->and($openMenu)->toContain('My Voting Bloc')
        ->and($openMenu)->toContain('Direct Referrals');
});
