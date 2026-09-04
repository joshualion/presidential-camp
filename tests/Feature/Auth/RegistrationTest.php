<?php

use App\Models\Country;
use App\Models\SystemSetting;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

function enablePublicRegistrationForAuthTest(): void
{
    Country::query()->create(['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);

    SystemSetting::query()->updateOrCreate(['id' => 1], [
        'package' => 'presidential',
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'frontend_community' => 0,
        'frontend_registration' => 1,
        'require_bank_details' => 0,
        'enable_local_storage' => 1,
        'enable_s3_storage' => 0,
    ]);

    Role::query()->firstOrCreate(['name' => 'Member', 'guard_name' => 'web']);
}

test('registration screen can be rendered when public registration is enabled', function () {
    enablePublicRegistrationForAuthTest();

    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registration screen is hidden when public registration is closed', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});

test('new public member registration creates a member and redirects to profile completion', function () {
    enablePublicRegistrationForAuthTest();

    $response = $this->withSession(['logic_question_answer' => 4])->post('/register', [
        'username' => 'testmember',
        'firstname' => 'Test',
        'lastname' => 'Member',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'logic_question' => '4',
        'nickname' => '',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('user.account.complete-profile', absolute: false));
});