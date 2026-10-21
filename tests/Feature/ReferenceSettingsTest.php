<?php

use App\Models\AgeGrade;
use App\Models\SupportGroup;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Volunteer Settings Test']);
    $this->volunteerAdmin = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active', 'email' => 'volunteer-admin@campaign.test', 'password' => Hash::make('volunteer-admin-test-password')]);
    $this->adminGroup = SupportGroup::create(['name' => 'Administration Volunteers']);
    $this->adminGroup->users()->attach($this->volunteerAdmin->id);
});

test('volunteer group settings renders for an onboarded Super Admin', function () {
    $this->actingAs($this->volunteerAdmin)->get('/superadmin/setting/volunteer/group')
        ->assertOk()->assertSee('All Volunteer Groups')->assertSee('Add New Group');
});

test('volunteer group settings renders populated groups and membership deletion protection', function () {
    $group = SupportGroup::create(['name' => 'Community Volunteers', 'description' => 'Existing group']);
    $group->users()->attach(User::factory()->create()->id);
    $this->actingAs($this->volunteerAdmin)->get(route('superadmin.volunteer.group'))
        ->assertOk()->assertSee('Community Volunteers')->assertSee('data-associated-users="true"', false);
});

test('volunteer group create and edit pages resolve on case sensitive hosts', function () {
    $group = SupportGroup::create(['name' => 'Existing Volunteers', 'description' => 'Existing description']);
    $this->actingAs($this->volunteerAdmin)->get(route('superadmin.volunteer.group.add'))->assertOk();
    $this->get(route('superadmin.volunteer.group.edit', $group->uuid))->assertOk()->assertSee('Existing Volunteers');
});

test('creating a volunteer group redirects to its named settings route', function () {
    $this->actingAs($this->volunteerAdmin)->post(route('superadmin.volunteer.group.store'), [
        'name' => 'New Volunteers', 'description' => 'New group description',
    ])->assertSessionHasNoErrors()->assertRedirect(route('superadmin.volunteer.group'));
    $this->assertDatabaseHas('support_groups', ['name' => 'New Volunteers']);
});

test('updating a volunteer group preserves its identity and returns to settings', function () {
    $group = SupportGroup::create(['name' => 'Old name', 'description' => 'Old description']);
    $this->actingAs($this->volunteerAdmin)->post(route('superadmin.volunteer.group.update', $group->uuid), [
        'name' => 'Updated Volunteers', 'description' => 'Updated description',
    ])->assertRedirect(route('superadmin.volunteer.group'));
    $this->assertDatabaseHas('support_groups', ['uuid' => $group->uuid, 'name' => 'Updated Volunteers']);
});

test('a volunteer group with members cannot be deleted', function () {
    $group = SupportGroup::create(['name' => 'Protected Volunteers']);
    $member = User::factory()->create();
    $group->users()->attach($member->id);
    $this->actingAs($this->volunteerAdmin)->delete(route('superadmin.volunteer.group.delete', $group->uuid))
        ->assertRedirect(route('superadmin.volunteer.group'))->assertSessionHas('alert-type', 'error');
    $this->assertDatabaseHas('support_groups', ['id' => $group->id]);
    $this->assertDatabaseHas('user_support_group', ['support_group_id' => $group->id, 'user_id' => $member->id]);
});

test('an empty volunteer group can be deleted', function () {
    $group = SupportGroup::create(['name' => 'Empty Volunteers']);
    $this->actingAs($this->volunteerAdmin)->delete(route('superadmin.volunteer.group.delete', $group->uuid))
        ->assertRedirect(route('superadmin.volunteer.group'));
    $this->assertDatabaseMissing('support_groups', ['id' => $group->id]);
});

test('ordinary members cannot manage reference settings', function () {
    $group = SupportGroup::create(['name' => 'Protected Volunteers']);
    $member = User::factory()->create(['access_level' => 'member', 'status' => 'active', 'email' => 'volunteer-member@campaign.test', 'password' => Hash::make('volunteer-member-test-password')]);
    $this->adminGroup->users()->attach($member->id);
    $this->actingAs($member);
    $this->get(route('superadmin.volunteer.group'))->assertForbidden();
    $this->post(route('superadmin.volunteer.group.store'), ['name' => 'Unauthorized', 'description' => 'Denied'])->assertForbidden();
    $this->delete(route('superadmin.volunteer.group.delete', $group->uuid))->assertForbidden();
    $this->get(route('superadmin.agegrade'))->assertForbidden();
    $this->post(route('superadmin.agegrade.store'), ['name' => 'Unauthorized'])->assertForbidden();
    $this->assertDatabaseHas('support_groups', ['id' => $group->id]);
    $this->assertDatabaseMissing('support_groups', ['name' => 'Unauthorized']);
});

test('age grade settings renders both empty and populated data', function () {
    $this->actingAs($this->volunteerAdmin)->get('/superadmin/setting/agegrade')->assertOk()->assertSee('All Age Grades');
    AgeGrade::create(['name' => 'Young Adults', 'description' => 'Existing age grade']);
    $this->get(route('superadmin.agegrade'))->assertOk()->assertSee('Young Adults');
});

test('age grade create and edit pages resolve on case sensitive hosts', function () {
    $grade = AgeGrade::create(['name' => 'Adults']);
    $this->actingAs($this->volunteerAdmin)->get(route('superadmin.agegrade.add'))->assertOk();
    $this->get(route('superadmin.agegrade.edit', $grade->uuid))->assertOk()->assertSee('Adults');
});

test('optional descriptions work when creating and updating reference settings', function ($type) {
    $route = $type === 'volunteer' ? 'superadmin.volunteer.group' : 'superadmin.agegrade';
    $model = $type === 'volunteer' ? SupportGroup::class : AgeGrade::class;
    $this->actingAs($this->volunteerAdmin)->post(route($route.'.store'), ['name' => 'Optional Description'])
        ->assertSessionHasNoErrors()->assertRedirect(route($route));
    $record = $model::where('name', 'Optional Description')->firstOrFail();
    $this->post(route($route.'.update', $record->uuid), ['name' => 'Updated Without Description', 'description' => null])
        ->assertSessionHasNoErrors()->assertRedirect(route($route));
    expect($record->fresh()->name)->toBe('Updated Without Description')->and($record->fresh()->description)->toBeNull();
})->with(['volunteer', 'agegrade']);

test('an empty age grade deletes and returns to the current administrators settings', function () {
    $grade = AgeGrade::create(['name' => 'Unused Age Grade']);
    $this->actingAs($this->volunteerAdmin)->delete(route('superadmin.agegrade.delete', $grade->uuid))
        ->assertRedirect(route('superadmin.agegrade'));
    $this->assertDatabaseMissing('age_grades', ['id' => $grade->id]);
});

test('age grades with associated users cannot cascade delete those users', function () {
    $grade = AgeGrade::create(['name' => 'Protected Age Grade']);
    $member = User::factory()->create(['age_grade_id' => $grade->id]);
    $this->actingAs($this->volunteerAdmin)->delete(route('superadmin.agegrade.delete', $grade->uuid))
        ->assertRedirect(route('superadmin.agegrade'))->assertSessionHas('alert-type', 'error');
    $this->assertDatabaseHas('age_grades', ['id' => $grade->id]);
    $this->assertDatabaseHas('users', ['id' => $member->id, 'age_grade_id' => $grade->id]);
});

test('missing age grades return not found instead of a server error', function ($action, $method) {
    $this->actingAs($this->volunteerAdmin)->{$method}(route('superadmin.agegrade.'.$action, '00000000-0000-4000-8000-000000000001'), ['name' => 'Missing', 'description' => 'Missing'])
        ->assertNotFound();
})->with([['edit', 'get'], ['update', 'post'], ['delete', 'delete']]);
