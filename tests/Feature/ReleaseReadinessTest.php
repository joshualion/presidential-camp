<?php

use App\Services\ReleaseReadinessService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

function releaseReadinessFixture(array $omit = [], array $extraFiles = []): string
{
    $basePath = storage_path('framework/testing/release-readiness/'.(string) Str::uuid());
    $config = config('release');

    File::ensureDirectoryExists($basePath);

    foreach ($config['writable_directories'] as $directory) {
        File::ensureDirectoryExists($basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $directory));
    }

    $requiredFiles = array_merge($config['required_files'], $config['required_location_csvs']);

    foreach ($requiredFiles as $file) {
        if (in_array($file, $omit, true)) {
            continue;
        }

        $path = $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, match ($file) {
            '.env.example', '.env.production.example' => releaseReadinessEnvExample(),
            'public/build/manifest.json' => '{}',
            default => 'fixture',
        });
    }

    foreach ($extraFiles as $file) {
        $path = $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'local-only');
    }

    return $basePath;
}

function releaseReadinessEnvExample(): string
{
    return implode("\n", [
        'APP_NAME="Campaign Manager"',
        'APP_ENV=production',
        'APP_KEY=',
        'APP_DEBUG=false',
        'APP_URL=https://clientdomain.com',
        'DEPLOYMENT_MODE=self_hosted',
        'INSTALLER_ENABLED=true',
        'CAMPAIGN_PORTAL_API_BASE=https://campaignmanager.ng/api/v2',
        'SESSION_DRIVER=file',
        'BROADCAST_CONNECTION=log',
        'QUEUE_CONNECTION=sync',
        'CACHE_STORE=file',
    ]);
}

function releaseReadinessCheck(array $checks, string $label): array
{
    return collect($checks)->firstWhere('label', $label) ?? [];
}

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/release-readiness'));
});

test('release readiness passes with complete package fixture', function () {
    $service = new ReleaseReadinessService(releaseReadinessFixture(), config('release'));

    $checks = $service->inspect();

    expect($service->hasCriticalFailures($checks))->toBeFalse();
});

test('release readiness detects missing location csv file', function () {
    $missing = 'storage/app/imports/campaign-locations/bootstrap/polling_units.csv';
    $service = new ReleaseReadinessService(releaseReadinessFixture([$missing]), config('release'));

    $check = releaseReadinessCheck($service->inspect(), 'Required location CSV: '.$missing);

    expect($check['status'])->toBe('fail')
        ->and($check['critical'])->toBeTrue();
});

test('release readiness detects missing build manifest', function () {
    $missing = 'public/build/manifest.json';
    $service = new ReleaseReadinessService(releaseReadinessFixture([$missing]), config('release'));

    $check = releaseReadinessCheck($service->inspect(), 'Required file: '.$missing);

    expect($check['status'])->toBe('fail')
        ->and($check['critical'])->toBeTrue();
});

test('release readiness detects missing vendor autoload', function () {
    $missing = 'vendor/autoload.php';
    $service = new ReleaseReadinessService(releaseReadinessFixture([$missing]), config('release'));

    $check = releaseReadinessCheck($service->inspect(), 'Required file: '.$missing);

    expect($check['status'])->toBe('fail')
        ->and($check['critical'])->toBeTrue();
});

test('release readiness treats local-only files as excluded warnings', function () {
    $service = new ReleaseReadinessService(releaseReadinessFixture([], [
        '.env',
        'storage/app/installed.lock',
        'public/uploads/community/photos/test.jpg',
    ]), config('release'));

    $checks = $service->inspect();

    expect(releaseReadinessCheck($checks, 'Release exclude rule: .env')['status'])->toBe('pass')
        ->and(releaseReadinessCheck($checks, 'Release exclude rule: storage/app/installed.lock')['status'])->toBe('pass')
        ->and(releaseReadinessCheck($checks, 'Release exclude rule: public/uploads/community/photos/*')['status'])->toBe('pass')
        ->and(releaseReadinessCheck($checks, 'Local-only file present and excluded: .env')['status'])->toBe('warning')
        ->and(releaseReadinessCheck($checks, 'Local-only file present and excluded: storage/app/installed.lock')['status'])->toBe('warning')
        ->and($service->hasCriticalFailures($checks))->toBeFalse();
});

test('real env examples keep self-hosted safe defaults', function () {
    $checks = app(ReleaseReadinessService::class)->inspect();

    $envChecks = collect($checks)->filter(fn (array $check) => str_contains($check['label'], '.env') && str_contains($check['label'], 'default:'));

    expect($envChecks)->not->toBeEmpty()
        ->and($envChecks->pluck('status')->unique()->values()->all())->toBe(['pass']);
});
