<?php

use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\LocalLicense;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use App\Services\CampaignPackageLocationFormService;
use Illuminate\Support\Str;

function createLocationFormLicense(string $package, string $scopeType, array $scope): LocalLicense
{
    $license = LocalLicense::query()->create([
        'license_key' => 'CM-LOCATION-FORM-'.Str::upper(Str::random(8)),
        'deployment_mode' => 'self_hosted',
        'package_type' => $package,
        'status' => 'active',
    ]);

    $license->scope()->create(array_merge([
        'scope_type' => $scopeType,
        'scope_name' => ucfirst(str_replace('_', ' ', $scopeType)),
    ], $scope));

    return $license;
}

function locationFormState(int $id, string $name, int $regionId = 1): State
{
    Country::query()->firstOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Nigeria',
    ]);

    Region::query()->firstOrCreate(['id' => $regionId], [
        'name' => 'Region '.$regionId,
        'country_id' => 1,
    ]);

    return State::query()->firstOrCreate(['id' => $id], [
        'name' => $name,
        'region_id' => $regionId,
    ]);
}

function locationFormActor(string $accessLevel = 'superadmin'): User
{
    return User::factory()->create([
        'access_level' => $accessLevel,
        'country_id' => null,
        'region_id' => null,
        'state_id' => null,
        'lga_id' => null,
        'ward_id' => null,
        'polling_unit_id' => null,
        'religion_id' => null,
        'age_grade_id' => null,
        'requires_update' => false,
    ]);
}

test('governorship form context fixes licensed state and excludes other states', function () {
    $crossRiver = locationFormState(9, 'Cross River', 2);
    locationFormState(10, 'Akwa Ibom', 3);
    createLocationFormLicense('governorship', 'state', [
        'state_id' => $crossRiver->id,
        'state_name' => $crossRiver->name,
        'scope_name' => $crossRiver->name,
    ]);

    $service = app(CampaignPackageLocationFormService::class);
    $context = $service->context(locationFormActor());
    $options = $service->options(locationFormActor());

    expect($context['fixed'])->toMatchArray([
        'region_id' => $crossRiver->region_id,
        'state_id' => $crossRiver->id,
    ])
        ->and($context['visible_fields']['state_id'])->toBeFalse()
        ->and($options['states']->pluck('id')->all())->toBe([$crossRiver->id]);
});

test('presidential form context exposes all nationally licensed states', function () {
    $crossRiver = locationFormState(9, 'Cross River', 2);
    $akwaIbom = locationFormState(10, 'Akwa Ibom', 3);
    createLocationFormLicense('presidential', 'national', [
        'scope_name' => 'Nigeria',
    ]);

    $service = app(CampaignPackageLocationFormService::class);
    $context = $service->context(locationFormActor());
    $options = $service->options();

    expect($context['fixed'])->toBe([])
        ->and($options['states']->pluck('id')->sort()->values()->all())
        ->toBe([$crossRiver->id, $akwaIbom->id]);
});

test('profile onboarding options use the licensed boundary before a member has a structural assignment', function () {
    $crossRiver = locationFormState(9, 'Cross River', 2);
    locationFormState(10, 'Akwa Ibom', 3);
    $lga = LocalGovernmentArea::query()->create([
        'name' => 'Calabar Municipal',
        'state_id' => $crossRiver->id,
    ]);
    $ward = Ward::query()->create(['name' => 'Ward 1', 'lga_id' => $lga->id]);
    $pollingUnit = PollingUnit::query()->create(['name' => 'PU 1', 'ward_id' => $ward->id]);

    createLocationFormLicense('governorship', 'state', [
        'state_id' => $crossRiver->id,
        'state_name' => $crossRiver->name,
        'scope_name' => $crossRiver->name,
    ]);

    $options = app(CampaignPackageLocationFormService::class)->options();

    expect($options['states']->pluck('id')->all())->toBe([$crossRiver->id])
        ->and($options['lgas']->pluck('id')->all())->toBe([$lga->id])
        ->and($options['wards']->pluck('id')->all())->toBe([$ward->id])
        ->and($options['pollingUnits']->pluck('id')->all())->toBe([$pollingUnit->id]);
});

test('profile onboarding ajax cascade uses licensed scope for an unassigned member', function () {
    $crossRiver = locationFormState(9, 'Cross River', 2);
    locationFormState(10, 'Akwa Ibom', 3);
    $lga = LocalGovernmentArea::query()->create([
        'name' => 'Calabar Municipal',
        'state_id' => $crossRiver->id,
    ]);
    $ward = Ward::query()->create(['name' => 'Ward 1', 'lga_id' => $lga->id]);
    $pollingUnit = PollingUnit::query()->create(['name' => 'PU 1', 'ward_id' => $ward->id]);

    createLocationFormLicense('governorship', 'state', [
        'state_id' => $crossRiver->id,
        'state_name' => $crossRiver->name,
        'scope_name' => $crossRiver->name,
    ]);

    $member = locationFormActor('user');
    $this->actingAs($member)
        ->postJson(route('getStates'), ['region_id' => $crossRiver->region_id])
        ->assertOk()
        ->assertJsonPath('states.0.id', $crossRiver->id)
        ->assertJsonCount(1, 'states');

    $this->actingAs($member)
        ->postJson(route('getLgas'), ['state_id' => $crossRiver->id])
        ->assertOk()
        ->assertJsonPath('lgas.0.id', $lga->id);

    $this->actingAs($member)
        ->postJson(route('getWards'), ['lga_id' => $lga->id])
        ->assertOk()
        ->assertJsonPath('wards.0.id', $ward->id);

    $this->actingAs($member)
        ->postJson(route('getPollingUnits'), ['ward_id' => $ward->id])
        ->assertOk()
        ->assertJsonPath('pollingUnits.0.id', $pollingUnit->id);
});

test('senatorial form context fixes district and scopes related lgas', function () {
    $state = locationFormState(9, 'Cross River', 2);
    $district = SenatorialDistrict::query()->create([
        'name' => 'Cross River South',
        'state_id' => $state->id,
    ]);
    $inside = LocalGovernmentArea::query()->create([
        'name' => 'Calabar South',
        'state_id' => $state->id,
        'senatorial_district_id' => $district->id,
    ]);
    LocalGovernmentArea::query()->create([
        'name' => 'Outside LGA',
        'state_id' => $state->id,
    ]);

    createLocationFormLicense('senatorial', 'senatorial_district', [
        'state_id' => $state->id,
        'senatorial_district_id' => $district->id,
        'senatorial_district_name' => $district->name,
        'scope_name' => $district->name,
    ]);

    $service = app(CampaignPackageLocationFormService::class);
    $context = $service->context(locationFormActor());
    $options = $service->options(locationFormActor());

    expect($context['fixed'])->toMatchArray([
        'state_id' => $state->id,
        'senatorial_district_id' => $district->id,
    ])
        ->and($context['visible_fields']['senatorial_district_id'])->toBeFalse()
        ->and($options['lgas']->pluck('id')->all())->toBe([$inside->id]);
});

test('federal constituency form context fixes constituency and scopes related lgas', function () {
    $state = locationFormState(9, 'Cross River', 2);
    $district = SenatorialDistrict::query()->create([
        'name' => 'Cross River South',
        'state_id' => $state->id,
    ]);
    $constituency = FederalConstituency::query()->create([
        'name' => 'Calabar Municipal/Odukpani',
        'state_id' => $state->id,
        'senatorial_district_id' => $district->id,
    ]);
    $inside = LocalGovernmentArea::query()->create([
        'name' => 'Calabar Municipal',
        'state_id' => $state->id,
        'federal_constituency_id' => $constituency->id,
    ]);
    LocalGovernmentArea::query()->create([
        'name' => 'Outside LGA',
        'state_id' => $state->id,
    ]);

    createLocationFormLicense('federal_constituency', 'federal_constituency', [
        'state_id' => $state->id,
        'federal_constituency_id' => $constituency->id,
        'federal_constituency_name' => $constituency->name,
        'scope_name' => $constituency->name,
    ]);

    $service = app(CampaignPackageLocationFormService::class);
    $context = $service->context(locationFormActor());
    $options = $service->options(locationFormActor());

    expect($context['fixed'])->toMatchArray([
        'state_id' => $state->id,
        'federal_constituency_id' => $constituency->id,
    ])
        ->and($context['visible_fields']['federal_constituency_id'])->toBeFalse()
        ->and($options['lgas']->pluck('id')->all())->toBe([$inside->id]);
});

test('chairmanship form context fixes lga and exposes only wards and polling units inside it', function () {
    $state = locationFormState(9, 'Cross River', 2);
    $lga = LocalGovernmentArea::query()->create([
        'name' => 'Calabar Municipal',
        'state_id' => $state->id,
    ]);
    $otherLga = LocalGovernmentArea::query()->create([
        'name' => 'Outside LGA',
        'state_id' => $state->id,
    ]);
    $ward = Ward::query()->create(['name' => 'Ward 1', 'lga_id' => $lga->id]);
    Ward::query()->create(['name' => 'Ward 2', 'lga_id' => $otherLga->id]);
    $pollingUnit = PollingUnit::query()->create(['name' => 'PU 1', 'ward_id' => $ward->id]);

    createLocationFormLicense('chairmanship', 'lga', [
        'state_id' => $state->id,
        'lga_id' => $lga->id,
        'lga_name' => $lga->name,
        'scope_name' => $lga->name,
    ]);

    $service = app(CampaignPackageLocationFormService::class);
    $context = $service->context(locationFormActor());
    $options = $service->options(locationFormActor());

    expect($context['fixed'])->toMatchArray([
        'state_id' => $state->id,
        'lga_id' => $lga->id,
    ])
        ->and($context['visible_fields']['lga_id'])->toBeFalse()
        ->and($options['wards']->pluck('id')->all())->toBe([$ward->id])
        ->and($options['pollingUnits']->pluck('id')->all())->toBe([$pollingUnit->id]);
});

test('managed mode without local license keeps broad form behavior', function () {
    config(['campaign.deployment_mode' => 'managed']);

    locationFormState(9, 'Cross River', 2);
    locationFormState(10, 'Akwa Ibom', 3);

    $service = app(CampaignPackageLocationFormService::class);
    $context = $service->context(locationFormActor());
    $options = $service->options(locationFormActor());

    expect($context['fixed'])->toBe([])
        ->and($context['visible_fields']['state_id'])->toBeTrue()
        ->and($options['states']->pluck('id')->sort()->values()->all())->toBe([9, 10]);
});
