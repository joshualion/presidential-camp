<?php

use App\Models\LocalLicense;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CampaignPackagePermissionService;
use App\Services\CampaignPackageRoleService;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function createRbacLicense(string $package, array $scope = []): LocalLicense
{
    $license = LocalLicense::create([
        'license_key' => 'CM-RBAC-'.strtoupper(str_replace('_', '-', $package)).'-'.Str::random(6),
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

function rbacActor(string $accessLevel = 'superadmin'): User
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

function createRbacSystemSetting(): SystemSetting
{
    return SystemSetting::create([
        'package' => 'governorship',
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'campaign_slogan' => 'Test campaign',
        'copyright' => null,
    ]);
}

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('governorship role management excludes presidential roles', function () {
    createRbacLicense('governorship', [
        'scope_type' => 'state',
        'state_id' => 9,
        'state_name' => 'Cross River',
        'scope_name' => 'Cross River',
    ]);
    Role::create(['name' => 'Presidential Candidate', 'guard_name' => 'web', 'group_name' => 'nationaladmin']);

    $roles = app(CampaignPackageRoleService::class)->manageableRoles(rbacActor())->pluck('name');

    expect($roles)->toContain('Governorship Candidate')
        ->and($roles)->toContain('State ICT Director')
        ->and($roles)->not->toContain('Presidential Candidate')
        ->and($roles)->not->toContain('Vice Presidential Candidate');
});

test('senatorial role management excludes presidential and governorship roles', function () {
    createRbacLicense('senatorial', [
        'scope_type' => 'senatorial_district',
        'senatorial_district_id' => 12,
        'senatorial_district_name' => 'Cross River South',
        'scope_name' => 'Cross River South',
    ]);

    $roles = app(CampaignPackageRoleService::class)->manageableRoles(rbacActor())->pluck('name');

    expect($roles)->toContain('Senatorial Candidate')
        ->and($roles)->toContain('Senatorial ICT Director')
        ->and($roles)->not->toContain('Presidential Candidate')
        ->and($roles)->not->toContain('Governorship Candidate');
});

test('lga role management includes chairmanship lga ward and polling unit roles', function () {
    createRbacLicense('lga', [
        'scope_type' => 'lga',
        'lga_id' => 44,
        'lga_name' => 'Calabar Municipal',
        'scope_name' => 'Calabar Municipal',
    ]);

    $roles = app(CampaignPackageRoleService::class)->manageableRoles(rbacActor())->pluck('name');

    expect($roles)->toContain('Chairmanship Candidate')
        ->and($roles)->toContain('LGA ICT Director')
        ->and($roles)->toContain('Ward Coordinator')
        ->and($roles)->toContain('Polling Unit Agent');
});

test('managed mode preserves broad role management visibility', function () {
    config(['campaign.deployment_mode' => 'managed']);
    Role::create(['name' => 'Presidential Candidate', 'guard_name' => 'web', 'group_name' => 'nationaladmin']);

    $roles = app(CampaignPackageRoleService::class)->manageableRoles(rbacActor())->pluck('name');

    expect(app(CampaignPackageRoleService::class)->usesPackageRoleMap())->toBeFalse()
        ->and($roles)->toContain('Presidential Candidate');
});

test('ict director has edit user capability while coordinator does not', function () {
    $service = app(CampaignPackagePermissionService::class);

    expect($service->roleHasCapability('State ICT Director', 'members.edit'))->toBeTrue()
        ->and($service->roleHasCapability('State Coordinator', 'members.edit'))->toBeFalse()
        ->and($service->roleHasCapability('LGA ICT Director', 'members.edit'))->toBeTrue()
        ->and($service->roleHasCapability('LGA Coordinator', 'members.edit'))->toBeFalse();
});

test('role management page uses package aware role list', function () {
    createRbacLicense('governorship', [
        'scope_type' => 'state',
        'state_id' => 9,
        'state_name' => 'Cross River',
        'scope_name' => 'Cross River',
    ]);
    Role::create(['name' => 'Presidential Candidate', 'guard_name' => 'web', 'group_name' => 'nationaladmin']);
    $actor = rbacActor();
    createRbacSystemSetting();

    $this->withoutMiddleware()
        ->actingAs($actor)
        ->get(route('superadmin.settings.roles'))
        ->assertOk()
        ->assertSee('Governorship Candidate')
        ->assertSee('Showing roles available')
        ->assertDontSee('Presidential Candidate');
});

test('out of package role permission assignment is rejected', function () {
    createRbacLicense('governorship', [
        'scope_type' => 'state',
        'state_id' => 9,
        'state_name' => 'Cross River',
        'scope_name' => 'Cross River',
    ]);
    $actor = rbacActor();
    $role = Role::create(['name' => 'Presidential Candidate', 'guard_name' => 'web', 'group_name' => 'nationaladmin']);
    $permission = Permission::create(['name' => 'manage national settings', 'guard_name' => 'web', 'group_name' => 'nationaladmin']);

    $this->withoutMiddleware()
        ->actingAs($actor)
        ->from(route('superadmin.settings.addrole.permission'))
        ->post(route('superadmin.settings.role.permission.store'), [
            'role_id' => $role->id,
            'permission' => [$permission->id],
        ])
        ->assertRedirect(route('superadmin.settings.addrole.permission'))
        ->assertSessionHasErrors('role');
});
