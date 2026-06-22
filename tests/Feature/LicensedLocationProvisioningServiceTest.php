<?php

use App\Models\LocalGovernmentArea;
use App\Models\LocalLicense;
use App\Models\LocalLicenseScope;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\State;
use App\Models\Ward;
use App\Models\LocationProvisioningRun;
use App\Services\LicensedLocationProvisioningService;
use Illuminate\Support\Facades\Config;

function writeProvisioningFixture(): string
{
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'campaign-location-fixtures'.DIRECTORY_SEPARATOR.uniqid('locations-', true);
    mkdir($path, 0777, true);

    file_put_contents($path.'/states.csv', implode("\n", [
        'country_name,region_name,state_code,name',
        'Nigeria,South South,CR,Cross River',
        'Nigeria,North Central,BN,Benue',
    ]));

    file_put_contents($path.'/senatorial_districts.csv', implode("\n", [
        'state_code,state_name,name',
        'CR,Cross River,Cross River South',
        'CR,Cross River,Cross River Central',
        'BN,Benue,Benue South',
    ]));

    file_put_contents($path.'/federal_constituencies.csv', implode("\n", [
        'state_code,state_name,senatorial_district_name,name',
        'CR,Cross River,Cross River South,Calabar Municipality/Odukpani',
        'CR,Cross River,Cross River South,Calabar South/Akpabuyo/Bakassi',
        'BN,Benue,Benue South,Otukpo/Ohimini',
    ]));

    file_put_contents($path.'/local_government_areas.csv', implode("\n", [
        'state_code,state_name,lga_code,name,senatorial_district_name,federal_constituency_name',
        'CR,Cross River,CR01,Calabar South,Cross River South,Calabar South/Akpabuyo/Bakassi',
        'CR,Cross River,CR02,Odukpani,Cross River South,Calabar Municipality/Odukpani',
        'BN,Benue,BN01,Otukpo,Benue South,Otukpo/Ohimini',
    ]));

    file_put_contents($path.'/wards.csv', implode("\n", [
        'state_code,state_name,lga_code,lga_name,ward_code,name',
        'CR,Cross River,CR01,Calabar South,W01,Ward One',
        'CR,Cross River,CR02,Odukpani,W02,Ward Two',
        'BN,Benue,BN01,Otukpo,W03,Ward Three',
    ]));

    file_put_contents($path.'/polling_units.csv', implode("\n", [
        'state_code,state_name,lga_code,lga_name,ward_code,ward_name,pu_code,inec_full_code,name,senatorial_district_name,federal_constituency_name',
        'CR,Cross River,CR01,Calabar South,W01,Ward One,PU01,CR-01-W01-PU01,PU One,Cross River South,Calabar South/Akpabuyo/Bakassi',
        'CR,Cross River,CR02,Odukpani,W02,Ward Two,PU02,CR-02-W02-PU02,PU Two,Cross River South,Calabar Municipality/Odukpani',
        'BN,Benue,BN01,Otukpo,W03,Ward Three,PU03,BN-01-W03-PU03,PU Three,Benue South,Otukpo/Ohimini',
    ]));

    return $path;
}

function makeProvisioningLicense(string $packageType, string $scopeType, array $scope): LocalLicense
{
    $license = LocalLicense::query()->create([
        'license_key' => 'LIC-TEST',
        'deployment_mode' => 'self_hosted',
        'package_type' => $packageType,
        'status' => 'active',
    ]);

    LocalLicenseScope::query()->create(array_merge([
        'local_license_id' => $license->id,
        'scope_type' => $scopeType,
        'scope_name' => $scope['scope_name'] ?? null,
    ], $scope));

    return $license->fresh('scope');
}

it('dry-runs without inserting location data', function () {
    Config::set('campaign.deployment_mode', 'self_hosted');
    $source = writeProvisioningFixture();
    $license = makeProvisioningLicense('governorship', 'state', [
        'state_name' => 'Cross River',
        'scope_name' => 'Cross River',
    ]);

    $result = app(LicensedLocationProvisioningService::class)->provision($license, true, $source);

    expect($result['status'])->toBe('dry_run')
        ->and($result['summary']['states']['selected'])->toBe(1)
        ->and(State::query()->count())->toBe(0);
});

it('provisions only the licensed governorship state and parent region', function () {
    Config::set('campaign.deployment_mode', 'self_hosted');
    $source = writeProvisioningFixture();
    $license = makeProvisioningLicense('governorship', 'state', [
        'state_name' => 'Cross River',
        'scope_name' => 'Cross River',
    ]);

    app(LicensedLocationProvisioningService::class)->provision($license, false, $source);

    expect(State::query()->pluck('name')->all())->toBe(['Cross River'])
        ->and(Region::query()->pluck('name')->all())->toBe(['South South'])
        ->and(LocalGovernmentArea::query()->count())->toBe(2)
        ->and(Ward::query()->count())->toBe(2)
        ->and(PollingUnit::query()->count())->toBe(2);
});

it('provisions only an lga scope and remains idempotent on rerun', function () {
    Config::set('campaign.deployment_mode', 'self_hosted');
    $source = writeProvisioningFixture();
    $license = makeProvisioningLicense('lga', 'lga', [
        'state_name' => 'Cross River',
        'lga_name' => 'Calabar South',
        'scope_name' => 'Calabar South',
    ]);
    $service = app(LicensedLocationProvisioningService::class);

    $service->provision($license, false, $source);
    $service->provision($license->fresh('scope'), false, $source);

    expect(State::query()->pluck('name')->all())->toBe(['Cross River'])
        ->and(LocalGovernmentArea::query()->pluck('name')->all())->toBe(['Calabar South'])
        ->and(Ward::query()->pluck('name')->all())->toBe(['Ward One'])
        ->and(PollingUnit::query()->pluck('name')->all())->toBe(['PU One']);
});

it('fails clearly when required csv files are missing', function () {
    Config::set('campaign.deployment_mode', 'self_hosted');
    $source = sys_get_temp_dir().DIRECTORY_SEPARATOR.'campaign-location-fixtures'.DIRECTORY_SEPARATOR.uniqid('missing-', true);
    mkdir($source, 0777, true);
    $license = makeProvisioningLicense('governorship', 'state', [
        'state_name' => 'Cross River',
        'scope_name' => 'Cross River',
    ]);

    app(LicensedLocationProvisioningService::class)->provision($license, true, $source);
})->throws(RuntimeException::class, 'Location data files are missing');

it('skips managed mode without a local license', function () {
    Config::set('campaign.deployment_mode', 'managed');
    $source = writeProvisioningFixture();

    $result = app(LicensedLocationProvisioningService::class)->provision(null, true, $source);

    expect($result['status'])->toBe('skipped')
        ->and(State::query()->count())->toBe(0);
});

it('presidential provisioning can run in resumable chunks without duplicates', function () {
    Config::set('campaign.deployment_mode', 'self_hosted');
    $source = writeProvisioningFixture();
    $license = makeProvisioningLicense('presidential', 'national', [
        'scope_name' => 'National',
    ]);
    $service = app(LicensedLocationProvisioningService::class);

    $run = $service->startOrResumeChunked($license, $source);
    expect($run->status)->toBe('pending');

    do {
        $result = $service->processNextChunk($run->fresh(), 1);
        $run = LocationProvisioningRun::query()->find($run->id);
    } while ($result['status'] !== 'completed');

    $service->processNextChunk($run->fresh(), 1);

    expect($result['next_action'])->toBe('complete')
        ->and(State::query()->count())->toBe(2)
        ->and(LocalGovernmentArea::query()->count())->toBe(3)
        ->and(Ward::query()->count())->toBe(3)
        ->and(PollingUnit::query()->count())->toBe(3);
});

it('polling unit parser repairs malformed physical lines and skips invalid rows without crashing', function () {
    Config::set('campaign.deployment_mode', 'self_hosted');
    $source = writeProvisioningFixture();
    file_put_contents($source.'/polling_units.csv', "\n".implode("\n", [
        'CR,Cross River,CR01,Calabar South,W01,Ward One,PU04,CR-01-W01-PU04,"Name With, Extra, Commas",Cross River South,Calabar South/Akpabuyo/Bakassi',
        'CR,Cross River,CR01,Calabar South,W01,Ward One,PU05,CR-01-W01-PU05,"Broken \a\""""",Cross River South,Calabar South/Akpabuyo/Bakassi',
        'CR,Cross River,CR01,Calabar South,W01',
        'CR,Cross River,CR01,Calabar South,W01,Ward One,PU06,CR-01-W01-PU06,'.str_repeat('Long Name ', 40).',Cross River South,Calabar South/Akpabuyo/Bakassi',
    ]), FILE_APPEND);

    $license = makeProvisioningLicense('presidential', 'national', [
        'scope_name' => 'National',
    ]);
    $service = app(LicensedLocationProvisioningService::class);
    $run = $service->startOrResumeChunked($license, $source);

    do {
        $result = $service->processNextChunk($run->fresh(), 2);
        $run = LocationProvisioningRun::query()->find($run->id);
    } while ($result['status'] !== 'completed');

    expect(PollingUnit::query()->where('inec_full_code', 'CR-01-W01-PU04')->value('name'))->toBe('Name With, Extra, Commas')
        ->and(PollingUnit::query()->where('inec_full_code', 'CR-01-W01-PU05')->value('name'))->toBe('Broken a')
        ->and(mb_strlen(PollingUnit::query()->where('inec_full_code', 'CR-01-W01-PU06')->value('name')))->toBeLessThanOrEqual(255)
        ->and($run->summary['polling_units']['skipped'])->toBeGreaterThan(0);
});
