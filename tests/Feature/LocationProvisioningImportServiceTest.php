<?php

use App\Models\LocalLicense;
use App\Models\LocalLicenseScope;
use App\Models\LocationProvisioningBatch;
use App\Models\PollingUnit;
use App\Models\State;
use App\Services\LocationProvisioningException;
use App\Services\LocationProvisioningImportService;
use Illuminate\Http\Client\ConnectionException;
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
            'name' => 'Cross River South',
        ],
        'federal_constituencies' => [
            'state_code' => 'CR',
            'state_name' => 'Cross River',
            'senatorial_district_name' => 'Cross River South',
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
        ->and(LocationProvisioningBatch::query()->first()->status)->toBe('completed');
});

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
        ->and(PollingUnit::query()->count())->toBe(0);
});
