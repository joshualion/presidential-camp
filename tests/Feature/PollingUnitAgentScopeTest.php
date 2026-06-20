<?php

use App\Models\User;
use App\Services\PollingUnitAgentAssignmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('federal admin can access agent assignment through lga boundary when polling unit boundary is missing', function () {
    $now = now();

    DB::table('countries')->insert([
        'id' => 1,
        'uuid' => (string) Str::uuid(),
        'name' => 'Nigeria',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('regions')->insert([
        'id' => 1,
        'uuid' => (string) Str::uuid(),
        'name' => 'South East',
        'country_id' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('states')->insert([
        'id' => 1,
        'uuid' => (string) Str::uuid(),
        'name' => 'Anambra',
        'region_id' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('senatorial_districts')->insert([
        'id' => 1,
        'uuid' => (string) Str::uuid(),
        'state_id' => 1,
        'name' => 'Anambra Central',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('federal_constituencies')->insert([
        [
            'id' => 1,
            'uuid' => (string) Str::uuid(),
            'state_id' => 1,
            'senatorial_district_id' => 1,
            'name' => 'Awka North/South',
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'id' => 2,
            'uuid' => (string) Str::uuid(),
            'state_id' => 1,
            'senatorial_district_id' => 1,
            'name' => 'Onitsha North/South',
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    DB::table('local_government_areas')->insert([
        'id' => 1,
        'uuid' => (string) Str::uuid(),
        'name' => 'Awka South',
        'state_id' => 1,
        'senatorial_district_id' => 1,
        'federal_constituency_id' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('wards')->insert([
        'id' => 1,
        'uuid' => (string) Str::uuid(),
        'name' => 'Ward 04',
        'lga_id' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('polling_units')->insert([
        'id' => 1,
        'uuid' => (string) Str::uuid(),
        'name' => 'PU 21/04/08/003',
        'ward_id' => 1,
        'senatorial_district_id' => null,
        'federal_constituency_id' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $federalAdmin = User::withoutEvents(fn () => User::factory()->create([
        'access_level' => 'federaladmin',
        'country_id' => 1,
        'region_id' => 1,
        'state_id' => 1,
        'federal_constituency_id' => 1,
        'religion_id' => null,
        'age_grade_id' => null,
    ]));
    $otherFederalAdmin = User::withoutEvents(fn () => User::factory()->create([
        'access_level' => 'federaladmin',
        'country_id' => 1,
        'region_id' => 1,
        'state_id' => 1,
        'federal_constituency_id' => 2,
        'religion_id' => null,
        'age_grade_id' => null,
    ]));
    $agent = User::withoutEvents(fn () => User::factory()->create([
        'country_id' => 1,
        'region_id' => 1,
        'state_id' => 1,
        'lga_id' => 1,
        'ward_id' => 1,
        'polling_unit_id' => 1,
        'religion_id' => null,
        'age_grade_id' => null,
    ]));

    $assignmentUuid = (string) Str::uuid();
    DB::table('polling_unit_agent_assignments')->insert([
        'uuid' => $assignmentUuid,
        'user_id' => $agent->id,
        'polling_unit_id' => 1,
        'status' => 'pending',
        'source' => 'self_request',
        'identity_verification_status' => 'pending',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $service = app(PollingUnitAgentAssignmentService::class);

    expect($service->scopedQuery($federalAdmin)->where('uuid', $assignmentUuid)->first())->not->toBeNull()
        ->and($service->scopedQuery($otherFederalAdmin)->where('uuid', $assignmentUuid)->first())->toBeNull();
});
