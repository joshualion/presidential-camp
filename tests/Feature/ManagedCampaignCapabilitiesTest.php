<?php

require_once __DIR__.'/../Support/V120Fixtures.php';

use App\Models\User;
use App\Services\CampaignCapabilityService;
use App\Services\CampaignPackageRoleService;
use App\Services\LocationScopeService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    \App\Models\SystemSetting::create(['package' => 'presidential', 'system_name' => 'Capability Test', 'system_country' => 'Nigeria']);
});

test('all CRUD combinations are independent for a managed custom role', function (int $mask, string $module) {
    $user = User::factory()->create(['access_level' => 'stateadmin']);
    $role = Role::create(['name' => 'Custom operator '.$mask, 'guard_name' => 'web', 'group_name' => 'user', 'capabilities_managed' => true]);
    $names = array_map(fn ($action) => $module.'.'.$action, ['view', 'create', 'update', 'delete']);
    foreach ($names as $index => $name) {
        if ($mask & (1 << $index)) { $role->givePermissionTo(Permission::findOrCreate($name, 'web')); }
    }
    $user->assignRole($role);
    foreach ($names as $index => $name) {
        expect(app(CampaignCapabilityService::class)->allows($user, $name))->toBe((bool) ($mask & (1 << $index)));
        expect($user->can($name))->toBe((bool) ($mask & (1 << $index)));
        expect($user->hasPermissionTo($name))->toBe((bool) ($mask & (1 << $index)));
    }
})->with(range(0, 15))->with(['members', 'election.results', 'incidents']);

test('individual restriction defeats role and direct grants and exposes its source', function () {
    $user = User::factory()->create(['access_level' => 'wardadmin']);
    $user->forceFill(['capabilities_managed' => true])->save();
    $user->givePermissionTo(Permission::findOrCreate('members.update', 'web'));
    DB::table('campaign_permission_restrictions')->insert(['user_id' => $user->id, 'permission_name' => 'members.update']);
    $decision = app(CampaignCapabilityService::class)->decision($user, 'members.update');
    expect($decision)->toBe(['allowed' => false, 'reason' => 'Individual restriction']);
    expect($user->can('members.update'))->toBeFalse()->and($user->can('members.edit'))->toBeFalse();
});

test('every access level retains geography despite capability grants', function (string $level) {
    $own = v120Location('own'); $other = v120Location('other');
    $actor = User::factory()->create(array_merge($own, ['access_level' => $level]));
    $target = User::factory()->create(array_merge($other, ['access_level' => 'user']));
    $actor->forceFill(['capabilities_managed' => true])->save();
    $actor->givePermissionTo(Permission::findOrCreate('members.view', 'web'));
    $query = User::whereKey($target->id);
    app(LocationScopeService::class)->applyScope($query, $actor, 'users', 'users');
    expect($query->exists())->toBe(in_array($level, ['superadmin', 'nationaladmin'], true));
})->with(['superadmin', 'nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user']);

test('every non Super Admin is denied role management despite a direct management grant', function (string $level) {
    $user = User::factory()->create(['access_level' => $level]);
    $user->givePermissionTo(Permission::findOrCreate('authorization.manage', 'web'));
    expect(app(CampaignCapabilityService::class)->allows($user, 'authorization.manage'))->toBeFalse();
    $this->actingAs($user)->get(route('superadmin.authorization.index'))->assertForbidden();
    $this->post(route('superadmin.authorization.roles.store'), ['name' => 'Escalation', 'permissions' => ['members.delete']])->assertForbidden();
    $this->put(route('superadmin.authorization.users.update', $user), ['roles' => [], 'grants' => ['members.delete'], 'restrictions' => []])->assertForbidden();
    $this->post(route('superadmin.authorization.defaults'), [])->assertForbidden();
})->with(['nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user']);

test('Super Admin assigns a reusable role and overrides without changing access level or location', function () {
    $admin = User::factory()->create(['access_level' => 'superadmin']);
    $user = User::factory()->create(['access_level' => 'lgaadmin']);
    $role = Role::create(['name' => 'Results reader', 'guard_name' => 'web', 'group_name' => 'user', 'capabilities_managed' => true]);
    $role->givePermissionTo(Permission::findOrCreate('election.results.view', 'web'));
    $before = $user->only(['access_level', 'region_id', 'state_id', 'lga_id', 'ward_id', 'polling_unit_id']);
    $this->actingAs($admin)->put(route('superadmin.authorization.users.update', $user), [
        'roles' => [$role->id], 'grants' => ['incidents.view'], 'restrictions' => ['election.results.delete'],
    ])->assertRedirect();
    $user = $user->fresh();
    expect($user->only(array_keys($before)))->toBe($before)->and($user->can('election.results.view'))->toBeTrue()
        ->and($user->can('incidents.view'))->toBeTrue()->and($user->can('election.results.create'))->toBeFalse();
    $this->put(route('superadmin.authorization.users.update', $user), [
        'roles' => [], 'grants' => [], 'restrictions' => [], 'access_level' => 'superadmin',
    ])->assertUnprocessable();
});

test('other administrators can apply only the configured default role during creation', function () {
    $actor = User::factory()->create(['access_level' => 'nationaladmin']);
    $default = Role::create(['name' => 'Safe field operator', 'guard_name' => 'web', 'group_name' => 'user', 'capabilities_managed' => true]);
    $privileged = Role::create(['name' => 'Privileged operator', 'guard_name' => 'web', 'group_name' => 'user', 'capabilities_managed' => true]);
    DB::table('campaign_default_roles')->insert(['access_level' => 'wardadmin', 'role_id' => $default->id]);
    $service = app(CampaignPackageRoleService::class);
    expect($service->canAssignRole($actor, $default, 'wardadmin'))->toBeTrue()
        ->and($service->canAssignRole($actor, $privileged, 'wardadmin'))->toBeFalse()
        ->and($service->canAssignRole($actor, $privileged, 'superadmin'))->toBeFalse();
});

test('migration is additive idempotent and preserves customer roles and permissions', function () {
    $role = Role::create(['name' => 'Customer legacy role', 'guard_name' => 'web', 'group_name' => 'stateadmin']);
    $permission = Permission::findOrCreate('customer.specific', 'web'); $role->givePermissionTo($permission);
    $migration = require database_path('migrations/2026_10_25_000001_add_managed_campaign_capabilities.php');
    $migration->up(); $migration->up();
    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['customer.specific'])
        ->and((bool) $role->fresh()->capabilities_managed)->toBeFalse();
});

test('Super Admin operational capabilities can be restricted while authorization recovery remains available', function () {
    $user = User::factory()->create(['access_level' => 'superadmin']);
    foreach (['members.delete', 'authorization.manage'] as $name) {
        DB::table('campaign_permission_restrictions')->insert(['user_id' => $user->id, 'permission_name' => $name]);
    }
    expect($user->can('members.delete'))->toBeFalse()->and($user->can('authorization.manage'))->toBeTrue();
});

test('read-only custom role cannot invoke member mutations exports or Ajax list endpoints for other modules', function () {
    $user = User::factory()->create(['access_level' => 'nationaladmin']);
    $user->forceFill(['capabilities_managed' => true])->save();
    $user->givePermissionTo(Permission::findOrCreate('members.view', 'web'));
    $this->actingAs($user)->post(route('nationaladmin.member.store'), [])->assertForbidden();
    $this->put(route('nationaladmin.member.update'), [])->assertForbidden();
    $this->post(route('members.export'), ['headers' => ['email']])->assertForbidden();
    $this->post(route('getLgas'), [])->assertForbidden();
});

test('user editing cannot replace roles through a normal member endpoint', function () {
    $actor = User::factory()->create(['access_level' => 'nationaladmin']);
    $actor->forceFill(['capabilities_managed' => true])->save();
    $actor->givePermissionTo(Permission::findOrCreate('members.update', 'web'));
    $target = User::factory()->create(['access_level' => 'user']);
    $role = Role::create(['name' => 'Attempted escalation', 'guard_name' => 'web', 'group_name' => 'user', 'capabilities_managed' => true]);
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class, \App\Http\Middleware\KuyakEyenIno::class, \App\Http\Middleware\ValidateLicense::class]);
    $this->actingAs($actor)->put(route('nationaladmin.member.update'), ['id' => $target->id, 'roles' => $role->id])->assertForbidden();
    expect($target->fresh()->roles)->toHaveCount(0);
});
