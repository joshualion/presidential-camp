<?php

use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\LocalLicense;
use App\Models\Region;
use App\Models\Religion;
use App\Models\SupportGroup;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    File::ensureDirectoryExists(storage_path('app'));
    File::put(storage_path('app/installed.lock'), json_encode(['deployment_mode' => 'self_hosted']));
});

afterEach(function () {
    if (File::exists(storage_path('app/installed.lock'))) {
        File::delete(storage_path('app/installed.lock'));
    }
});

function activationBypassReferenceRows(): void
{
    SystemSetting::query()->updateOrCreate(['id' => 1], [
        'package' => 'governorship',
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'frontend_community' => 0,
        'require_bank_details' => 0,
        'enable_local_storage' => 1,
        'enable_s3_storage' => 0,
        'activation_code' => null,
        'domain' => null,
        'api_token' => null,
        'activated_at' => null,
    ]);

    Country::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Nigeria',
    ]);

    Region::query()->updateOrCreate(['id' => 1], [
        'name' => 'South South',
        'country_id' => 1,
    ]);

    Religion::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Christianity',
    ]);

    AgeGrade::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Adult',
    ]);
}

function activationBypassUser(array $attributes = []): User
{
    $user = User::withoutEvents(fn () => User::factory()->create(array_merge([
        'firstname' => 'Self',
        'lastname' => 'Hosted',
        'username' => 'self_hosted_superadmin',
        'email' => 'self-hosted-superadmin@example.test',
        'phone' => '+2348099001100',
        'password' => Hash::make('secret-password'),
        'access_level' => 'superadmin',
        'status' => 'active',
        'requires_update' => false,
        'country_id' => null,
        'region_id' => null,
        'state_id' => null,
        'lga_id' => null,
        'ward_id' => null,
        'polling_unit_id' => null,
        'religion_id' => 1,
        'age_grade_id' => 1,
    ], $attributes)));

    $group = SupportGroup::query()->create(['name' => 'Default Volunteers']);
    $user->supportGroups()->sync([$group->id]);

    return $user;
}

function activationBypassLocalLicense(): LocalLicense
{
    $license = LocalLicense::query()->create([
        'license_key' => 'CM-INSTALLER-ACTIVE',
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
        ['module_key' => 'elections', 'enabled' => true],
        ['module_key' => 'members', 'enabled' => true],
    ]);

    return $license;
}

test('self hosted installed active local license reaches superadmin dashboard without legacy activation redirect', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    activationBypassReferenceRows();
    activationBypassLocalLicense();
    $user = activationBypassUser();

    $this->actingAs($user)
        ->get('/superadmin/dashboard')
        ->assertOk();
});

test('self hosted installed active local license redirects activation page to license status', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    activationBypassReferenceRows();
    activationBypassLocalLicense();
    $user = activationBypassUser();

    $this->actingAs($user)
        ->get('/superadmin/activate')
        ->assertRedirect(route('admin.license.show', absolute: false));
});

test('profile completion redirects superadmin to dashboard not legacy activation page', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    activationBypassReferenceRows();
    activationBypassLocalLicense();
    $user = activationBypassUser([
        'phone' => '+2348099002200',
    ]);

    $this->actingAs($user)
        ->post('/complete-profile', [
            'firstname' => 'Cross',
            'lastname' => 'River',
            'email' => 'cross-river-superadmin@example.test',
            'phone' => '+2348099003300',
            'validvoter' => 'yes',
            'age_grade_id' => 1,
            'gender' => 'male',
            'religion_id' => 1,
        ])
        ->assertRedirect(route('superadmin.dashboard', absolute: false));
});

test('self hosted installed missing local license redirects activation page to modern license status', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    activationBypassReferenceRows();
    $user = activationBypassUser();

    $this->actingAs($user)
        ->get('/superadmin/activate')
        ->assertRedirect(route('admin.license.show', absolute: false));
});

test('managed legacy activation form remains available when no local license exists', function () {
    config(['campaign.deployment_mode' => 'managed']);
    if (File::exists(storage_path('app/installed.lock'))) {
        File::delete(storage_path('app/installed.lock'));
    }
    activationBypassReferenceRows();
    $user = activationBypassUser();

    $this->actingAs($user)
        ->get('/superadmin/activate')
        ->assertOk()
        ->assertSee('Activation Code');
});

test('admin license screen works after installer activation', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    activationBypassReferenceRows();
    activationBypassLocalLicense();
    $user = activationBypassUser();

    $this->actingAs($user)
        ->get('/admin/license')
        ->assertOk()
        ->assertSee('This installation was activated during setup')
        ->assertSee('governorship')
        ->assertSee('Cross River');
});
