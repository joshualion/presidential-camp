<?php

use App\Models\Country;
use App\Models\LocalLicense;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use App\Models\PollingUnit;
use App\Services\LicensedScopeQueryService;
use App\Services\LocationDashboardMetricsService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Str;

function createScopeTestUser(string $email, array $attributes = []): User
{
    return User::create(array_merge([
        'firstname' => 'Scope',
        'lastname' => 'User',
        'username' => str_replace(['@', '.'], '_', $email),
        'email' => $email,
        'password' => 'password',
        'access_level' => 'user',
        'status' => 'active',
    ], $attributes));
}

function createScopeTestLicense(string $scopeType, array $scopeAttributes = [], string $packageType = 'governorship'): LocalLicense
{
    $license = LocalLicense::create([
        'license_key' => 'CM-SCOPE-'.$scopeType.'-'.random_int(1000, 9999),
        'deployment_mode' => 'self_hosted',
        'package_type' => $packageType,
        'status' => 'active',
    ]);

    $license->scope()->create(array_merge([
        'scope_type' => $scopeType,
        'scope_name' => ucfirst(str_replace('_', ' ', $scopeType)),
    ], $scopeAttributes));

    return $license;
}

function createScopeTestState(int $id): State
{
    $country = Country::query()->firstOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Nigeria',
    ]);

    $region = Region::query()->firstOrCreate(['id' => 1], [
        'name' => 'Region 1',
        'country_id' => $country->id,
    ]);

    return State::query()->firstOrCreate(['id' => $id], [
        'name' => 'State '.$id,
        'region_id' => $region->id,
    ]);
}

function createScopeTestSenatorialDistrict(int $id, int $stateId = 1): SenatorialDistrict
{
    createScopeTestState($stateId);

    return SenatorialDistrict::query()->firstOrCreate(['id' => $id], [
        'name' => 'District '.$id,
        'state_id' => $stateId,
    ]);
}

function createScopeTestFederalConstituency(int $id, int $stateId = 1, ?int $districtId = null): FederalConstituency
{
    createScopeTestState($stateId);
    if ($districtId) {
        createScopeTestSenatorialDistrict($districtId, $stateId);
    }

    return FederalConstituency::query()->firstOrCreate(['id' => $id], [
        'name' => 'Federal '.$id,
        'state_id' => $stateId,
        'senatorial_district_id' => $districtId,
    ]);
}

function createScopeTestLga(int $id, int $stateId = 1): LocalGovernmentArea
{
    createScopeTestState($stateId);

    return LocalGovernmentArea::query()->firstOrCreate(['id' => $id], [
        'name' => 'LGA '.$id,
        'state_id' => $stateId,
    ]);
}

function createScopeTestWard(int $id, int $lgaId = 1, int $stateId = 1): Ward
{
    createScopeTestLga($lgaId, $stateId);

    return Ward::query()->firstOrCreate(['id' => $id], [
        'name' => 'Ward '.$id,
        'lga_id' => $lgaId,
    ]);
}

function createScopeTestPollingUnit(int $id, int $wardId = 1, int $lgaId = 1, int $stateId = 1): PollingUnit
{
    createScopeTestWard($wardId, $lgaId, $stateId);

    return PollingUnit::query()->firstOrCreate(['id' => $id], [
        'name' => 'PU '.$id,
        'ward_id' => $wardId,
    ]);
}

test('managed mode does not constrain read queries without local license', function () {
    config(['campaign.deployment_mode' => 'managed']);

    createScopeTestState(1);
    createScopeTestState(2);
    createScopeTestUser('scope-one@example.test', ['state_id' => 1]);
    createScopeTestUser('scope-two@example.test', ['state_id' => 2]);

    $query = User::query();
    app(LicensedScopeQueryService::class)->applyToUsersQuery($query);

    expect($query->count())->toBe(2);
});

test('presidential national license allows all read rows', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    createScopeTestLicense('national', [], 'presidential');
    createScopeTestState(1);
    createScopeTestState(2);
    createScopeTestUser('scope-one@example.test', ['state_id' => 1]);
    createScopeTestUser('scope-two@example.test', ['state_id' => 2]);

    $query = User::query();
    app(LicensedScopeQueryService::class)->applyToUsersQuery($query);

    expect($query->count())->toBe(2);
});

test('governorship license constrains direct user reads by state', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    createScopeTestState(9);
    createScopeTestState(10);
    createScopeTestLicense('state', ['state_id' => 9], 'governorship');
    createScopeTestUser('inside@example.test', ['state_id' => 9]);
    createScopeTestUser('outside@example.test', ['state_id' => 10]);

    $query = User::query();
    app(LicensedScopeQueryService::class)->applyToUsersQuery($query);

    expect($query->pluck('email')->all())->toBe(['inside@example.test']);
});

test('governorship license constrains region option queries by licensed state parent', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    $insideState = createScopeTestState(9);

    $outsideRegion = Region::query()->firstOrCreate(['id' => 2], [
        'name' => 'Region 2',
        'country_id' => 1,
    ]);
    State::query()->firstOrCreate(['id' => 10], [
        'name' => 'State 10',
        'region_id' => $outsideRegion->id,
    ]);

    createScopeTestLicense('state', ['state_id' => $insideState->id], 'governorship');

    $query = Region::query()->orderBy('id');
    app(LicensedScopeQueryService::class)->applyToRegionsQuery($query);

    expect($query->pluck('id')->all())->toBe([$insideState->region_id]);
});

test('senatorial license constrains direct user reads by senatorial district', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    createScopeTestSenatorialDistrict(4);
    createScopeTestSenatorialDistrict(5);
    createScopeTestLicense('senatorial_district', ['senatorial_district_id' => 4], 'senatorial');
    createScopeTestUser('inside@example.test', ['senatorial_district_id' => 4]);
    createScopeTestUser('outside@example.test', ['senatorial_district_id' => 5]);

    $query = User::query();
    app(LicensedScopeQueryService::class)->applyToUsersQuery($query);

    expect($query->pluck('email')->all())->toBe(['inside@example.test']);
});

test('senatorial license includes users through lga fallback when direct district is missing', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    createScopeTestSenatorialDistrict(4, 9);
    createScopeTestSenatorialDistrict(5, 9);
    $insideLga = createScopeTestLga(44, 9);
    $insideLga->forceFill(['senatorial_district_id' => 4])->save();
    $outsideLga = createScopeTestLga(45, 9);
    $outsideLga->forceFill(['senatorial_district_id' => 5])->save();
    createScopeTestLicense('senatorial_district', ['senatorial_district_id' => 4], 'senatorial');

    createScopeTestUser('inside-lga@example.test', [
        'senatorial_district_id' => null,
        'lga_id' => $insideLga->id,
    ]);
    createScopeTestUser('outside-lga@example.test', [
        'senatorial_district_id' => null,
        'lga_id' => $outsideLga->id,
    ]);

    $query = User::query();
    app(LicensedScopeQueryService::class)->applyToUsersQuery($query);

    expect($query->pluck('email')->all())->toBe(['inside-lga@example.test']);
});

test('senatorial license includes users through polling unit fallback when direct district is missing', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    createScopeTestSenatorialDistrict(4, 9);
    createScopeTestSenatorialDistrict(5, 9);
    $insidePu = createScopeTestPollingUnit(91, 31, 44, 9);
    $insidePu->forceFill(['senatorial_district_id' => 4])->save();
    $outsidePu = createScopeTestPollingUnit(92, 32, 45, 9);
    $outsidePu->forceFill(['senatorial_district_id' => 5])->save();
    createScopeTestLicense('senatorial_district', ['senatorial_district_id' => 4], 'senatorial');

    createScopeTestUser('inside-pu@example.test', [
        'senatorial_district_id' => null,
        'lga_id' => null,
        'ward_id' => null,
        'polling_unit_id' => $insidePu->id,
    ]);
    createScopeTestUser('outside-pu@example.test', [
        'senatorial_district_id' => null,
        'lga_id' => null,
        'ward_id' => null,
        'polling_unit_id' => $outsidePu->id,
    ]);

    $query = User::query();
    app(LicensedScopeQueryService::class)->applyToUsersQuery($query);

    expect($query->pluck('email')->all())->toBe(['inside-pu@example.test']);
});

test('federal constituency license constrains direct user reads by federal constituency', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    createScopeTestFederalConstituency(8);
    createScopeTestFederalConstituency(9);
    createScopeTestLicense('federal_constituency', ['federal_constituency_id' => 8], 'federal_constituency');
    createScopeTestUser('inside@example.test', ['federal_constituency_id' => 8]);
    createScopeTestUser('outside@example.test', ['federal_constituency_id' => 9]);

    $query = User::query();
    app(LicensedScopeQueryService::class)->applyToUsersQuery($query);

    expect($query->pluck('email')->all())->toBe(['inside@example.test']);
});

test('lga license constrains direct user reads by lga', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    createScopeTestLga(17);
    createScopeTestLga(18);
    createScopeTestLicense('lga', ['lga_id' => 17], 'lga');
    createScopeTestUser('inside@example.test', ['lga_id' => 17]);
    createScopeTestUser('outside@example.test', ['lga_id' => 18]);

    $query = User::query();
    app(LicensedScopeQueryService::class)->applyToUsersQuery($query);

    expect($query->pluck('email')->all())->toBe(['inside@example.test']);
});

test('self hosted without local license does not crash or constrain', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    createScopeTestState(1);
    createScopeTestState(2);
    createScopeTestUser('scope-one@example.test', ['state_id' => 1]);
    createScopeTestUser('scope-two@example.test', ['state_id' => 2]);

    $query = User::query();
    app(LicensedScopeQueryService::class)->applyToUsersQuery($query);

    expect($query->count())->toBe(2);
});

test('write payload validation accepts in-scope and rejects out-of-scope ids', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    createScopeTestState(9);
    createScopeTestState(10);
    createScopeTestLicense('state', ['state_id' => 9], 'governorship');

    $service = app(LicensedScopeQueryService::class);

    expect($service->payloadErrors(['state_id' => 9]))->toBe([])
        ->and($service->payloadErrors(['state_id' => 10]))->toHaveKey('state_id');
});

test('lga detail guard blocks another lga', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    createScopeTestLga(17);
    createScopeTestLga(18);
    createScopeTestLicense('lga', ['lga_id' => 17], 'lga');

    app(LicensedScopeQueryService::class)->abortIfLgaNotAllowed(17);

    expect(fn () => app(LicensedScopeQueryService::class)->abortIfLgaNotAllowed(18))
        ->toThrow(HttpException::class);
});

test('polling unit payload validation uses parent ward and lga relationship', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    createScopeTestPollingUnit(101, 11, 17);
    createScopeTestPollingUnit(102, 12, 18);
    createScopeTestLicense('lga', ['lga_id' => 17], 'lga');

    $service = app(LicensedScopeQueryService::class);

    expect($service->payloadErrors(['polling_unit_id' => 101]))->toBe([])
        ->and($service->payloadErrors(['polling_unit_id' => 102]))->toHaveKey('polling_unit_id');
});

test('dashboard people aggregate respects self hosted state license for national users', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    createScopeTestState(9);
    createScopeTestState(10);
    createScopeTestLicense('state', ['state_id' => 9], 'governorship');
    createScopeTestUser('dashboard-inside@example.test', ['state_id' => 9]);
    createScopeTestUser('dashboard-outside@example.test', ['state_id' => 10]);

    $metrics = app(LocationDashboardMetricsService::class)->forUser(null);

    expect($metrics['people']['users'])->toBe(1);
});
