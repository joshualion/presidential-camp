<?php

use App\Services\Installer\EnvironmentWriterService;
use App\Services\ReleaseManifestSigner;
use App\Services\ReleaseSigningKeyService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->trustBase = storage_path('framework/testing/installer-trust/'.Str::uuid());
    File::ensureDirectoryExists($this->trustBase.'/config');
    File::copy(base_path('config/release-public.pem'), $this->trustBase.'/config/release-public.pem');
    $this->writer = new EnvironmentWriterService($this->trustBase);
});

afterEach(function () {
    File::deleteDirectory($this->trustBase);
});

test('installer configures bundled trust without manual environment editing', function ($oldPath) {
    File::put($this->trustBase.'/.env', 'APP_KEY=existing-installation-key'.PHP_EOL
        .'CAMPAIGN_RELEASE_PUBLIC_KEY_PATH='.$oldPath.PHP_EOL);
    config(['app.key' => 'different-runtime-key']);

    $this->writer->write(['APP_NAME' => 'My Campaign']);
    $saved = $this->writer->read(['APP_KEY', 'CAMPAIGN_RELEASE_PUBLIC_KEY_PATH', 'RELEASE_SIGNING_PUBLIC_KEY']);

    expect($saved)->toBe([
        'APP_KEY' => 'existing-installation-key',
        'CAMPAIGN_RELEASE_PUBLIC_KEY_PATH' => ReleaseSigningKeyService::BUNDLED_PUBLIC_KEY_PATH,
        'RELEASE_SIGNING_PUBLIC_KEY' => '',
    ]);
})->with(['', ReleaseSigningKeyService::LEGACY_PUBLIC_KEY_PATH]);

test('installer generates application key and trust configuration for a new installation', function () {
    config(['app.key' => null]);
    $this->writer->write(['APP_NAME' => 'New Campaign']);
    $saved = $this->writer->read(['APP_KEY', 'CAMPAIGN_RELEASE_PUBLIC_KEY_PATH']);

    expect($saved['APP_KEY'])->toStartWith('base64:')
        ->and(strlen(base64_decode(substr($saved['APP_KEY'], 7))))->toBe(32)
        ->and($saved['CAMPAIGN_RELEASE_PUBLIC_KEY_PATH'])->toBe(ReleaseSigningKeyService::BUNDLED_PUBLIC_KEY_PATH);
});

test('installer preserves deliberately configured trust', function ($path, $inline) {
    File::put($this->trustBase.'/.env', 'APP_KEY=existing'.PHP_EOL
        .'CAMPAIGN_RELEASE_PUBLIC_KEY_PATH='.$path.PHP_EOL.'RELEASE_SIGNING_PUBLIC_KEY='.$inline.PHP_EOL);
    $this->writer->write(['APP_NAME' => 'Custom Campaign']);

    expect($this->writer->read(['CAMPAIGN_RELEASE_PUBLIC_KEY_PATH', 'RELEASE_SIGNING_PUBLIC_KEY']))->toBe([
        'CAMPAIGN_RELEASE_PUBLIC_KEY_PATH' => $path,
        'RELEASE_SIGNING_PUBLIC_KEY' => $inline,
    ]);
})->with([
    ['custom/trusted.pem', ''],
    [ReleaseSigningKeyService::LEGACY_PUBLIC_KEY_PATH, 'custom-inline-key'],
]);

test('installer refuses to configure a missing or invalid bundled key', function ($missing) {
    if ($missing) {
        File::delete($this->trustBase.'/config/release-public.pem');
    } else {
        File::put($this->trustBase.'/config/release-public.pem', 'invalid');
    }
    expect(fn () => $this->writer->write([]))->toThrow(RuntimeException::class);
})->with([true, false]);

test('upgraded application reads bundled trust for only the missing legacy default', function () {
    $keys = new ReleaseSigningKeyService($this->trustBase, [
        'public_key_path' => ReleaseSigningKeyService::LEGACY_PUBLIC_KEY_PATH,
    ]);
    expect($keys->readPublicKey())->toBe(File::get($this->trustBase.'/config/release-public.pem'));
});

test('upgraded application rejects missing custom trust and invalid existing legacy trust', function ($custom) {
    $path = $custom ? 'custom/trusted.pem' : ReleaseSigningKeyService::LEGACY_PUBLIC_KEY_PATH;
    if (! $custom) {
        File::ensureDirectoryExists(dirname($this->trustBase.'/'.$path));
        File::put($this->trustBase.'/'.$path, 'invalid-existing-key');
    }
    $keys = new ReleaseSigningKeyService($this->trustBase, ['public_key_path' => $path]);
    expect(fn () => $keys->readPublicKey())->toThrow(RuntimeException::class);
})->with([true, false]);

test('installer preserves an existing legacy trust file', function () {
    $path = ReleaseSigningKeyService::LEGACY_PUBLIC_KEY_PATH;
    File::ensureDirectoryExists(dirname($this->trustBase.'/'.$path));
    File::copy($this->trustBase.'/config/release-public.pem', $this->trustBase.'/'.$path);
    File::put($this->trustBase.'/.env', 'APP_KEY=existing'.PHP_EOL.'CAMPAIGN_RELEASE_PUBLIC_KEY_PATH='.$path.PHP_EOL);
    $this->writer->write(['APP_NAME' => 'Existing Campaign']);
    expect($this->writer->read(['CAMPAIGN_RELEASE_PUBLIC_KEY_PATH']))->toBe(['CAMPAIGN_RELEASE_PUBLIC_KEY_PATH' => $path]);
});

test('explicit inline trust cannot silently fall back to bundled trust', function () {
    $signer = new ReleaseManifestSigner;
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $otherKey = openssl_pkey_new(['private_key_bits' => 2048]);
    File::put($this->trustBase.'/config/release-public.pem', openssl_pkey_get_details($key)['key']);
    $manifest = ['version' => 'example'];
    openssl_sign($signer->canonicalPayload($manifest), $signature, $key, OPENSSL_ALGO_SHA256);
    $manifest['manifest_signature'] = ['value' => base64_encode($signature)];
    $config = [
        '_base_path' => $this->trustBase,
        'public_key_path' => ReleaseSigningKeyService::LEGACY_PUBLIC_KEY_PATH,
    ];
    expect($signer->verify($manifest, signingConfig: $config))->toBeTrue();
    $config['public_key'] = openssl_pkey_get_details($otherKey)['key'];
    expect($signer->verify($manifest, signingConfig: $config))->toBeFalse();
});

test('installer repairs the copied legacy environment template automatically', function () {
    File::put($this->trustBase.'/.env.example', 'APP_KEY='.PHP_EOL
        .'CAMPAIGN_RELEASE_PUBLIC_KEY_PATH='.ReleaseSigningKeyService::LEGACY_PUBLIC_KEY_PATH.PHP_EOL);
    $this->writer->write(['APP_NAME' => 'Template Campaign']);
    expect($this->writer->read(['CAMPAIGN_RELEASE_PUBLIC_KEY_PATH']))->toBe([
        'CAMPAIGN_RELEASE_PUBLIC_KEY_PATH' => ReleaseSigningKeyService::BUNDLED_PUBLIC_KEY_PATH,
    ]);
});
