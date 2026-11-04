<?php

use App\Models\Country;
use App\Models\LocalGovernmentArea;
use App\Models\LocalLicense;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Ward;
use App\Services\Enterprise\EntitlementService;
use App\Services\Enterprise\WorkforceReportService;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

function workforceReportFixture(): array
{
    config(['campaign.deployment_mode' => 'managed']);
    $country = Country::create(['uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    $region = Region::create(['name' => 'Report Region', 'country_id' => $country->id]);
    $states = [];
    foreach (['Authorized State', 'Other State'] as $name) {
        $state = State::create(['name' => $name, 'region_id' => $region->id]);
        $lga = LocalGovernmentArea::create(['name' => $name.' LGA', 'state_id' => $state->id]);
        $ward = Ward::create(['name' => $name.' Ward', 'lga_id' => $lga->id]);
        $unit = PollingUnit::create(['name' => $name.' PU', 'ward_id' => $ward->id]);
        $member = User::factory()->create([
            'state_id' => $state->id, 'region_id' => $region->id,
            'lga_id' => $lga->id, 'ward_id' => $ward->id, 'polling_unit_id' => $unit->id,
            'validVoter' => 'yes', 'status' => 'active', 'created_at' => '2026-10-01 12:00:00',
        ]);
        $states[] = compact('state', 'lga', 'ward', 'unit', 'member');
    }

    return $states;
}

test('workforce summary and distribution intersect user jurisdiction with all filters', function () {
    [$allowed, $other] = workforceReportFixture();
    $viewer = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => $allowed['state']->id]);
    $reports = app(WorkforceReportService::class);
    $summary = $reports->summary($viewer);
    expect($summary['members'])->toBe(2)
        ->and($summary['polling_units'])->toBe(1)
        ->and($summary['covered_polling_units'])->toBe(1)
        ->and($summary['coverage_percent'])->toBe(100.0);
    expect($reports->distribution($viewer)->pluck('location_name')->all())->toBe(['Authorized State']);
    expect($reports->summary($viewer, ['state_id' => $other['state']->id])['members'])->toBe(0);
    expect($reports->summary($viewer, ['from' => '2026-11-01'])['covered_polling_units'])->toBe(0);
    expect($reports->summary($viewer, ['to' => '2026-10-02'])['members'])->toBe(1);
});

test('workforce reports retain governorship licence isolation even for Super Admin', function () {
    [$allowed, $other] = workforceReportFixture();
    config(['campaign.deployment_mode' => 'self_hosted']);
    $license = LocalLicense::create([
        'license_key' => 'CM-REPORT-'.Str::random(12), 'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship', 'status' => 'active',
    ]);
    $license->scope()->create([
        'scope_type' => 'state', 'state_id' => $allowed['state']->id,
        'state_name' => $allowed['state']->name, 'scope_name' => $allowed['state']->name,
    ]);
    $viewer = User::factory()->create(['access_level' => 'superadmin']);
    $reports = app(WorkforceReportService::class);
    expect($reports->summary($viewer)['members'])->toBe(1);
    expect($reports->distribution($viewer, ['group' => 'polling_unit'])->pluck('location_name')->all())
        ->toBe(['Authorized State PU']);
    expect($reports->summary($viewer, ['state_id' => $other['state']->id])['polling_units'])->toBe(0);
});

test('workforce CSV neutralizes spreadsheet formulas and retains ordinary names', function () {
    foreach (['=1+1', '+SUM(A1)', '@SUM(A1)', '-1+2', " \t=1"] as $value) {
        expect(WorkforceReportService::csvCell($value))->toBe("'".$value);
    }
    expect(WorkforceReportService::csvCell('Cross River'))->toBe('Cross River');
});

test('workforce report routes reject unlicensed and unauthorized users', function () {
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Reports', 'system_country' => 'Nigeria']);
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $user = User::factory()->create(['access_level' => 'stateadmin']);
    $this->actingAs($user)->get(route('enterprise.reports.workforce'))->assertForbidden();
    $user->givePermissionTo(Permission::findOrCreate('reports.view', 'web'));
    $this->mock(EntitlementService::class)->shouldReceive('active')->with('advanced_reports')->andReturn(false);
    $this->get(route('enterprise.reports.workforce'))->assertStatus(402);
    $this->get(route('enterprise.reports.workforce.export'))->assertStatus(402);
});

test('CSV requires export permission separately and contains only authorized aggregates', function () {
    [$allowed, $other] = workforceReportFixture();
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Reports', 'system_country' => 'Nigeria']);
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $user = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => $allowed['state']->id]);
    $user->givePermissionTo(Permission::findOrCreate('reports.view', 'web'));
    $this->mock(EntitlementService::class)->shouldReceive('active')->with('advanced_reports')->andReturn(true);
    $this->actingAs($user)->get(route('enterprise.reports.workforce.export'))->assertForbidden();
    $user->givePermissionTo(Permission::findOrCreate('reports.export', 'web'));
    $response = $this->get(route('enterprise.reports.workforce.export'))->assertOk();
    expect($response->streamedContent())->toContain('Authorized State')->not->toContain('Other State')
        ->not->toContain($allowed['member']->email)->not->toContain($other['member']->email);
});

test('workforce report validates grouping and date range and renders a useful dashboard', function () {
    [$allowed] = workforceReportFixture();
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Reports', 'system_country' => 'Nigeria']);
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $user = User::factory()->create(['access_level' => 'superadmin']);
    $this->mock(EntitlementService::class)->shouldReceive('active')->with('advanced_reports')->andReturn(true);
    $this->actingAs($user)->get(route('enterprise.reports.workforce', ['group' => 'users.email']))
        ->assertSessionHasErrors('group');
    $this->get(route('enterprise.reports.workforce', ['from' => '2026-11-01', 'to' => '2026-10-01']))
        ->assertSessionHasErrors('to');
    $this->get(route('enterprise.reports.workforce', ['to' => '2026-10-02']))->assertOk()
        ->assertSee('Campaign workforce reports')->assertSee('Authorized State')->assertSee('Export CSV');
});
