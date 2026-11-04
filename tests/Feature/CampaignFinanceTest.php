<?php

use App\Models\CampaignFinanceAccount;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Enterprise\CampaignFinanceService;
use App\Services\Enterprise\EntitlementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Finance test', 'system_country' => 'Nigeria']);
    $entitlements = $this->mock(EntitlementService::class);
    $entitlements->shouldReceive('active')->with('campaign_finance')->andReturn(true)->byDefault();
    $entitlements->shouldReceive('active')->with('sms_messaging')->andReturn(false)->byDefault();
    $entitlements->shouldReceive('cached')->andReturn(null)->byDefault();
    $country = \App\Models\Country::create(['name' => 'Nigeria', 'uuid' => (string) Str::uuid()]);
    $region = \App\Models\Region::create(['name' => 'Region', 'country_id' => $country->id]);
    \App\Models\State::create(['id' => 1, 'name' => 'First', 'region_id' => $region->id]);
    \App\Models\State::create(['id' => 2, 'name' => 'Second', 'region_id' => $region->id]);
    $this->finance = app(CampaignFinanceService::class);
    $this->requester = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => 1, 'status' => 'active']);
    $this->approver = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => 1, 'status' => 'active']);
    $this->outsider = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => 2, 'status' => 'active']);
    foreach ([$this->requester, $this->approver, $this->outsider] as $user) {
        foreach (['finance.view', 'finance.accounts.manage', 'finance.record', 'finance.budget.manage', 'finance.disbursement.create', 'finance.disbursement.approve', 'finance.disbursement.execute', 'finance.reports.export'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    }
    $this->account = $this->finance->createAccount($this->requester, 'State operations', $this->requester->id);
});

test('finance records income with an immutable audit and idempotent retry', function () {
    $key = (string) Str::uuid();
    $id = $this->finance->record($this->requester, $this->account->id, 'income', 100000, 'Opening donation', $key);
    expect($this->finance->record($this->requester, $this->account->id, 'income', 100000, 'Opening donation', $key))->toBe($id);
    expect($this->account->fresh()->balance_minor)->toBe(100000);
    expect(DB::table('campaign_finance_entries')->count())->toBe(1);
    expect(DB::table('campaign_finance_audits')->where('action', 'entry.recorded')->count())->toBe(1);
    expect(fn () => $this->finance->record($this->requester, $this->account->id, 'income', 200000, 'Opening donation', $key))->toThrow(ValidationException::class);
});

test('approved expenditure debits the account and budget exactly once', function () {
    $this->finance->record($this->requester, $this->account->id, 'income', 100000, 'Opening funds', (string) Str::uuid());
    $budget = $this->finance->createBudget($this->requester, $this->account->id, 'Transport', 50000);
    $request = $this->finance->request($this->requester, $this->account->id, $budget, 30000, 'Travel receipts');
    $this->finance->decide($this->approver, $request, true);
    $key = (string) Str::uuid();
    $this->finance->record($this->requester, $this->account->id, 'expenditure', 30000, 'Travel paid', $key, $request);
    $this->finance->record($this->requester, $this->account->id, 'expenditure', 30000, 'Travel paid', $key, $request);
    expect($this->account->fresh()->balance_minor)->toBe(70000);
    expect((int) DB::table('campaign_finance_budgets')->where('id', $budget)->value('utilized_minor'))->toBe(30000);
    expect(DB::table('campaign_finance_requests')->where('id', $request)->value('status'))->toBe('recorded');
    expect(fn () => $this->finance->record($this->requester, $this->account->id, 'expenditure', 30000, 'Duplicate', (string) Str::uuid(), $request))->toThrow(ValidationException::class);
});

test('requesters cannot approve their own requests and approvers cannot execute them', function () {
    $this->finance->record($this->requester, $this->account->id, 'income', 100000, 'Funds', (string) Str::uuid());
    $request = $this->finance->request($this->requester, $this->account->id, null, 10000, 'Supplies');
    try { $this->finance->decide($this->requester, $request, true); $this->fail('Self approval allowed.'); }
    catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { expect($e->getStatusCode())->toBe(403); }
    $this->finance->decide($this->approver, $request, true);
    try { $this->finance->record($this->approver, $this->account->id, 'expenditure', 10000, 'Supplies', (string) Str::uuid(), $request); $this->fail('Approver executed payment.'); }
    catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { expect($e->getStatusCode())->toBe(403); }
    expect($this->account->fresh()->balance_minor)->toBe(100000);
});

test('finance refuses over budget spending atomically', function () {
    $this->finance->record($this->requester, $this->account->id, 'income', 100000, 'Funds', (string) Str::uuid());
    $budget = $this->finance->createBudget($this->requester, $this->account->id, 'Budget', 10000);
    $request = $this->finance->request($this->requester, $this->account->id, $budget, 20000, 'Too large');
    $this->finance->decide($this->approver, $request, true);
    expect(fn () => $this->finance->record($this->requester, $this->account->id, 'expenditure', 20000, 'Too large', (string) Str::uuid(), $request))->toThrow(ValidationException::class);
    expect($this->account->fresh()->balance_minor)->toBe(100000);
    expect((int) DB::table('campaign_finance_budgets')->where('id', $budget)->value('utilized_minor'))->toBe(0);
    expect(DB::table('campaign_finance_requests')->where('id', $request)->value('status'))->toBe('approved');
});

test('pending and rejected requests cannot be recorded and balances cannot go negative', function () {
    $request = $this->finance->request($this->requester, $this->account->id, null, 10000, 'No funds');
    expect(fn () => $this->finance->record($this->requester, $this->account->id, 'expenditure', 10000, 'Pending', (string) Str::uuid(), $request))->toThrow(ValidationException::class);
    $this->finance->decide($this->approver, $request, true);
    expect(fn () => $this->finance->record($this->requester, $this->account->id, 'expenditure', 10000, 'No funds', (string) Str::uuid(), $request))->toThrow(ValidationException::class);
    expect($this->account->fresh()->balance_minor)->toBe(0);
    expect(DB::table('campaign_finance_entries')->count())->toBe(0);
    $rejected = $this->finance->request($this->requester, $this->account->id, null, 1000, 'Rejected request');
    $this->finance->decide($this->approver, $rejected, false);
    expect(fn () => $this->finance->record($this->requester, $this->account->id, 'expenditure', 1000, 'Rejected', (string) Str::uuid(), $rejected))->toThrow(ValidationException::class);
});

test('financial account data and writes remain geographically isolated', function () {
    expect($this->finance->accounts($this->outsider)->count())->toBe(0);
    expect(fn () => $this->finance->account($this->outsider, $this->account->id))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect(fn () => $this->finance->createAccount($this->outsider, 'Hidden custodian', $this->requester->id))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect(fn () => $this->finance->record($this->outsider, $this->account->id, 'income', 1000, 'Unauthorized', (string) Str::uuid()))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect(CampaignFinanceAccount::count())->toBe(1);
});

test('service boundary requires both licence and independent user permissions', function () {
    $user = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => 1, 'status' => 'active']);
    try { $this->finance->createAccount($user, 'Forbidden', $user->id); $this->fail('Missing permission accepted.'); }
    catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { expect($e->getStatusCode())->toBe(403); }
    $this->mock(EntitlementService::class)->shouldReceive('active')->with('campaign_finance')->andReturn(false);
    try { $this->finance->record($this->requester, $this->account->id, 'income', 1000, 'Unlicensed', (string) Str::uuid()); $this->fail('Missing entitlement accepted.'); }
    catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { expect($e->getStatusCode())->toBe(402); }
});

test('supporting documents remain private and are tied to scoped accounts', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $file = \Illuminate\Http\UploadedFile::fake()->image('receipt.png');
    $id = $this->finance->attachDocument($this->requester, $this->account->id, null, $file);
    $document = DB::table('campaign_finance_documents')->where('id', $id)->first();
    \Illuminate\Support\Facades\Storage::disk('local')->assertExists($document->path);
    expect($document->sha256)->toBe(hash_file('sha256', $file->getRealPath()));
    expect(fn () => $this->finance->attachDocument($this->outsider, $this->account->id, null, $file))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect(fn () => $this->finance->attachDocument($this->requester, $this->account->id, null, \Illuminate\Http\UploadedFile::fake()->create('unsafe.html', 1, 'text/html')))->toThrow(ValidationException::class);
});

test('financial dashboard and full CSV statement are permission and scope protected', function () {
    if (!app('router')->getRoutes()->getByName('finance.index')) { \Illuminate\Support\Facades\Route::middleware('web')->group(base_path('routes/enterprise-finance.php')); app('router')->getRoutes()->refreshNameLookups(); }
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $this->finance->record($this->requester, $this->account->id, 'income', 100, '=SUM(A1:A2)', (string) Str::uuid());
    $this->actingAs($this->requester)->get(route('finance.index', ['account' => $this->account->id]))->assertOk()->assertSee('State operations')->assertSee('Campaign Finance');
    $response = $this->get(route('finance.statement', $this->account->id))->assertOk();
    expect($response->streamedContent())->toContain("'=SUM(A1:A2)");
    $this->actingAs($this->outsider)->get(route('finance.statement', $this->account->id))->assertNotFound();
});

function financeTerritoryFixtures(): array
{
    $country = \App\Models\Country::first();
    $secondRegion = \App\Models\Region::create(['name' => 'Second region', 'country_id' => $country->id]);
    \App\Models\State::findOrFail(2)->update(['region_id' => $secondRegion->id]);
    $firstRegion = \App\Models\State::findOrFail(1)->region_id;
    $rows = [];
    foreach ([
        [1, $firstRegion, 1, 1, 1, 1, 1, 1],
        [2, $firstRegion, 1, 2, 2, 2, 2, 2],
        [3, $firstRegion, 1, 1, 1, 1, 3, 3],
        [4, $secondRegion->id, 2, 3, 3, 3, 4, 4],
        [5, $firstRegion, 1, 1, 4, 4, 5, 5],
    ] as [$n, $region, $state, $senate, $federal, $lga, $ward, $pu]) {
        \App\Models\SenatorialDistrict::firstOrCreate(['id' => $senate], ['name' => 'District '.$senate, 'state_id' => $state]);
        \App\Models\FederalConstituency::firstOrCreate(['id' => $federal], ['name' => 'Federal '.$federal, 'state_id' => $state, 'senatorial_district_id' => $senate]);
        \App\Models\LocalGovernmentArea::firstOrCreate(['id' => $lga], ['name' => 'LGA '.$lga, 'state_id' => $state, 'senatorial_district_id' => $senate, 'federal_constituency_id' => $federal]);
        \App\Models\Ward::firstOrCreate(['id' => $ward], ['name' => 'Ward '.$ward, 'lga_id' => $lga]);
        \App\Models\PollingUnit::firstOrCreate(['id' => $pu], ['name' => 'PU '.$pu, 'ward_id' => $ward, 'senatorial_district_id' => $senate, 'federal_constituency_id' => $federal]);
        $location = ['region_id' => $region, 'state_id' => $state, 'senatorial_district_id' => $senate, 'federal_constituency_id' => $federal,
            'lga_id' => $lga, 'ward_id' => $ward, 'polling_unit_id' => $pu];
        $owner = User::factory()->create($location + ['access_level' => 'user', 'status' => 'active']);
        $account = CampaignFinanceAccount::create($location + ['name' => 'Territory '.$n, 'owner_id' => $owner->id, 'created_by' => $owner->id, 'currency' => 'NGN']);
        $rows[$n] = ['account' => $account, 'owner' => $owner, 'location' => $location];
    }
    return $rows;
}

test('finance account visibility enforces every administrative geographic boundary', function (string $level, array $expected) {
    $rows = financeTerritoryFixtures();
    $actor = $this->requester;
    $actor->forceFill($rows[1]['location'] + ['access_level' => $level])->save();
    $allIds = array_map(fn ($row) => $row['account']->id, $rows);
    $visibleIds = $this->finance->accounts($actor)->whereIn('id', $allIds)->orderBy('id')->pluck('id')->all();
    expect($visibleIds)->toBe(array_map(fn ($n) => $rows[$n]['account']->id, $expected));
    foreach (array_diff(array_keys($rows), $expected) as $n) {
        expect(fn () => $this->finance->account($actor, $rows[$n]['account']->id))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    }
})->with([
    ['superadmin', [1, 2, 3, 4, 5]], ['nationaladmin', [1, 2, 3, 4, 5]], ['regionaladmin', [1, 2, 3, 5]],
    ['stateadmin', [1, 2, 3, 5]], ['senatorialadmin', [1, 3, 5]], ['federaladmin', [1, 3]],
    ['lgaadmin', [1, 3]], ['wardadmin', [1]], ['puadmin', [1]], ['user', [1]],
]);

test('finance account visibility intersects each licensed campaign scope even for Super Admin', function (string $package, string $type, array $attributes, array $expected) {
    $rows = financeTerritoryFixtures();
    config(['campaign.deployment_mode' => 'self_hosted']);
    $license = \App\Models\LocalLicense::create(['license_key' => 'FINANCE-'.Str::uuid(), 'deployment_mode' => 'self_hosted', 'package_type' => $package, 'status' => 'active']);
    $license->scope()->create($attributes + ['scope_type' => $type, 'scope_name' => 'Finance test scope']);
    $actor = $this->requester;
    $actor->update(['access_level' => 'superadmin']);
    $allIds = array_map(fn ($row) => $row['account']->id, $rows);
    $visibleIds = $this->finance->accounts($actor->fresh())->whereIn('id', $allIds)->orderBy('id')->pluck('id')->all();
    expect($visibleIds)->toBe(array_map(fn ($n) => $rows[$n]['account']->id, $expected));
})->with([
    ['presidential', 'national', [], [1, 2, 3, 4, 5]],
    ['governorship', 'state', ['state_id' => 1], [1, 2, 3, 5]],
    ['senatorial', 'senatorial_district', ['state_id' => 1, 'senatorial_district_id' => 1], [1, 3, 5]],
    ['federal_constituency', 'federal_constituency', ['state_id' => 1, 'senatorial_district_id' => 1, 'federal_constituency_id' => 1], [1, 3]],
    ['chairmanship', 'lga', ['state_id' => 1, 'senatorial_district_id' => 1, 'federal_constituency_id' => 1, 'lga_id' => 1], [1, 3]],
]);

test('finance account snapshot is not reassigned when its custodian changes geographic area', function () {
    $rows = financeTerritoryFixtures();
    $rows[1]['owner']->update($rows[4]['location']);
    $this->requester->forceFill($rows[1]['location'] + ['access_level' => 'wardadmin'])->save();
    expect($this->finance->accounts($this->requester)->whereKey($rows[1]['account']->id)->exists())->toBeTrue();
    $this->outsider->forceFill($rows[4]['location'] + ['access_level' => 'wardadmin'])->save();
    expect($this->finance->accounts($this->outsider)->whereKey($rows[1]['account']->id)->exists())->toBeFalse();
});

test('finance rejects inconsistent custodian geography before exposing any account', function () {
    $rows = financeTerritoryFixtures();
    $owner = $rows[1]['owner'];
    $owner->update(['state_id' => 2]);
    $before = CampaignFinanceAccount::count();
    $actor = $this->requester;
    $actor->update(['access_level' => 'superadmin']);
    expect(fn () => $this->finance->createAccount($actor, 'Contradictory account', $owner->id))->toThrow(ValidationException::class);
    expect(CampaignFinanceAccount::count())->toBe($before);
});

test('finance derives missing geographic parents when recording account jurisdiction', function () {
    $rows = financeTerritoryFixtures();
    $owner = $rows[1]['owner'];
    $owner->update(['region_id' => null, 'state_id' => null, 'lga_id' => null, 'ward_id' => null, 'senatorial_district_id' => null, 'federal_constituency_id' => null]);
    $actor = $this->requester;
    $actor->update(['access_level' => 'superadmin']);
    $account = $this->finance->createAccount($actor, 'Derived jurisdiction', $owner->id);
    foreach ($rows[1]['location'] as $field => $value) { expect((int)$account->{$field})->toBe((int)$value); }
});

test('finance HTTP routes enforce decision permission and retain independent decision audit', function () {
    if (!app('router')->getRoutes()->getByName('finance.index')) { \Illuminate\Support\Facades\Route::middleware('web')->group(base_path('routes/enterprise-finance.php')); app('router')->getRoutes()->refreshNameLookups(); }
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $request = $this->finance->request($this->requester, $this->account->id, null, 1000, 'Decision test');
    $viewer = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => 1, 'status' => 'active']);
    $viewer->givePermissionTo(Permission::findOrCreate('finance.view', 'web'));
    $this->actingAs($viewer)->post(route('finance.requests.decide', $request), ['decision' => 'approve'])->assertForbidden();
    $this->actingAs($this->requester)->post(route('finance.requests.decide', $request), ['decision' => 'approve'])->assertForbidden();
    $this->actingAs($this->outsider)->post(route('finance.requests.decide', $request), ['decision' => 'approve'])->assertNotFound();
    $this->actingAs($this->approver)->post(route('finance.requests.decide', $request), ['decision' => 'approve'])->assertRedirect();
    $event = DB::table('campaign_finance_audits')->where('action', 'request.approved')->first();
    expect((int)$event->actor_id)->toBe($this->approver->id);
    expect(json_decode($event->details, true)['request_id'])->toBe($request);
    expect((int)DB::table('campaign_finance_requests')->where('id', $request)->value('decided_by'))->toBe($this->approver->id);
    expect(DB::table('campaign_finance_audits')->where('action', 'request.created')->count())->toBe(1);
});

test('finance provides older transaction history and current month summaries', function () {
    if (!app('router')->getRoutes()->getByName('finance.index')) { \Illuminate\Support\Facades\Route::middleware('web')->group(base_path('routes/enterprise-finance.php')); app('router')->getRoutes()->refreshNameLookups(); }
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $old = $this->finance->record($this->requester, $this->account->id, 'income', 100, 'Prior month income', (string) Str::uuid());
    DB::table('campaign_finance_entries')->where('id', $old)->update(['created_at' => now()->subMonthNoOverflow()->startOfMonth()]);
    for ($n = 1; $n <= 30; $n++) { $this->finance->record($this->requester, $this->account->id, 'income', 100, 'Current month entry '.$n, (string) Str::uuid()); }
    $response = $this->actingAs($this->requester)->get(route('finance.index', ['account' => $this->account->id, 'entries_page' => 2]))->assertOk()->assertSee('Prior month income');
    $response->assertViewHas('monthlyTotals', fn ($totals) => (int)$totals['income'] === 3000);
    $response->assertViewHas('entries', fn ($entries) => $entries->total() === 31 && $entries->currentPage() === 2);
    $response->assertViewHas('audits', fn ($audits) => $audits->total() === 32);
});

test('finance document downloads enforce scope and integrity before returning attachments', function () {
    if (!app('router')->getRoutes()->getByName('finance.index')) { \Illuminate\Support\Facades\Route::middleware('web')->group(base_path('routes/enterprise-finance.php')); app('router')->getRoutes()->refreshNameLookups(); }
    \Illuminate\Support\Facades\Storage::fake('local');
    $id = $this->finance->attachDocument($this->requester, $this->account->id, null, \Illuminate\Http\UploadedFile::fake()->image('receipt.png'));
    $document = DB::table('campaign_finance_documents')->where('id', $id)->first();
    $this->actingAs($this->requester)->get(route('finance.documents.download', $id))->assertOk()->assertHeader('Content-Type', 'application/octet-stream');
    $this->actingAs($this->outsider)->get(route('finance.documents.download', $id))->assertNotFound();
    \Illuminate\Support\Facades\Storage::disk('local')->put($document->path, 'tampered');
    $this->actingAs($this->requester)->get(route('finance.documents.download', $id))->assertNotFound();
});



test('finance custodian search exposes names only within the authorized jurisdiction', function () {
    $this->withoutMiddleware(\App\Http\Middleware\CheckUserOnboarding::class);
    $this->actingAs($this->requester)->get(route('finance.index'))
        ->assertOk()->assertSee($this->approver->username)->assertDontSee($this->outsider->username);
    $this->get(route('finance.index', ['custodian_search' => $this->outsider->username]))
        ->assertOk()->assertDontSee('<option value="'.$this->outsider->id.'">', false);
});
