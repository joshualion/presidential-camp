<?php

use App\Models\LocalLicense;
use App\Models\LocalLicenseScope;
use App\Models\LocationProvisioningBatch;
use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\SystemSetting;
use App\Services\LocationProvisioningException;
use App\Services\DashboardDrilldownService;
use App\Services\GeographyIdentityRepairService;
use App\Services\LicensedScopeQueryService;
use App\Services\LocationProvisioningIntegrityService;
use App\Services\LocationProvisioningImportService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function portalProvisioningLicense(string $packageType = 'presidential', string $activationId = 'ACT-123'): LocalLicense
{
    $license = LocalLicense::query()->create([
        'license_key' => 'LIC-PORTAL-'.Str::random(6),
        'deployment_mode' => 'self_hosted',
        'package_type' => $packageType,
        'status' => 'active',
        'activation_id' => $activationId,
    ]);

    LocalLicenseScope::query()->create([
        'local_license_id' => $license->id,
        'scope_type' => $packageType === 'presidential' ? 'national' : 'state',
        'scope_name' => $packageType === 'presidential' ? 'National' : 'Cross River',
        'state_name' => $packageType === 'presidential' ? null : 'Cross River',
        'raw_payload' => [
            'scope_type' => $packageType === 'presidential' ? 'national' : 'state',
            'scope_name' => $packageType === 'presidential' ? 'National' : 'Cross River',
            'state_name' => $packageType === 'presidential' ? null : 'Cross River',
            'campaign_state_id' => $packageType === 'presidential' ? null : 11,
        ],
    ]);

    return $license->fresh('scope');
}

function portalSessionPayload(array $datasets = LocationProvisioningImportService::DATASETS): array
{
    return [
        'provisioning_session_id' => 'SESSION-1',
        'session_token' => 'TOKEN-1',
        'location_data_version' => '2026.1',
        'package_type' => 'presidential',
        'release_profile' => 'full',
        'datasets' => $datasets,
        'total_estimated_records' => array_fill_keys($datasets, 1),
        'scope_payload' => ['scope_type' => 'national', 'scope_name' => 'National'],
    ];
}

function portalBatchPayload(string $dataset, array $records, array $extra = []): array
{
    return array_merge([
        'dataset' => $dataset,
        'records' => $records,
        'next_cursor' => count($records),
        'has_more' => false,
        'total_records' => 6,
    ], $extra);
}

function portalRecord(string $dataset): array
{
    return match ($dataset) {
        'states' => [
            'country_name' => 'Nigeria',
            'region_name' => 'South South',
            'state_code' => 'CR',
            'name' => 'Cross River',
        ],
        'senatorial_districts' => [
            'state_code' => 'CR',
            'state_name' => 'Cross River',
            'senatorial_district_code' => 'CR-SD-S',
            'inec_senatorial_district_code' => 'CR-SD-S',
            'name' => 'Cross River South',
        ],
        'federal_constituencies' => [
            'state_code' => 'CR',
            'state_name' => 'Cross River',
            'senatorial_district_name' => 'Cross River South',
            'federal_constituency_code' => 'CR-FC-CAL',
            'inec_federal_constituency_code' => 'CR-FC-CAL',
            'name' => 'Calabar South/Akpabuyo/Bakassi',
        ],
        'local_government_areas' => [
            'state_code' => 'CR',
            'state_name' => 'Cross River',
            'lga_code' => 'CR01',
            'name' => 'Calabar South',
            'senatorial_district_name' => 'Cross River South',
            'federal_constituency_name' => 'Calabar South/Akpabuyo/Bakassi',
        ],
        'wards' => [
            'state_code' => 'CR',
            'state_name' => 'Cross River',
            'lga_code' => 'CR01',
            'lga_name' => 'Calabar South',
            'ward_code' => 'W01',
            'name' => 'Ward One',
        ],
        'polling_units' => [
            'state_code' => 'CR',
            'state_name' => 'Cross River',
            'lga_code' => 'CR01',
            'lga_name' => 'Calabar South',
            'ward_code' => 'W01',
            'ward_name' => 'Ward One',
            'pu_code' => 'PU01',
            'inec_full_code' => 'CR-01-W01-PU01',
            'name' => 'PU One',
            'senatorial_district_name' => 'Cross River South',
            'federal_constituency_name' => 'Calabar South/Akpabuyo/Bakassi',
        ],
    };
}

beforeEach(function () {
    Config::set('campaign.portal_api_base', 'https://portal.test/api/v2');
});

test('successful provisioning session and batch import', function () {
    $license = portalProvisioningLicense();
    Http::fakeSequence()
        ->push(portalSessionPayload())
        ->push(portalBatchPayload('states', [portalRecord('states')]))
        ->push(portalBatchPayload('senatorial_districts', [portalRecord('senatorial_districts')]))
        ->push(portalBatchPayload('federal_constituencies', [portalRecord('federal_constituencies')]))
        ->push(portalBatchPayload('local_government_areas', [portalRecord('local_government_areas')]))
        ->push(portalBatchPayload('wards', [portalRecord('wards')]))
        ->push(portalBatchPayload('polling_units', [portalRecord('polling_units')]))
        ->push(['ok' => true]);

    $result = app(LocationProvisioningImportService::class)->provision($license, 500);

    expect($result['status'])->toBe('completed')
        ->and(State::query()->where('name', 'Cross River')->exists())->toBeTrue()
        ->and(PollingUnit::query()->where('inec_full_code', 'CR-01-W01-PU01')->exists())->toBeTrue()
        ->and(LocationProvisioningBatch::query()->first()->total_records)->toBe(6)
        ->and(LocationProvisioningBatch::query()->first()->status)->toBe('completed');
});

test('quickstart provisioning starts without activation id when activation is not required', function () {
    $license = LocalLicense::query()->create([
        'license_key' => 'QS-GEO-'.Str::random(6),
        'deployment_mode' => 'self_hosted',
        'package_type' => 'presidential',
        'status' => 'active',
        'activation_id' => null,
        'raw_payload' => [
            'license' => [
                'license_key' => 'QS-GEO',
                'entitlement_type' => 'quickstart_geography',
                'provisioning_type' => 'quickstart_geography',
                'activation_required' => false,
                'status' => 'active',
                'package_type' => 'presidential',
            ],
            'scope' => [
                'scope_type' => 'national',
                'scope_name' => 'National',
            ],
        ],
    ]);

    LocalLicenseScope::query()->create([
        'local_license_id' => $license->id,
        'scope_type' => 'national',
        'scope_name' => 'National',
        'raw_payload' => ['scope_type' => 'national', 'scope_name' => 'National'],
    ]);

    $sessionPayloads = [];
    Http::fake(function ($request) use (&$sessionPayloads) {
        if (str_contains($request->url(), '/session')) {
            $sessionPayloads[] = $request->data();

            return Http::response(portalSessionPayload(['states']));
        }

        if (str_contains($request->url(), '/complete')) {
            return Http::response(['ok' => true]);
        }

        return Http::response(portalBatchPayload('states', [portalRecord('states')], [
            'datasets' => ['states'],
            'total_records' => 1,
        ]));
    });

    $result = app(LocationProvisioningImportService::class)->provision($license->fresh('scope'), 500);
    $run = LocationProvisioningBatch::query()->first();

    expect($result['status'])->toBe('completed')
        ->and($sessionPayloads[0])->not->toHaveKey('activation_id')
        ->and($run->activation_id)->toBeNull()
        ->and(State::query()->where('name', 'Cross River')->exists())->toBeTrue();
});

test('legacy provisioning still requires activation id when activation is required', function () {
    $license = LocalLicense::query()->create([
        'license_key' => 'LEGACY-'.Str::random(6),
        'deployment_mode' => 'self_hosted',
        'package_type' => 'presidential',
        'status' => 'active',
        'activation_id' => null,
        'raw_payload' => [
            'license' => [
                'license_key' => 'LEGACY',
                'activation_required' => true,
                'status' => 'active',
                'package_type' => 'presidential',
            ],
        ],
    ]);

    LocalLicenseScope::query()->create([
        'local_license_id' => $license->id,
        'scope_type' => 'national',
        'scope_name' => 'National',
        'raw_payload' => ['scope_type' => 'national', 'scope_name' => 'National'],
    ]);

    app(LocationProvisioningImportService::class)->startOrResume($license->fresh('scope'));
})->throws(RuntimeException::class, 'License activation is incomplete');

test('portal unreachable fallback is tracked without importing data', function () {
    $license = portalProvisioningLicense();
    Http::fake(fn () => throw new ConnectionException('network down'));

    app(LocationProvisioningImportService::class)->startOrResume($license);
})->throws(LocationProvisioningException::class, 'Unable to reach the Campaign Manager portal');

test('retry resumes from last cursor', function () {
    $license = portalProvisioningLicense();
    $seenCursors = [];
    Http::fake(function ($request) use (&$seenCursors) {
        if (str_contains($request->url(), '/session')) {
            return Http::response(portalSessionPayload(['states']));
        }

        if (str_contains($request->url(), '/complete')) {
            return Http::response(['ok' => true]);
        }

        $payload = $request->data();
        $seenCursors[] = (int) $payload['cursor'];

        if ((int) $payload['cursor'] === 0) {
            return Http::response(portalBatchPayload('states', [portalRecord('states')], ['has_more' => true, 'next_cursor' => 1, 'total_records' => 2]));
        }

        return Http::response(portalBatchPayload('states', [[
            'country_name' => 'Nigeria',
            'region_name' => 'North Central',
            'state_code' => 'BN',
            'name' => 'Benue',
        ]], ['has_more' => false, 'next_cursor' => 2, 'total_records' => 2]));
    });

    $service = app(LocationProvisioningImportService::class);
    $run = $service->startOrResume($license, 1);
    $service->processNextBatch($run, 1);

    expect($run->fresh()->current_cursor)->toBe(1);

    $result = $service->processNextBatch($run->fresh(), 1);

    expect($result['status'])->toBe('completed')
        ->and($seenCursors)->toBe([0, 1])
        ->and(State::query()->pluck('name')->sort()->values()->all())->toBe(['Benue', 'Cross River']);
});

test('unauthorized provisioning is blocked', function () {
    $license = portalProvisioningLicense();
    Http::fakeSequence()->push(['message' => 'Unauthorized'], 401);

    app(LocationProvisioningImportService::class)->startOrResume($license);
})->throws(LocationProvisioningException::class, 'Unauthorized');

test('community profile imports only datasets returned by portal', function () {
    $license = portalProvisioningLicense('governorship');
    $datasets = ['states', 'senatorial_districts', 'federal_constituencies', 'local_government_areas', 'wards'];

    Http::fakeSequence()
        ->push(portalSessionPayload($datasets))
        ->push(portalBatchPayload('states', [portalRecord('states')], ['datasets' => $datasets]))
        ->push(portalBatchPayload('senatorial_districts', [portalRecord('senatorial_districts')], ['datasets' => $datasets]))
        ->push(portalBatchPayload('federal_constituencies', [portalRecord('federal_constituencies')], ['datasets' => $datasets]))
        ->push(portalBatchPayload('local_government_areas', [portalRecord('local_government_areas')], ['datasets' => $datasets]))
        ->push(portalBatchPayload('wards', [portalRecord('wards')], ['datasets' => $datasets]))
        ->push(['ok' => true]);

    $result = app(LocationProvisioningImportService::class)->provision($license, 500);

    expect($result['status'])->toBe('completed')
        ->and($result['summary']['polling_units']['imported'] ?? 0)->toBe(0)
        ->and($license->scope->fresh()->state_id)->toBe(State::query()->where('name', 'Cross River')->value('id'))
        ->and($license->scope->fresh()->state_id)->not->toBe(11)
        ->and(PollingUnit::query()->count())->toBe(0);
});

test('quickstart reuses same-name null-code geography and keeps scoped descendants visible', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    $country = Country::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    $region = Region::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'South South', 'country_id' => $country->id]);
    $state = State::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Cross River', 'region_id' => $region->id]);
    $district = SenatorialDistrict::query()->create(['uuid' => (string) Str::uuid(), 'state_id' => $state->id, 'name' => 'Cross River South', 'normalized_name' => 'cross-river-south']);
    $constituency = FederalConstituency::query()->create(['uuid' => (string) Str::uuid(), 'state_id' => $state->id, 'senatorial_district_id' => $district->id, 'name' => 'Calabar South/Akpabuyo/Bakassi', 'normalized_name' => 'calabar-south-akpabuyo-bakassi']);
    $lga = LocalGovernmentArea::query()->create(['uuid' => (string) Str::uuid(), 'state_id' => $state->id, 'senatorial_district_id' => $district->id, 'federal_constituency_id' => $constituency->id, 'name' => 'Calabar South']);

    SystemSetting::query()->create([
        'system_name' => 'Campaign Manager',
        'campaign_slogan' => '',
        'system_country' => 'Nigeria',
        'package' => 'lga',
        'campaign_scope_type' => 'lga',
        'campaign_scope_name' => 'Calabar South',
        'campaign_state_id' => $state->id,
        'campaign_senatorial_district_id' => $district->id,
        'campaign_federal_constituency_id' => $constituency->id,
        'campaign_lga_id' => $lga->id,
    ]);

    $license = LocalLicense::query()->create([
        'license_key' => 'QS-GEO-'.Str::random(6),
        'deployment_mode' => 'self_hosted',
        'package_type' => 'lga',
        'status' => 'active',
        'activation_id' => null,
        'raw_payload' => ['license' => ['activation_required' => false]],
    ]);

    LocalLicenseScope::query()->create([
        'local_license_id' => $license->id,
        'scope_type' => 'lga',
        'scope_name' => 'Calabar South',
        'state_name' => 'Cross River',
        'senatorial_district_name' => 'Cross River South',
        'federal_constituency_name' => 'Calabar South/Akpabuyo/Bakassi',
        'lga_name' => 'Calabar South',
        'raw_payload' => [
            'scope_type' => 'lga',
            'scope_name' => 'Calabar South',
            'campaign_state_id' => 10,
            'campaign_lga_id' => 301,
            'state_name' => 'Cross River',
            'state_code' => 'CR',
            'senatorial_district_name' => 'Cross River South',
            'senatorial_district_code' => 'CR-SD-S',
            'federal_constituency_name' => 'Calabar South/Akpabuyo/Bakassi',
            'federal_constituency_code' => 'CR-FC-CAL',
            'lga_name' => 'Calabar South',
            'lga_code' => 'CR01',
        ],
    ]);

    Http::fakeSequence()
        ->push(portalSessionPayload())
        ->push(portalBatchPayload('states', [portalRecord('states')]))
        ->push(portalBatchPayload('senatorial_districts', [portalRecord('senatorial_districts')]))
        ->push(portalBatchPayload('federal_constituencies', [portalRecord('federal_constituencies')]))
        ->push(portalBatchPayload('local_government_areas', [portalRecord('local_government_areas')]))
        ->push(portalBatchPayload('wards', [portalRecord('wards')]))
        ->push(portalBatchPayload('polling_units', [portalRecord('polling_units')]))
        ->push(['ok' => true]);

    $result = app(LocationProvisioningImportService::class)->provision($license->fresh('scope'), 500);

    $scope = $license->fresh('scope')->scope;
    $settings = SystemSetting::query()->first();
    $pollingUnitId = PollingUnit::query()->value('id');

    expect($result['status'])->toBe('completed')
        ->and(State::query()->where('name', 'Cross River')->count())->toBe(1)
        ->and(SenatorialDistrict::query()->where('name', 'Cross River South')->count())->toBe(1)
        ->and(FederalConstituency::query()->where('name', 'Calabar South/Akpabuyo/Bakassi')->count())->toBe(1)
        ->and(LocalGovernmentArea::query()->where('name', 'Calabar South')->count())->toBe(1)
        ->and($state->fresh()->inec_state_code)->toBe('CR')
        ->and($district->fresh()->inec_senatorial_district_code)->toBe('CR-SD-S')
        ->and($constituency->fresh()->inec_federal_constituency_code)->toBe('CR-FC-CAL')
        ->and($lga->fresh()->inec_lga_code)->toBe('CR01')
        ->and($scope->state_id)->toBe($state->id)
        ->and($scope->senatorial_district_id)->toBe($district->id)
        ->and($scope->federal_constituency_id)->toBe($constituency->id)
        ->and($scope->lga_id)->toBe($lga->id)
        ->and($settings->campaign_state_id)->toBe($state->id)
        ->and($settings->campaign_senatorial_district_id)->toBe($district->id)
        ->and($settings->campaign_federal_constituency_id)->toBe($constituency->id)
        ->and($settings->campaign_lga_id)->toBe($lga->id)
        ->and(app(LicensedScopeQueryService::class)->allowsPayload(['polling_unit_id' => $pollingUnitId]))->toBeTrue()
        ->and(app(DashboardDrilldownService::class)->senatorialDistrict($district->uuid)->is($district))->toBeTrue()
        ->and(PollingUnit::query()->count())->toBe(1);
});

test('geography identity repair merges coded duplicates and is idempotent', function () {
    $country = Country::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    $region = Region::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'South South', 'country_id' => $country->id]);
    $canonicalState = State::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Cross River', 'region_id' => $region->id]);
    $duplicateState = State::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Cross River', 'region_id' => $region->id, 'inec_state_code' => 'CR']);
    $canonicalDistrict = SenatorialDistrict::query()->create(['uuid' => (string) Str::uuid(), 'state_id' => $canonicalState->id, 'name' => 'Cross River South', 'normalized_name' => 'cross-river-south']);
    $duplicateDistrict = SenatorialDistrict::query()->create(['uuid' => (string) Str::uuid(), 'state_id' => $duplicateState->id, 'name' => 'Cross River South', 'normalized_name' => 'cross-river-south', 'inec_senatorial_district_code' => 'CR-SD-S']);
    $canonicalFederal = FederalConstituency::query()->create(['uuid' => (string) Str::uuid(), 'state_id' => $canonicalState->id, 'senatorial_district_id' => $canonicalDistrict->id, 'name' => 'Calabar South/Akpabuyo/Bakassi', 'normalized_name' => 'calabar-south-akpabuyo-bakassi']);
    $duplicateFederal = FederalConstituency::query()->create(['uuid' => (string) Str::uuid(), 'state_id' => $duplicateState->id, 'senatorial_district_id' => $duplicateDistrict->id, 'name' => 'Calabar South/Akpabuyo/Bakassi', 'normalized_name' => 'calabar-south-akpabuyo-bakassi', 'inec_federal_constituency_code' => 'CR-FC-CAL']);
    $canonicalLga = LocalGovernmentArea::query()->create(['uuid' => (string) Str::uuid(), 'state_id' => $canonicalState->id, 'senatorial_district_id' => $canonicalDistrict->id, 'federal_constituency_id' => $canonicalFederal->id, 'name' => 'Calabar South']);
    $duplicateLga = LocalGovernmentArea::query()->create(['uuid' => (string) Str::uuid(), 'state_id' => $duplicateState->id, 'senatorial_district_id' => $duplicateDistrict->id, 'federal_constituency_id' => $duplicateFederal->id, 'name' => 'Calabar South', 'inec_lga_code' => 'CR01']);

    DB::table('wards')->insert([
        'uuid' => (string) Str::uuid(),
        'lga_id' => $duplicateLga->id,
        'name' => 'Ward One',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    SystemSetting::query()->create([
        'system_name' => 'Campaign Manager',
        'campaign_slogan' => '',
        'system_country' => 'Nigeria',
        'package' => 'lga',
        'campaign_scope_type' => 'lga',
        'campaign_scope_name' => 'Calabar South',
        'campaign_state_id' => $duplicateState->id,
        'campaign_senatorial_district_id' => $duplicateDistrict->id,
        'campaign_federal_constituency_id' => $duplicateFederal->id,
        'campaign_lga_id' => $duplicateLga->id,
    ]);

    $license = LocalLicense::query()->create(['license_key' => 'QS-REPAIR', 'deployment_mode' => 'self_hosted', 'package_type' => 'lga', 'status' => 'active']);
    $license->scope()->create([
        'scope_type' => 'lga',
        'scope_name' => 'Calabar South',
        'state_id' => $duplicateState->id,
        'senatorial_district_id' => $duplicateDistrict->id,
        'federal_constituency_id' => $duplicateFederal->id,
        'lga_id' => $duplicateLga->id,
    ]);

    $summary = app(GeographyIdentityRepairService::class)->repair();
    $secondSummary = app(GeographyIdentityRepairService::class)->repair();

    expect($summary['states'])->toBe(1)
        ->and($summary['senatorial_districts'])->toBe(1)
        ->and($summary['federal_constituencies'])->toBe(1)
        ->and($summary['local_government_areas'])->toBe(1)
        ->and($secondSummary['total_merged'])->toBe(0)
        ->and(State::query()->where('name', 'Cross River')->count())->toBe(1)
        ->and(SenatorialDistrict::query()->where('name', 'Cross River South')->count())->toBe(1)
        ->and(FederalConstituency::query()->where('name', 'Calabar South/Akpabuyo/Bakassi')->count())->toBe(1)
        ->and(LocalGovernmentArea::query()->where('name', 'Calabar South')->count())->toBe(1)
        ->and($canonicalState->fresh()->inec_state_code)->toBe('CR')
        ->and($canonicalDistrict->fresh()->inec_senatorial_district_code)->toBe('CR-SD-S')
        ->and($canonicalFederal->fresh()->inec_federal_constituency_code)->toBe('CR-FC-CAL')
        ->and($canonicalLga->fresh()->inec_lga_code)->toBe('CR01')
        ->and(DB::table('wards')->value('lga_id'))->toBe($canonicalLga->id)
        ->and(SystemSetting::query()->value('campaign_state_id'))->toBe($canonicalState->id)
        ->and(SystemSetting::query()->value('campaign_senatorial_district_id'))->toBe($canonicalDistrict->id)
        ->and(SystemSetting::query()->value('campaign_federal_constituency_id'))->toBe($canonicalFederal->id)
        ->and(SystemSetting::query()->value('campaign_lga_id'))->toBe($canonicalLga->id)
        ->and($license->fresh('scope')->scope->state_id)->toBe($canonicalState->id)
        ->and($license->fresh('scope')->scope->senatorial_district_id)->toBe($canonicalDistrict->id)
        ->and($license->fresh('scope')->scope->federal_constituency_id)->toBe($canonicalFederal->id)
        ->and($license->fresh('scope')->scope->lga_id)->toBe($canonicalLga->id);
});
test('completed scoped provisioning cannot pass integrity with an unresolved local boundary', function () {
    $license = portalProvisioningLicense('governorship');
    $summary = [
        '_available_datasets' => LocationProvisioningImportService::DATASETS,
        'states' => ['imported' => 1],
        'senatorial_districts' => ['imported' => 3],
        'federal_constituencies' => ['imported' => 8],
        'local_government_areas' => ['imported' => 18],
        'wards' => ['imported' => 193],
        'polling_units' => ['imported' => 3281],
    ];

    $result = app(LocationProvisioningIntegrityService::class)->verifyBeforeInstallLock(
        $license,
        ['status' => 'completed', 'data_profile' => 'portal', 'summary' => $summary],
    );

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toContain('could not resolve the licensed scope');
});
