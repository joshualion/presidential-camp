<?php

use App\Imports\LocalGovernmentImport;
use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

function manualLgaFixture(): array
{
    config(['campaign.deployment_mode' => 'self_hosted', 'campaign.portal_web_base' => 'https://portal.test']);

    $country = Country::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    $region = Region::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'South South', 'country_id' => $country->id]);
    $state = State::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Cross River', 'region_id' => $region->id]);
    $otherState = State::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Lagos', 'region_id' => $region->id]);
    $district = SenatorialDistrict::query()->create(['state_id' => $state->id, 'name' => 'Cross River South']);
    $otherDistrict = SenatorialDistrict::query()->create(['state_id' => $otherState->id, 'name' => 'Lagos West']);
    $constituency = FederalConstituency::query()->create(['state_id' => $state->id, 'senatorial_district_id' => $district->id, 'name' => 'Calabar Municipal/Odukpani']);
    $otherConstituency = FederalConstituency::query()->create(['state_id' => $otherState->id, 'name' => 'Ikeja']);
    $admin = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);

    SystemSetting::query()->updateOrCreate(['id' => 1], [
        'system_name' => 'Cross River Campaign',
        'campaign_slogan' => '',
        'system_country' => 'Nigeria',
        'package' => 'governorship',
        'campaign_scope_type' => 'state',
        'campaign_scope_name' => 'Cross River',
        'campaign_state_id' => $state->id,
    ]);

    return compact('state', 'otherState', 'district', 'otherDistrict', 'constituency', 'otherConstituency', 'admin');
}

test('governorship super admin can access add lga from lga list', function () {
    $fixture = manualLgaFixture();

    $response = $this->actingAs($fixture['admin'])->get(route('superadmin.location.lgas'));

    $response->assertOk()
        ->assertSee(route('superadmin.location.lga.add'), false)
        ->assertDontSee(route('nationaladmin.location.lga.add'), false);

    $this->actingAs($fixture['admin'])
        ->get(route('superadmin.location.lga.add'))
        ->assertOk()
        ->assertSee('Add Local Government Area')
        ->assertSee('Senatorial District')
        ->assertSee('Federal Constituency')
        ->assertSee('Cross River South')
        ->assertSee('Calabar Municipal/Odukpani');
});

test('governorship super admin can create mapped lga inside configured state', function () {
    $fixture = manualLgaFixture();

    $this->actingAs($fixture['admin'])->post(route('superadmin.location.lga.store'), [
        'name' => 'Calabar Municipal',
        'state' => $fixture['state']->id,
        'senatorial_district_id' => $fixture['district']->id,
        'federal_constituency_id' => $fixture['constituency']->id,
    ])->assertRedirect(route('superadmin.location.lgas'));

    $lga = LocalGovernmentArea::query()->where('name', 'Calabar Municipal')->firstOrFail();

    expect($lga->state_id)->toBe($fixture['state']->id)
        ->and($lga->senatorial_district_id)->toBe($fixture['district']->id)
        ->and($lga->federal_constituency_id)->toBe($fixture['constituency']->id);
});

test('manual lga creation rejects out of scope and invalid political boundary mappings', function () {
    $fixture = manualLgaFixture();

    $this->actingAs($fixture['admin'])->post(route('superadmin.location.lga.store'), [
        'name' => 'Ikeja',
        'state' => $fixture['otherState']->id,
        'senatorial_district_id' => $fixture['otherDistrict']->id,
        'federal_constituency_id' => $fixture['otherConstituency']->id,
    ])->assertSessionHasErrors('state_id');

    $this->actingAs($fixture['admin'])->post(route('superadmin.location.lga.store'), [
        'name' => 'Bad Mapping',
        'state' => $fixture['state']->id,
        'senatorial_district_id' => $fixture['otherDistrict']->id,
        'federal_constituency_id' => $fixture['constituency']->id,
    ])->assertSessionHasErrors('senatorial_district_id');
});

test('ward and polling unit descendants resolve through mapped lga', function () {
    $fixture = manualLgaFixture();
    $lga = LocalGovernmentArea::query()->create([
        'name' => 'Calabar Municipal',
        'state_id' => $fixture['state']->id,
        'senatorial_district_id' => $fixture['district']->id,
        'federal_constituency_id' => $fixture['constituency']->id,
    ]);
    $ward = Ward::query()->create(['name' => 'Ward 1', 'lga_id' => $lga->id]);
    $pollingUnit = PollingUnit::query()->create(['name' => 'PU 1', 'ward_id' => $ward->id]);

    expect($ward->localGovernmentArea->senatorial_district_id)->toBe($fixture['district']->id)
        ->and($ward->localGovernmentArea->federal_constituency_id)->toBe($fixture['constituency']->id)
        ->and($pollingUnit->ward->localGovernmentArea->id)->toBe($lga->id);
});

test('existing lga import remains simple and does not add senatorial or federal mappings', function () {
    $fixture = manualLgaFixture();
    Auth::login($fixture['admin']);

    $import = new LocalGovernmentImport();
    $model = $import->model(['name' => 'Akamkpa', 'state' => 'Cross River']);
    $model->save();

    $lga = LocalGovernmentArea::query()->where('name', 'Akamkpa')->firstOrFail();

    expect($import->successfulInserts)->toBe(1)
        ->and($lga->state_id)->toBe($fixture['state']->id)
        ->and($lga->senatorial_district_id)->toBeNull()
        ->and($lga->federal_constituency_id)->toBeNull();
});

test('lga import warning and quickstart cta are displayed', function () {
    $fixture = manualLgaFixture();

    $this->actingAs($fixture['admin'])
        ->get(route('superadmin.location.lga.import'))
        ->assertOk()
        ->assertSee('Imported LGAs are added to your campaign geography but are not automatically mapped to Senatorial Districts and Federal Constituencies')
        ->assertSee('Manual Add LGA lets administrators map each LGA correctly while creating it')
        ->assertSee('Configure with QuickStart')
        ->assertSee('target="_blank"', false)
        ->assertSee('rel="noopener"', false)
        ->assertSee('https://portal.test/order/campaign-manager?service=quickstart_geography&amp;package=governorship&amp;scope_type=state', false);
});
