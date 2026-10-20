<?php

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\OfficialUpdateService;
use App\Services\ReleaseManifestSigner;
use App\Services\Updates\AutomaticUpdateFlow;
use App\Services\Updates\AutomaticUpdateService;
use App\Services\Updates\UpdateFlowException;
use App\Services\Updates\UpdatePortalClient;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Flow Test']);
    $this->flowAdmin = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $this->oldFlowStorage = storage_path();
    $this->flowStorage = sys_get_temp_dir().'/campaign-flow-'.Str::uuid();
    app()->useStoragePath($this->flowStorage);
    File::ensureDirectoryExists(storage_path('app/updates'));
    app(\App\Services\InstallationStateService::class)->create(['fixture' => 'automatic-update-flow']);
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $this->flowKey);
    config(['release.signing.public_key' => openssl_pkey_get_details($key)['key'], 'release.identity.version' => '1.1.4']);
    $manifest = [
        'version' => '9.0.0', 'checksum_sha256' => str_repeat('a', 64), 'size_bytes' => 100,
        'upgrade' => ['protocol' => 1, 'supported_sources' => ['1.1.4']],
    ];
    openssl_sign(app(ReleaseManifestSigner::class)->canonicalPayload($manifest), $signature, $this->flowKey, OPENSSL_ALGO_SHA256);
    $manifest['manifest_signature'] = ['algorithm' => 'rsa-sha256', 'value' => base64_encode($signature)];
    $this->flowStatus = [
        'checked_at' => now()->toIso8601String(), 'latest_version' => '9.0.0',
        'available' => true, 'verified' => true, 'manifest' => $manifest,
    ];
    File::put(storage_path('app/updates/check.json'), json_encode($this->flowStatus));
    $this->flowQuote = [
        'entitled' => false, 'contact_confirmed' => true, 'payment_available' => true,
        'price_minor' => 1200000, 'currency' => 'NGN', 'scope_label' => 'Presidential',
        'coverage_months' => 12, 'checkout_url' => 'https://campaignmanager.ng/automatic-update/checkout/test',
    ];
    $this->flowAutomatic = $this->mock(AutomaticUpdateService::class);
    $this->flowAutomatic->shouldReceive('readiness')->andReturn(0)->byDefault();
    $this->flowPortal = $this->mock(UpdatePortalClient::class);
    $this->flowPortal->shouldReceive('status')->andReturnUsing(fn () => $this->flowQuote)->byDefault();
    Http::preventStrayRequests();
});

afterEach(function () {
    app()->useStoragePath($this->oldFlowStorage);
    File::deleteDirectory($this->flowStorage);
});

test('unpaid installations see payment before any package download or stage creation', function () {
    $this->flowAutomatic->shouldNotReceive('start');
    $this->actingAs($this->flowAdmin)->get(route('superadmin.updates.automatic'))
        ->assertOk()->assertSee('Continue to secure payment')->assertSee('I have paid')
        ->assertDontSee('Back up and update to');
    expect(File::isDirectory(storage_path('app/updates/staged')))->toBeFalse();
    Http::assertNothingSent();
});

test('a crafted start request without coverage cannot download or start a worker', function () {
    $this->flowAutomatic->shouldNotReceive('start');
    $this->actingAs($this->flowAdmin)->post(route('superadmin.updates.prepare'), [
        'version' => '9.0.0', 'sha256' => str_repeat('a', 64), 'update_consent' => '1',
    ])->assertRedirect(route('superadmin.updates.automatic'))->assertSessionHas('update_error');
    expect(File::isDirectory(storage_path('app/updates/staged')))->toBeFalse();
    Http::assertNothingSent();
});

test('the download service itself rejects unpaid preparation even through a legacy entry point', function () {
    $base = 'https://github.com/joshualion/Campaign-Manager/releases/download/v9.0.0/';
    Http::fake([
        'https://api.github.com/repos/joshualion/Campaign-Manager/releases/latest' => Http::response([
            'tag_name' => 'v9.0.0', 'assets' => [
                ['name' => 'release-manifest.json', 'browser_download_url' => $base.'release-manifest.json'],
                ['name' => 'campaign-manager.zip', 'browser_download_url' => $base.'campaign-manager.zip'],
            ],
        ]),
        $base.'release-manifest.json' => Http::response($this->flowStatus['manifest']),
    ]);
    expect(fn () => app(OfficialUpdateService::class)->prepare())->toThrow(UpdateFlowException::class, 'coverage');
    expect(File::isDirectory(storage_path('app/updates/staged')))->toBeFalse();
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
});

test('covered installations get one backup-and-update action without another payment', function () {
    $this->flowQuote['entitled'] = true;
    $this->actingAs($this->flowAdmin)->get(route('superadmin.updates.automatic'))
        ->assertOk()->assertSee('Back up and update to 9.0.0')
        ->assertSee('No additional payment')->assertDontSee('Continue to secure payment');
    Http::assertNothingSent();
});

test('payment return refreshes coverage on the same screen without downloading', function () {
    $this->actingAs($this->flowAdmin)->get(route('superadmin.updates.automatic'))->assertSee('Continue to secure payment');
    $this->flowQuote['entitled'] = true;
    $this->get(route('superadmin.updates.automatic'))->assertSee('Back up and update to 9.0.0');
    Http::assertNothingSent();
});

test('database readiness problems show actionable table details without an installation-settings link', function () {
    $this->flowAutomatic->shouldReceive('readiness')->andThrow(new UpdateFlowException('database_storage', 'legacy_supporters (MyISAM) needs a verified database backup.'));
    $this->flowPortal->shouldNotReceive('status');
    $this->actingAs($this->flowAdmin)->get(route('superadmin.updates.automatic'))
        ->assertOk()->assertSee('legacy_supporters (MyISAM)')
        ->assertDontSee('Connect or confirm this installation')
        ->assertDontSee('Continue to secure payment');
    Http::assertNothingSent();
});

test('only a registration problem offers the installation connection link', function () {
    $this->flowPortal->shouldReceive('status')->andThrow(new UpdateFlowException('registration', 'Confirm this installation.'));
    $this->actingAs($this->flowAdmin)->get(route('superadmin.updates.automatic'))
        ->assertOk()->assertSee('Connect or confirm this installation');
});

test('a changed target cannot silently download a different release', function () {
    $this->flowQuote['entitled'] = true;
    $this->flowAutomatic->shouldNotReceive('start');
    expect(fn () => app(AutomaticUpdateFlow::class)->start('8.0.0', str_repeat('a', 64)))
        ->toThrow(UpdateFlowException::class, 'release changed');
    Http::assertNothingSent();
});

test('a paid matching staged package is reused and starts the existing safe worker', function () {
    $this->flowQuote['entitled'] = true;
    $stage = (string) Str::uuid();
    File::ensureDirectoryExists(storage_path('app/updates/staged/'.$stage));
    File::put(storage_path('app/updates/staged/'.$stage.'/journal.json'), json_encode([
        'id' => $stage, 'state' => 'prepared', 'version' => '9.0.0',
        'manifest' => $this->flowStatus['manifest'],
    ]));
    $this->flowAutomatic->shouldReceive('start')->once()->with($stage)->andReturn(['id' => 'safe-worker', 'status_token' => 'test-token']);
    expect(app(AutomaticUpdateFlow::class)->start('9.0.0', str_repeat('a', 64))['id'])->toBe('safe-worker');
    Http::assertNothingSent();
});

test('start requires explicit maintenance consent and administrator access', function () {
    $this->actingAs($this->flowAdmin)->postJson(route('superadmin.updates.prepare'), [
        'version' => '9.0.0', 'sha256' => str_repeat('a', 64),
    ])->assertUnprocessable()->assertJsonValidationErrors('update_consent');
    $this->actingAs(User::factory()->create(['access_level' => 'user', 'status' => 'active']))
        ->get(route('superadmin.updates.automatic'))->assertForbidden();
});

test('non-boolean entitlement values never authorize a package download', function () {
    $this->flowQuote['entitled'] = 'false';
    $screen = app(AutomaticUpdateFlow::class)->review();
    expect($screen['entitlement']['entitled'])->toBeFalse();
    $this->flowAutomatic->shouldNotReceive('start');
    expect(fn () => app(AutomaticUpdateFlow::class)->start('9.0.0', str_repeat('a', 64)))
        ->toThrow(UpdateFlowException::class, 'payment');
    Http::assertNothingSent();
});

test('coverage revoked after review is rechecked before the archive request', function () {
    $covered = $this->flowQuote;
    $covered['entitled'] = true;
    $this->flowPortal->shouldReceive('status')->twice()->andReturn($covered, $this->flowQuote);
    $this->flowAutomatic->shouldNotReceive('start');
    $base = 'https://github.com/joshualion/Campaign-Manager/releases/download/v9.0.0/';
    Http::fake([
        'https://api.github.com/repos/joshualion/Campaign-Manager/releases/latest' => Http::response([
            'tag_name' => 'v9.0.0', 'assets' => [
                ['name' => 'release-manifest.json', 'browser_download_url' => $base.'release-manifest.json'],
                ['name' => 'campaign-manager.zip', 'browser_download_url' => $base.'campaign-manager.zip'],
            ],
        ]),
        $base.'release-manifest.json' => Http::response($this->flowStatus['manifest']),
    ]);
    expect(fn () => app(AutomaticUpdateFlow::class)->start('9.0.0', str_repeat('a', 64)))
        ->toThrow(UpdateFlowException::class, 'coverage');
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
});
