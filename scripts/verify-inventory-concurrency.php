<?php

// Real multiprocess row-lock rehearsal; synthetic database only, no external services.
use App\Models\{Country, Region, State, LocalGovernmentArea, Ward, PollingUnit, SystemSetting, User};
use App\Services\Enterprise\{CampaignInventoryService, EntitlementService};
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\{Artisan, DB, File};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || DB::connection()->getDriverName() !== 'mysql'
    || ! preg_match('/^campaign_inventory_concurrency_[a-z0-9_]+$/D', DB::connection()->getDatabaseName())) {
    throw new RuntimeException('Use APP_ENV=testing and an isolated MySQL campaign_inventory_concurrency_* database.');
}
// A test-only entitlement fixture; production trust and external APIs are never involved.
$app->instance(EntitlementService::class, new class extends EntitlementService {
    public function __construct() {}
    public function active(string $feature, bool $requireOnline = false): bool { return $feature === 'campaign_merchandise'; }
});
$inventory = $app->make(CampaignInventoryService::class);

if (($argv[1] ?? '') === 'worker') {
    $job = json_decode(file_get_contents($argv[2]), true, flags: JSON_THROW_ON_ERROR);
    touch($argv[2].'.ready');
    $deadline = microtime(true) + 15;
    while (! is_file($job['barrier'])) {
        if (microtime(true) > $deadline) { throw new RuntimeException('Concurrency barrier timed out.'); }
        usleep(10000);
    }
    try {
        $actor = User::findOrFail($job['actor']);
        if ($job['method'] === 'adjust') { $inventory->adjust($actor, $job['data']); }
        elseif ($job['method'] === 'cancel') { $inventory->cancel($actor, $job['data']['request'], 'Synthetic cancellation race'); }
        else { $inventory->fulfil($actor, $job['data']['request']); }
        echo json_encode(['result' => 'accepted'], JSON_THROW_ON_ERROR);
    } catch (ValidationException) {
        echo json_encode(['result' => 'insufficient'], JSON_THROW_ON_ERROR);
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
        if ($exception->getStatusCode() !== 409) { throw $exception; }
        echo json_encode(['result' => 'conflict'], JSON_THROW_ON_ERROR);
    }
    exit(0);
}

Artisan::call('migrate', ['--force' => true]);
if (DB::table('local_licenses')->exists()) { throw new RuntimeException('Concurrency fixture database must not contain licences.'); }
SystemSetting::firstOrCreate([], ['package' => 'presidential', 'system_name' => 'Inventory concurrency fixture', 'system_country' => 'Nigeria']);
$actor = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
$country = Country::create(['uuid' => (string) Str::uuid(), 'name' => 'Synthetic country']);
$region = Region::create(['name' => 'Synthetic region', 'country_id' => $country->id]);
$state = State::create(['name' => 'Synthetic state', 'region_id' => $region->id]);
$lga = LocalGovernmentArea::create(['name' => 'Synthetic LGA', 'state_id' => $state->id]);
$ward = Ward::create(['name' => 'Synthetic ward', 'lga_id' => $lga->id]);
$source = PollingUnit::create(['name' => 'Synthetic source', 'ward_id' => $ward->id])->id;
$destination = PollingUnit::create(['name' => 'Synthetic destination', 'ward_id' => $ward->id])->id;
$suffix = (string) Str::uuid();
$category = $inventory->category($actor, ['name' => 'Concurrency '.$suffix]);
$material = $inventory->material($actor, ['category_id' => $category, 'name' => 'Synthetic posters', 'sku' => $suffix, 'unit' => 'pieces', 'low_stock_level' => 5]);
$adjustment = ['material_id' => $material, 'polling_unit_id' => $source, 'quantity' => 100, 'reference' => (string) Str::uuid(), 'reason' => 'Synthetic opening receipt'];
$inventory->adjust($actor, $adjustment);
$directory = storage_path('framework/testing/inventory-concurrency-'.$suffix);
File::ensureDirectoryExists($directory, 0700);

function inventoryRace(array $jobs, string $directory, int $actor): array
{
    $round = $directory.'/'.Str::uuid(); File::ensureDirectoryExists($round, 0700);
    $barrier = $round.'/go'; $processes = [];
    foreach ($jobs as $i => $job) {
        $file = $round.'/'.$i.'.json';
        file_put_contents($file, json_encode($job + ['barrier' => $barrier, 'actor' => $actor], JSON_THROW_ON_ERROR)); chmod($file, 0600);
        $process = proc_open([PHP_BINARY, __FILE__, 'worker', $file], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
        if (! is_resource($process)) { throw new RuntimeException('Could not start concurrent worker.'); }
        fclose($pipes[0]); $processes[] = [$process, $pipes, $file];
    }
    $deadline = microtime(true) + 15;
    while (collect($processes)->contains(fn ($process) => ! is_file($process[2].'.ready'))) {
        if (microtime(true) > $deadline) {
            foreach ($processes as [$process]) { proc_terminate($process); }
            throw new RuntimeException('Workers did not reach concurrency barrier.');
        }
        usleep(10000);
    }
    touch($barrier); $results = [];
    foreach ($processes as [$process, $pipes]) {
        $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]); $exit = proc_close($process);
        if ($exit !== 0) { throw new RuntimeException('Synthetic worker failed: '.$stderr.' '.$stdout); }
        $results[] = json_decode($stdout, true, flags: JSON_THROW_ON_ERROR)['result'];
    }
    sort($results); return $results;
}
function inventoryAssert(bool $condition, string $message): void { if (! $condition) { throw new RuntimeException($message); } }
function inventoryBalance(int $material, int $unit): int { return (int) DB::table('campaign_material_stocks')->where('material_id', $material)->where('polling_unit_id', $unit)->value('quantity'); }

try {
    $withdrawals = [];
    for ($i = 0; $i < 2; $i++) { $withdrawals[] = ['method' => 'adjust', 'data' => array_replace($adjustment, ['quantity' => -75, 'reference' => (string) Str::uuid(), 'reason' => 'Concurrent synthetic withdrawal'])]; }
    inventoryAssert(inventoryRace($withdrawals, $directory, $actor->id) === ['accepted', 'insufficient'], 'Concurrent withdrawals did not serialize.');
    inventoryAssert(inventoryBalance($material, $source) === 25, 'Concurrent withdrawals overspent stock.');
    $retry = ['method' => 'adjust', 'data' => array_replace($adjustment, ['quantity' => -10, 'reference' => (string) Str::uuid(), 'reason' => 'Duplicate browser retry'])];
    inventoryAssert(inventoryRace([$retry, $retry], $directory, $actor->id) === ['accepted', 'accepted'], 'Identical retries did not complete.');
    inventoryAssert(inventoryBalance($material, $source) === 15, 'Concurrent retry changed stock twice.');
    $request = $inventory->request($actor, ['material_id' => $material, 'source_polling_unit_id' => $source, 'destination_polling_unit_id' => $destination, 'quantity' => 10, 'purpose' => 'Synthetic distribution']);
    $fulfil = ['method' => 'fulfil', 'data' => ['request' => $request]];
    inventoryAssert(inventoryRace([$fulfil, $fulfil], $directory, $actor->id) === ['accepted', 'accepted'], 'Fulfilment retries failed.');
    inventoryAssert(inventoryBalance($material, $source) === 5 && inventoryBalance($material, $destination) === 10, 'Concurrent fulfilment did not preserve balances.');
    $forward = $inventory->request($actor, ['material_id' => $material, 'source_polling_unit_id' => $source, 'destination_polling_unit_id' => $destination, 'quantity' => 3, 'purpose' => 'Synthetic forward']);
    $reverse = $inventory->request($actor, ['material_id' => $material, 'source_polling_unit_id' => $destination, 'destination_polling_unit_id' => $source, 'quantity' => 7, 'purpose' => 'Synthetic reverse']);
    inventoryAssert(inventoryRace([['method' => 'fulfil', 'data' => ['request' => $forward]], ['method' => 'fulfil', 'data' => ['request' => $reverse]]], $directory, $actor->id) === ['accepted', 'accepted'], 'Opposite transfers did not complete.');
    inventoryAssert(inventoryBalance($material, $source) === 9 && inventoryBalance($material, $destination) === 6, 'Opposite transfers did not conserve stock.');
    $movements = DB::table('campaign_material_movements')->join('campaign_material_stocks', 'campaign_material_stocks.id', '=', 'campaign_material_movements.stock_id')->where('material_id', $material);
    inventoryAssert($movements->count() === 9, 'Audit history has missing or duplicate movements.');
    inventoryAssert((int) $movements->sum('change') === 15, 'Audit ledger does not reconcile with stock.');
    inventoryAssert(DB::table('campaign_material_request_events')->where('request_id', $request)->count() === 2, 'Fulfilment retry duplicated request audit.');
    $racingRequest = $inventory->request($actor, ['material_id' => $material, 'source_polling_unit_id' => $source, 'destination_polling_unit_id' => $destination, 'quantity' => 2, 'purpose' => 'Synthetic cancellation race']);
    inventoryAssert(inventoryRace([['method' => 'fulfil', 'data' => ['request' => $racingRequest]], ['method' => 'cancel', 'data' => ['request' => $racingRequest]]], $directory, $actor->id) === ['accepted', 'conflict'], 'Cancellation and fulfilment did not serialize.');
    $status = DB::table('campaign_material_requests')->where('id', $racingRequest)->value('status');
    inventoryAssert(in_array($status, ['fulfilled', 'cancelled'], true), 'Racing request did not reach a valid terminal state.');
    inventoryAssert(inventoryBalance($material, $source) === ($status === 'fulfilled' ? 7 : 9)
        && inventoryBalance($material, $destination) === ($status === 'fulfilled' ? 8 : 6), 'Cancellation race corrupted stock.');
    inventoryAssert(DB::table('campaign_material_request_events')->where('request_id', $racingRequest)->count() === 2, 'Cancellation race duplicated terminal audit.');
    echo json_encode(['passed' => true, 'driver' => DB::connection()->getDriverName(), 'server' => DB::selectOne('SELECT VERSION() AS version')->version,
        'multiprocess_workers' => 10, 'concurrent_withdrawal' => true, 'concurrent_adjustment_retry' => true,
        'concurrent_fulfilment_retry' => true, 'opposite_transfers' => true, 'audit_reconciled' => true, 'concurrent_cancel_fulfil' => true], JSON_THROW_ON_ERROR).PHP_EOL;
} finally { File::deleteDirectory($directory); }
