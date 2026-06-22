<?php

use App\Support\InstallerAppKeyBootstrapper;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

function appKeyBootstrapFixture(string $env, bool $lock = false): string
{
    $base = storage_path('framework/testing/app-key/'.(string) Str::uuid());
    File::ensureDirectoryExists($base.'/storage/app');
    File::put($base.'/.env', $env);
    File::put($base.'/.env.example', $env);

    if ($lock) {
        File::put($base.'/storage/app/installed.lock', 'installed');
    }

    return $base;
}

function fixtureEnvValue(string $path, string $key): string
{
    preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', File::get($path), $matches);

    return trim(trim($matches[1] ?? ''), "\"'");
}

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/app-key'));
});

test('first-run self-hosted blank app key gets generated', function () {
    $base = appKeyBootstrapFixture("DEPLOYMENT_MODE=self_hosted\nINSTALLER_ENABLED=true\nAPP_KEY=\n");

    InstallerAppKeyBootstrapper::bootstrap($base);

    expect(fixtureEnvValue($base.'/.env', 'APP_KEY'))->toStartWith('base64:');
});

test('existing app key is not overwritten', function () {
    $base = appKeyBootstrapFixture("DEPLOYMENT_MODE=self_hosted\nINSTALLER_ENABLED=true\nAPP_KEY=base64:existing\n");

    InstallerAppKeyBootstrapper::bootstrap($base);

    expect(fixtureEnvValue($base.'/.env', 'APP_KEY'))->toBe('base64:existing');
});

test('installed lock prevents app key generation', function () {
    $base = appKeyBootstrapFixture("DEPLOYMENT_MODE=self_hosted\nINSTALLER_ENABLED=true\nAPP_KEY=\n", true);

    InstallerAppKeyBootstrapper::bootstrap($base);

    expect(fixtureEnvValue($base.'/.env', 'APP_KEY'))->toBe('');
});

test('managed mode does not auto-generate app key', function () {
    $base = appKeyBootstrapFixture("DEPLOYMENT_MODE=managed\nINSTALLER_ENABLED=true\nAPP_KEY=\n");

    InstallerAppKeyBootstrapper::bootstrap($base);

    expect(fixtureEnvValue($base.'/.env', 'APP_KEY'))->toBe('');
});
