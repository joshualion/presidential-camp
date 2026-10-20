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
    $this->markApplicationInstalledForTests();
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
    $this->flowAutomatic = $this->partialMock(AutomaticUpdateService::class);
    $this->flowAutomatic->shouldReceive('readiness')->andReturn(0)->byDefault();
    $this->flowPortal = $this->mock(UpdatePortalClient::class);
    $this->flowPortal->shouldReceive('status')->andReturnUsing(fn () => $this->flowQuote)->byDefault();
    Http::preventStrayRequests();
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
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
});

test('a crafted start request without coverage cannot download or start a worker', function () {
    $this->flowAutomatic->shouldNotReceive('start');
    $this->actingAs($this->flowAdmin)->post(route('superadmin.updates.prepare'), [
        'version' => '9.0.0', 'sha256' => str_repeat('a', 64), 'update_consent' => '1',
    ])->assertRedirect(route('superadmin.updates.automatic'))->assertSessionHas('update_error');
    expect(File::isDirectory(storage_path('app/updates/staged')))->toBeFalse();
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
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
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
});

test('payment return refreshes coverage on the same screen without downloading', function () {
    $this->actingAs($this->flowAdmin)->get(route('superadmin.updates.automatic'))->assertSee('Continue to secure payment');
    $this->flowQuote['entitled'] = true;
    $this->get(route('superadmin.updates.automatic'))->assertSee('Back up and update to 9.0.0');
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
});

test('database readiness problems show actionable table details without an installation-settings link', function () {
    $this->flowAutomatic->shouldReceive('readiness')->andThrow(new UpdateFlowException('database_storage', 'legacy_supporters (MyISAM) needs a verified database backup.'));
    $this->flowPortal->shouldNotReceive('status');
    $this->actingAs($this->flowAdmin)->get(route('superadmin.updates.automatic'))
        ->assertOk()->assertSee('legacy_supporters (MyISAM)')
        ->assertDontSee('Connect or confirm this installation')
        ->assertDontSee('Continue to secure payment');
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
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
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
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
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
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
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
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


test('the legacy bootstrap renders both unpaid checkout and covered update screens', function () {
    app('view')->addNamespace('updaterRendering', base_path('tools/updater'));
    $data = [
        'installedVersion' => '1.1.3', 'registration' => ['registered' => true],
        'status' => $this->flowStatus, 'entitlement' => $this->flowQuote,
        'issue' => null, 'job' => null, 'stage' => null,
    ];
    $unpaid = view('updaterRendering::screen', $data)->render();
    expect($unpaid)->toContain('Continue to secure payment')->not->toContain('Back up and update to');
    $data['entitlement']['entitled'] = true;
    $covered = view('updaterRendering::screen', $data)->render();
    expect($covered)->toContain('Back up and update to 9.0.0')->not->toContain('Continue to secure payment');
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
});


function writeFlowStage(string $version, array $manifest = []): string
{
    $id = (string) Str::uuid();
    File::ensureDirectoryExists(storage_path('app/updates/staged/'.$id));
    File::put(storage_path('app/updates/staged/'.$id.'/journal.json'), json_encode([
        'id' => $id, 'state' => 'prepared', 'version' => $version, 'manifest' => $manifest,
    ]));

    return $id;
}

test('latest discovery supersedes an older unused stage while preserving all recovery backups', function () {
    $this->flowQuote['entitled'] = true;
    $old = writeFlowStage('1.1.4');
    $matching = writeFlowStage('9.0.0', $this->flowStatus['manifest']);
    $backup = storage_path('app/updates/jobs/'.Str::uuid().'/backup');
    File::ensureDirectoryExists($backup);
    File::put($backup.'/database.jsonl', 'private recovery data');
    $this->flowAutomatic->shouldReceive('start')->once()->with($matching)->andReturn(['id' => 'latest-worker']);
    expect(app(AutomaticUpdateFlow::class)->start('9.0.0', str_repeat('a', 64))['id'])->toBe('latest-worker');
    expect(File::exists(storage_path('app/updates/staged/'.$old)))->toBeFalse()
        ->and(File::exists(storage_path('app/updates/staged/'.$matching)))->toBeTrue()
        ->and(File::get($backup.'/database.jsonl'))->toBe('private recovery data');
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
});

test('unpaid clients retain their existing stage and cannot supersede or download it', function () {
    $old = writeFlowStage('1.1.4');
    expect(fn () => app(OfficialUpdateService::class)->prepare(['version' => '9.0.0', 'sha256' => str_repeat('a', 64)]))
        ->toThrow(UpdateFlowException::class, 'coverage');
    expect(File::exists(storage_path('app/updates/staged/'.$old)))->toBeTrue();
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
});

test('stages belonging to active or unrecovered jobs cannot be superseded', function (string $state) {
    $this->flowQuote['entitled'] = true;
    $old = writeFlowStage('1.1.4');
    writeFlowStage('9.0.0', $this->flowStatus['manifest']);
    $job = storage_path('app/updates/jobs/'.Str::uuid());
    File::ensureDirectoryExists($job);
    File::put($job.'/journal.json', json_encode(['stage_id' => $old, 'state' => $state]));
    $this->flowAutomatic->shouldNotReceive('start');
    expect(fn () => app(AutomaticUpdateFlow::class)->start('9.0.0', str_repeat('a', 64)))
        ->toThrow(\App\Services\Updates\UpdateException::class, 'active or unrecovered');
    expect(File::exists(storage_path('app/updates/staged/'.$old)))->toBeTrue();
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
})->with(['queued', 'applying', 'recovering', 'recovery_required']);

test('a newer or same-version conflicting stage is never silently deleted', function (string $version) {
    $this->flowQuote['entitled'] = true;
    $old = writeFlowStage($version, ['checksum_sha256' => str_repeat('b', 64)]);
    $this->flowAutomatic->shouldNotReceive('start');
    expect(fn () => app(AutomaticUpdateFlow::class)->start('9.0.0', str_repeat('a', 64)))
        ->toThrow(RuntimeException::class, 'conflicting or newer');
    expect(File::exists(storage_path('app/updates/staged/'.$old)))->toBeTrue();
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
})->with(['9.0.0', '10.0.0']);

test('fresh discovery rejects obsolete consent even when a matching old package was cached', function () {
    $this->flowQuote['entitled'] = true;
    $old = writeFlowStage('9.0.0', $this->flowStatus['manifest']);
    $newManifest = $this->flowStatus['manifest'];
    unset($newManifest['manifest_signature']);
    $newManifest['version'] = '9.0.1';
    openssl_sign(app(ReleaseManifestSigner::class)->canonicalPayload($newManifest), $signature, $this->flowKey, OPENSSL_ALGO_SHA256);
    $newManifest['manifest_signature'] = ['algorithm' => 'rsa-sha256', 'value' => base64_encode($signature)];
    $base = 'https://github.com/joshualion/Campaign-Manager/releases/download/v9.0.1/';
    Http::fake([
        'https://api.github.com/repos/joshualion/Campaign-Manager/releases/latest' => Http::response([
            'tag_name' => 'v9.0.1', 'assets' => [
                ['name' => 'release-manifest.json', 'browser_download_url' => $base.'release-manifest.json'],
                ['name' => 'campaign-manager.zip', 'browser_download_url' => $base.'campaign-manager.zip'],
            ],
        ]),
        $base.'release-manifest.json' => Http::response($newManifest),
    ]);
    $this->flowAutomatic->shouldNotReceive('start');
    expect(fn () => app(AutomaticUpdateFlow::class)->start('9.0.0', str_repeat('a', 64)))
        ->toThrow(UpdateFlowException::class, 'release changed');
    expect(File::exists(storage_path('app/updates/staged/'.$old)))->toBeTrue();
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
});

test('automatic screen checks latest metadata even when an older check is still fresh', function () {
    $cached = $this->flowStatus;
    $cached['latest_version'] = '1.1.5';
    File::put(storage_path('app/updates/check.json'), json_encode($cached));
    $this->flowQuote['entitled'] = true;
    $this->actingAs($this->flowAdmin)->get(route('superadmin.updates.automatic'))
        ->assertOk()->assertSee('Back up and update to 9.0.0');
    Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/repos/joshualion/Campaign-Manager/releases/latest');
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
});


test('legacy apply requests cannot install an obsolete selected package', function () {
    $this->flowQuote['entitled'] = true;
    $old = writeFlowStage('1.1.4', ['checksum_sha256' => str_repeat('b', 64)]);
    $this->flowAutomatic->shouldNotReceive('start');
    $this->actingAs($this->flowAdmin)->post(route('superadmin.updates.apply'), [
        'stage_id' => $old, 'update_consent' => '1',
    ])->assertRedirect()->assertSessionHas('update_error');
    expect(File::exists(storage_path('app/updates/staged/'.$old)))->toBeTrue();
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
});

test('a failed fresh discovery leaves an old prepared package and backups intact', function () {
    $this->flowQuote['entitled'] = true;
    $old = writeFlowStage('1.1.4');
    Http::fake(['https://api.github.com/*' => Http::response([], 503)]);
    $this->flowAutomatic->shouldNotReceive('start');
    expect(fn () => app(AutomaticUpdateFlow::class)->start('9.0.0', str_repeat('a', 64)))->toThrow(\Illuminate\Http\Client\RequestException::class);
    expect(File::exists(storage_path('app/updates/staged/'.$old)))->toBeTrue();
    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/campaign-manager.zip'));
});

test('a held preparation lock prevents superseding a package', function () {
    $this->flowQuote['entitled'] = true;
    $old = writeFlowStage('1.1.4');
    $lock = fopen(storage_path('app/updates/prepare.lock'), 'c');
    flock($lock, LOCK_EX);
    try {
        expect(fn () => app(AutomaticUpdateFlow::class)->start('9.0.0', str_repeat('a', 64)))
            ->toThrow(RuntimeException::class, 'already running');
        expect(File::exists(storage_path('app/updates/staged/'.$old)))->toBeTrue();
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
});

test('an older unused stage is replaced by a newly downloaded authenticated latest package', function () {
    $this->flowQuote['entitled'] = true;
    $old = writeFlowStage('1.1.4');
    $contents = '<?php return "latest";';
    $inner = ['version' => '9.0.0', 'checksums' => ['app/Example.php' => hash('sha256', $contents)]];
    openssl_sign(app(ReleaseManifestSigner::class)->canonicalPayload($inner), $signature, $this->flowKey, OPENSSL_ALGO_SHA256);
    $inner['manifest_signature'] = ['algorithm' => 'rsa-sha256', 'value' => base64_encode($signature)];
    $zipPath = storage_path('fixture.zip');
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFromString('app/Example.php', $contents);
    $zip->addFromString('release-manifest.json', json_encode($inner));
    $zip->close();
    $outer = $this->flowStatus['manifest'];
    unset($outer['manifest_signature']);
    $outer['checksum_sha256'] = hash_file('sha256', $zipPath);
    $outer['size_bytes'] = filesize($zipPath);
    openssl_sign(app(ReleaseManifestSigner::class)->canonicalPayload($outer), $signature, $this->flowKey, OPENSSL_ALGO_SHA256);
    $outer['manifest_signature'] = ['algorithm' => 'rsa-sha256', 'value' => base64_encode($signature)];
    $status = $this->flowStatus;
    $status['manifest'] = $outer;
    File::put(storage_path('app/updates/check.json'), json_encode($status));
    $base = 'https://github.com/joshualion/Campaign-Manager/releases/download/v9.0.0/';
    Http::fake([
        'https://api.github.com/repos/joshualion/Campaign-Manager/releases/latest' => Http::response([
            'tag_name' => 'v9.0.0', 'assets' => [
                ['name' => 'release-manifest.json', 'browser_download_url' => $base.'release-manifest.json'],
                ['name' => 'campaign-manager.zip', 'browser_download_url' => $base.'campaign-manager.zip'],
            ],
        ]),
        $base.'release-manifest.json' => Http::response($outer),
        $base.'campaign-manager.zip' => Http::response(File::get($zipPath)),
    ]);
    $this->flowAutomatic->shouldReceive('start')->once()->withArgs(function ($id) use ($old, $contents) {
        return $id !== $old && File::get(storage_path('app/updates/staged/'.$id.'/application/app/Example.php')) === $contents;
    })->andReturn(['id' => 'latest-worker']);
    expect(app(AutomaticUpdateFlow::class)->start('9.0.0', $outer['checksum_sha256'])['id'])->toBe('latest-worker');
    expect(File::exists(storage_path('app/updates/staged/'.$old)))->toBeFalse();
    Http::assertSent(fn ($request) => $request->url() === $base.'campaign-manager.zip');
});

test('unreadable job journals stop automatic stage removal', function () {
    $this->flowQuote['entitled'] = true;
    $old = writeFlowStage('1.1.4');
    writeFlowStage('9.0.0', $this->flowStatus['manifest']);
    $job = storage_path('app/updates/jobs/'.Str::uuid());
    File::ensureDirectoryExists($job);
    File::put($job.'/journal.json', '{incomplete');
    $this->flowAutomatic->shouldNotReceive('start');
    expect(fn () => app(AutomaticUpdateFlow::class)->start('9.0.0', str_repeat('a', 64)))
        ->toThrow(\App\Services\Updates\UpdateException::class, 'unreadable');
    expect(File::exists(storage_path('app/updates/staged/'.$old)))->toBeTrue();
});
