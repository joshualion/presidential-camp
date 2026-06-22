<?php

use App\Models\LocationProvisioningRun;
use App\Models\LocalLicense;
use App\Services\LocationProvisioningIntegrityService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

test('installer run view hides internal location source details', function () {
    $html = View::make('installer.step', [
        'step' => 'run',
        'steps' => ['welcome' => 'Welcome', 'run' => 'Run', 'complete' => 'Complete'],
        'state' => [],
        'previousStep' => 'admin',
        'completedSteps' => ['welcome'],
        'clickableSteps' => ['welcome', 'run'],
        'welcomeMeta' => [
            'app_url' => 'https://clientdomain.com',
            'detected_domain' => 'clientdomain.com',
            'deployment_mode' => 'self_hosted',
        ],
        'display' => ['package_type' => 'presidential', 'scope_name' => 'National'],
        'provisioningPreflight' => [
            'ok' => true,
            'message' => 'Campaign location data package: Ready.',
            'missing' => [],
        ],
        'provisioningCopy' => 'National campaign location setup may take a few minutes.',
        'provisioningStarted' => false,
        'errors' => new ViewErrorBag(),
    ])->render();

    expect($html)->toContain('Campaign location data package: Ready.')
        ->and($html)->not->toContain('storage/app/imports')
        ->and($html)->not->toContain('polling_units.csv')
        ->and($html)->not->toContain('states.csv')
        ->and($html)->not->toContain('Provisioning source');
});

test('installer license preview hides raw module keys', function () {
    $html = View::make('installer.step', [
        'step' => 'license',
        'steps' => ['welcome' => 'Welcome', 'license' => 'License'],
        'state' => [],
        'previousStep' => 'storage',
        'completedSteps' => ['welcome'],
        'clickableSteps' => ['welcome', 'license'],
        'welcomeMeta' => [
            'app_url' => 'https://clientdomain.com',
            'detected_domain' => 'clientdomain.com',
            'deployment_mode' => 'self_hosted',
        ],
        'display' => [
            'package_type' => 'presidential',
            'scope_type' => 'national',
            'scope_name' => 'National',
            'licensed_features' => 4,
        ],
        'errors' => new ViewErrorBag(),
    ])->render();

    expect($html)->toContain('Licensed features')
        ->and($html)->not->toContain('advanced_analytics')
        ->and($html)->not->toContain('bulk_sms')
        ->and($html)->not->toContain('operations_center');
});

test('full national integrity blocks install lock when expected counts are short', function () {
    config()->set('release.profiles.full.expected_location_records', [
        'regions' => 6,
        'states' => 37,
        'senatorial_districts' => 109,
        'federal_constituencies' => 360,
        'lgas' => 774,
        'wards' => 8809,
        'polling_units' => 176846,
    ]);

    $license = LocalLicense::query()->create([
        'license_key' => 'license-key',
        'deployment_mode' => 'self_hosted',
        'package_type' => 'presidential',
        'status' => 'active',
    ]);
    $license->scope()->create([
        'scope_type' => 'national',
        'scope_name' => 'National',
    ]);

    $result = app(LocationProvisioningIntegrityService::class)
        ->verifyBeforeInstallLock($license->fresh('scope'), ['data_profile' => 'full', 'scope_type' => 'national']);

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toContain('Campaign location setup is incomplete');
});

test('bootstrap profile does not enforce full national totals', function () {
    $license = LocalLicense::query()->create([
        'license_key' => 'license-key',
        'deployment_mode' => 'self_hosted',
        'package_type' => 'presidential',
        'status' => 'active',
    ]);
    $license->scope()->create([
        'scope_type' => 'national',
        'scope_name' => 'National',
    ]);

    $result = app(LocationProvisioningIntegrityService::class)
        ->verifyBeforeInstallLock($license->fresh('scope'), ['data_profile' => 'bootstrap', 'scope_type' => 'national']);

    expect($result['ok'])->toBeTrue();
});

test('installer status does not dump raw sql or giant provisioning row', function () {
    LocationProvisioningRun::query()->create([
        'status' => 'failed',
        'package_type' => 'presidential',
        'scope_type' => 'national',
        'current_stage' => 'polling_units',
        'processed_rows' => 112839,
        'total_rows' => 185747,
        'error_message' => 'SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column name at row containing Birni Yauri '.str_repeat('21,Kebbi,15,Ngaski,', 30),
    ]);

    $this->artisan('campaign:installer-status')
        ->expectsOutputToContain('Latest location setup:')
        ->doesntExpectOutputToContain('SQLSTATE[22001]')
        ->doesntExpectOutputToContain('Birni Yauri 21,Kebbi')
        ->assertExitCode(0);
});
