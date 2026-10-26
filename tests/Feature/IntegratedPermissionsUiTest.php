<?php

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CampaignCapabilityService;
use App\Services\OfficialUpdateService;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Branded Campaign', 'system_country' => 'Nigeria', 'dark_theme_color' => '#6f42c1']);
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class, \App\Http\Middleware\KuyakEyenIno::class, \App\Http\Middleware\ValidateLicense::class]);
    $this->admin = User::factory()->create(['access_level' => 'superadmin']);
});

test('existing role menus share the complete editor and preserve existing role identity', function () {
    $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web', 'group_name' => 'superadmin']);
    $this->actingAs($this->admin)->get(route('superadmin.settings.rolespermission'))->assertOk()->assertSee('Super Admin')->assertDontSee('Legacy permission');
    $this->get(route('superadmin.settings.roles'))->assertOk()->assertSee('Super Admin');
    $response = $this->get(route('superadmin.settings.role.permission.edit', encrypt($role->id)))->assertOk();
    if ($path = getenv('V121_BROWSER_OUTPUT')) {
        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname(base_path($path)));
        file_put_contents(base_path($path), $response->getContent());
    }
    foreach (app(CampaignCapabilityService::class)->catalog() as $permission) { $response->assertSee('value="'.$permission.'"', false); }
    $response->assertSee('card card-primary', false)->assertDontSee('Legacy compatibility')->assertDontSee('Roles &amp; Capabilities', false);
    $this->get(route('superadmin.authorization.index'))->assertRedirect(route('superadmin.settings.rolespermission'));
});

test('saving an existing role applies independently editable permissions across both entry points', function () {
    $role = Role::create(['name' => 'State Reader', 'guard_name' => 'web', 'group_name' => 'stateadmin']);
    $member = User::factory()->create(['access_level' => 'stateadmin']); $member->assignRole($role);
    $this->actingAs($this->admin)->put(route('superadmin.authorization.roles.update', $role), ['name' => $role->name, 'permissions' => ['members.view', 'incidents.create']])->assertRedirect();
    expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe(['incidents.create', 'members.view']);
    expect($member->fresh()->can('members.view'))->toBeTrue()->and($member->fresh()->can('members.delete'))->toBeFalse();
    $this->get(route('superadmin.settings.role.edit', encrypt($role->id)))->assertOk()->assertSee('State Reader')->assertSee('value="incidents.create" checked', false);
    $this->put(route('superadmin.authorization.roles.update', $role), ['name' => $role->name, 'permissions' => []])->assertRedirect();
    expect($member->fresh()->roles->pluck('id')->all())->toBe([$role->id])->and($member->fresh()->can('members.view'))->toBeFalse();
});

test('custom permission definitions are visible and editable with the same role', function () {
    $role = Role::create(['name' => 'Custom Role', 'guard_name' => 'web', 'group_name' => 'user']);
    $role->givePermissionTo(Permission::findOrCreate('custom.reporting', 'web'));
    Permission::findOrCreate('custom reporting', 'web');
    $this->actingAs($this->admin)->get(route('superadmin.settings.role.permission.edit', encrypt($role->id)))->assertOk()->assertSee('value="custom.reporting"', false)->assertSee('> Custom Reporting</label>', false);
    $this->put(route('superadmin.authorization.roles.update', $role), ['name' => $role->name, 'permissions' => ['custom.reporting', 'members.view']])->assertRedirect();
    expect($role->fresh()->permissions->pluck('name')->all())->toContain('custom.reporting', 'members.view');
});

test('every other account type is denied evidence settings and all permission management', function (string $level) {
    $this->actingAs(User::factory()->create(['access_level' => $level]));
    foreach (['superadmin.incident-evidence.settings', 'superadmin.settings.rolespermission', 'superadmin.settings.roles', 'superadmin.settings.addrole.permission'] as $name) { $this->get(route($name))->assertForbidden(); }
    $this->put(route('superadmin.incident-evidence.update'), [])->assertForbidden();
    $html = View::make('backend.template.superadminmenu', ['profileData' => auth()->user()])->render();
    expect($html)->not->toContain('Incident Evidence Settings', 'Official Updates', 'Manage Roles');
})->with(['nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user']);

test('sidebar starts with dashboard and places branded evidence and update links in their sections', function () {
    $this->actingAs($this->admin);
    $this->mock(OfficialUpdateService::class)->shouldReceive('status')->andReturn(['available' => true, 'verified' => true, 'latest_version' => '1.2.1']);
    $html = View::make('backend.template.superadminmenu', ['profileData' => $this->admin])->render();
    expect(strpos($html, 'Dashboard'))->toBeLessThan(strpos($html, 'Members'));
    expect(strpos($html, 'Incident Evidence Settings'))->toBeGreaterThan(strpos($html, 'Manage Elections'))->toBeLessThan(strpos($html, 'Situation Room'));
    expect(strpos($html, 'Official Updates'))->toBeGreaterThan(strpos($html, 'Roles & Permission'))->toBeLessThan(strpos($html, 'Profile'));
    expect($html)->toContain('badge-danger')->not->toContain('badge-info', 'Roles &amp; Capabilities');
    $notice = View::make('backend.shared.update-notification')->render();
    expect($notice)->toContain('alert-danger', '1.2.1', 'Review Update');
    $this->actingAs(User::factory()->create(['access_level' => 'stateadmin']));
    expect(View::make('backend.shared.update-notification')->render())->not->toContain('Review Update');
});


test('existing role defaults retain assignment-gated election actions before editing', function () {
    $role = Role::create(['name' => 'Polling Unit Agent', 'guard_name' => 'web', 'group_name' => 'puadmin']);
    expect(app(CampaignCapabilityService::class)->rolePermissions($role))->toContain('election.results.create', 'election.results.update', 'incidents.create');
    expect((bool) $role->fresh()->capabilities_managed)->toBeFalse();
});


test('role editing maps existing edit permissions to the same standardized update action', function () {
    $role = Role::create(['name' => 'Existing Editor', 'guard_name' => 'web', 'group_name' => 'user', 'capabilities_managed' => true]);
    $role->givePermissionTo(Permission::findOrCreate('members.edit', 'web'));
    $response = $this->actingAs($this->admin)->get(route('superadmin.settings.role.permission.edit', encrypt($role->id)))->assertOk();
    $response->assertSee('value="members.update" checked', false)->assertDontSee('value="members.edit"', false);
    $this->post(route('superadmin.settings.role.permission.update', $role->id), ['permission' => [Permission::findByName('members.edit', 'web')->id]])->assertRedirect();
    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['members.update']);
});


test('assigning an existing default role keeps the permissions shown in the role editor', function () {
    $role = Role::create(['name' => 'State Coordinator', 'guard_name' => 'web', 'group_name' => 'stateadmin']);
    $user = User::factory()->create(['access_level' => 'stateadmin']);
    $before = $user->only(['access_level', 'state_id', 'lga_id']);
    $this->actingAs($this->admin)->put(route('superadmin.authorization.users.update', $user), ['roles' => [$role->id], 'grants' => [], 'restrictions' => []])->assertRedirect();
    $user = $user->fresh();
    expect($user->can('members.view'))->toBeTrue()->and($user->can('members.update'))->toBeFalse()
        ->and($user->only(array_keys($before)))->toBe($before)->and((bool) $role->fresh()->capabilities_managed)->toBeFalse();
});

test('multiple roles combine grants while individual restrictions still win', function () {
    $existing = Role::create(['name' => 'State ICT Director', 'guard_name' => 'web', 'group_name' => 'stateadmin']);
    $reader = Role::create(['name' => 'Reader', 'guard_name' => 'web', 'group_name' => 'user', 'capabilities_managed' => true]);
    $reader->givePermissionTo(Permission::findOrCreate('members.view', 'web'));
    $user = User::factory()->create(['access_level' => 'stateadmin', 'capabilities_managed' => true]);
    $user->assignRole([$existing, $reader]);
    expect($user->can('members.update'))->toBeTrue()->and($user->can('authorization.manage'))->toBeFalse();
    \Illuminate\Support\Facades\DB::table('campaign_permission_restrictions')->insert(['user_id' => $user->id, 'permission_name' => 'members.update']);
    app()->forgetScopedInstances();
    expect($user->fresh()->can('members.update'))->toBeFalse();
});


test('the existing permission update route clears defaults using the same role save operation', function () {
    $role = Role::create(['name' => 'State Coordinator', 'guard_name' => 'web', 'group_name' => 'stateadmin']);
    $user = User::factory()->create(['access_level' => 'stateadmin']); $user->assignRole($role);
    expect($user->can('members.view'))->toBeTrue();
    $this->actingAs($this->admin)->post(route('superadmin.settings.role.permission.update', $role->id), ['permission' => []])->assertRedirect();
    expect((bool) $role->fresh()->capabilities_managed)->toBeTrue()->and($role->fresh()->permissions)->toHaveCount(0)
        ->and($user->fresh()->can('members.view'))->toBeFalse()->and($user->fresh()->roles->pluck('id')->all())->toBe([$role->id]);
});
