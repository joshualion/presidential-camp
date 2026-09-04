<?php

use App\Models\LocalLicense;
use App\Services\LocalLicenseService;
use App\Services\ModuleGateService;
use App\Services\PackageScopeService;

test('managed mode falls back without a local license', function () {
    config(['campaign.deployment_mode' => 'managed']);

    $scope = app(PackageScopeService::class)->current();

    expect($scope->deployment_mode)->toBe('managed')
        ->and($scope->package_type)->toBe('presidential')
        ->and($scope->fallback_used)->toBeTrue()
        ->and(app(ModuleGateService::class)->enabled('elections'))->toBeTrue();
});

test('self hosted local license package and scope are resolved', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    $license = LocalLicense::create([
        'license_key' => 'CM-TEST-KEY',
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
    ]);

    $license->scope()->create([
        'scope_type' => 'state',
        'state_id' => 9,
        'state_name' => 'Lagos',
        'scope_name' => 'Lagos',
    ]);

    $license->modules()->createMany([
        ['module_key' => 'elections', 'enabled' => true],
        ['module_key' => 'finance', 'enabled' => false],
    ]);

    $service = app(PackageScopeService::class);
    $scope = $service->current();

    expect($scope->deployment_mode)->toBe('self_hosted')
        ->and($scope->package_type)->toBe('governorship')
        ->and($scope->scope_type)->toBe('state')
        ->and($scope->scope_name)->toBe('Lagos')
        ->and($scope->state_id)->toBe(9)
        ->and($service->dashboardLabel())->toBe('State Campaign Dashboard')
        ->and($service->display())->toBe('Governorship - Lagos')
        ->and(app(ModuleGateService::class)->enabled('elections'))->toBeTrue()
        ->and(app(ModuleGateService::class)->disabled('finance'))->toBeTrue();
});

test('self hosted without local license does not crash', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    $scope = app(PackageScopeService::class)->current();

    expect($scope->deployment_mode)->toBe('self_hosted')
        ->and($scope->fallback_used)->toBeTrue()
        ->and(app(ModuleGateService::class)->enabled('elections'))->toBeTrue();
});

test('activation payload module map preserves disabled modules', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    app(LocalLicenseService::class)->persistActivation('CM-MODULE-MAP', [
        'license' => [
            'deployment_mode' => 'self_hosted',
            'package_type' => 'lga',
            'status' => 'active',
        ],
        'scope' => [
            'scope_type' => 'lga',
            'lga_id' => 44,
            'lga_name' => 'Ikeja',
            'scope_name' => 'Ikeja',
        ],
        'modules' => [
            'elections' => true,
            'finance' => false,
        ],
    ]);

    expect(app(ModuleGateService::class)->enabled('elections'))->toBeTrue()
        ->and(app(ModuleGateService::class)->disabled('finance'))->toBeTrue()
        ->and(app(PackageScopeService::class)->packageType())->toBe('lga');
});

