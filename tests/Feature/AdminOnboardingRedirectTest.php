<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function onboardingTestUser(string $accessLevel, array $attributes = []): User
{
    return User::withoutEvents(fn () => User::factory()->create(array_merge([
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
        'access_level' => $accessLevel,
        'status' => 'active',
        'password' => Hash::make('secret-password'),
        'requires_update' => false,
    ], $attributes)));
}

function onboardingReferenceRows(): void
{
    $now = now();

    DB::table('system_settings')->insert([
        'id' => 1,
        'package' => 'presidential',
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'frontend_community' => 0,
        'require_bank_details' => 0,
        'enable_local_storage' => 1,
        'enable_s3_storage' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('countries')->insert([
        'id' => 1,
        'uuid' => (string) Str::uuid(),
        'name' => 'Nigeria',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('regions')->insert([
        'id' => 1,
        'uuid' => (string) Str::uuid(),
        'name' => 'South East',
        'country_id' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('religions')->insert([
        'id' => 1,
        'uuid' => (string) Str::uuid(),
        'name' => 'Christianity',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('age_grades')->insert([
        'id' => 1,
        'uuid' => (string) Str::uuid(),
        'name' => 'Adult',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

test('national admin login redirects to national dashboard even when intended url is location import', function () {
    onboardingReferenceRows();
    $user = onboardingTestUser('nationaladmin', [
        'firstname' => 'National',
        'lastname' => 'ICT',
        'phone' => '+2348011111111',
        'email' => 'national@example.test',
        'religion_id' => 1,
        'age_grade_id' => 1,
    ]);

    $response = $this
        ->withSession(['url.intended' => '/national/location/importlgas', '_token' => 'test-token'])
        ->post('/login', [
            '_token' => 'test-token',
            'login' => $user->email,
            'password' => 'secret-password',
        ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/national/dashboard');
});

test('national admin profile completion page does not redirect to location setup when hierarchy rows are missing', function () {
    onboardingReferenceRows();
    $user = onboardingTestUser('nationaladmin');

    $this->actingAs($user)
        ->get('/complete-profile')
        ->assertOk()
        ->assertSee('COMPLETE YOUR PROFILE');
});

test('national admin can complete profile without creating or selecting location hierarchy data', function () {
    onboardingReferenceRows();
    $user = onboardingTestUser('nationaladmin');

    $this->actingAs($user)
        ->withSession(['_token' => 'test-token'])
        ->post('/complete-profile', [
            '_token' => 'test-token',
            'firstname' => 'National',
            'lastname' => 'ICT',
            'email' => 'national-complete@example.test',
            'phone' => '+2348022222222',
            'validvoter' => 'no',
            'age_grade_id' => 1,
            'gender' => 'male',
            'religion_id' => 1,
        ])
        ->assertRedirect(route('nationaladmin.dashboard', absolute: false));

    $user->refresh();
    expect($user->firstname)->toBe('National')
        ->and($user->lga_id)->toBeNull()
        ->and($user->polling_unit_id)->toBeNull();
});

test('national admin is forbidden from location import setup page', function () {
    $user = onboardingTestUser('nationaladmin');

    $this->actingAs($user)
        ->get('/national/location/importlgas')
        ->assertForbidden();
});
