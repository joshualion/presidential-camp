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
        ->assertOk()->assertViewHas('shouldCheck', false)
        ->assertSee('Download and verify 9.0.0')->assertDontSee('Register this deployment')
        ->assertDontSee('Remove registration and stop reporting');
    $this->get(route('superadmin.deployment.settings'))->assertOk()->assertSee('Installation registration');
    Http::assertNothingSent();
});

test('an old or missing update result requests automatic discovery', function () {
    File::put(storage_path('app/updates/check.json'), json_encode(['checked_at' => now()->subHour()->toIso8601String()]));
    $this->actingAs($this->uiAdmin)->get(route('superadmin.updates'))->assertOk()->assertViewHas('shouldCheck', true);
    File::delete(storage_path('app/updates/check.json'));
    $this->get(route('superadmin.updates'))->assertOk()->assertViewHas('shouldCheck', true);
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
    Http::assertNothingSent();
});

test('a prepared package is not presented as an installed update or offered for repeated preparation', function () {
    $directory = storage_path('app/updates/staged/ui-preview-test');
    File::ensureDirectoryExists($directory);
    File::put($directory.'/journal.json', json_encode(['state' => 'prepared', 'version' => '9.0.0']));
    try {
        $this->actingAs($this->uiAdmin)->get(route('superadmin.updates'))->assertOk()
            ->assertSee('Version 9.0.0 is ready for review.')
            ->assertSee('Your installed version is still')
            ->assertDontSee('action="'.route('superadmin.updates.prepare').'"', false);
    } finally {
        File::deleteDirectory($directory);
    }
});
