<?php

use App\Models\Country;
use App\Models\LocalGovernmentArea;
use App\Models\LocalLicense;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\State;
use App\Models\Ward;
use App\Services\DashboardDrilldownService;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function createDrilldownStateSet(): array
{
    $country = Country::create(['uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    $region = Region::create(['name' => 'South South', 'country_id' => $country->id]);
    $licensedState = State::create(['name' => 'Cross River', 'region_id' => $region->id]);
    $otherState = State::create(['name' => 'Lagos', 'region_id' => $region->id]);
    $licensedLga = LocalGovernmentArea::create(['name' => 'Calabar Municipal', 'state_id' => $licensedState->id]);
    $otherLga = LocalGovernmentArea::create(['name' => 'Ikeja', 'state_id' => $otherState->id]);

    return compact('country', 'region', 'licensedState', 'otherState', 'licensedLga', 'otherLga');
}

function createDrilldownLicense(string $package, array $scope): LocalLicense
{
    $license = LocalLicense::create([
        'license_key' => 'CM-DRILLDOWN-'.strtoupper($package),
        'deployment_mode' => 'self_hosted',
        'package_type' => $package,
        'status' => 'active',
    ]);

    $license->scope()->create($scope);

    return $license;
}

test('in scope state dashboard record resolves', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    $locations = createDrilldownStateSet();

    createDrilldownLicense('governorship', [
        'scope_type' => 'state',
        'state_id' => $locations['licensedState']->id,
        'state_name' => $locations['licensedState']->name,
        'scope_name' => $locations['licensedState']->name,
    ]);

    $state = app(DashboardDrilldownService::class)->state($locations['licensedState']->uuid);

    expect($state->is($locations['licensedState']))->toBeTrue();
});

test('governorship install cannot open another state dashboard', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    $locations = createDrilldownStateSet();

    createDrilldownLicense('governorship', [
        'scope_type' => 'state',
        'state_id' => $locations['licensedState']->id,
        'state_name' => $locations['licensedState']->name,
        'scope_name' => $locations['licensedState']->name,
    ]);

    app(DashboardDrilldownService::class)->state($locations['otherState']->uuid);
})->throws(HttpException::class, 'This record is outside the licensed campaign scope.');

test('missing dashboard uuid returns controlled not found', function () {
    app(DashboardDrilldownService::class)->state('00000000-0000-4000-8000-000000000000');
})->throws(NotFoundHttpException::class, 'State dashboard record was not found.');

test('lga license cannot open another lga dashboard', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    $locations = createDrilldownStateSet();

    createDrilldownLicense('lga', [
        'scope_type' => 'lga',
        'state_id' => $locations['licensedState']->id,
        'state_name' => $locations['licensedState']->name,
        'lga_id' => $locations['licensedLga']->id,
        'lga_name' => $locations['licensedLga']->name,
        'scope_name' => $locations['licensedLga']->name,
    ]);

    app(DashboardDrilldownService::class)->lga($locations['otherLga']->uuid);
})->throws(HttpException::class, 'This record is outside the licensed campaign scope.');

test('ward and polling unit drilldowns support lga relationship alias', function () {
    config(['campaign.deployment_mode' => 'managed']);
    $locations = createDrilldownStateSet();
    $ward = Ward::create([
        'name' => 'Ward 1',
        'lga_id' => $locations['licensedLga']->id,
    ]);
    $pollingUnit = PollingUnit::create([
        'name' => 'PU 1',
        'ward_id' => $ward->id,
    ]);

    $resolvedWard = app(DashboardDrilldownService::class)->ward($ward->uuid);
    $resolvedPollingUnit = app(DashboardDrilldownService::class)->pollingUnit($pollingUnit->uuid);

    expect($resolvedWard->relationLoaded('lga'))->toBeTrue()
        ->and($resolvedWard->lga->is($locations['licensedLga']))->toBeTrue()
        ->and($resolvedPollingUnit->ward->relationLoaded('lga'))->toBeTrue()
        ->and($resolvedPollingUnit->ward->lga->is($locations['licensedLga']))->toBeTrue();
});

test('managed mode leaves drilldown guards as no op', function () {
    config(['campaign.deployment_mode' => 'managed']);
    $locations = createDrilldownStateSet();

    $state = app(DashboardDrilldownService::class)->state($locations['otherState']->uuid);

    expect($state->is($locations['otherState']))->toBeTrue();
});
