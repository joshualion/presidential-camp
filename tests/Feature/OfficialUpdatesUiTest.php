<?php

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    \App\Models\SystemSetting::create(['package' => 'presidential', 'system_name' => 'UI Test Campaign', 'system_country' => 'Nigeria']);
    $this->uiAdmin = User::factory()->create(['access_level' => 'superadmin']);
    File::ensureDirectoryExists(storage_path('app/updates'));
});

afterEach(function () {
    File::delete(storage_path('app/updates/check.json'));
});

test('updates displays the latest cached result and keeps registration in settings', function () {
    File::put(storage_path('app/updates/check.json'), json_encode([
        'checked_at' => now()->toIso8601String(), 'latest_version' => '9.0.0',
        'available' => true, 'verified' => true,
    ]));
    $this->actingAs($this->uiAdmin)->get(route('superadmin.updates'))
        ->assertOk()
        ->assertSee('Update Automatically')->assertDontSee('Download and verify 9.0.0')->assertDontSee('Register this deployment')
        ->assertDontSee('Remove registration and stop reporting');
    $this->get(route('superadmin.deployment.settings'))->assertOk()->assertSee('Installation Registration');
    Http::assertNothingSent();
});

test('an old or missing update result waits for Check for Updates without fetching or downloading', function () {
    File::put(storage_path('app/updates/check.json'), json_encode(['checked_at' => now()->subHour()->toIso8601String()]));
    $this->actingAs($this->uiAdmin)->get(route('superadmin.updates'))->assertOk()->assertSee('Check for Updates');
    File::delete(storage_path('app/updates/check.json'));
    $this->get(route('superadmin.updates'))->assertOk()->assertSee('Check for Updates');
    Http::assertNothingSent();
});

test('automatic discovery returns a safe error and preserves a previous successful check', function () {
    $previous = ['checked_at' => now()->subHour()->toIso8601String(), 'latest_version' => '1.1.0'];
    File::put(storage_path('app/updates/check.json'), json_encode($previous));
    Http::fake(['api.github.com/*' => Http::response([], 503)]);
    $this->actingAs($this->uiAdmin)->postJson(route('superadmin.updates.check'))->assertStatus(503)->assertJson(['checked' => false]);
    expect(json_decode(File::get(storage_path('app/updates/check.json')), true))->toBe($previous);
});

test('automatic discovery uses the existing official release check', function () {
    Http::fake(['api.github.com/*' => Http::response(['tag_name' => 'v9.0.0', 'assets' => []])]);
    $this->actingAs($this->uiAdmin)->postJson(route('superadmin.updates.check'))->assertOk()->assertJson(['checked' => true]);
    expect(json_decode(File::get(storage_path('app/updates/check.json')), true)['latest_version'])->toBe('9.0.0');
});

test('ordinary members cannot change installation registration or check updates', function () {
    $this->actingAs(User::factory()->create(['access_level' => 'user']))
        ->get(route('superadmin.deployment.settings'))->assertForbidden();
    $this->postJson(route('superadmin.updates.check'))->assertForbidden();
    $this->post(route('superadmin.updates.apply'), [])->assertForbidden();
    $this->post(route('superadmin.updates.discard'), [])->assertForbidden();
    Http::assertNothingSent();
});

test('registered installations see a summary without repeated verification or private identifiers', function () {
    config(['app.url' => 'https://customer.example']);
    File::ensureDirectoryExists(storage_path('app/private'));
    $path = storage_path('app/private/deployment-registration.json');
    File::put($path, \Illuminate\Support\Facades\Crypt::encryptString(json_encode([
        'registered' => true, 'contact_verified' => true, 'domain' => 'customer.example',
        'contact_email' => 'owner@customer.example', 'id' => 'private-installation-identifier', 'token' => 'private-authentication-token',
    ])));
    try {
        $this->actingAs($this->uiAdmin)->get(route('superadmin.deployment.settings'))->assertOk()
            ->assertSee('Registered with CampaignManager.ng')->assertSee('owner@customer.example')
            ->assertSee('Manage Registration Details')->assertSee('Check for Portal Messages')
            ->assertDontSee('Send Verification Email')->assertDontSee('Request Domain Change')
            ->assertDontSee('private-installation-identifier')->assertDontSee('private-authentication-token');
        Http::assertNothingSent();
    } finally { File::delete($path); }
});

test('legacy unregistered installations have one guided connect action', function () {
    File::delete(storage_path('app/private/deployment-registration.json'));
    $this->actingAs($this->uiAdmin)->get(route('superadmin.deployment.settings'))->assertOk()
        ->assertSee('Connect Installation')->assertDontSee('Send Verification Email')->assertDontSee('Request Domain Change');
    Http::assertNothingSent();
});

test('a prepared package is not presented as an installed update or offered for repeated preparation', function () {
    $directory = storage_path('app/updates/staged/ui-preview-test');
    File::ensureDirectoryExists($directory);
    File::put($directory.'/journal.json', json_encode(['state' => 'prepared', 'version' => '9.0.0']));
    try {
        $this->actingAs($this->uiAdmin)->get(route('superadmin.updates'))->assertOk()
            ->assertSee('Installed version:')
            ->assertDontSee('ready for review')
            ->assertDontSee('action="'.route('superadmin.updates.prepare').'"', false);
    } finally {
        File::deleteDirectory($directory);
    }
});


test('a staged old download cannot hide a newer discovered release', function () {
    $directory = storage_path('app/updates/staged/ui-obsolete-test');
    File::ensureDirectoryExists($directory);
    File::put($directory.'/journal.json', json_encode(['state' => 'prepared', 'version' => '1.1.4']));
    File::put(storage_path('app/updates/check.json'), json_encode([
        'checked_at' => now()->toIso8601String(), 'latest_version' => '9.0.0', 'available' => true, 'verified' => true,
    ]));
    try {
        $this->actingAs($this->uiAdmin)->get(route('superadmin.updates'))->assertOk()
            ->assertSee('Available version:')->assertSee('9.0.0')
            ->assertDontSee('Previously downloaded')
            ->assertDontSee('Version 1.1.4 is ready for review.');
    } finally {
        File::deleteDirectory($directory);
    }
});

test('cached availability is recalculated after an installed version changes', function () {
    config(['release.identity.version' => '9.0.0']);
    File::put(storage_path('app/updates/check.json'), json_encode([
        'checked_at' => now()->toIso8601String(), 'latest_version' => '9.0.0', 'available' => true, 'verified' => true,
    ]));
    $this->actingAs($this->uiAdmin)->get(route('superadmin.updates'))->assertOk()->assertSee('Your installation is up to date.');
});


test('a leftover older stage cannot offer a downgrade after manual installation', function () {
    config(['release.identity.version' => '9.0.0']);
    $directory = storage_path('app/updates/staged/ui-installed-test');
    File::ensureDirectoryExists($directory);
    File::put($directory.'/journal.json', json_encode(['state' => 'prepared', 'version' => '1.1.4']));
    File::put(storage_path('app/updates/check.json'), json_encode([
        'checked_at' => now()->toIso8601String(), 'latest_version' => '9.0.0', 'available' => true, 'verified' => true,
    ]));
    try {
        $this->actingAs($this->uiAdmin)->get(route('superadmin.updates'))->assertOk()
            ->assertSee('Your installation is up to date.')
            ->assertDontSee('Version 1.1.4 is ready for review.');
    } finally {
        File::deleteDirectory($directory);
    }
});
