<?php

use App\Models\{Country, Region, State, LocalGovernmentArea, Ward, PollingUnit, User, LocalLicense};
use App\Services\Enterprise\{CampaignInventoryService, EntitlementService};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;

function inventoryUnitFixture(int $id, int $stateId = 1): int
{
    $country = Country::firstOrCreate(['id' => 1], ['uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    $region = Region::firstOrCreate(['id' => 1], ['name' => 'Region', 'country_id' => $country->id]);
    $state = State::firstOrCreate(['id' => $stateId], ['name' => 'State '.$stateId, 'region_id' => $region->id]);
    $lga = LocalGovernmentArea::firstOrCreate(['id' => $stateId], ['name' => 'LGA '.$stateId, 'state_id' => $state->id]);
    $ward = Ward::firstOrCreate(['id' => $stateId], ['name' => 'Ward '.$stateId, 'lga_id' => $lga->id]);
    return PollingUnit::create(['id' => $id, 'name' => 'Office '.$id, 'ward_id' => $ward->id])->id;
}

beforeEach(function () {
    $this->mock(EntitlementService::class)->shouldReceive('active')->with('campaign_merchandise')->andReturn(true)->byDefault();
    $this->inventory = app(CampaignInventoryService::class);
    $this->admin = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $this->source = inventoryUnitFixture(901);
    $this->destination = inventoryUnitFixture(902);
    $category = $this->inventory->category($this->admin, ['name' => 'Campaign print']);
    $this->material = $this->inventory->material($this->admin, [
        'category_id' => $category, 'name' => '=SUM(1,2)', 'sku' => 'POSTER', 'unit' => 'pieces', 'low_stock_level' => 5,
    ]);
    $this->adjustment = ['material_id' => $this->material, 'polling_unit_id' => $this->source,
        'quantity' => 100, 'reason' => 'Receipt INV-001', 'reference' => (string) Str::uuid()];
    \Illuminate\Support\Facades\Route::middleware('web')->group(base_path('routes/enterprise-inventory.php'));
    app('router')->getRoutes()->refreshNameLookups();
});

test('stock receipt retries are idempotent and adjustments keep an immutable movement history', function () {
    $this->inventory->adjust($this->admin, $this->adjustment);
    $this->inventory->adjust($this->admin, $this->adjustment);
    $this->inventory->adjust($this->admin, array_replace($this->adjustment, ['quantity' => -7, 'reason' => 'Damaged posters', 'reference' => (string) Str::uuid()]));
    expect(DB::table('campaign_material_stocks')->value('quantity'))->toBe(93);
    expect(DB::table('campaign_material_movements')->orderBy('id')->pluck('balance_after')->all())->toBe([100, 93]);
    expect(DB::table('campaign_material_movements')->pluck('actor_id')->unique()->all())->toBe([$this->admin->id]);
});

test('an adjustment cannot underflow and failed adjustments leave no stock or audit mutation', function () {
    try {
        $this->inventory->adjust($this->admin, array_replace($this->adjustment, ['quantity' => -1]));
        $this->fail('Negative stock was accepted.');
    } catch (ValidationException) {}
    expect(DB::table('campaign_material_stocks')->count())->toBe(0);
    expect(DB::table('campaign_material_movements')->count())->toBe(0);
});

test('an adjustment reference cannot be reused to change the original amount', function () {
    $this->inventory->adjust($this->admin, $this->adjustment);
    try {
        $this->inventory->adjust($this->admin, array_replace($this->adjustment, ['quantity' => 99]));
        $this->fail('A changed retry was accepted.');
    } catch (HttpException $e) { expect($e->getStatusCode())->toBe(409); }
    expect(DB::table('campaign_material_stocks')->value('quantity'))->toBe(100);
});

test('distribution atomically moves stock between offices and fulfilment retries never double spend it', function () {
    $this->inventory->adjust($this->admin, $this->adjustment);
    $id = $this->inventory->request($this->admin, ['material_id' => $this->material, 'source_polling_unit_id' => $this->source,
        'destination_polling_unit_id' => $this->destination, 'quantity' => 40, 'purpose' => 'Ward mobilisation']);
    $this->inventory->fulfil($this->admin, $id);
    $this->inventory->fulfil($this->admin, $id);
    expect(DB::table('campaign_material_stocks')->orderBy('polling_unit_id')->pluck('quantity')->all())->toBe([60, 40]);
    expect(DB::table('campaign_material_movements')->where('request_id', $id)->pluck('change')->all())->toBe([-40, 40]);
    expect(DB::table('campaign_material_requests')->where('id', $id)->value('status'))->toBe('fulfilled');
});

test('insufficient distribution rolls back both offices and leaves the request pending', function () {
    $this->inventory->adjust($this->admin, $this->adjustment);
    $id = $this->inventory->request($this->admin, ['material_id' => $this->material, 'source_polling_unit_id' => $this->source,
        'destination_polling_unit_id' => $this->destination, 'quantity' => 101, 'purpose' => 'Campaign event']);
    try { $this->inventory->fulfil($this->admin, $id); $this->fail('Overspending stock was accepted.'); }
    catch (ValidationException) {}
    expect(DB::table('campaign_material_stocks')->count())->toBe(1);
    expect(DB::table('campaign_material_stocks')->value('quantity'))->toBe(100);
    expect(DB::table('campaign_material_movements')->count())->toBe(1);
    expect(DB::table('campaign_material_requests')->where('id', $id)->value('status'))->toBe('pending');
});

test('geographic permissions filter stock and reject cross jurisdiction adjustments and distributions', function () {
    $outside = inventoryUnitFixture(903, 2);
    $this->inventory->adjust($this->admin, $this->adjustment);
    $this->inventory->adjust($this->admin, array_replace($this->adjustment, ['polling_unit_id' => $outside, 'reference' => (string) Str::uuid()]));
    $stateAdmin = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => 1, 'status' => 'active']);
    foreach (['merchandise.view', 'merchandise.inventory.manage', 'merchandise.orders.view', 'merchandise.orders.fulfil'] as $name) {
        $stateAdmin->givePermissionTo(Permission::findOrCreate($name, 'web'));
    }
    expect($this->inventory->stocks($stateAdmin)->pluck('polling_unit_id')->all())->toBe([$this->source]);
    try { $this->inventory->adjust($stateAdmin, array_replace($this->adjustment, ['polling_unit_id' => $outside, 'reference' => (string) Str::uuid()])); $this->fail('Foreign office accepted.'); }
    catch (HttpException $e) { expect($e->getStatusCode())->toBe(403); }
    $id = $this->inventory->request($this->admin, ['material_id' => $this->material, 'source_polling_unit_id' => $this->source,
        'destination_polling_unit_id' => $outside, 'quantity' => 10, 'purpose' => 'Outside transfer']);
    expect($this->inventory->requests($stateAdmin)->count())->toBe(0);
    try { $this->inventory->fulfil($stateAdmin, $id); $this->fail('Foreign transfer accepted.'); }
    catch (HttpException $e) { expect($e->getStatusCode())->toBe(403); }
    expect(DB::table('campaign_material_requests')->where('id', $id)->value('status'))->toBe('pending');
});

test('installation licence scope also constrains super admin inventory', function () {
    $outside = inventoryUnitFixture(903, 2);
    $license = LocalLicense::create(['license_key' => 'INVENTORY-SCOPE', 'deployment_mode' => 'self_hosted', 'package_type' => 'governorship', 'status' => 'active']);
    $license->scope()->create(['scope_type' => 'state', 'scope_name' => 'State 1', 'state_id' => 1]);
    expect($this->inventory->units($this->admin)->pluck('id')->all())->toBe([$this->source, $this->destination]);
    try { $this->inventory->adjust($this->admin, array_replace($this->adjustment, ['polling_unit_id' => $outside])); $this->fail('Foreign licensed scope accepted.'); }
    catch (HttpException $e) { expect($e->getStatusCode())->toBe(403); }
});

test('installation ownership never bypasses a missing merchandise entitlement', function () {
    $this->mock(EntitlementService::class)->shouldReceive('active')->with('campaign_merchandise')->andReturn(false);
    $inventory = app(CampaignInventoryService::class);
    try { $inventory->adjust($this->admin, $this->adjustment); $this->fail('Unlicensed adjustment accepted.'); }
    catch (HttpException $e) { expect($e->getStatusCode())->toBe(402); }
    expect(DB::table('campaign_material_movements')->count())->toBe(0);
});

test('geographic administrator rank does not grant material management', function () {
    $actor = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => 1, 'status' => 'active']);
    try { $this->inventory->adjust($actor, $this->adjustment); $this->fail('Implicit permission accepted.'); }
    catch (HttpException $e) { expect($e->getStatusCode())->toBe(403); }
});

test('inventory routes enforce entitlement and user capability and stock CSV escapes spreadsheet formulas', function () {
    $this->inventory->adjust($this->admin, $this->adjustment);
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $response = $this->actingAs($this->admin)->get(route('enterprise.inventory.export'))->assertOk();
    expect($response->streamedContent())->toContain("'=SUM(1,2)");
    foreach (['index', 'locations', 'export', 'categories', 'materials', 'adjustments', 'requests', 'fulfil'] as $name) {
        $route = app('router')->getRoutes()->getByName('enterprise.inventory.'.$name);
        expect(collect($route->gatherMiddleware())->contains(fn ($name) => str_starts_with($name, 'enterprise.feature:campaign_merchandise,')))->toBeTrue();
    }
});

test('office lookup returns only scoped locations and dashboard renders stock workflows', function () {
    \App\Models\SystemSetting::create(['package' => 'presidential', 'system_name' => 'Inventory fixture', 'system_country' => 'Nigeria']);
    inventoryUnitFixture(903, 2);
    $stateAdmin = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => 1, 'status' => 'active']);
    $stateAdmin->givePermissionTo(Permission::findOrCreate('merchandise.view', 'web'));
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $this->actingAs($stateAdmin)->get(route('enterprise.inventory.locations', ['q' => 'Office']))->assertOk()
        ->assertJsonCount(2, 'locations')->assertJsonMissing(['id' => 903]);
    $this->actingAs($this->admin)->get(route('enterprise.inventory.index'))->assertOk()
        ->assertSee('Stock balances')->assertSee('Distribution requests')->assertSee('Stock movement audit');
});
