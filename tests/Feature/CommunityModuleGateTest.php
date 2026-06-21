<?php

use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\LocalLicense;
use App\Models\Religion;
use App\Models\SupportGroup;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CommunityRealtimeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function () {
    File::ensureDirectoryExists(storage_path('app'));
    File::put(storage_path('app/installed.lock'), json_encode(['deployment_mode' => 'self_hosted']));

    config([
        'app.url' => 'http://localhost',
        'campaign.deployment_mode' => 'self_hosted',
        'broadcasting.default' => 'log',
    ]);

    SystemSetting::query()->updateOrCreate(['id' => 1], [
        'package' => 'governorship',
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'frontend_community' => 0,
        'frontend_registration' => 0,
        'require_bank_details' => 0,
        'enable_local_storage' => 1,
        'enable_s3_storage' => 0,
    ]);

    Country::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Nigeria',
    ]);

    DB::table('regions')->updateOrInsert(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'South South',
        'country_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('states')->updateOrInsert(['id' => 9], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Cross River',
        'region_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('local_government_areas')->updateOrInsert(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Calabar Municipal',
        'state_id' => 9,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('wards')->updateOrInsert(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Ward One',
        'lga_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('polling_units')->updateOrInsert(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'PU One',
        'ward_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Religion::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Christianity',
    ]);

    AgeGrade::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Adult',
    ]);
});

afterEach(function () {
    if (File::exists(storage_path('app/installed.lock'))) {
        File::delete(storage_path('app/installed.lock'));
    }
});

function communityGateUser(): User
{
    $user = User::withoutEvents(fn () => User::factory()->create([
        'firstname' => 'Community',
        'lastname' => 'Tester',
        'username' => 'community_tester',
        'email' => 'community-tester@test.local',
        'phone' => '+2348000001111',
        'password' => Hash::make('secret-password'),
        'access_level' => 'superadmin',
        'status' => 'active',
        'requires_update' => false,
        'country_id' => 1,
        'region_id' => 1,
        'state_id' => 9,
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
        'religion_id' => 1,
        'age_grade_id' => 1,
    ]));

    $group = SupportGroup::query()->create(['name' => 'Community Volunteers']);
    $user->supportGroups()->sync([$group->id]);

    return $user;
}

function communityGateLicense(bool $communityEnabled = true): void
{
    $license = LocalLicense::query()->create([
        'license_key' => 'CM-COMMUNITY-GATE',
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
        'app_url' => 'http://localhost',
        'domain' => 'localhost',
        'activated_at' => now(),
        'last_checked_at' => now(),
    ]);

    $license->scope()->create([
        'scope_type' => 'state',
        'scope_name' => 'Cross River',
        'state_id' => 9,
        'state_name' => 'Cross River',
    ]);

    $license->modules()->createMany([
        ['module_key' => 'members', 'enabled' => true],
        ['module_key' => 'elections', 'enabled' => true],
        ['module_key' => 'community', 'enabled' => $communityEnabled],
    ]);
}

test('community disabled redirects browser access away from feed', function () {
    communityGateLicense(true);

    $response = $this->actingAs(communityGateUser())->get(route('timeline'));

    $response->assertRedirect(route('superadmin.dashboard'));
});

test('community enabled allows community endpoints', function () {
    communityGateLicense(true);
    SystemSetting::query()->whereKey(1)->update(['frontend_community' => 1]);

    $response = $this->actingAs(communityGateUser())->getJson(route('posts.fetch'));

    $response->assertOk();
});

test('community redirects users with incomplete voting location profile', function () {
    communityGateLicense(true);
    SystemSetting::query()->whereKey(1)->update(['frontend_community' => 1]);

    $user = User::withoutEvents(fn () => User::factory()->create([
        'firstname' => 'Incomplete',
        'lastname' => 'Location',
        'username' => 'incomplete_location',
        'email' => 'incomplete-location@test.local',
        'phone' => '+2348000002222',
        'password' => Hash::make('secret-password'),
        'access_level' => 'user',
        'status' => 'active',
        'requires_update' => false,
        'country_id' => 1,
        'region_id' => null,
        'state_id' => null,
        'lga_id' => null,
        'ward_id' => null,
        'polling_unit_id' => null,
        'religion_id' => 1,
        'age_grade_id' => 1,
    ]));
    $group = SupportGroup::query()->firstOrCreate(['name' => 'Community Volunteers']);
    $user->supportGroups()->sync([$group->id]);

    $response = $this->actingAs($user)->get(route('timeline'));

    $response->assertRedirect(route('user.profile'));
});

test('community ajax endpoint returns forbidden json when disabled', function () {
    communityGateLicense(true);

    $response = $this
        ->actingAs(communityGateUser())
        ->getJson(route('posts.fetch'));

    $response->assertForbidden()
        ->assertJson(['message' => 'Community Forum is currently disabled for this installation.']);
});

test('community module unavailable keeps service disabled even when setting is on', function () {
    communityGateLicense(false);
    SystemSetting::query()->whereKey(1)->update(['frontend_community' => 1]);

    $service = app(CommunityRealtimeService::class);

    expect($service->moduleAvailable())->toBeFalse()
        ->and($service->enabled())->toBeFalse();
});

test('self hosted license without community module keeps toggle available but disabled by default', function () {
    $license = LocalLicense::query()->create([
        'license_key' => 'CM-COMMUNITY-ABSENT',
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
        'app_url' => 'http://localhost',
        'domain' => 'localhost',
        'activated_at' => now(),
        'last_checked_at' => now(),
    ]);

    $license->scope()->create([
        'scope_type' => 'state',
        'scope_name' => 'Cross River',
        'state_id' => 9,
        'state_name' => 'Cross River',
    ]);

    $license->modules()->createMany([
        ['module_key' => 'members', 'enabled' => true],
        ['module_key' => 'elections', 'enabled' => true],
    ]);

    $service = app(CommunityRealtimeService::class);

    expect($service->moduleAvailable())->toBeTrue()
        ->and($service->enabled())->toBeFalse()
        ->and($service->mode())->toBe('polling');
});

test('explicit community true allows toggle availability', function () {
    communityGateLicense(true);

    expect(app(CommunityRealtimeService::class)->moduleAvailable())->toBeTrue();
});

test('backend topbar labels enabled frontend as community forum', function () {
    communityGateLicense(true);
    SystemSetting::query()->whereKey(1)->update(['frontend_community' => 1]);

    $this->actingAs(communityGateUser());

    $html = view('backend.template.navbar', [
        'SystemSetting' => SystemSetting::query()->first(),
    ])->render();

    expect($html)->toContain('Community Forum')
        ->and($html)->not->toContain('FRONTEND COMMUNITY')
        ->and($html)->not->toContain('Front-end Community');
});

test('community footer links show managed legal pages', function () {
    communityGateLicense(true);
    SystemSetting::query()->whereKey(1)->update([
        'frontend_community' => 1,
        'privacy_policy' => '<p>Managed privacy content.</p>',
        'tos' => '<p>Managed terms content.</p>',
        'disclaimer' => '<p>Managed disclaimer content.</p>',
    ]);

    $user = communityGateUser();

    $this->actingAs($user)
        ->get(route('community.legal', ['page' => 'privacy-policy']))
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee('Managed privacy content.', false)
        ->assertSee(route('community.legal', ['page' => 'terms-and-conditions']), false)
        ->assertSee(route('community.legal', ['page' => 'disclaimer']), false)
        ->assertDontSee('<a href="#" class="text-white" style="text-decoration: none;">Privacy Policy</a>', false);

    $this->actingAs($user)
        ->get(route('community.legal', ['page' => 'terms-and-conditions']))
        ->assertOk()
        ->assertSee('Managed terms content.', false);

    $this->actingAs($user)
        ->get(route('community.legal', ['page' => 'disclaimer']))
        ->assertOk()
        ->assertSee('Managed disclaimer content.', false);
});

test('community middleware fails safely when settings table is unavailable', function () {
    communityGateLicense(true);
    Schema::dropIfExists('system_settings');

    expect(app(CommunityRealtimeService::class)->enabled())->toBeFalse();
});

test('community realtime service defaults to polling without reverb config', function () {
    config(['broadcasting.default' => 'log']);

    expect(app(CommunityRealtimeService::class)->mode())->toBe('polling')
        ->and(app(CommunityRealtimeService::class)->realtimeAvailable())->toBeFalse();
});

test('community realtime service reports realtime only with complete reverb config', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'key',
        'broadcasting.connections.reverb.secret' => 'secret',
        'broadcasting.connections.reverb.app_id' => 'app',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
    ]);

    expect(app(CommunityRealtimeService::class)->mode())->toBe('realtime')
        ->and(app(CommunityRealtimeService::class)->realtimeAvailable())->toBeTrue();
});
