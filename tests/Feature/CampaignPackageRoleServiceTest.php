<?php

use App\Models\LocalLicense;
use App\Models\User;
use App\Services\CampaignPackageRoleService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function createRoleLicense(string $package, array $scope = []): LocalLicense
{
    $license = LocalLicense::create([
        'license_key' => 'CM-ROLE-'.strtoupper(str_replace('_', '-', $package)),
        'deployment_mode' => 'self_hosted',
        'package_type' => $package,
        'status' => 'active',
    ]);

    $license->scope()->create(array_merge([
        'scope_type' => 'national',
        'scope_name' => 'Nigeria',
    ], $scope));

    return $license;
}

function roleActor(string $accessLevel = 'superadmin'): User
{
    return User::factory()->create([
        'access_level' => $accessLevel,
        'country_id' => null,
        'region_id' => null,
        'state_id' => null,
        'lga_id' => null,
        'ward_id' => null,
        'polling_unit_id' => null,
        'religion_id' => null,
        'age_grade_id' => null,
        'requires_update' => false,
    ]);
}

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('presidential package includes national roles', function () {
    createRoleLicense('presidential');

    $service = app(CampaignPackageRoleService::class);
    $actor = roleActor();

    $roles = $service->rolesForAccessLevel($actor, 'nationaladmin')->pluck('name');

    expect($roles)->toContain('Presidential Candidate')
        ->and($roles)->toContain('Vice Presidential Candidate')
        ->and($roles)->toContain('National ICT Director')
        ->and($service->assignableAccessLevels($actor))->toHaveKeys(['superadmin', 'nationaladmin', 'regionaladmin', 'stateadmin']);
});

test('governorship package limits access levels and excludes presidential roles', function () {
    createRoleLicense('governorship', [
        'scope_type' => 'state',
        'state_id' => 9,
        'state_name' => 'Cross River',
        'scope_name' => 'Cross River',
    ]);

    $service = app(CampaignPackageRoleService::class);
    $actor = roleActor();

    $accessLevels = $service->assignableAccessLevels($actor);
    $roles = $service->rolesForAccessLevel($actor, 'stateadmin')->pluck('name');

    expect($accessLevels)->toHaveKeys(['superadmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'])
        ->and($accessLevels)->not->toHaveKey('nationaladmin')
        ->and($accessLevels)->not->toHaveKey('regionaladmin')
        ->and($roles)->toContain('Governorship Candidate')
        ->and($roles)->toContain('State ICT Director')
        ->and($roles)->not->toContain('Presidential Candidate')
        ->and($service->canAssignAccessLevel($actor, 'nationaladmin'))->toBeFalse();
});

test('senatorial package returns senatorial roles and rejects national role selection', function () {
    createRoleLicense('senatorial', [
        'scope_type' => 'senatorial_district',
        'senatorial_district_id' => 12,
        'senatorial_district_name' => 'Cross River South',
        'scope_name' => 'Cross River South',
    ]);

    $service = app(CampaignPackageRoleService::class);
    $actor = roleActor();

    $roles = $service->rolesForAccessLevel($actor, 'senatorialadmin')->pluck('name');
    $lgaRoles = $service->rolesForAccessLevel($actor, 'lgaadmin')->pluck('name');
    $wardRoles = $service->rolesForAccessLevel($actor, 'wardadmin')->pluck('name');
    $puRoles = $service->rolesForAccessLevel($actor, 'puadmin')->pluck('name');
    $nationalRole = Role::create([
        'name' => 'Presidential Candidate',
        'guard_name' => 'web',
        'group_name' => 'nationaladmin',
    ]);

    expect($service->assignableAccessLevels($actor))->toHaveKeys(['superadmin', 'senatorialadmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'])
        ->and($service->assignableAccessLevels($actor))->not->toHaveKey('nationaladmin')
        ->and($roles)->toContain('Senatorial Candidate')
        ->and($roles)->toContain('Senatorial ICT Director')
        ->and($roles)->not->toContain('Presidential Candidate')
        ->and($lgaRoles)->toContain('LGA Coordinator')
        ->and($wardRoles)->toContain('Ward Coordinator')
        ->and($puRoles)->toContain('Polling Unit Agent')
        ->and($service->canAssignRole($actor, $nationalRole, 'nationaladmin'))->toBeFalse();
});

test('lga package returns chairmanship and ward polling unit roles', function () {
    createRoleLicense('lga', [
        'scope_type' => 'lga',
        'lga_id' => 44,
        'lga_name' => 'Calabar Municipal',
        'scope_name' => 'Calabar Municipal',
    ]);

    $service = app(CampaignPackageRoleService::class);
    $actor = roleActor();

    expect($service->assignableAccessLevels($actor))->toHaveKeys(['superadmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'])
        ->and($service->assignableAccessLevels($actor))->not->toHaveKey('stateadmin')
        ->and($service->rolesForAccessLevel($actor, 'lgaadmin')->pluck('name'))->toContain('Chairmanship Candidate')
        ->and($service->rolesForAccessLevel($actor, 'wardadmin')->pluck('name'))->toContain('Ward Coordinator')
        ->and($service->rolesForAccessLevel($actor, 'puadmin')->pluck('name'))->toContain('Polling Unit Agent');
});

test('managed mode preserves broad table based role behavior', function () {
    config(['campaign.deployment_mode' => 'managed']);

    Role::create([
        'name' => 'Presidential Candidate',
        'guard_name' => 'web',
        'group_name' => 'nationaladmin',
    ]);

    $service = app(CampaignPackageRoleService::class);
    $actor = roleActor();

    expect($service->usesPackageRoleMap())->toBeFalse()
        ->and($service->assignableAccessLevels($actor))->toHaveKeys(['superadmin', 'nationaladmin', 'regionaladmin', 'stateadmin'])
        ->and($service->rolesForAccessLevel($actor, 'nationaladmin')->pluck('name'))->toContain('Presidential Candidate');
});

test('role ajax endpoint returns package aware roles', function () {
    createRoleLicense('senatorial', [
        'scope_type' => 'senatorial_district',
        'senatorial_district_id' => 12,
        'senatorial_district_name' => 'Cross River South',
        'scope_name' => 'Cross River South',
    ]);

    $actor = roleActor();

    $this->actingAs($actor)
        ->getJson(route('ajaxGetRolesByAccessLevel', ['accessLevel' => 'senatorialadmin']))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Senatorial Candidate'])
        ->assertJsonMissing(['name' => 'Presidential Candidate']);
});

test('member store rejects out of package access level', function () {
    createRoleLicense('governorship', [
        'scope_type' => 'state',
        'state_id' => 9,
        'state_name' => 'Cross River',
        'scope_name' => 'Cross River',
    ]);

    $actor = roleActor();
    $role = Role::create([
        'name' => 'Presidential Candidate',
        'guard_name' => 'web',
        'group_name' => 'nationaladmin',
    ]);

    $this->withoutMiddleware()
        ->actingAs($actor)
        ->from('/superadmin/member/add')
        ->post(route('superadmin.member.store'), [
            'username' => 'out-of-package-admin',
            'firstname' => 'Out',
            'lastname' => 'Package',
            'email' => 'out-of-package-admin@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'access_level' => 'nationaladmin',
            'gender' => 'male',
            'roles' => $role->id,
        ])
        ->assertRedirect('/superadmin/member/add')
        ->assertSessionHasErrors('access_level');
});
