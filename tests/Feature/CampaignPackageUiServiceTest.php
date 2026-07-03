<?php

use App\Models\LocalLicense;
use App\Services\CampaignPackageUiService;

function createUiLicense(string $package, array $scope = []): void
{
    $license = LocalLicense::create([
        'license_key' => 'CM-UI-'.strtoupper(str_replace('_', '-', $package)),
        'deployment_mode' => 'self_hosted',
        'package_type' => $package,
        'status' => 'active',
    ]);

    $license->scope()->create(array_merge([
        'scope_type' => 'national',
        'scope_name' => 'Nigeria',
    ], $scope));
}

test('presidential package returns national labels', function () {
    config(['campaign.deployment_mode' => 'managed']);

    $ui = app(CampaignPackageUiService::class);

    expect($ui->dashboardTitle())->toBe('National Campaign Dashboard')
        ->and($ui->statisticsTitle())->toBe('National Statistics')
        ->and($ui->electionStatisticsTitle())->toBe('National Election Statistics')
        ->and($ui->geographyDistributionTitle())->toBe('Members Distribution by Geopolitical Region')
        ->and($ui->primaryGeographyLabel())->toBe('Regions')
        ->and($ui->supportsChart('region'))->toBeTrue()
        ->and($ui->supportsChart('state'))->toBeTrue();
});

test('governorship package returns state labels', function () {
    createUiLicense('governorship', [
        'scope_type' => 'state',
        'state_id' => 9,
        'state_name' => 'Cross River',
        'scope_name' => 'Cross River',
    ]);

    $ui = app(CampaignPackageUiService::class);

    expect($ui->dashboardTitle())->toBe('State Campaign Dashboard')
        ->and($ui->statisticsTitle())->toBe('State Campaign Statistics')
        ->and($ui->statisticsTitle())->not->toBe('National Statistics')
        ->and($ui->electionStatisticsTitle())->toBe('State Election Statistics')
        ->and($ui->geographyDistributionTitle())->toBe('Members Distribution by Local Government')
        ->and($ui->primaryGeographyLabel())->toBe('LGAs')
        ->and($ui->scopeDisplayName())->toBe('Cross River')
        ->and($ui->memberNavigationLevels())->toBe(['lgas', 'wards', 'polling_units'])
        ->and($ui->supportsChart('region'))->toBeFalse()
        ->and($ui->supportsChart('state'))->toBeFalse();
});

test('senatorial package returns senatorial labels', function () {
    createUiLicense('senatorial', [
        'scope_type' => 'senatorial_district',
        'senatorial_district_id' => 12,
        'senatorial_district_name' => 'Cross River South',
        'scope_name' => 'Cross River South',
    ]);

    $ui = app(CampaignPackageUiService::class);

    expect($ui->dashboardTitle())->toBe('Senatorial District Campaign Dashboard')
        ->and($ui->statisticsTitle())->toBe('Senatorial District Statistics')
        ->and($ui->electionStatisticsTitle())->toBe('Senatorial District Election Statistics')
        ->and($ui->geographyDistributionTitle())->toBe('Members Distribution by LGA / Ward')
        ->and($ui->primaryGeographyLabel())->toBe('LGAs');
});

test('federal constituency package returns federal constituency labels', function () {
    createUiLicense('federal_constituency', [
        'scope_type' => 'federal_constituency',
        'federal_constituency_id' => 22,
        'federal_constituency_name' => 'Calabar Municipal/Odukpani',
        'scope_name' => 'Calabar Municipal/Odukpani',
    ]);

    $ui = app(CampaignPackageUiService::class);

    expect($ui->dashboardTitle())->toBe('Federal Constituency Campaign Dashboard')
        ->and($ui->statisticsTitle())->toBe('Federal Constituency Statistics')
        ->and($ui->electionStatisticsTitle())->toBe('Federal Constituency Election Statistics')
        ->and($ui->geographyDistributionTitle())->toBe('Members Distribution by LGA / Ward')
        ->and($ui->primaryGeographyLabel())->toBe('LGAs');
});

test('lga package returns lga and ward labels', function () {
    createUiLicense('lga', [
        'scope_type' => 'lga',
        'lga_id' => 44,
        'lga_name' => 'Calabar Municipal',
        'scope_name' => 'Calabar Municipal',
    ]);

    $ui = app(CampaignPackageUiService::class);

    expect($ui->dashboardTitle())->toBe('Local Government Campaign Dashboard')
        ->and($ui->statisticsTitle())->toBe('LGA Campaign Statistics')
        ->and($ui->electionStatisticsTitle())->toBe('LGA Election Statistics')
        ->and($ui->geographyDistributionTitle())->toBe('Members Distribution by Ward')
        ->and($ui->primaryGeographyLabel())->toBe('Wards')
        ->and($ui->locationHierarchyLabels())->toBe(['Wards', 'Polling Units'])
        ->and($ui->memberNavigationLevels())->toBe(['wards', 'polling_units']);
});

test('missing local license fallback does not crash', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    $ui = app(CampaignPackageUiService::class);

    expect($ui->dashboardTitle())->toBe('National Campaign Dashboard')
        ->and($ui->statisticsTitle())->toBe('National Statistics')
        ->and($ui->emptyStateMessage('chart data'))->toBe('No chart data is available for this licensed campaign scope yet.');
});
