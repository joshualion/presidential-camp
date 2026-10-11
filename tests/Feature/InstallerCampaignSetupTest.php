<?php

use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\LocalLicense;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\SystemSetting;
use App\Services\Installer\BaseCampaignGeographyBootstrapper;
use App\Services\CampaignInstallationSettingsService;
use App\Services\Installer\InstallerStateStore;
use App\Services\PackageGovernanceService;
use App\Services\PackageScopeService;
use App\Services\RemoteLicenseClient;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['campaign.deployment_mode' => 'self_hosted', 'campaign.installer_enabled' => true]);
    File::ensureDirectoryExists(storage_path('app'));
    if (File::exists(storage_path('app/installed.lock'))) {
        File::delete(storage_path('app/installed.lock'));
    }
    app(InstallerStateStore::class)->clear();
});

afterEach(function () {
    if (File::exists(storage_path('app/installed.lock'))) {
        File::delete(storage_path('app/installed.lock'));
    }
    app(InstallerStateStore::class)->clear();
});

function phase2PrimeInstaller(array $extra = []): void
{
    app(InstallerStateStore::class)->merge(array_replace_recursive([
        'completed_steps' => ['requirements', 'mail', 'storage'],
        'environment' => [
            'APP_NAME' => 'Campaign Manager',
            'APP_URL' => 'http://localhost',
            'APP_ENV' => 'testing',
            'APP_DEBUG' => false,
            'APP_TIMEZONE' => 'Africa/Lagos',
            'DEPLOYMENT_MODE' => 'self_hosted',
        ],
        'database' => [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'campaign_manager_test',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
        ],
        'mail' => [],
        'storage' => ['status' => 'pass'],
    ], $extra));
}

function phase2PrimeCampaign(array $extra = []): void
{
    phase2PrimeInstaller(array_replace_recursive([
        'completed_steps' => ['requirements', 'mail', 'storage', 'campaign_scope', 'campaign_geography', 'campaign_identity'],
        'campaign_scope' => [
            'package_type' => PackageGovernanceService::GOVERNORSHIP,
            'scope_type' => 'state',
            'scope_name' => 'Selected State',
        ],
        'campaign_geography' => [
            'scope_name' => 'Cross River',
            'state_id' => null,
            'senatorial_district_id' => null,
            'federal_constituency_id' => null,
            'lga_id' => null,
        ],
        'campaign_identity' => [
            'system_name' => 'Joshua Ekpe Governorship Campaign 2027',
            'campaign_slogan' => 'Forward together',
        ],
    ], $extra));
}

function phase2SeedGeography(): array
{
    $country = Country::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    $region = Region::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'South South', 'country_id' => $country->id]);
    $crossRiver = State::query()->create(['name' => 'Cross River', 'region_id' => $region->id]);
    $lagos = State::query()->create(['name' => 'Lagos', 'region_id' => $region->id]);
    $district = SenatorialDistrict::query()->create(['state_id' => $crossRiver->id, 'name' => 'Cross River South', 'normalized_name' => 'cross-river-south']);
    $otherDistrict = SenatorialDistrict::query()->create(['state_id' => $lagos->id, 'name' => 'Lagos West', 'normalized_name' => 'lagos-west']);
    $constituency = FederalConstituency::query()->create(['state_id' => $crossRiver->id, 'senatorial_district_id' => $district->id, 'name' => 'Calabar Municipal/Odukpani', 'normalized_name' => 'calabar-municipal-odukpani']);
    $lga = LocalGovernmentArea::query()->create(['name' => 'Calabar Municipal', 'state_id' => $crossRiver->id]);

    return compact('crossRiver', 'lagos', 'district', 'otherDistrict', 'constituency', 'lga');
}

function phase2FakePortalDirectory(): void
{
    config(['campaign.portal_directory_base' => 'https://portal.test/api']);

    Http::fake([
        'https://portal.test/api/campaign-locations/states' => Http::response([
            ['id' => 10, 'name' => 'Cross River', 'code' => 'CR', 'inec_code' => '9'],
            ['id' => 25, 'name' => 'Lagos', 'code' => 'LA', 'inec_code' => '25'],
        ]),
        'https://portal.test/api/campaign-locations/states/10/senatorial-districts' => Http::response([
            ['id' => 101, 'name' => 'Cross River South', 'code' => 'CR-SD-S', 'inec_code' => '9-1'],
            ['id' => 102, 'name' => 'Cross River Central', 'code' => 'CR-SD-C', 'inec_code' => '9-2'],
            ['id' => 103, 'name' => 'Cross River North', 'code' => 'CR-SD-N', 'inec_code' => '9-3'],
        ]),
        'https://portal.test/api/campaign-locations/states/25/senatorial-districts' => Http::response([
            ['id' => 251, 'name' => 'Lagos West', 'code' => 'LA-SD-W', 'inec_code' => '25-1'],
        ]),
        'https://portal.test/api/campaign-locations/states/10/federal-constituencies' => Http::response([
            ['id' => 201, 'name' => 'Calabar Municipal/Odukpani', 'code' => 'CR-FC-CAL', 'inec_code' => '9-01'],
            ['id' => 202, 'name' => 'Bakassi/Akpabuyo/Calabar South', 'code' => 'CR-FC-BAK', 'inec_code' => '9-02'],
        ]),
        'https://portal.test/api/campaign-locations/states/25/federal-constituencies' => Http::response([
            ['id' => 252, 'name' => 'Ikeja', 'code' => 'LA-FC-IKE', 'inec_code' => '25-01'],
        ]),
        'https://portal.test/api/campaign-locations/states/10/lgas' => Http::response([
            ['id' => 301, 'name' => 'Calabar Municipal', 'code' => 'CR-LGA-CAL', 'inec_code' => '9-01'],
            ['id' => 302, 'name' => 'Odukpani', 'code' => 'CR-LGA-ODU', 'inec_code' => '9-02'],
        ]),
        'https://portal.test/api/campaign-locations/states/25/lgas' => Http::response([
            ['id' => 253, 'name' => 'Ikeja', 'code' => 'LA-LGA-IKE', 'inec_code' => '25-01'],
        ]),
        'https://portal.test/api/campaign-locations/summary*' => Http::response([
            'scope_type' => 'state',
            'scope_name' => 'Cross River',
            'counts' => [
                'senatorial_districts' => 3,
                'federal_constituencies' => 2,
                'local_government_areas' => 2,
                'wards' => 196,
                'polling_units' => 3281,
            ],
        ]),
    ]);
}

function phase2FakePortalUnavailable(): void
{
    config(['campaign.portal_directory_base' => 'https://portal.test/api']);
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('offline'));
}

function phase2PersistBootstrappedGeography(array $campaignScope, array $campaignGeography): array
{
    SystemSetting::query()->updateOrCreate(['id' => 1], [
        'system_name' => 'Campaign Manager',
        'campaign_slogan' => '',
        'system_country' => 'Nigeria',
        'package' => 'presidential',
    ]);

    $localGeography = app(BaseCampaignGeographyBootstrapper::class)->bootstrap($campaignScope, $campaignGeography);
    app(CampaignInstallationSettingsService::class)->persist($campaignScope, $localGeography, [
        'system_name' => 'Joshua Ekpe Campaign 2027',
        'campaign_slogan' => 'Forward together',
    ]);

    return $localGeography;
}

test('community installer displays supported campaign scope selection only', function () {
    phase2PrimeInstaller();

    $this->get(route('install.campaign_scope'))
        ->assertOk()
        ->assertSee('Campaign Scope')
        ->assertSee('Presidential / National')
        ->assertSee('Governorship / State')
        ->assertSee('Senatorial')
        ->assertSee('Federal Constituency / House of Representatives')
        ->assertSee('LGA / Chairmanship')
        ->assertDontSee('State House of Assembly')
        ->assertDontSee('Custom Campaign');
});

test('campaign scope is required before campaign identity', function () {
    phase2PrimeInstaller();

    $this->get(route('install.campaign_identity'))
        ->assertRedirect(route('install.campaign_scope'));
});

test('governorship geography asks for state', function () {
    phase2FakePortalUnavailable();
    phase2PrimeInstaller(['campaign_scope' => ['package_type' => PackageGovernanceService::GOVERNORSHIP, 'scope_type' => 'state', 'scope_name' => 'Selected State']]);
    phase2SeedGeography();

    $this->get(route('install.campaign_geography'))->assertOk()->assertSee('State')->assertDontSee('Senatorial District')->assertDontSee('Federal Constituency')->assertDontSee('LGA</label>', false);
});

test('senatorial geography asks for state and senatorial district', function () {
    phase2FakePortalUnavailable();
    phase2PrimeInstaller(['campaign_scope' => ['package_type' => PackageGovernanceService::SENATORIAL, 'scope_type' => 'senatorial_district', 'scope_name' => 'Selected Senatorial District']]);
    phase2SeedGeography();

    $this->get(route('install.campaign_geography'))->assertOk()->assertSee('State')->assertSee('Senatorial District')->assertSee('Cross River South');
});

test('federal constituency geography asks for state and federal constituency', function () {
    phase2FakePortalUnavailable();
    phase2PrimeInstaller(['campaign_scope' => ['package_type' => PackageGovernanceService::FEDERAL, 'scope_type' => 'federal_constituency', 'scope_name' => 'Selected Federal Constituency']]);
    phase2SeedGeography();

    $this->get(route('install.campaign_geography'))->assertOk()->assertSee('State')->assertSee('Federal Constituency')->assertSee('Calabar Municipal/Odukpani');
});

test('chairmanship geography asks for state and lga', function () {
    phase2FakePortalUnavailable();
    phase2PrimeInstaller(['campaign_scope' => ['package_type' => PackageGovernanceService::CHAIRMANSHIP, 'scope_type' => 'lga', 'scope_name' => 'Selected LGA']]);
    phase2SeedGeography();

    $this->get(route('install.campaign_geography'))->assertOk()->assertSee('State')->assertSee('LGA')->assertSee('Calabar Municipal');
});

test('presidential geography does not require state or constituency boundaries', function () {
    phase2FakePortalUnavailable();
    phase2PrimeInstaller(['campaign_scope' => ['package_type' => PackageGovernanceService::PRESIDENTIAL, 'scope_type' => 'national', 'scope_name' => 'Nigeria']]);
    phase2SeedGeography();

    $this->get(route('install.campaign_geography'))->assertOk()->assertSee('Country')->assertDontSee('Choose state')->assertDontSee('Federal Constituency');

    $this->post(route('install.campaign_geography'), [])->assertRedirect(route('install.campaign_identity'));
});

test('selected geographic boundary persists in installer state', function () {
    $geo = phase2SeedGeography();
    phase2PrimeInstaller(['campaign_scope' => ['package_type' => PackageGovernanceService::SENATORIAL, 'scope_type' => 'senatorial_district', 'scope_name' => 'Selected Senatorial District']]);

    $this->post(route('install.campaign_geography'), [
        'state_id' => $geo['crossRiver']->id,
        'senatorial_district_id' => $geo['district']->id,
    ])->assertRedirect(route('install.campaign_identity'));

    $state = app(InstallerStateStore::class)->all();
    expect($state['campaign_geography']['state_id'])->toBe($geo['crossRiver']->id)
        ->and($state['campaign_geography']['senatorial_district_id'])->toBe($geo['district']->id)
        ->and($state['campaign_geography']['scope_name'])->toBe('Cross River South');
});

test('portal directory state selection bootstraps governorship state locally', function () {
    phase2FakePortalDirectory();
    phase2PrimeInstaller(['campaign_scope' => ['package_type' => PackageGovernanceService::GOVERNORSHIP, 'scope_type' => 'state', 'scope_name' => 'Selected State']]);

    $this->get(route('install.campaign_geography'))->assertOk()->assertSee('Cross River');
    $this->post(route('install.campaign_geography'), ['state_id' => 10])->assertRedirect(route('install.campaign_identity'));

    $campaignScope = app(InstallerStateStore::class)->all()['campaign_scope'];
    $campaignGeography = app(InstallerStateStore::class)->all()['campaign_geography'];
    $local = phase2PersistBootstrappedGeography($campaignScope, $campaignGeography);

    $state = State::query()->with('region')->find($local['state_id']);
    $settings = SystemSetting::query()->first();

    expect($state->name)->toBe('Cross River')
        ->and($campaignGeography['state_id'])->toBeNull()
        ->and($campaignGeography['portal_state_id'])->toBe(10)
        ->and($campaignGeography['state_code'])->toBe('9')
        ->and($state->inec_state_code)->toBe('9')
        ->and($state->region->name)->toBe('South South')
        ->and($settings->campaign_state_id)->toBe($state->id)
        ->and($settings->campaign_scope_name)->toBe('Cross River')
        ->and($settings->campaign_senatorial_district_id)->toBeNull();
});

test('portal directory senatorial selection bootstraps state and district locally', function () {
    phase2FakePortalDirectory();
    phase2PrimeInstaller(['campaign_scope' => ['package_type' => PackageGovernanceService::SENATORIAL, 'scope_type' => 'senatorial_district', 'scope_name' => 'Selected Senatorial District']]);

    $this->post(route('install.campaign_geography'), ['state_id' => 10, 'senatorial_district_id' => 101])->assertRedirect(route('install.campaign_identity'));

    $campaignScope = app(InstallerStateStore::class)->all()['campaign_scope'];
    $campaignGeography = app(InstallerStateStore::class)->all()['campaign_geography'];
    $local = phase2PersistBootstrappedGeography($campaignScope, $campaignGeography);

    $district = SenatorialDistrict::query()->with('state.region')->find($local['senatorial_district_id']);
    $settings = SystemSetting::query()->first();

    expect($district->name)->toBe('Cross River South')
        ->and($campaignGeography['state_id'])->toBeNull()
        ->and($campaignGeography['senatorial_district_id'])->toBeNull()
        ->and($campaignGeography['portal_state_id'])->toBe(10)
        ->and($campaignGeography['portal_senatorial_district_id'])->toBe(101)
        ->and($campaignGeography['state_code'])->toBe('9')
        ->and($campaignGeography['senatorial_district_code'])->toBe('9-1')
        ->and($district->state->inec_state_code)->toBe('9')
        ->and($district->inec_senatorial_district_code)->toBe('9-1')
        ->and($district->state->name)->toBe('Cross River')
        ->and($district->state->region->name)->toBe('South South')
        ->and($settings->campaign_state_id)->toBe($district->state_id)
        ->and($settings->campaign_senatorial_district_id)->toBe($district->id);
});

test('portal directory federal selection bootstraps state and constituency locally', function () {
    phase2FakePortalDirectory();
    phase2PrimeInstaller(['campaign_scope' => ['package_type' => PackageGovernanceService::FEDERAL, 'scope_type' => 'federal_constituency', 'scope_name' => 'Selected Federal Constituency']]);

    $this->post(route('install.campaign_geography'), ['state_id' => 10, 'federal_constituency_id' => 201])->assertRedirect(route('install.campaign_identity'));

    $campaignScope = app(InstallerStateStore::class)->all()['campaign_scope'];
    $campaignGeography = app(InstallerStateStore::class)->all()['campaign_geography'];
    $local = phase2PersistBootstrappedGeography($campaignScope, $campaignGeography);

    $constituency = FederalConstituency::query()->with('state.region')->find($local['federal_constituency_id']);

    expect($constituency->name)->toBe('Calabar Municipal/Odukpani')
        ->and($campaignGeography['state_id'])->toBeNull()
        ->and($campaignGeography['federal_constituency_id'])->toBeNull()
        ->and($campaignGeography['portal_state_id'])->toBe(10)
        ->and($campaignGeography['portal_federal_constituency_id'])->toBe(201)
        ->and($campaignGeography['state_code'])->toBe('9')
        ->and($campaignGeography['federal_constituency_code'])->toBe('9-01')
        ->and($constituency->state->inec_state_code)->toBe('9')
        ->and($constituency->inec_federal_constituency_code)->toBe('9-01')
        ->and($constituency->state->name)->toBe('Cross River')
        ->and($constituency->state->region->name)->toBe('South South')
        ->and(SystemSetting::query()->first()->campaign_federal_constituency_id)->toBe($constituency->id);
});

test('portal directory chairmanship selection bootstraps state and lga locally', function () {
    phase2FakePortalDirectory();
    phase2PrimeInstaller(['campaign_scope' => ['package_type' => PackageGovernanceService::CHAIRMANSHIP, 'scope_type' => 'lga', 'scope_name' => 'Selected LGA']]);

    $this->post(route('install.campaign_geography'), ['state_id' => 10, 'lga_id' => 301])->assertRedirect(route('install.campaign_identity'));

    $campaignScope = app(InstallerStateStore::class)->all()['campaign_scope'];
    $campaignGeography = app(InstallerStateStore::class)->all()['campaign_geography'];
    $local = phase2PersistBootstrappedGeography($campaignScope, $campaignGeography);

    $lga = LocalGovernmentArea::query()->with('state.region')->find($local['lga_id']);

    expect($lga->name)->toBe('Calabar Municipal')
        ->and($campaignGeography['state_id'])->toBeNull()
        ->and($campaignGeography['lga_id'])->toBeNull()
        ->and($campaignGeography['portal_state_id'])->toBe(10)
        ->and($campaignGeography['portal_lga_id'])->toBe(301)
        ->and($campaignGeography['state_code'])->toBe('9')
        ->and($campaignGeography['lga_code'])->toBe('9-01')
        ->and($lga->state->inec_state_code)->toBe('9')
        ->and($lga->inec_lga_code)->toBe('9-01')
        ->and($lga->state->name)->toBe('Cross River')
        ->and($lga->state->region->name)->toBe('South South')
        ->and(SystemSetting::query()->first()->campaign_lga_id)->toBe($lga->id);
});

test('manual mode local base state remains available in post install geography forms', function () {
    $local = phase2PersistBootstrappedGeography(
        ['package_type' => PackageGovernanceService::GOVERNORSHIP, 'scope_type' => 'state', 'scope_name' => 'Selected State'],
        ['scope_name' => 'Cross River', 'state_name' => 'Cross River', 'portal_state_id' => 10]
    );

    expect(State::query()->whereKey($local['state_id'])->where('name', 'Cross River')->exists())->toBeTrue();
});

test('quickstart setup displays scoped portal directory counts', function () {
    phase2FakePortalDirectory();
    phase2PrimeCampaign([
        'campaign_scope' => ['package_type' => PackageGovernanceService::GOVERNORSHIP, 'scope_type' => 'state', 'scope_name' => 'Selected State'],
        'campaign_geography' => ['scope_name' => 'Cross River', 'state_name' => 'Cross River', 'portal_state_id' => 10],
    ]);

    $this->get(route('install.geography_setup'))
        ->assertOk()
        ->assertSee('Selected campaign geography')
        ->assertSee('Cross River')
        ->assertSee('Senatorial Districts')
        ->assertSee('3')
        ->assertSee('Federal Constituencies')
        ->assertSee('2')
        ->assertSee('LGAs')
        ->assertSee('2')
        ->assertSee('Wards')
        ->assertSee('196')
        ->assertSee('Polling Units')
        ->assertSee('3,281');
});

test('geography setup summary appears before manual and quickstart choice', function () {
    phase2FakePortalDirectory();
    phase2PrimeCampaign([
        'campaign_scope' => ['package_type' => PackageGovernanceService::GOVERNORSHIP, 'scope_type' => 'state', 'scope_name' => 'Selected State'],
        'campaign_geography' => ['scope_name' => 'Cross River', 'state_name' => 'Cross River', 'portal_state_id' => 10],
    ]);

    $html = $this->get(route('install.geography_setup'))
        ->assertOk()
        ->assertSee('Selected campaign geography')
        ->assertSee('Configure Geography Manually')
        ->assertSee('Populate Campaign Locations Automatically')
        ->getContent();

    expect(strpos($html, 'Selected campaign geography'))->toBeLessThan(strpos($html, 'Configure Geography Manually'))
        ->and(strpos($html, 'Selected campaign geography'))->toBeLessThan(strpos($html, 'Populate Campaign Locations Automatically'));
});

test('geography summary remains visible for manual and quickstart options', function () {
    phase2FakePortalDirectory();
    phase2PrimeCampaign([
        'campaign_scope' => ['package_type' => PackageGovernanceService::GOVERNORSHIP, 'scope_type' => 'state', 'scope_name' => 'Selected State'],
        'campaign_geography' => ['scope_name' => 'Cross River', 'state_name' => 'Cross River', 'portal_state_id' => 10],
    ]);

    $this->get(route('install.geography_setup'))
        ->assertOk()
        ->assertSee('Selected campaign geography')
        ->assertSee('Wards')
        ->assertSee('Polling Units')
        ->assertSee('Configure Geography Manually')
        ->assertSee('Populate Campaign Locations Automatically');
});

test('presidential geography setup requests national aggregate counts', function () {
    config(['campaign.portal_directory_base' => 'https://portal.test/api']);
    Http::fake([
        'https://portal.test/api/campaign-locations/summary*' => Http::response([
            'scope_type' => 'national',
            'scope_name' => 'Nigeria',
            'counts' => [
                'geopolitical_regions' => 6,
                'states' => 37,
                'senatorial_districts' => 109,
                'federal_constituencies' => 360,
                'local_government_areas' => 774,
                'wards' => 8809,
                'polling_units' => 176846,
            ],
        ]),
    ]);
    phase2PrimeCampaign([
        'campaign_scope' => ['package_type' => PackageGovernanceService::PRESIDENTIAL, 'scope_type' => 'national', 'scope_name' => 'Nigeria'],
        'campaign_geography' => ['country_name' => 'Nigeria', 'scope_name' => 'Nigeria'],
    ]);

    $this->get(route('install.geography_setup'))
        ->assertOk()
        ->assertSee('Geopolitical Regions')
        ->assertSee('37')
        ->assertSee('176,846');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/campaign-locations/summary')
        && $request['scope_type'] === 'national');
});

test('geography setup requests counts for selected state district constituency and lga', function (array $scope, array $geography, array $expectedQuery) {
    config(['campaign.portal_directory_base' => 'https://portal.test/api']);
    Http::fake([
        'https://portal.test/api/campaign-locations/summary*' => Http::response([
            'scope_type' => $scope['scope_type'],
            'scope_name' => $geography['scope_name'],
            'counts' => ['wards' => 7, 'polling_units' => 19],
        ]),
    ]);
    phase2PrimeCampaign([
        'campaign_scope' => $scope,
        'campaign_geography' => $geography,
    ]);

    $this->get(route('install.geography_setup'))
        ->assertOk()
        ->assertSee('7')
        ->assertSee('19');

    Http::assertSent(function ($request) use ($expectedQuery) {
        if (! str_contains($request->url(), '/campaign-locations/summary')) {
            return false;
        }

        foreach ($expectedQuery as $key => $value) {
            if ((string) $request[$key] !== (string) $value) {
                return false;
            }
        }

        return true;
    });
})->with([
    'governorship state' => [
        ['package_type' => PackageGovernanceService::GOVERNORSHIP, 'scope_type' => 'state', 'scope_name' => 'Selected State'],
        ['scope_name' => 'Cross River', 'state_name' => 'Cross River', 'portal_state_id' => 10],
        ['scope_type' => 'state', 'state_id' => 10],
    ],
    'senatorial district' => [
        ['package_type' => PackageGovernanceService::SENATORIAL, 'scope_type' => 'senatorial_district', 'scope_name' => 'Selected Senatorial District'],
        ['scope_name' => 'Cross River South', 'state_name' => 'Cross River', 'portal_state_id' => 10, 'portal_senatorial_district_id' => 101],
        ['scope_type' => 'senatorial_district', 'state_id' => 10, 'senatorial_district_id' => 101],
    ],
    'federal constituency' => [
        ['package_type' => PackageGovernanceService::FEDERAL, 'scope_type' => 'federal_constituency', 'scope_name' => 'Selected Federal Constituency'],
        ['scope_name' => 'Calabar Municipal/Odukpani', 'state_name' => 'Cross River', 'portal_state_id' => 10, 'portal_federal_constituency_id' => 201],
        ['scope_type' => 'federal_constituency', 'state_id' => 10, 'federal_constituency_id' => 201],
    ],
    'chairmanship lga' => [
        ['package_type' => PackageGovernanceService::CHAIRMANSHIP, 'scope_type' => 'lga', 'scope_name' => 'Selected LGA'],
        ['scope_name' => 'Calabar Municipal', 'state_name' => 'Cross River', 'portal_state_id' => 10, 'portal_lga_id' => 301],
        ['scope_type' => 'lga', 'state_id' => 10, 'lga_id' => 301],
    ],
]);

test('quickstart key field and purchase link are scoped to quickstart selection', function () {
    config(['campaign.portal_web_base' => 'https://portal.test']);
    phase2FakePortalDirectory();
    phase2PrimeCampaign([
        'campaign_scope' => ['package_type' => PackageGovernanceService::SENATORIAL, 'scope_type' => 'senatorial_district', 'scope_name' => 'Selected Senatorial District'],
        'campaign_geography' => ['scope_name' => 'Cross River South', 'state_name' => 'Cross River', 'portal_state_id' => 10, 'portal_senatorial_district_id' => 101],
    ]);

    $this->get(route('install.geography_setup'))
        ->assertOk()
        ->assertSee('id="quickstart-key"', false)
        ->assertSee('Geography Provisioning Code')
        ->assertSee('Buy a Geography Provisioning Code')
        ->assertSee('target="_blank"', false)
        ->assertSee('rel="noopener"', false)
        ->assertSee('https://portal.test/order/campaign-manager?service=quickstart_geography&amp;package=senatorial&amp;scope_type=senatorial_district&amp;campaign_state_id=10&amp;campaign_senatorial_district_id=101', false);
});

test('quickstart order url includes only valid context for each campaign scope', function (array $scope, array $geography, string $expectedUrl, array $notExpected) {
    config(['campaign.portal_web_base' => 'https://portal.test']);
    phase2FakePortalDirectory();
    phase2PrimeCampaign([
        'campaign_scope' => $scope,
        'campaign_geography' => $geography,
    ]);

    $response = $this->get(route('install.geography_setup'))->assertOk();
    $response->assertSee($expectedUrl, false);

    foreach ($notExpected as $fragment) {
        $response->assertDontSee($fragment, false);
    }
})->with([
    'presidential' => [
        ['package_type' => PackageGovernanceService::PRESIDENTIAL, 'scope_type' => 'national', 'scope_name' => 'Nigeria'],
        ['scope_name' => 'Nigeria', 'portal_state_id' => 10, 'portal_lga_id' => 301],
        'https://portal.test/order/campaign-manager?service=quickstart_geography&amp;package=presidential&amp;scope_type=national',
        ['campaign_state_id=', 'campaign_senatorial_district_id=', 'campaign_federal_constituency_id=', 'campaign_lga_id='],
    ],
    'governorship' => [
        ['package_type' => PackageGovernanceService::GOVERNORSHIP, 'scope_type' => 'state', 'scope_name' => 'Selected State'],
        ['scope_name' => 'Cross River', 'portal_state_id' => 10, 'portal_lga_id' => 301],
        'https://portal.test/order/campaign-manager?service=quickstart_geography&amp;package=governorship&amp;scope_type=state&amp;campaign_state_id=10',
        ['campaign_senatorial_district_id=', 'campaign_federal_constituency_id=', 'campaign_lga_id='],
    ],
    'senatorial' => [
        ['package_type' => PackageGovernanceService::SENATORIAL, 'scope_type' => 'senatorial_district', 'scope_name' => 'Selected Senatorial District'],
        ['scope_name' => 'Cross River South', 'portal_state_id' => 10, 'portal_senatorial_district_id' => 101, 'portal_lga_id' => 301],
        'https://portal.test/order/campaign-manager?service=quickstart_geography&amp;package=senatorial&amp;scope_type=senatorial_district&amp;campaign_state_id=10&amp;campaign_senatorial_district_id=101',
        ['campaign_federal_constituency_id=', 'campaign_lga_id='],
    ],
    'federal' => [
        ['package_type' => PackageGovernanceService::FEDERAL, 'scope_type' => 'federal_constituency', 'scope_name' => 'Selected Federal Constituency'],
        ['scope_name' => 'Calabar Municipal/Odukpani', 'portal_state_id' => 10, 'portal_federal_constituency_id' => 201, 'portal_lga_id' => 301],
        'https://portal.test/order/campaign-manager?service=quickstart_geography&amp;package=federal_constituency&amp;scope_type=federal_constituency&amp;campaign_state_id=10&amp;campaign_federal_constituency_id=201',
        ['campaign_senatorial_district_id=', 'campaign_lga_id='],
    ],
    'chairmanship' => [
        ['package_type' => PackageGovernanceService::CHAIRMANSHIP, 'scope_type' => 'lga', 'scope_name' => 'Selected LGA'],
        ['scope_name' => 'Calabar Municipal', 'portal_state_id' => 10, 'portal_senatorial_district_id' => 101, 'portal_lga_id' => 301],
        'https://portal.test/order/campaign-manager?service=quickstart_geography&amp;package=lga&amp;scope_type=lga&amp;campaign_state_id=10&amp;campaign_lga_id=301',
        ['campaign_senatorial_district_id=', 'campaign_federal_constituency_id='],
    ],
]);

test('portal summary failure is controlled and manual installation still requires no key', function () {
    config(['campaign.portal_directory_base' => 'https://portal.test/api']);
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('offline'));
    phase2PrimeCampaign([
        'campaign_scope' => ['package_type' => PackageGovernanceService::GOVERNORSHIP, 'scope_type' => 'state', 'scope_name' => 'Selected State'],
        'campaign_geography' => ['scope_name' => 'Cross River', 'state_name' => 'Cross River', 'portal_state_id' => 10],
    ]);

    $this->get(route('install.geography_setup'))
        ->assertOk()
        ->assertSee('The geography summary is temporarily unavailable')
        ->assertSee('Manual setup can continue');

    $this->post(route('install.geography_setup'), ['method' => 'manual'])->assertRedirect(route('install.admin'));
});

test('portal directory failure is controlled when no local fallback exists', function () {
    config(['campaign.portal_directory_base' => 'https://portal.test/api']);
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('offline'));
    phase2PrimeInstaller(['campaign_scope' => ['package_type' => PackageGovernanceService::GOVERNORSHIP, 'scope_type' => 'state', 'scope_name' => 'Selected State']]);

    $this->get(route('install.campaign_geography'))
        ->assertOk()
        ->assertSee('Unable to reach the Campaign Manager geography directory')
        ->assertSee('No geography directory records are available right now');
});

test('installer validation prevents unrelated district combination', function () {
    $geo = phase2SeedGeography();
    phase2PrimeInstaller(['campaign_scope' => ['package_type' => PackageGovernanceService::SENATORIAL, 'scope_type' => 'senatorial_district', 'scope_name' => 'Selected Senatorial District']]);

    $this->post(route('install.campaign_geography'), [
        'state_id' => $geo['crossRiver']->id,
        'senatorial_district_id' => $geo['otherDistrict']->id,
    ])->assertSessionHasErrors('campaign_geography');
});

test('campaign scope and identity persist independently from local license', function () {
    $geo = phase2SeedGeography();
    SystemSetting::query()->create(['system_name' => 'Campaign Manager', 'campaign_slogan' => '', 'system_country' => 'Nigeria', 'package' => 'presidential']);

    app(CampaignInstallationSettingsService::class)->persist(
        ['package_type' => PackageGovernanceService::GOVERNORSHIP, 'scope_type' => 'state', 'scope_name' => 'Selected State'],
        ['scope_name' => 'Cross River', 'state_id' => $geo['crossRiver']->id],
        ['system_name' => 'Joshua Ekpe Governorship Campaign 2027', 'campaign_slogan' => 'Forward together']
    );

    $settings = SystemSetting::query()->first();
    $scope = app(PackageScopeService::class)->current();

    expect(LocalLicense::query()->count())->toBe(0)
        ->and($settings->campaign_scope_type)->toBe('state')
        ->and($settings->campaign_state_id)->toBe($geo['crossRiver']->id)
        ->and($settings->system_name)->toBe('Joshua Ekpe Governorship Campaign 2027')
        ->and($scope->scope_name)->toBe('Cross River');
});

test('manual geography setup completes without key or portal preview', function () {
    phase2PrimeCampaign();
    app()->instance(RemoteLicenseClient::class, new class extends RemoteLicenseClient {
        public function preview(array $payload): array
        {
            throw new RuntimeException('Portal preview should not be called for manual geography setup.');
        }
    });

    $this->post(route('install.geography_setup'), ['method' => 'manual'])->assertRedirect(route('install.admin'));

    $state = app(InstallerStateStore::class)->all();
    expect($state['geography_setup']['method'])->toBe('manual')
        ->and($state['community_install'])->toBeTrue()
        ->and($state['license_key'])->toBeNull();
});

test('quickstart selection requires and previews provisioning key', function () {
    phase2PrimeCampaign();

    app()->instance(RemoteLicenseClient::class, new class extends RemoteLicenseClient {
        public function preview(array $payload): array
        {
            return ['ok' => true, 'message' => 'ok', 'data' => [
                'license' => ['package_type' => 'governorship', 'deployment_mode' => 'self_hosted'],
                'scope' => ['scope_type' => 'state', 'scope_name' => 'Cross River'],
                'modules' => [['module_key' => 'members', 'enabled' => true]],
            ]];
        }
    });

    $this->post(route('install.geography_setup'), ['method' => 'quickstart'])->assertSessionHasErrors('license_key');
    $this->post(route('install.geography_setup'), ['method' => 'quickstart', 'license_key' => 'QS-GEO-123'])->assertRedirect(route('install.admin'));

    $state = app(InstallerStateStore::class)->all();
    expect($state['geography_setup']['method'])->toBe('quickstart')
        ->and($state['license_key'])->toBe('QS-GEO-123')
        ->and($state['community_install'])->toBeFalse()
        ->and($state['license_preview'])->not->toBeEmpty();
});

test('legacy license route remains compatible with geography setup branch', function () {
    phase2PrimeCampaign();

    $this->post(route('install.license'), ['method' => 'manual'])->assertRedirect(route('install.admin'));

    expect(app(InstallerStateStore::class)->all()['geography_setup']['method'])->toBe('manual');
});

