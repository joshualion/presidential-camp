<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\ReleaseSigningKeyService;

function releaseKeysFixtureConfig(): array
{
    $base = storage_path('framework/testing/release-keys/'.(string) Str::uuid());

    return [
        'base' => $base,
        'config' => [
            'keys_dir' => $base.'/storage/app/release-keys',
            'private_key_path' => $base.'/storage/app/release-keys/campaign_release_private.pem',
            'public_key_path' => $base.'/storage/app/release-keys/campaign_release_public.pem',
            'public_key_id' => 'test-release-key',
        ],
    ];
}

function useReleaseKeysFixture(): array
{
    $fixture = releaseKeysFixtureConfig();
    Config::set('release.signing', $fixture['config']);
    app()->bind(ReleaseSigningKeyService::class, fn () => new ReleaseSigningKeyService(base_path(), Config::get('release.signing')));

    return $fixture;
}

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/release-keys'));
});

test('campaign release keys generate creates private and public keys', function () {
    $fixture = useReleaseKeysFixture();

    $exit = Artisan::call('campaign:release-keys', ['--generate' => true]);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and(File::isFile($fixture['config']['private_key_path']))->toBeTrue()
        ->and(File::isFile($fixture['config']['public_key_path']))->toBeTrue()
        ->and($output)->toContain('Release signing keys generated.')
        ->and($output)->not->toContain(File::get($fixture['config']['private_key_path']));
});

test('campaign release keys does not overwrite keys by default', function () {
    $fixture = useReleaseKeysFixture();

    Artisan::call('campaign:release-keys', ['--generate' => true]);
    $privateHash = hash_file('sha256', $fixture['config']['private_key_path']);
    $publicHash = hash_file('sha256', $fixture['config']['public_key_path']);

    Artisan::call('campaign:release-keys', ['--generate' => true]);

    expect(hash_file('sha256', $fixture['config']['private_key_path']))->toBe($privateHash)
        ->and(hash_file('sha256', $fixture['config']['public_key_path']))->toBe($publicHash);
});

test('campaign release keys verify confirms signing works', function () {
    useReleaseKeysFixture();

    Artisan::call('campaign:release-keys', ['--generate' => true]);
    $exit = Artisan::call('campaign:release-keys', ['--verify' => true]);

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toContain('Release signing key pair verified.');
});

test('campaign release keys status does not print private key contents', function () {
    $fixture = useReleaseKeysFixture();

    Artisan::call('campaign:release-keys', ['--generate' => true]);
    $privateContents = File::get($fixture['config']['private_key_path']);

    Artisan::call('campaign:release-keys', ['--generate' => true, '--status' => true]);
    $output = Artisan::output();

    expect($output)->not->toContain($privateContents)
        ->and($output)->toContain('Private key: present');
});
