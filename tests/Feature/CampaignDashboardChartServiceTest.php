<?php

use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\LocalGovernmentArea;
use App\Models\LocalLicense;
use App\Models\Region;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use App\Services\CampaignDashboardChartService;
use Illuminate\Support\Str;

function createChartLocationSet(): array
{
    $country = Country::create(['uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    $region = Region::create(['name' => 'South South', 'country_id' => $country->id]);
    $licensedState = State::create(['name' => 'Cross River', 'region_id' => $region->id]);
    $otherState = State::create(['name' => 'Lagos', 'region_id' => $region->id]);
    $licensedLga = LocalGovernmentArea::create(['name' => 'Calabar Municipal', 'state_id' => $licensedState->id]);
    $otherLga = LocalGovernmentArea::create(['name' => 'Ikeja', 'state_id' => $otherState->id]);
    $licensedWard = Ward::create(['name' => 'Ward 1', 'lga_id' => $licensedLga->id]);
    $otherWard = Ward::create(['name' => 'Ward 2', 'lga_id' => $otherLga->id]);

    return compact('country', 'region', 'licensedState', 'otherState', 'licensedLga', 'otherLga', 'licensedWard', 'otherWard');
}

function createChartLicense(string $package, array $scope): LocalLicense
{
    $license = LocalLicense::create([
        'license_key' => 'CM-CHART-'.strtoupper(str_replace('_', '-', $package)).'-'.random_int(1000, 9999),
        'deployment_mode' => 'self_hosted',
        'package_type' => $package,
        'status' => 'active',
    ]);

    $license->scope()->create($scope);

    return $license;
}

function createChartUser(string $email, array $attributes = []): User
{
    $user = User::create(array_merge([
        'uuid' => (string) Str::uuid(),
        'firstname' => 'Chart',
        'lastname' => 'User',
        'username' => str_replace(['@', '.'], '_', $email),
        'email' => $email,
        'password' => 'password',
        'access_level' => 'user',
        'status' => 'active',
        'validVoter' => 'yes',
    ], $attributes));

    if ($attributes !== []) {
        $user->forceFill($attributes)->save();
    }

    return $user;
}

test('empty member data returns empty-state chart json', function () {
    AgeGrade::create(['name' => '18-25']);

    $payload = app(CampaignDashboardChartService::class)->age();

    expect($payload['labels'])->toBe(['18-25'])
        ->and($payload['datasets'][0]['data'])->toBe([0])
        ->and($payload['total'])->toBe(0)
        ->and($payload['empty'])->toBeTrue()
        ->and($payload['message'])->toBe('No member data available yet.');
});

test('governorship chart config hides geopolitical region as primary chart', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    $locations = createChartLocationSet();

    createChartLicense('governorship', [
        'scope_type' => 'state',
        'state_id' => $locations['licensedState']->id,
        'state_name' => $locations['licensedState']->name,
        'scope_name' => $locations['licensedState']->name,
    ]);

    $keys = collect(app(CampaignDashboardChartService::class)->visibleCharts())->pluck('key')->all();

    expect($keys)->toContain('geography')
        ->and($keys)->not->toContain('region');
});

test('governorship geography distribution returns only licensed state lgas', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    $locations = createChartLocationSet();

    createChartLicense('governorship', [
        'scope_type' => 'state',
        'state_id' => $locations['licensedState']->id,
        'state_name' => $locations['licensedState']->name,
        'scope_name' => $locations['licensedState']->name,
    ]);

    createChartUser('in-scope@example.test', [
        'gender' => 'male',
        'state_id' => $locations['licensedState']->id,
        'lga_id' => $locations['licensedLga']->id,
    ]);
    createChartUser('out-of-scope@example.test', [
        'gender' => 'female',
        'state_id' => $locations['otherState']->id,
        'lga_id' => $locations['otherLga']->id,
    ]);

    $payload = app(CampaignDashboardChartService::class)->packageGeography();

    expect($payload['labels'])->toBe([$locations['licensedLga']->name])
        ->and($payload['datasets'][0]['data'])->toBe([1])
        ->and($payload['total'])->toBe(1)
        ->and($payload['empty'])->toBeFalse();
});

test('lga package geography distribution uses wards', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    $locations = createChartLocationSet();

    createChartLicense('lga', [
        'scope_type' => 'lga',
        'state_id' => $locations['licensedState']->id,
        'state_name' => $locations['licensedState']->name,
        'lga_id' => $locations['licensedLga']->id,
        'lga_name' => $locations['licensedLga']->name,
        'scope_name' => $locations['licensedLga']->name,
    ]);

    createChartUser('ward-member@example.test', [
        'gender' => 'female',
        'state_id' => $locations['licensedState']->id,
        'lga_id' => $locations['licensedLga']->id,
        'ward_id' => $locations['licensedWard']->id,
    ]);

    $payload = app(CampaignDashboardChartService::class)->packageGeography();

    expect($payload['labels'])->toBe([$locations['licensedWard']->name])
        ->and($payload['datasets'][0]['data'])->toBe([1])
        ->and($payload['total'])->toBe(1);
});

test('managed mode keeps broad national chart behavior', function () {
    config(['campaign.deployment_mode' => 'managed']);
    $locations = createChartLocationSet();

    createChartUser('cross-river@example.test', [
        'gender' => 'male',
        'state_id' => $locations['licensedState']->id,
        'lga_id' => $locations['licensedLga']->id,
    ]);
    createChartUser('lagos@example.test', [
        'gender' => 'female',
        'state_id' => $locations['otherState']->id,
        'lga_id' => $locations['otherLga']->id,
    ]);

    $payload = app(CampaignDashboardChartService::class)->states();

    expect($payload['labels'])->toContain($locations['licensedState']->name, $locations['otherState']->name)
        ->and($payload['total'])->toBe(2);
});

test('self-hosted scoped gender chart excludes out-of-scope users', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    $locations = createChartLocationSet();

    createChartLicense('governorship', [
        'scope_type' => 'state',
        'state_id' => $locations['licensedState']->id,
        'state_name' => $locations['licensedState']->name,
        'scope_name' => $locations['licensedState']->name,
    ]);

    createChartUser('licensed-male@example.test', [
        'gender' => 'male',
        'state_id' => $locations['licensedState']->id,
        'lga_id' => $locations['licensedLga']->id,
    ]);
    createChartUser('outside-female@example.test', [
        'gender' => 'female',
        'state_id' => $locations['otherState']->id,
        'lga_id' => $locations['otherLga']->id,
    ]);

    $payload = app(CampaignDashboardChartService::class)->gender();

    expect($payload['datasets'][0]['data'])->toBe([1, 0])
        ->and($payload['total'])->toBe(1);
});

test('user gender is normalized for profile forms and edit preselection', function () {
    $user = createChartUser('mixed-case-gender@example.test', [
        'gender' => ' Male ',
    ]);

    expect($user->gender)->toBe('male');

    $user->forceFill(['gender' => 'FEMALE'])->save();

    expect($user->refresh()->gender)->toBe('female');
});

test('user gender can be saved through mass assignment updates', function () {
    $user = createChartUser('mass-assigned-gender@example.test');

    $user->update(['gender' => 'female']);

    expect($user->refresh()->gender)->toBe('female');
});

test('gender chart counts normalized member gender values', function () {
    config(['campaign.deployment_mode' => 'managed']);

    createChartUser('chart-male@example.test', [
        'gender' => 'Male',
    ]);
    createChartUser('chart-female@example.test', [
        'gender' => 'FEMALE',
    ]);

    $payload = app(CampaignDashboardChartService::class)->gender();

    expect($payload['datasets'][0]['data'])->toBe([1, 1])
        ->and($payload['total'])->toBe(2)
        ->and($payload['empty'])->toBeFalse();
});
