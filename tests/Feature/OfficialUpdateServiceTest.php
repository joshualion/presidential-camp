<?php

use App\Services\OfficialUpdateService;
use App\Services\ReleaseManifestSigner;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
    openssl_pkey_export($key, $private);
    $this->updatePrivateKey = $private;
    config(['release.signing.public_key' => openssl_pkey_get_details($key)['key']]);
    $this->updateFixture = storage_path('framework/testing/updates/'.Str::uuid());
    File::ensureDirectoryExists($this->updateFixture);
});

afterEach(function () {
    File::deleteDirectory($this->updateFixture);
    File::delete(storage_path('app/updates/check.json'));
});

function signUpdateFixture(array $manifest, string $privateKey): array
{
    $signer = new ReleaseManifestSigner();
    openssl_sign($signer->canonicalPayload($manifest), $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $manifest['manifest_signature'] = ['algorithm' => 'rsa-sha256', 'key_id' => 'test', 'value' => base64_encode($signature)];
    return $manifest;
}

test('official checks authenticate metadata and transmit no installation identity', function () {
    $manifest = signUpdateFixture(['version' => '9.0.0', 'checksum_sha256' => str_repeat('a', 64), 'size_bytes' => 100], $this->updatePrivateKey);
    $base = 'https://github.com/joshualion/Campaign-Manager/releases/download/v9.0.0/';
    Http::fake([
        'https://api.github.com/repos/joshualion/Campaign-Manager/releases/latest' => Http::response([
            'tag_name' => 'v9.0.0', 'draft' => false, 'prerelease' => false,
            'assets' => [
                ['name' => 'release-manifest.json', 'browser_download_url' => $base.'release-manifest.json'],
                ['name' => 'campaign-manager.zip', 'browser_download_url' => $base.'campaign-manager.zip'],
            ],
        ]),
        $base.'release-manifest.json' => Http::response($manifest),
    ]);
    $result = app(OfficialUpdateService::class)->check();
    expect($result['verified'])->toBeTrue()->and($result['available'])->toBeTrue();
    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => $request->method() === 'GET' && $request->body() === '');
});

test('unsigned or altered update metadata cannot authorize a package', function () {
    $manifest = ['version' => '9.0.0', 'checksum_sha256' => str_repeat('a', 64), 'size_bytes' => 100];
    expect(fn () => app(OfficialUpdateService::class)->validateManifest($manifest, '9.0.0'))->toThrow(RuntimeException::class, 'signature');
    $manifest = signUpdateFixture($manifest, $this->updatePrivateKey);
    $manifest['size_bytes'] = 200;
    expect(fn () => app(OfficialUpdateService::class)->validateManifest($manifest, '9.0.0'))->toThrow(RuntimeException::class, 'signature');
});

test('legacy releases show manual availability without enabling verified preparation', function () {
    Http::fake(['https://api.github.com/repos/joshualion/Campaign-Manager/releases/latest' => Http::response(['tag_name' => 'v9.0.0', 'assets' => []])]);
    $result = app(OfficialUpdateService::class)->check();
    expect($result['available'])->toBeTrue()->and($result['verified'])->toBeFalse();
});

test('unsafe archive paths and symbolic links are rejected before extraction', function () {
    foreach (['../outside.php', '/absolute.php', '.env', 'app/../../outside.php', 'app\\outside.php', 'C:evil.php'] as $name) {
        $zipPath = $this->updateFixture.'/unsafe.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString($name, 'unsafe');
        $zip->close();
        $destination = $this->updateFixture.'/application';
        expect(fn () => app(OfficialUpdateService::class)->extractVerifiedPackage($zipPath, $destination))->toThrow(RuntimeException::class, 'unsafe path');
        expect(File::exists($destination))->toBeFalse();
    }
});

test('a signed staged package verifies every application file and rejects changes', function () {
    $contents = '<?php return true;';
    $inner = signUpdateFixture(['version' => '9.0.0', 'checksums' => ['app/Example.php' => hash('sha256', $contents)]], $this->updatePrivateKey);
    $zipPath = $this->updateFixture.'/valid.zip';
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFromString('app/Example.php', $contents);
    $zip->addFromString('release-manifest.json', json_encode($inner));
    $zip->close();
    app(OfficialUpdateService::class)->extractVerifiedPackage($zipPath, $this->updateFixture.'/valid', '9.0.0');
    expect(File::get($this->updateFixture.'/valid/app/Example.php'))->toBe($contents);

    $zip->open($zipPath);
    $zip->addFromString('app/Example.php', 'tampered');
    $zip->close();
    expect(fn () => app(OfficialUpdateService::class)->extractVerifiedPackage($zipPath, $this->updateFixture.'/invalid', '9.0.0'))->toThrow(RuntimeException::class, 'checksum');
});
