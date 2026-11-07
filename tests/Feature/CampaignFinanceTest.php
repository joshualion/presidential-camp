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
    // Explicit test policy: previous role grants do not bypass finance governance.
    DB::table('campaign_finance_governance')->insert([
        'previous_id' => 0, 'actor_id' => $this->requester->id, 'mode' => 'delegated',
        'grants' => json_encode(['level:stateadmin' => array_values(array_filter(
            config('enterprise.permissions'), fn ($name) => str_starts_with($name, 'finance.')))], JSON_THROW_ON_ERROR),
        'created_at' => now(),
    ]);
    foreach ([$this->requester, $this->approver, $this->outsider] as $user) {
        foreach (['finance.dashboard.view', 'finance.requests.view'] as $permission) {
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
    $response = $this->actingAs($this->requester)->get(route('finance.section', ['section' => 'reports', 'account' => $this->account->id, 'entries_page' => 2]))->assertOk()->assertSee('Prior month income');
    $response->assertViewHas('entries', fn ($entries) => $entries->total() === 31 && $entries->currentPage() === 2);
    $this->get(route('finance.index', ['from' => now()->startOfMonth()->toDateString()]))
        ->assertViewHas('totals', fn ($totals) => (int) $totals['income'] === 3000);
});

test('finance document downloads enforce scope and integrity before returning attachments', function () {
    if (!app('router')->getRoutes()->getByName('finance.index')) { \Illuminate\Support\Facades\Route::middleware('web')->group(base_path('routes/enterprise-finance.php')); app('router')->getRoutes()->refreshNameLookups(); }
    \Illuminate\Support\Facades\Storage::fake('local');
    $id = $this->finance->attachDocument($this->requester, $this->account->id, null, \Illuminate\Http\UploadedFile::fake()->image('receipt.png'));
    $document = DB::table('campaign_finance_documents')->where('id', $id)->first();
    $this->actingAs($this->requester)->get(route('finance.documents.download', $id))->assertForbidden();
    foreach ([$this->requester, $this->outsider] as $reader) {
        $reader->givePermissionTo(Permission::findOrCreate('finance.documents.view', 'web'));
        $reader->unsetRelation('permissions');
    }
    $this->actingAs($this->requester)->get(route('finance.documents.download', $id))->assertOk()->assertHeader('Content-Type', 'application/octet-stream');
    $this->actingAs($this->outsider)->get(route('finance.documents.download', $id))->assertNotFound();
    \Illuminate\Support\Facades\Storage::disk('local')->put($document->path, 'tampered');
    $this->actingAs($this->requester)->get(route('finance.documents.download', $id))->assertNotFound();
});



test('finance custodian search exposes names only within the authorized jurisdiction', function () {
    $this->withoutMiddleware(\App\Http\Middleware\CheckUserOnboarding::class);
    $this->actingAs($this->requester)->get(route('finance.section', ['section' => 'accounts']))
        ->assertOk()->assertSee($this->approver->username)->assertDontSee($this->outsider->username);
    $this->get(route('finance.section', ['section' => 'accounts', 'custodian_search' => $this->outsider->username]))
        ->assertOk()->assertDontSee('<option value="'.$this->outsider->id.'">', false);
});

test('historical entries reconcile once and new postings preserve paired minor-unit amounts', function () {
    $this->requester->givePermissionTo(Permission::findOrCreate('finance.reconcile', 'web'));
    $historical = DB::table('campaign_finance_entries')->insertGetId([
        'account_id' => $this->account->id, 'idempotency_key' => (string) Str::uuid(), 'kind' => 'income',
        'amount_minor' => 1100, 'balance_after_minor' => 1100, 'description' => 'v1.2.5 receipt',
        'recorded_by' => $this->requester->id, 'created_at' => now()->subDay(),
    ]);
    $this->account->update(['balance_minor' => 1100]);

    $this->finance->reconcileHistory($this->requester, $this->account->id);
    $this->finance->reconcileHistory($this->requester, $this->account->id);
    $new = $this->finance->record($this->requester, $this->account->id, 'income', 100, 'New receipt', (string) Str::uuid());

    expect(DB::table('campaign_finance_journal')->count())->toBe(2);
    $this->assertDatabaseHas('campaign_finance_journal', ['entry_id' => $historical, 'debit_code' => '1000', 'credit_code' => '4000', 'amount_minor' => 1100]);
    $this->assertDatabaseHas('campaign_finance_journal', ['entry_id' => $new, 'amount_minor' => 100]);
    $this->assertDatabaseHas('campaign_finance_entries', ['id' => $historical, 'balance_after_minor' => 1100, 'description' => 'v1.2.5 receipt']);
    $this->assertDatabaseHas('campaign_finance_ledger_checks', ['account_id' => $this->account->id, 'historical_entries' => 1, 'historical_balance_minor' => 1100]);
});

test('a broken historical balance blocks posting without changing legacy records', function () {
    $this->account->update(['balance_minor' => 1000]);
    expect(fn () => $this->finance->record($this->requester, $this->account->id, 'income', 100, 'New receipt', (string) Str::uuid()))
        ->toThrow(ValidationException::class);
    expect($this->account->fresh()->balance_minor)->toBe(1000);
    expect(DB::table('campaign_finance_journal')->count())->toBe(0);
    expect(DB::table('campaign_finance_ledger_checks')->count())->toBe(0);
    expect(DB::table('campaign_finance_entries')->count())->toBe(0);
});

test('a snapshotted approval chain requires distinct scoped actors and reserves funds after the final approval', function () {
    $secondApprover = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => 1, 'status' => 'active']);
    $secondApprover->givePermissionTo(Permission::findOrCreate('finance.disbursement.approve', 'web'));
    DB::table('campaign_finance_policies')->insert([
        'previous_id' => 0, 'actor_id' => $secondApprover->id, 'menus' => '{}',
        'approval_rules' => json_encode([['minimum_minor' => 0, 'stages' => ['level:stateadmin', 'level:stateadmin']]]),
        'created_at' => now(),
    ]);

    $account = $this->account->id;
    $this->finance->record($this->requester, $account, 'income', 10000, 'Recorded income', (string) Str::uuid());
    $budget = $this->finance->createBudget($this->requester, $account, 'Transport', 10000);
    $request = $this->finance->request($this->requester, $account, $budget, 8000, 'Travel');
    $this->finance->decide($this->approver, $request, true);
    expect(DB::table('campaign_finance_requests')->where('id', $request)->value('status'))->toBe('pending');
    expect(fn () => $this->finance->decide($this->approver, $request, true))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect(fn () => $this->finance->decide($this->outsider, $request, true))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    $this->finance->decide($secondApprover, $request, true);
    expect((int) $this->finance->reservations()->where('account_id', $account)->sum('amount_minor'))->toBe(8000);
    expect(fn () => $this->finance->record($this->approver, $account, 'expenditure', 8000, 'Travel paid', (string) Str::uuid(), $request))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $next = $this->finance->request($this->requester, $account, $budget, 3000, 'Other travel');
    $this->finance->decide($this->approver, $next, true);
    expect(fn () => $this->finance->decide($secondApprover, $next, true))->toThrow(ValidationException::class);
    expect(DB::table('campaign_finance_decisions')->where('request_id', $next)->count())->toBe(1);
    $this->finance->cancel($this->requester, $request);
    $this->finance->decide($secondApprover, $next, true);
    $entry = $this->finance->record($this->requester, $account, 'expenditure', 3000, 'Other travel paid', (string) Str::uuid(), $next);
    expect((int) $this->finance->reservations()->where('account_id', $account)->sum('amount_minor'))->toBe(0);
    $this->assertDatabaseHas('campaign_finance_journal', ['entry_id' => $entry, 'debit_code' => '5000', 'credit_code' => '1000', 'amount_minor' => 3000]);
});

test('only a licensed super admin changes menu policies and menu visibility grants no writes', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $super = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $payload = [
        'version' => 0, 'subject' => 'level:stateadmin', 'menus' => ['dashboard'],
        'rules' => [['minimum_minor' => 0, 'stages' => ['level:stateadmin', '']]],
    ];

    $this->actingAs($this->requester)->post(route('finance.settings.save'), $payload)->assertForbidden();
    $this->actingAs($super)->get(route('finance.settings'))->assertOk();
    $this->post(route('finance.settings.save'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('finance.settings.save'), $payload)->assertSessionHasErrors('version');
    $viewer = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => 1, 'status' => 'active']);
    // Dashboard visibility is intentionally independent from finance.view.
    $viewer->givePermissionTo(
        Permission::findOrCreate('finance.view', 'web'),
        Permission::findOrCreate('finance.dashboard.view', 'web')
    );
    $this->actingAs($viewer)->get(route('finance.index'))->assertOk();
    $this->get(route('finance.section', ['section' => 'income']))->assertNotFound();
    $this->post(route('finance.entries.store'), [
        'account_id' => $this->account->id, 'kind' => 'income', 'amount' => '5.00',
        'description' => 'Unauthorized', 'idempotency_key' => (string) Str::uuid(),
    ])->assertForbidden();
});

test('a finance appointment is independent of the ordinary role, scoped, and revocable', function () {
    $this->withoutMiddleware(\App\Http\Middleware\CheckUserOnboarding::class);
    $super = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $appointment = [
        'user_id' => $this->approver->id, 'account_id' => $this->account->id,
        'office' => 'treasurer', 'boundary_column' => 'state_id', 'expires_at' => now()->addDay()->toDateString(),
    ];
    $this->actingAs($this->requester)->post(route('finance.appointments.store'), $appointment)->assertForbidden();
    $this->actingAs($super)->post(route('finance.appointments.store'), array_replace($appointment, ['user_id' => $this->outsider->id]))->assertNotFound();
    $this->post(route('finance.appointments.store'), $appointment)->assertRedirect()->assertSessionHasNoErrors();
    $id = DB::table('campaign_finance_appointments')->value('id');

    $this->post(route('finance.settings.save'), [
        'version' => 0, 'rules' => [['minimum_minor' => 0, 'stages' => ['office:treasurer']]],
    ])->assertRedirect()->assertSessionHasNoErrors();
    $account = $this->account->id;
    $this->finance->record($this->requester, $account, 'income', 5000, 'Recorded income', (string) Str::uuid());
    $first = $this->finance->request($this->requester, $account, null, 1000, 'First request');
    $this->finance->decide($this->approver, $first, true);

    $this->post(route('finance.appointments.revoke', $id))->assertRedirect();
    $second = $this->finance->request($this->requester, $account, null, 1000, 'Second request');
    expect(fn () => $this->finance->decide($this->approver, $second, true))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $this->assertDatabaseHas('campaign_finance_appointments', ['id' => $id, 'revoked_by' => $super->id]);
});

test('finance navigation responds to the signed finance claim and its expiry', function () {
    $this->withoutMiddleware(\App\Http\Middleware\CheckUserOnboarding::class);
    $entitlements = app(EntitlementService::class);
    $entitlements->shouldReceive('cached')->andReturn([
        'features' => ['campaign_finance' => ['active' => true, 'starts_at' => time() - 60, 'expires_at' => time() + 60]],
    ])->byDefault();
    $this->actingAs($this->requester)->get(route('finance.index'))->assertOk()
        ->assertSee(route('finance.section', ['section' => 'income']))
        ->assertDontSee(route('finance.settings'));

    $entitlements->shouldReceive('cached')->andReturn([
        'features' => ['campaign_finance' => ['active' => true, 'starts_at' => time() - 60, 'expires_at' => time() - 1]],
    ])->byDefault();
    $entitlements->shouldReceive('active')->with('campaign_finance')->andReturn(false);
    $this->get(route('finance.section', ['section' => 'income']))->assertStatus(402);
});

test('finance migrations can re-enter after their tables already exist', function () {
    $this->finance->record($this->requester, $this->account->id, 'income', 100, 'Existing receipt', (string) Str::uuid());
    foreach (['2026_11_05_000010_add_campaign_finance_journal.php', '2026_11_05_000011_add_campaign_finance_policies.php', '2026_11_05_000012_add_campaign_finance_reversals.php'] as $file) {
        $migration = require database_path('migrations/'.$file);
        $migration->up();
    }
    expect(DB::table('campaign_finance_entries')->count())->toBe(1);
    expect(DB::table('campaign_finance_journal')->count())->toBe(1);
    expect($this->account->fresh()->balance_minor)->toBe(100);
});

test('a reversal preserves its original and posts an equal amount with the journal codes swapped', function () {
    $this->withoutMiddleware(\App\Http\Middleware\CheckUserOnboarding::class);
    $permission = Permission::findOrCreate('finance.entries.reverse', 'web');
    $this->approver->givePermissionTo($permission);
    $this->requester->givePermissionTo($permission);
    $account = $this->account->id;
    $original = $this->finance->record($this->requester, $account, 'income', 10000, 'Duplicate receipt', (string) Str::uuid());
    $key = (string) Str::uuid();
    expect(fn () => $this->finance->reverse($this->requester, $account, $original, 'Correction', $key))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $id = $this->finance->reverse($this->approver, $account, $original, 'Correction', $key);
    expect($this->finance->reverse($this->approver, $account, $original, 'Correction', $key))->toBe($id);
    expect(fn () => $this->finance->reverse($this->approver, $account, $original, 'Again', (string) Str::uuid()))
        ->toThrow(ValidationException::class);
    expect($this->account->fresh()->balance_minor)->toBe(0);
    $this->assertDatabaseHas('campaign_finance_entries', ['id' => $original, 'kind' => 'income', 'amount_minor' => 10000]);
    $this->assertDatabaseHas('campaign_finance_journal', ['entry_id' => $id, 'debit_code' => '4000', 'credit_code' => '1000', 'amount_minor' => 10000]);
    $this->assertDatabaseHas('campaign_finance_reversals', ['entry_id' => $original, 'reversal_id' => $id]);
    $this->actingAs($this->approver)->get(route('finance.index', ['from' => now()->toDateString()]))
        ->assertOk()->assertViewHas('totals', fn ($totals) => (int) $totals['income'] === 0);
    $this->get(route('finance.section', ['section' => 'reports', 'account' => $account]))
        ->assertOk()->assertSee('Reverses entry '.$original);
});

test('an expense reversal restores the recorded balance and budget but cannot reopen its request', function () {
    $this->approver->givePermissionTo(Permission::findOrCreate('finance.entries.reverse', 'web'));
    $account = $this->account->id;
    $this->finance->record($this->requester, $account, 'income', 10000, 'Receipt', (string) Str::uuid());
    $budget = $this->finance->createBudget($this->requester, $account, 'Transport', 5000);
    $request = $this->finance->request($this->requester, $account, $budget, 3000, 'Trip');
    $this->finance->decide($this->approver, $request, true);
    $expense = $this->finance->record($this->requester, $account, 'expenditure', 3000, 'Trip expense', (string) Str::uuid(), $request);
    $this->finance->reverse($this->approver, $account, $expense, 'Correction', (string) Str::uuid());
    expect($this->account->fresh()->balance_minor)->toBe(10000);
    $this->assertDatabaseHas('campaign_finance_budgets', ['id' => $budget, 'utilized_minor' => 0]);
    $this->assertDatabaseHas('campaign_finance_requests', ['id' => $request, 'status' => 'recorded']);
});

test('an income reversal cannot consume funds reserved for an approved request', function () {
    $this->approver->givePermissionTo(Permission::findOrCreate('finance.entries.reverse', 'web'));
    DB::table('campaign_finance_policies')->insert([
        'previous_id' => 0, 'actor_id' => $this->approver->id, 'menus' => '{}',
        'approval_rules' => json_encode([['minimum_minor' => 0, 'stages' => ['level:stateadmin']]]), 'created_at' => now(),
    ]);
    $account = $this->account->id;
    $income = $this->finance->record($this->requester, $account, 'income', 10000, 'Receipt', (string) Str::uuid());
    $request = $this->finance->request($this->requester, $account, null, 1000, 'Trip');
    $this->finance->decide($this->approver, $request, true);
    expect(fn () => $this->finance->reverse($this->approver, $account, $income, 'Correction', (string) Str::uuid()))
        ->toThrow(ValidationException::class);
    expect($this->account->fresh()->balance_minor)->toBe(10000);
    expect(DB::table('campaign_finance_reversals')->count())->toBe(0);
});

test('the Finance entry point opens an authorized section when the dashboard is hidden', function () {
    $this->withoutMiddleware(\App\Http\Middleware\CheckUserOnboarding::class);
    DB::table('campaign_finance_policies')->insert([
        'previous_id' => 0, 'actor_id' => $this->requester->id,
        'menus' => json_encode(['level:stateadmin' => ['requests']]), 'approval_rules' => '[]', 'created_at' => now(),
    ]);
    $this->actingAs($this->requester)->get(route('finance.index'))
        ->assertRedirect(route('finance.section', ['section' => 'requests']));
    $this->get(route('finance.section', ['section' => 'dashboard']))->assertNotFound();
    $this->get(route('finance.section', ['section' => 'requests']))->assertOk();
});

test('manual expenses use a separate permission and preserve evidence and budget totals on retry', function () {
    $this->finance->record($this->requester, $this->account->id, 'income', 100000, 'Opening funds', (string) Str::uuid());
    $budget = $this->finance->createBudget($this->requester, $this->account->id, 'Transport', 50000);
    $key = (string) Str::uuid();
    $receipt = ['payee' => 'Example Transport', 'paid_on' => today()->toDateString(),
        'reference' => 'RECEIPT-1', 'evidence_reference' => 'Paper receipt 1'];
    $record = fn ($actor, $details, $token) => $this->finance->recordManualExpense(
        $actor, $this->account->id, 12000, 'Transport paid externally', $token, $details, $budget
    );

    expect(fn () => $record($this->requester, $receipt, $key))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    foreach ([$this->requester, $this->outsider] as $user) {
        $user->givePermissionTo(Permission::findOrCreate('finance.expenses.record', 'web'));
    }
    expect(fn () => $record($this->outsider, $receipt, $key))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    $id = $record($this->requester, $receipt, $key);
    expect($record($this->requester, $receipt, $key))->toBe($id);
    expect(fn () => $record($this->requester, ['reference' => 'receipt-1'] + $receipt, (string) Str::uuid()))->toThrow(ValidationException::class);
    expect($this->account->fresh()->balance_minor)->toBe(88000);
    $this->assertDatabaseHas('campaign_finance_manual_expenses', $receipt + ['entry_id' => $id]);
    $this->assertDatabaseHas('campaign_finance_entries', ['id' => $id, 'request_id' => null, 'recorded_by' => $this->requester->id]);
    $this->assertDatabaseHas('campaign_finance_budgets', ['id' => $budget, 'utilized_minor' => 12000]);
    expect(DB::table('campaign_finance_audits')->where('action', 'expense.recorded_manually')->count())->toBe(1);
});

test('an approved internal allocation shifts both balances once without booking an expense', function () {
    $this->requester->givePermissionTo(Permission::findOrCreate('finance.movements.post', 'web'));
    $destination = $this->finance->createAccount($this->requester, 'Local operations', $this->requester->id);
    $this->finance->record($this->requester, $this->account->id, 'income', 100000, 'Opening funds', (string) Str::uuid());
    $id = $this->finance->request($this->requester, $this->account->id, null, 25000, 'Local allocation', 'transfer', $destination->id);
    $this->finance->decide($this->approver, $id, true);
    expect(fn () => $this->finance->record($this->requester, $this->account->id, 'expenditure', 25000, 'Incorrect route', (string) Str::uuid(), $id))
        ->toThrow(ValidationException::class);
    $key = (string) Str::uuid();
    $entry = $this->finance->postApprovedMovement($this->requester, $id, $key, 'Allocation 1');
    expect($this->finance->postApprovedMovement($this->requester, $id, $key, 'Allocation 1'))->toBe($entry);
    expect($this->account->fresh()->balance_minor)->toBe(75000);
    expect($destination->fresh()->balance_minor)->toBe(25000);
    expect(DB::table('campaign_finance_entries')->where('kind', 'expenditure')->count())->toBe(0);
    $this->assertDatabaseHas('campaign_finance_entries', ['id' => $entry, 'kind' => 'transfer_out', 'request_id' => $id]);
    $this->assertDatabaseHas('campaign_finance_entries', ['account_id' => $destination->id, 'kind' => 'transfer_in']);
});

test('a paid advance is settled once with documented spending and returned funds', function () {
    $this->requester->givePermissionTo(Permission::findOrCreate('finance.movements.post', 'web'));
    $this->approver->givePermissionTo(Permission::findOrCreate('finance.advances.settle', 'web'));
    $this->finance->record($this->requester, $this->account->id, 'income', 100000, 'Opening funds', (string) Str::uuid());
    $budget = $this->finance->createBudget($this->requester, $this->account->id, 'Field work', 70000);
    $id = $this->finance->request($this->requester, $this->account->id, $budget, 30000, 'Field advance', 'advance', null, 'Example Officer');
    $this->finance->decide($this->approver, $id, true);
    $this->finance->postApprovedMovement($this->requester, $id, (string) Str::uuid(), 'Confirmed transfer 1');
    expect($this->account->fresh()->balance_minor)->toBe(70000);
    expect(DB::table('campaign_finance_entries')->where('kind', 'advance_expense')->count())->toBe(0);
    $key = (string) Str::uuid();
    $this->finance->settleAdvance($this->approver, $id, 18000, 'Receipt and return reference', $key);
    $this->finance->settleAdvance($this->approver, $id, 18000, 'Receipt and return reference', $key);
    expect($this->account->fresh()->balance_minor)->toBe(82000);
    $this->assertDatabaseHas('campaign_finance_budgets', ['id' => $budget, 'utilized_minor' => 18000]);
    $this->assertDatabaseHas('campaign_finance_entries', ['kind' => 'advance_expense', 'amount_minor' => 18000, 'balance_after_minor' => 70000]);
    $this->assertDatabaseHas('campaign_finance_entries', ['kind' => 'advance_return', 'amount_minor' => 12000, 'balance_after_minor' => 82000]);
    expect(DB::table('campaign_finance_entries')->whereIn('kind', ['advance_expense', 'advance_return'])->count())->toBe(2);
});

test('finance governance denies ordinary members and explicit capability alone is insufficient', function () {
    $member = User::factory()->create(['access_level' => 'user', 'state_id' => 1, 'status' => 'active']);
    foreach (['finance.view', 'finance.disbursement.create', 'finance.dashboard.view'] as $permission) {
        $member->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    expect(app(\App\Services\Enterprise\CampaignFinancePolicy::class)->visible($member))->toBe([]);
    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $this->finance->request($member, $this->account->id, null, 1000, 'No finance delegation');
});

test('central mode supports request-only leadership without disclosing dashboard or payment rights', function () {
    $policy = app(\App\Services\Enterprise\CampaignFinancePolicy::class);
    $leader = $this->requester;
    DB::table('campaign_finance_governance')->insert([
        'previous_id' => 1, 'actor_id' => $leader->id, 'mode' => 'central',
        'grants' => json_encode(['level:stateadmin' => [
            'finance.view', 'finance.disbursement.create',
            'finance.dashboard.view', 'finance.disbursement.approve',
        ]], JSON_THROW_ON_ERROR),
        'created_at' => now(),
    ]);
    expect($policy->permits($leader, 'finance.disbursement.create'))->toBeTrue();
    expect($policy->permits($leader, 'finance.disbursement.approve'))->toBeFalse();
    expect($policy->visible($leader))->not->toHaveKey('approvals');
    // A view grant permits financial dashboard access only if separately configured.
    $version = DB::table('campaign_finance_governance')->max('id');
    DB::table('campaign_finance_governance')->insert([
        'previous_id' => $version, 'actor_id' => $leader->id, 'mode' => 'central',
        'grants' => json_encode(['level:stateadmin' => ['finance.view', 'finance.disbursement.create']], JSON_THROW_ON_ERROR),
        'created_at' => now(),
    ]);
    expect($policy->visible($leader))->not->toHaveKey('dashboard');
    expect($policy->visible($leader))->toHaveKey('requests');
});

test('delegated governance never replaces role permissions or geographic account scoping', function () {
    $leader = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => 2, 'status' => 'active']);
    expect(app(\App\Services\Enterprise\CampaignFinancePolicy::class)->permits($leader, 'finance.disbursement.approve'))->toBeTrue();
    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $this->finance->authorize($leader, 'finance.disbursement.approve');
});

test('central governance grants finance actions to expressly authorized campaign root officers', function () {
    $root = User::factory()->create(['access_level' => 'nationaladmin', 'status' => 'active']);
    $policy = app(\App\Services\Enterprise\CampaignFinancePolicy::class);
    DB::table('campaign_finance_governance')->insert([
        'previous_id' => 1, 'actor_id' => $this->requester->id, 'mode' => 'central',
        'grants' => json_encode([
            'level:nationaladmin' => ['finance.disbursement.approve'],
            'level:stateadmin' => ['finance.disbursement.approve'],
        ], JSON_THROW_ON_ERROR),
        'created_at' => now(),
    ]);
    expect($policy->permits($root, 'finance.disbursement.approve'))->toBeTrue();
    expect($policy->permits($this->approver, 'finance.disbursement.approve'))->toBeFalse();
});

test('named funding recipients must reconcile exactly to the requested amount', function () {
    $this->finance->record($this->requester, $this->account->id, 'income', 80000, 'Funds', (string) Str::uuid());
    $id = $this->finance->request($this->requester, $this->account->id, null, 50000,
        'Ward outreach', 'expense', null, null, [
            ['name' => 'Vendor Alpha', 'amount_minor' => 30000],
            ['name' => 'Vendor Beta', 'amount_minor' => 20000],
        ]);
    expect(DB::table('campaign_finance_request_recipients')->where('request_id', $id)->count())->toBe(2);
    expect(DB::table('campaign_finance_request_origins')->where('request_id', $id)->value('payment_plan'))->toBe('named_recipients');
    $this->finance->decide($this->approver, $id, true);
    expect(fn () => $this->finance->record($this->requester, $this->account->id, 'expenditure',
        50000, 'Paid everyone', (string) Str::uuid(), $id))->toThrow(ValidationException::class);
    expect($this->account->fresh()->balance_minor)->toBe(80000);
    expect(DB::table('campaign_finance_requests')->where('id', $id)->value('status'))->toBe('approved');
});

test('invalid, duplicate or mismatched named recipient allocations never submit a funding request', function () {
    $starting = DB::table('campaign_finance_requests')->count();
    foreach ([
        [['name' => 'Alpha', 'amount_minor' => 2500]],
        [['name' => 'Alpha', 'amount_minor' => 5000], ['name' => 'alpha', 'amount_minor' => 5000]],
        [['name' => 'Alpha', 'amount_minor' => -1000], ['name' => 'Beta', 'amount_minor' => 11000]],
    ] as $recipients) {
        try {
            $this->finance->request($this->requester, $this->account->id, null, 10000,
                'Invalid recipient plan', 'expense', null, null, $recipients);
            $this->fail('Invalid recipients accepted.');
        } catch (ValidationException $e) {
            expect($e)->toBeInstanceOf(ValidationException::class);
        }
    }
    expect(DB::table('campaign_finance_requests')->count())->toBe($starting);
});

test('funding request retains requesting location separate from internal allocation destination', function () {
    $destination = $this->finance->createAccount($this->requester, 'Second location', $this->requester->id);
    $id = $this->finance->request($this->requester, $this->account->id, null, 1000,
        'Transfer to branch', 'transfer', $destination->id);
    $origin = DB::table('campaign_finance_request_origins')->where('request_id', $id)->first();
    expect($origin->payment_plan)->toBe('branch_allocation');
    expect($origin->requesting_level)->toBe('state');
    expect((int) $origin->requesting_location_id)->toBe(1);
    expect((int) $origin->beneficiary_location_id)->toBe($destination->id);
});

test('branch funding proposals preserve original territorial identity and do not require paying-account access', function () {
    $branch = $this->outsider;
    DB::table('campaign_finance_governance')->insert([
        'previous_id' => 1, 'actor_id' => $this->requester->id, 'mode' => 'central',
        'grants' => json_encode(['level:stateadmin' => [
            'finance.view', 'finance.disbursement.create',
        ]], JSON_THROW_ON_ERROR), 'created_at' => now(),
    ]);
    expect($this->finance->accounts($branch)->whereKey($this->account->id)->exists())->toBeFalse();
    $key = (string) Str::uuid();
    $id = $this->finance->submitFundingProposal($branch, 12000, 'Ward transport funding', $key,
        [['name' => 'Transport vendor', 'amount_minor' => 12000]]);
    expect($this->finance->submitFundingProposal($branch, 12000, 'Ward transport funding', $key,
        [['name' => 'Transport vendor', 'amount_minor' => 12000]]))->toBe($id);
    $proposal = DB::table('campaign_finance_funding_proposals')->find($id);
    expect((int) $proposal->requested_by)->toBe($branch->id);
    expect($proposal->requesting_level)->toBe('state');
    expect((int) $proposal->requesting_location_id)->toBe(2);
    expect($proposal->status)->toBe('submitted');
    expect(DB::table('campaign_finance_entries')->count())->toBe(0);
    expect(DB::table('campaign_finance_requests')->count())->toBe(0);
});

test('branch funding proposals cannot be forged, duplicated with changes or submitted by ordinary members', function () {
    $key = (string) Str::uuid();
    $this->finance->submitFundingProposal($this->requester, 10000, 'Event', $key);
    expect(fn () => $this->finance->submitFundingProposal($this->requester, 20000, 'Changed', $key))
        ->toThrow(ValidationException::class);
    expect(fn () => $this->finance->submitFundingProposal($this->requester, 10000, 'Event', (string) Str::uuid(),
        [['name' => 'A', 'amount_minor' => 5000]]))->toThrow(ValidationException::class);
    $member = User::factory()->create(['access_level' => 'user', 'status' => 'active']);
    $member->givePermissionTo(Permission::findOrCreate('finance.disbursement.create', 'web'));
    try {
        $this->finance->submitFundingProposal($member, 10000, 'Unauthorized', (string) Str::uuid());
        $this->fail('Ordinary member submitted proposal.');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }
    expect(DB::table('campaign_finance_funding_proposals')->count())->toBe(1);
});

test('finance approval inbox does not expose proposals submitted outside approver geography', function () {
    if (!app('router')->getRoutes()->getByName('finance.index')) {
        \Illuminate\Support\Facades\Route::middleware('web')->group(base_path('routes/enterprise-finance.php'));
        app('router')->getRoutes()->refreshNameLookups();
    }
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $this->finance->submitFundingProposal($this->requester, 10000, 'First state funded event', (string) Str::uuid());
    $this->finance->submitFundingProposal($this->outsider, 20000, 'Second state private event', (string) Str::uuid());
    $this->actingAs($this->approver)->get(route('finance.section', ['section' => 'approvals']))
        ->assertOk()->assertSee('First state funded event')->assertDontSee('Second state private event');
});


/*
 * Headquarters proposal processing: branch origin and beneficiary revisions must
 * remain independent of the treasury account and the approving officer.
 */
test('finance headquarters returns a proposal then the original branch officer revises and resubmits', function () {
    $workflow = app(\App\Services\Enterprise\CampaignFundingProposalService::class);
    $id = $this->finance->submitFundingProposal($this->requester, 15000, 'First purpose', (string) Str::uuid(),
        [['name' => 'Original recipient', 'amount_minor' => 15000]]);
    $workflow->review($this->approver, $id, 1, 'return', 'Provide an updated beneficiary.');
    expect(DB::table('campaign_finance_funding_proposals')->where('id', $id)->value('status'))->toBe('returned');
    expect(fn () => $workflow->review($this->approver, $id, 1, 'reject', 'Stale form'))
        ->toThrow(ValidationException::class);
    expect(fn () => $workflow->resubmit($this->outsider, $id, 2, 15000, 'Forgery', []))
        ->toThrow(\Illuminate\Database\RecordNotFoundException::class);
    $workflow->resubmit($this->requester, $id, 2, 17000, 'Updated purpose',
        [['name' => 'Updated recipient', 'amount_minor' => 17000]]);
    $proposal = DB::table('campaign_finance_funding_proposals')->find($id);
    expect($proposal->status)->toBe('submitted');
    expect((int) $proposal->amount_minor)->toBe(17000);
    expect(json_decode($proposal->recipients, true)[0]['name'])->toBe('Updated recipient');
    $events = DB::table('campaign_finance_proposal_events')->where('proposal_id', $id)
        ->orderBy('revision')->get();
    expect($events->pluck('action')->all())->toBe(['submit', 'return', 'resubmit']);
    expect(json_decode($events[2]->details, true)['previous_recipients'][0]['name'])
        ->toBe('Original recipient');
    expect((int) DB::table('campaign_finance_proposal_controls')->where('proposal_id', $id)->value('revision'))->toBe(3);
});

test('funding proposals can be rejected only by independent geographically authorized reviewers', function () {
    $workflow = app(\App\Services\Enterprise\CampaignFundingProposalService::class);
    $id = $this->finance->submitFundingProposal($this->requester, 10000, 'Pending grant', (string) Str::uuid());
    expect(fn () => $workflow->review($this->requester, $id, 1, 'reject', 'Own proposal'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect(fn () => $workflow->review($this->outsider, $id, 1, 'reject', 'Wrong territory'))
        ->toThrow(\Illuminate\Database\RecordNotFoundException::class);
    $workflow->review($this->approver, $id, 1, 'reject', 'Insufficient evidence.');
    expect(DB::table('campaign_finance_funding_proposals')->where('id', $id)->value('status'))->toBe('rejected');
    expect(fn () => $workflow->resubmit($this->requester, $id, 2, 10000, 'Retry', []))
        ->toThrow(ValidationException::class);
    expect(DB::table('campaign_finance_requests')->count())->toBe(0);
});

test('headquarters allocation requires an explicit approval chain and cannot mint approved money', function () {
    $workflow = app(\App\Services\Enterprise\CampaignFundingProposalService::class);
    $id = $this->finance->submitFundingProposal($this->requester, 12000, 'Branch project', (string) Str::uuid());
    $this->finance->record($this->requester, $this->account->id, 'income', 20000,
        'Opening balance', (string) Str::uuid());
    expect(fn () => $workflow->assign($this->approver, $id, 1, $this->account->id))
        ->toThrow(ValidationException::class);
    expect(DB::table('campaign_finance_requests')->count())->toBe(0);
    expect(DB::table('campaign_finance_funding_proposals')->where('id', $id)->value('status'))
        ->toBe('submitted');

    DB::table('campaign_finance_policies')->insert([
        'previous_id' => 0, 'actor_id' => $this->approver->id,
        'menus' => '{}', 'approval_rules' => json_encode([
            ['minimum_minor' => 0, 'stages' => ['level:stateadmin']],
        ]), 'created_at' => now(),
    ]);
    $requestId = $workflow->assign($this->approver, $id, 1, $this->account->id);
    $request = DB::table('campaign_finance_requests')->find($requestId);
    expect($request->status)->toBe('pending');
    expect((int) $request->requested_by)->toBe($this->requester->id);
    expect((int) DB::table('campaign_finance_funding_proposals')
        ->where('id', $id)->value('assigned_request_id'))->toBe($requestId);
    expect((int) $this->finance->reservations()->where('account_id', $this->account->id)
        ->sum('amount_minor'))->toBe(0);
    expect(fn () => $workflow->assign($this->approver, $id, 1, $this->account->id))
        ->toThrow(ValidationException::class);
    expect(fn () => $this->finance->decide($this->approver, $requestId, true))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect(fn () => $this->finance->decide($this->requester, $requestId, true))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    $independent = User::factory()->create([
        'access_level' => 'stateadmin', 'state_id' => 1, 'status' => 'active',
    ]);
    $independent->givePermissionTo(Permission::findOrCreate('finance.disbursement.approve', 'web'));
    $this->finance->decide($independent, $requestId, true);
    expect(DB::table('campaign_finance_requests')->where('id', $requestId)
        ->value('status'))->toBe('approved');
    expect((int) $this->finance->reservations()->where('account_id', $this->account->id)
        ->sum('amount_minor'))->toBe(12000);
    expect($this->account->fresh()->balance_minor)->toBe(20000);
});

test('headquarters allocation retains named recipients and original location and blocks a lump-sum payout', function () {
    $workflow = app(\App\Services\Enterprise\CampaignFundingProposalService::class);
    DB::table('campaign_finance_policies')->insert([
        'previous_id' => 0, 'actor_id' => $this->approver->id,
        'menus' => '{}', 'approval_rules' => json_encode([
            ['minimum_minor' => 0, 'stages' => ['level:stateadmin']],
        ]), 'created_at' => now(),
    ]);
    $id = $this->finance->submitFundingProposal($this->outsider, 20000,
        'Second state vendors', (string) Str::uuid(), [
            ['name' => 'Vendor A', 'amount_minor' => 7000],
            ['name' => 'Vendor B', 'amount_minor' => 13000],
        ]);
    expect(fn () => $workflow->assign($this->approver, $id, 1, $this->account->id))
        ->toThrow(\Illuminate\Database\RecordNotFoundException::class);

    $regional = User::factory()->create([
        'access_level' => 'superadmin', 'status' => 'active',
    ]);
    $requestId = $workflow->assign($regional, $id, 1, $this->account->id);
    $origin = DB::table('campaign_finance_request_origins')->where('request_id', $requestId)->first();
    expect($origin->requesting_level)->toBe('state');
    expect((int) $origin->requesting_location_id)->toBe(2);
    expect((int) DB::table('campaign_finance_request_recipients')->where('request_id', $requestId)
        ->sum('amount_minor'))->toBe(20000);
    expect(DB::table('campaign_finance_requests')->where('id', $requestId)
        ->value('status'))->toBe('pending');
});

test('headquarters branch allocations record a recipient account rather than treating it as an expense', function () {
    $workflow = app(\App\Services\Enterprise\CampaignFundingProposalService::class);
    DB::table('campaign_finance_policies')->insert([
        'previous_id' => 0, 'actor_id' => $this->approver->id,
        'menus' => '{}', 'approval_rules' => json_encode([
            ['minimum_minor' => 0, 'stages' => ['level:stateadmin']],
        ]), 'created_at' => now(),
    ]);
    $destination = $this->finance->createAccount($this->requester,
        'Receiving branch treasury', $this->requester->id);
    $id = $this->finance->submitFundingProposal($this->requester, 5000,
        'Unspecified branch beneficiaries', (string) Str::uuid());
    $requestId = $workflow->assign($this->approver, $id, 1,
        $this->account->id, null, $destination->id);
    $routing = DB::table('campaign_finance_request_routes')->where('request_id', $requestId)->first();
    expect($routing->purpose)->toBe('transfer');
    expect((int) $routing->destination_id)->toBe($destination->id);
    expect(DB::table('campaign_finance_entries')->count())->toBe(0);
    expect(DB::table('campaign_finance_request_origins')->where('request_id', $requestId)
        ->value('payment_plan'))->toBe('branch_allocation');
});


test('proposal approval and audit visibility stay tied to immutable submitting jurisdiction after a member relocates', function () {
    $workflow = app(\App\Services\Enterprise\CampaignFundingProposalService::class);
    $id = $this->finance->submitFundingProposal($this->requester, 8000,
        'Original first-state branch proposal', (string) Str::uuid());
    $this->requester->update(['state_id' => 2]);
    $this->requester->refresh();
    expect($workflow->reviewerQueue($this->approver)->where('proposals.id', $id)->exists())->toBeTrue();
    expect($workflow->reviewerQueue($this->outsider)->where('proposals.id', $id)->exists())->toBeFalse();
    expect(fn () => $workflow->history($this->outsider, $id))
        ->toThrow(\Illuminate\Database\RecordNotFoundException::class);
    [$proposal, $events] = $workflow->history($this->requester, $id);
    expect($proposal->requesting_level)->toBe('state');
    expect((int) $proposal->requesting_location_id)->toBe(1);
    expect($events->first()->action)->toBe('submit');
});

test('headquarters workflow migration safely reenters without losing proposal audit history', function () {
    $id = $this->finance->submitFundingProposal($this->requester, 3000,
        'No duplicate migration history', (string) Str::uuid());
    $migration = require database_path('migrations/2026_11_06_000018_add_campaign_finance_proposal_workflow.php');
    $migration->up();
    $migration->up();
    expect(DB::table('campaign_finance_proposal_controls')->where('proposal_id', $id)->count())->toBe(1);
    expect(DB::table('campaign_finance_proposal_locations')->where('proposal_id', $id)->count())->toBe(1);
    expect(DB::table('campaign_finance_proposal_events')->where('proposal_id', $id)->count())->toBe(1);
    expect(DB::table('campaign_finance_funding_proposals')->where('id', $id)->exists())->toBeTrue();
});


test('bank statement reconciliation snapshots ledger balance and requires independent review', function () {
    $service = app(\App\Services\Enterprise\CampaignFinanceStatementService::class);
    foreach ([$this->requester, $this->approver] as $user) {
        $user->givePermissionTo(Permission::findOrCreate('finance.reconcile', 'web'));
    }
    $this->finance->record($this->requester, $this->account->id, 'income', 120000,
        'Opening income', (string) Str::uuid());
    $check = $service->prepare($this->requester, $this->account->id,
        today()->toDateString(), 120000, 'External bank statement A');
    expect(DB::table('campaign_finance_statement_checks')->where('id', $check)
        ->value('variance_minor'))->toBe(0);
    expect(fn () => $service->review($this->requester, $check, 'My own statement'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $service->review($this->approver, $check, 'Checked the supplied statement reference.');
    $row = DB::table('campaign_finance_statement_checks')->find($check);
    expect($row->review_outcome)->toBe('balanced');
    expect((int) $row->reviewed_by)->toBe($this->approver->id);
    expect($this->account->fresh()->balance_minor)->toBe(120000);
    expect(DB::table('campaign_finance_audits')
        ->whereIn('action', ['statement.check.prepared','statement.check.reviewed'])->count())->toBe(2);
    expect(fn () => $service->review($this->approver, $check, 'Approve again'))
        ->toThrow(ValidationException::class);
});

test('statement variance is flagged rather than silently mutating recorded campaign funds', function () {
    $service = app(\App\Services\Enterprise\CampaignFinanceStatementService::class);
    $this->requester->givePermissionTo(Permission::findOrCreate('finance.reconcile', 'web'));
    $this->approver->givePermissionTo(Permission::findOrCreate('finance.reconcile', 'web'));
    $this->finance->record($this->requester, $this->account->id, 'income', 50000,
        'Recorded donation', (string) Str::uuid());
    $id = $service->prepare($this->requester, $this->account->id, today()->toDateString(),
        45000, 'External bank balance B');
    $service->review($this->approver, $id, 'Investigate the difference before booking adjustments.');
    $row = DB::table('campaign_finance_statement_checks')->find($id);
    expect((int) $row->variance_minor)->toBe(-5000);
    expect($row->review_outcome)->toBe('variance');
    expect($this->account->fresh()->balance_minor)->toBe(50000);
    expect(DB::table('campaign_finance_entries')->count())->toBe(1);
    expect(fn () => $service->prepare($this->requester, $this->account->id,
        today()->toDateString(), 45000, 'Duplicate bank statement'))
        ->toThrow(ValidationException::class);
});

test('reconciliation requires explicit privileges and cannot expose another jurisdiction', function () {
    $service = app(\App\Services\Enterprise\CampaignFinanceStatementService::class);
    expect(fn () => $service->prepare($this->requester, $this->account->id,
        today()->toDateString(), 0, 'No reconciler role'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $this->outsider->givePermissionTo(Permission::findOrCreate('finance.reconcile', 'web'));
    expect(fn () => $service->prepare($this->outsider, $this->account->id,
        today()->toDateString(), 0, 'Different state statement'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect(DB::table('campaign_finance_statement_checks')->count())->toBe(0);
});

test('closing requires an independent statement and reopening restores entry rights', function () {
    $this->requester->givePermissionTo(Permission::findOrCreate('finance.expenses.record', 'web'));
    foreach (['finance.reconcile', 'finance.periods.close', 'finance.periods.reopen'] as $permission) {
        foreach ([$this->requester, $this->approver] as $user) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    }
    $this->travel(-2)->days();
    $date = today()->toDateString();
    $this->finance->record($this->requester, $this->account->id, 'income', 100000, 'Opening funds', (string) Str::uuid());
    $this->travelBack();
    $statements = app(\App\Services\Enterprise\CampaignFinanceStatementService::class);
    $periods = app(\App\Services\Enterprise\CampaignFinancePeriodService::class);
    $check = $statements->prepare($this->requester, $this->account->id, $date, 100000, 'Statement #1');
    expect(fn () => $periods->close($this->approver, $this->account->id, $check, 'Early close'))->toThrow(ValidationException::class);
    $statements->review($this->approver, $check, 'Matched bank statement');
    $periods->close($this->approver, $this->account->id, $check, 'Close reviewed period');
    $receipt = ['payee' => 'Supplier', 'paid_on' => $date, 'reference' => 'REF-9', 'evidence_reference' => 'Receipt #9'];
    $post = fn () => $this->finance->recordManualExpense($this->requester, $this->account->id, 1000, 'Paid invoice', (string) Str::uuid(), $receipt);
    expect($post)->toThrow(ValidationException::class);
    $event = $periods->latest($this->account->id);
    $periods->reopen($this->approver, $this->account->id, $event->id, 'Late receipt verified');
    expect($post())->toBeInt();
    expect($this->account->fresh()->balance_minor)->toBe(99000);
    expect(DB::table('campaign_finance_period_events')->count())->toBe(2);
});

test('Paystack preserves four encrypted keys across switches and verifies late sandbox webhooks', function () {
    $admin = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $payments = app(\App\Services\Enterprise\CampaignPaystackService::class);
    $keys = [
        'test_public' => 'pk_test_'.str_repeat('a', 30), 'test_secret' => 'sk_test_'.str_repeat('b', 30),
        'live_public' => 'pk_live_'.str_repeat('c', 30), 'live_secret' => 'sk_live_'.str_repeat('d', 30),
        'test_webhook' => 'whsec_'.str_repeat('e', 30),
    ];
    $http = \Illuminate\Support\Facades\Http::class;
    $http::fake([
        'https://api.paystack.co/balance' => $http::response(['status' => true, 'data' => []]),
        'https://api.paystack.co/transaction/initialize' => fn ($request) => $http::response([
            'status' => true, 'data' => ['reference' => $request['reference'], 'authorization_url' => 'https://checkout.paystack.com/test'],
        ]),
        'https://api.paystack.co/transaction/verify/*' => fn ($request) => $http::response([
            'status' => true, 'data' => ['reference' => basename($request->url()), 'domain' => 'test',
                'status' => 'success', 'currency' => 'NGN', 'amount' => 50000],
        ]),
    ]);
    expect(fn () => $payments->saveSettings($this->requester, $keys, 'test', true, true))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $payments->saveSettings($admin, $keys, 'test', true, false);
    expect($payments->collect($admin, $this->account->id, 50000, 'payer@example.invalid', 'https://example.invalid/return'))
        ->toBe('https://checkout.paystack.com/test');
    expect(fn () => $payments->saveSettings($admin, [], 'live', true, false))->toThrow(ValidationException::class);
    $payments->saveSettings($admin, [], 'live', false, false);
    $reference = DB::table('campaign_paystack_operations')->value('reference');
    expect(fn () => $payments->operations($this->outsider))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $this->outsider->givePermissionTo(Permission::findOrCreate('finance.paystack.view', 'web'));
    expect($payments->operations($this->outsider)->count())->toBe(0);
    $body = json_encode(['event' => 'charge.success', 'data' => ['domain' => 'test', 'reference' => $reference]]);
    $time = now()->timestamp;
    $signature = 't='.$time.',v1=key:'.hash_hmac('sha512', $time.'.'.$body, $keys['test_webhook']);
    expect(fn () => $payments->receiveWebhook('test', $body, $signature.'bad', ''))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $payments->receiveWebhook('test', $body, $signature, '');
    $payments->receiveWebhook('test', $body, $signature, '');
    $payments->saveSettings($admin, ['test_secret' => ''], 'test', true, false);
    $saved = DB::table('campaign_paystack_settings')->first();
    foreach ($keys as $column => $key) {
        expect($saved->{$column})->not->toBe($key);
        expect(\Illuminate\Support\Facades\Crypt::decryptString($saved->{$column}))->toBe($key);
    }
    expect($payments->settings())->not->toHaveProperty('live_secret');
    expect(DB::table('campaign_paystack_operations')->value('status'))->toBe('success');
    expect(DB::table('campaign_paystack_webhook_events')->count())->toBe(1);
    expect($this->account->fresh()->balance_minor)->toBe(0);
    $http::assertSentCount(3);
});

test('Paystack branch payouts require separate authorization and one sandbox submission', function () {
    $admin = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $payments = app(\App\Services\Enterprise\CampaignPaystackService::class);
    $http = \Illuminate\Support\Facades\Http::class;
    $http::fake([
        'https://api.paystack.co/transferrecipient/*' => $http::response([
            'status' => true, 'data' => ['recipient_code' => 'RCP_abcd1234', 'domain' => 'test', 'currency' => 'NGN', 'active' => true],
        ]),
        'https://api.paystack.co/transfer' => $http::response(['status' => true, 'data' => ['status' => 'pending']]),
        'https://api.paystack.co/transfer/verify/*' => fn ($request) => $http::response([
            'status' => true, 'data' => ['reference' => basename($request->url()), 'domain' => 'test', 'amount' => 15000,
                'status' => 'success', 'currency' => 'NGN', 'recipient' => ['recipient_code' => 'RCP_abcd1234']],
        ]),
    ]);
    $payments->saveSettings($admin, ['test_public' => 'pk_test_'.str_repeat('a', 30),
        'test_secret' => 'sk_test_'.str_repeat('b', 30)], 'test', false, true);
    $this->approver->givePermissionTo(Permission::findOrCreate('finance.paystack.approve', 'web'));
    $this->approver->givePermissionTo(Permission::findOrCreate('finance.paystack.transfer', 'web'));
    $this->requester->givePermissionTo(Permission::findOrCreate('finance.paystack.transfer', 'web'));
    $this->requester->givePermissionTo(Permission::findOrCreate('finance.paystack.view', 'web'));
    $destination = $this->finance->createAccount($this->requester, 'Test destination', $this->requester->id);
    $payments->saveRecipient($admin, $destination->id, 'RCP_abcd1234');
    $this->finance->record($this->requester, $this->account->id, 'income', 50000, 'Initial funds', (string) Str::uuid());
    $id = $this->finance->request($this->requester, $this->account->id, null, 15000, 'Test allocation', 'transfer', $destination->id);
    $this->finance->decide($this->approver, $id, true);
    $payments->approveTransfer($this->approver, $id);
    expect(fn () => $payments->transfer($this->approver, $id))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $payments->transfer($this->requester, $id);
    expect(fn () => $payments->transfer($this->requester, $id))->toThrow(ValidationException::class);
    expect($payments->verify($this->requester, DB::table('campaign_paystack_operations')->value('reference')))->toBe('success');
    expect($this->account->fresh()->balance_minor)->toBe(50000);
    expect($destination->fresh()->balance_minor)->toBe(0);
    expect(DB::table('campaign_finance_requests')->where('id', $id)->value('status'))->toBe('approved');
    $http::assertSentCount(3);
});

test('Paystack beneficiary test batch locks verified recipients and reconciles individual outcomes', function () {
    $admin = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $payments = app(\App\Services\Enterprise\CampaignPaystackService::class);
    $http = \Illuminate\Support\Facades\Http::class;
    $status = ['RCP_alice001' => 'success', 'RCP_bob00001' => 'failed'];
    $http::fake([
        'https://api.paystack.co/transferrecipient/*' => fn ($request) => $http::response([
            'status' => true, 'data' => ['recipient_code' => basename($request->url()), 'domain' => 'test',
                'currency' => 'NGN', 'active' => true, 'type' => 'nuban', 'details' => [
                    'account_name' => str_contains($request->url(), 'alice') ? 'Alice Example' : 'Bob Example',
                ]],
        ]),
        'https://api.paystack.co/transfer/bulk' => fn ($request) => $http::response(['status' => true, 'data' => $request['transfers']]),
        'https://api.paystack.co/transfer/verify/*' => function ($request) use ($http, &$status) {
            $operation = DB::table('campaign_paystack_operations')->where('reference', basename($request->url()))->first();
            return $http::response(['status' => true, 'data' => [
                'reference' => $operation->reference, 'domain' => 'test', 'currency' => 'NGN',
                'status' => $status[$operation->recipient_code], 'amount' => (int) $operation->amount_minor,
                'recipient' => ['recipient_code' => $operation->recipient_code],
            ]]);
        },
    ]);
    $payments->saveSettings($admin, ['test_public' => 'pk_test_'.str_repeat('a', 30),
        'test_secret' => 'sk_test_'.str_repeat('b', 30)], 'test', false, true);
    $this->approver->givePermissionTo(Permission::findOrCreate('finance.paystack.approve', 'web'));
    $this->requester->givePermissionTo(Permission::findOrCreate('finance.paystack.transfer', 'web'));
    $this->requester->givePermissionTo(Permission::findOrCreate('finance.paystack.view', 'web'));
    $this->finance->record($this->requester, $this->account->id, 'income', 50000, 'Initial funds', (string) Str::uuid());
    $request = $this->finance->request($this->requester, $this->account->id, null, 20000, 'Test project', 'expense', null, null, [
        ['name' => 'Alice Example', 'amount_minor' => 12000], ['name' => 'Bob Example', 'amount_minor' => 8000],
    ]);
    $this->finance->decide($this->approver, $request, true);
    $beneficiaries = DB::table('campaign_finance_request_recipients')->where('request_id', $request)->orderBy('position')->pluck('id');
    expect(fn () => $payments->linkBeneficiary($admin, $beneficiaries[0], 'RCP_bob00001'))->toThrow(ValidationException::class);
    $payments->linkBeneficiary($admin, $beneficiaries[0], 'RCP_alice001');
    expect(fn () => $payments->approveBatch($this->approver, $request))->toThrow(ValidationException::class);
    expect(DB::table('campaign_paystack_operations')->count())->toBe(0);
    $payments->linkBeneficiary($admin, $beneficiaries[1], 'RCP_bob00001');
    $payments->approveBatch($this->approver, $request);
    $payments->submitBatch($this->requester, $request);
    expect(fn () => $payments->submitBatch($this->requester, $request))->toThrow(ValidationException::class);
    $operations = DB::table('campaign_paystack_operations')->pluck('reference', 'recipient_code');
    foreach ($status as $recipient => $result) {
        expect($payments->verify($this->requester, $operations[$recipient]))->toBe($result);
    }
    $status['RCP_alice001'] = 'reversed';
    expect($payments->verify($this->requester, $operations['RCP_alice001']))->toBe('reversed');
    expect($this->account->fresh()->balance_minor)->toBe(50000);
    expect(DB::table('campaign_finance_requests')->where('id', $request)->value('status'))->toBe('approved');
    $http::assertSentCount(7);
});

test('Paystack refund checks bind the provider refund to its original collection and cap completed totals', function () {
    $admin = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $payments = app(\App\Services\Enterprise\CampaignPaystackService::class);
    $http = \Illuminate\Support\Facades\Http::class;
    $http::fake([
        'https://api.paystack.co/transaction/initialize' => fn ($request) => $http::response([
            'status' => true, 'data' => ['reference' => $request['reference'], 'authorization_url' => 'https://checkout.paystack.com/test'],
        ]),
        'https://api.paystack.co/transaction/verify/*' => fn ($request) => $http::response([
            'status' => true, 'data' => ['id' => 100, 'reference' => basename($request->url()), 'domain' => 'test',
                'status' => 'success', 'currency' => 'NGN', 'amount' => 50000],
        ]),
        'https://api.paystack.co/refund/*' => fn ($request) => $http::response([
            'status' => true, 'data' => ['id' => (int) basename($request->url()), 'domain' => 'test', 'currency' => 'NGN',
                'amount' => 30000, 'status' => 'processed', 'transaction' => str_ends_with($request->url(), '/3') ? 200 : 100],
        ]),
    ]);
    $payments->saveSettings($admin, ['test_public' => 'pk_test_'.str_repeat('a', 30),
        'test_secret' => 'sk_test_'.str_repeat('b', 30)], 'test', true, false);
    $payments->collect($admin, $this->account->id, 50000, 'payer@example.invalid', 'https://example.invalid/return');
    $reference = DB::table('campaign_paystack_operations')->value('reference');
    expect($payments->reconcileRefund($admin, $reference, 1))->toBe('processed');
    expect($payments->reconcileRefund($admin, $reference, 1))->toBe('processed');
    expect(fn () => $payments->reconcileRefund($admin, $reference, 2))->toThrow(ValidationException::class);
    expect(fn () => $payments->reconcileRefund($admin, $reference, 3))->toThrow(ValidationException::class);
    expect(DB::table('campaign_paystack_refund_checks')->count())->toBe(1);
    expect($this->account->fresh()->balance_minor)->toBe(0);
});

test('statement CSV is balanced before storage and unresolved variances block period close', function () {
    $statements = app(\App\Services\Enterprise\CampaignFinanceStatementService::class);
    $periods = app(\App\Services\Enterprise\CampaignFinancePeriodService::class);
    foreach (['finance.reconcile', 'finance.periods.close'] as $permission) {
        foreach ([$this->requester, $this->approver, $this->outsider] as $user) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    }

    $this->travel(-3)->days();
    $earlier = today()->toDateString();
    $this->finance->record($this->requester, $this->account->id, 'income', 5000, 'Opening donation', (string) Str::uuid());
    $this->travelBack();
    $later = today()->subDay()->toDateString();
    $accountId = $this->account->id;
    $csv = "date,reference,amount_minor\n{$later},Receipt 18,5000\n";
    expect(fn () => $statements->import($this->requester, $accountId, $earlier, $later, 0, 4999, 'Statement 18', $csv))
        ->toThrow(ValidationException::class);
    expect(fn () => $statements->import($this->outsider, $accountId, $earlier, $later, 0, 5000, 'Statement 18', $csv))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect(DB::table('campaign_finance_statement_checks')->count())->toBe(0);

    $variance = $statements->prepare($this->requester, $accountId, $earlier, 4000, 'Statement 17');
    $statements->review($this->approver, $variance, 'Investigated the reported variance');
    $matched = $statements->import($this->requester, $accountId, $earlier, $later, 0, 5000, 'Statement 18', $csv);
    $statements->review($this->approver, $matched, 'Matched the corrected bank statement');
    expect(DB::table('campaign_finance_statement_imports')->where('check_id', $matched)->value('sha256'))
        ->toBe(hash('sha256', $csv));
    expect(fn () => $periods->close($this->approver, $accountId, $matched, 'Close reviewed period'))
        ->toThrow(ValidationException::class);
    expect(fn () => $statements->resolve($this->requester, $variance, $matched, 'Duplicate statement amount corrected'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $statements->resolve($this->approver, $variance, $matched, 'Duplicate statement amount corrected');
    $periods->close($this->approver, $accountId, $matched, 'Close reviewed period');
    expect(DB::table('campaign_finance_statement_checks')->find($variance)->review_outcome)->toBe('variance');
    expect(DB::table('campaign_finance_statement_resolutions')->where('check_id', $variance)->count())->toBe(1);
    expect(DB::table('campaign_finance_entries')->count())->toBe(1);
});

test('reviewed bank credit matches one verified Paystack live settlement without booking cash', function () {
    $admin = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $payments = app(\App\Services\Enterprise\CampaignPaystackService::class);
    $statements = app(\App\Services\Enterprise\CampaignFinanceStatementService::class);
    foreach ([$this->requester, $this->approver, $admin] as $user) {
        $user->givePermissionTo(Permission::findOrCreate('finance.reconcile', 'web'));
    }
    $payments->saveSettings($admin, ['live_secret' => 'sk_live_'.str_repeat('a', 30)], 'test', false, false);
    $date = today()->subDay()->toDateString();
    $credit = 'BANK-REF-173';
    $csv = "date,reference,amount_minor\n{$date},{$credit},9800\n";
    $check = $statements->import($this->requester, $this->account->id, $date, $date, 0, 9800, 'Bank statement', $csv);

    $http = \Illuminate\Support\Facades\Http::class;
    $providerStatus = 'pending';
    $http::preventStrayRequests();
    $http::fake(['https://api.paystack.co/settlement?*' => function () use ($http, $date, &$providerStatus) {
        return $http::response(['status' => true, 'data' => [[
            'id' => 123, 'integration' => 81, 'domain' => 'live', 'status' => $providerStatus,
            'currency' => 'NGN', 'total_processed' => 10000, 'total_fees' => 200,
            'effective_amount' => 9800, 'total_amount' => 9800, 'deductions' => null,
            'settlement_date' => $date.'T00:00:00.000Z',
        ]]]);
    }]);
    expect(fn () => $payments->matchSettlement($admin, $check, 123, $credit))->toThrow(ValidationException::class);
    $statements->review($this->approver, $check, 'Verified the statement evidence');
    expect(fn () => $payments->matchSettlement($this->approver, $check, 123, $credit))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect(fn () => $payments->matchSettlement($admin, $check, 123, $credit))->toThrow(ValidationException::class);
    $providerStatus = 'success';
    $payments->matchSettlement($admin, $check, 123, $credit);
    $payments->matchSettlement($admin, $check, 123, $credit);

    expect(DB::table('campaign_paystack_settlement_matches')->count())->toBe(1);
    expect((int) DB::table('campaign_paystack_settlement_matches')->value('fee_minor'))->toBe(200);
    expect(DB::table('campaign_finance_entries')->count())->toBe(0);
    $http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->hasHeader('Authorization', 'Bearer sk_live_'.str_repeat('a', 30)));
});

test('approved reimbursement reuses its bound profile in a single audited test payout', function () {
    $this->requester->update(['bank' => 'First Bank', 'bank_account_number' => '0000000000']);
    $linker = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $payoutApprover = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $executor = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    foreach ([$this->outsider, $linker, $payoutApprover, $executor, $this->requester] as $user) {
        foreach (['finance.paystack.view', 'finance.paystack.approve', 'finance.paystack.transfer', 'finance.paystack.recipients.manage'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    }
    $payments = app(\App\Services\Enterprise\CampaignPaystackService::class);
    $payments->saveSettings($linker, ['test_public' => 'pk_test_'.str_repeat('a', 30),
        'test_secret' => 'sk_test_'.str_repeat('b', 30)], 'test', false, true);
    $this->finance->record($this->requester, $this->account->id, 'income', 12000, 'Opening funds', (string) Str::uuid());
    $requestId = $this->finance->request($this->requester, $this->account->id, null, 5000,
        'Travel reimbursement', profileBeneficiary: $this->requester);
    $beneficiaryId = (int) DB::table('campaign_finance_request_recipients')->where('request_id', $requestId)->value('id');
    expect((int) DB::table('campaign_finance_profile_beneficiaries')->where('beneficiary_id', $beneficiaryId)->value('user_id'))
        ->toBe($this->requester->id);

    $http = \Illuminate\Support\Facades\Http::class;
    $http::preventStrayRequests();
    $http::fake([
        'https://api.paystack.co/transferrecipient' => $http::response(['status' => true, 'data' => [
            'recipient_code' => 'RCP_profile123', 'domain' => 'test', 'type' => 'nuban', 'currency' => 'NGN',
            'active' => true, 'details' => ['account_number' => '0000000000', 'bank_code' => '011'],
        ]]),
        'https://api.paystack.co/transfer' => $http::response(['status' => true, 'data' => ['status' => 'pending']]),
        'https://api.paystack.co/transfer/verify/*' => fn ($request) => $http::response([
            'status' => true, 'data' => ['reference' => basename($request->url()), 'domain' => 'test',
                'status' => 'success', 'currency' => 'NGN', 'amount' => 5000, 'recipient' => ['recipient_code' => 'RCP_profile123']],
        ]),
    ]);
    expect(fn () => $payments->linkProfileBeneficiary($linker, $beneficiaryId))->toThrow(ValidationException::class);
    $this->finance->decide($this->approver, $requestId, true);
    expect(fn () => $payments->linkProfileBeneficiary($this->outsider, $beneficiaryId))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect(fn () => $payments->linkBeneficiary($linker, $beneficiaryId, 'RCP_profile123'))->toThrow(ValidationException::class);
    $payments->linkProfileBeneficiary($linker, $beneficiaryId);
    expect(fn () => $payments->approveBatch($linker, $requestId))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $payments->approveBatch($payoutApprover, $requestId);
    expect(fn () => $payments->submitBatch($this->requester, $requestId))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $staleRequest = $this->finance->request($this->requester, $this->account->id, null, 1000,
        'Separate stale reimbursement', profileBeneficiary: $this->requester);
    $staleBeneficiary = (int) DB::table('campaign_finance_request_recipients')->where('request_id', $staleRequest)->value('id');
    $this->finance->decide($this->approver, $staleRequest, true);
    $payments->linkProfileBeneficiary($linker, $staleBeneficiary);
    $payments->approveBatch($payoutApprover, $staleRequest);
    $payments->submitBatch($executor, $requestId);
    expect(fn () => $payments->submitBatch($executor, $requestId))->toThrow(ValidationException::class);
    $operation = DB::table('campaign_paystack_operations')->orderBy('id')->first();
    expect($operation->status)->toBe('pending');
    expect($payments->verify($executor, $operation->reference))->toBe('success');
    $statuses = DB::table('campaign_finance_audits')->whereIn('action', [
        'paystack.payout.authorized', 'paystack.payout.submitted', 'paystack.operation.verified',
    ])->orderBy('id')->get()->filter(fn ($event) => data_get(json_decode($event->details, true), 'reference') === $operation->reference);
    expect($statuses->pluck('action')->all())->toBe(['paystack.payout.authorized', 'paystack.payout.submitted', 'paystack.operation.verified']);
    expect($statuses->pluck('actor_id')->map(fn ($id) => (int) $id)->all())->toBe([$payoutApprover->id, $executor->id, $executor->id]);
    $this->requester->update(['bank_account_number' => '1111111111']);
    $this->requester->update(['bank_account_number' => '0000000000']);
    expect(fn () => $payments->submitBatch($executor, $staleRequest))->toThrow(ValidationException::class);
    expect(DB::table('campaign_finance_audits')->where('action', 'paystack.profile_beneficiary.prepared')->count())->toBe(2);
    expect($this->account->fresh()->balance_minor)->toBe(12000);
    $http::assertSentCount(4);
});

test('an authorized officer can select an existing member profile without copying bank fields into the request', function () {
    $member = User::factory()->create([
        'access_level' => 'user', 'state_id' => 1, 'status' => 'active', 'username' => 'member-reimbursement',
        'bank' => 'First Bank', 'bank_account_number' => '0000000000',
    ]);
    $otherState = User::factory()->create([
        'access_level' => 'user', 'state_id' => 2, 'status' => 'active', 'username' => 'out-of-state-member',
        'bank' => 'First Bank', 'bank_account_number' => '0000000000',
    ]);
    $payload = ['account_id' => $this->account->id, 'amount' => '50', 'description' => 'Documented travel reimbursement'];
    $this->actingAs($this->requester)->post(route('finance.requests.store'), $payload + ['profile_username' => $member->username])
        ->assertForbidden();
    $this->requester->givePermissionTo(Permission::findOrCreate('finance.paystack.recipients.manage', 'web'));
    $this->post(route('finance.requests.store'), $payload + ['profile_username' => $otherState->username])->assertNotFound();
    $this->post(route('finance.requests.store'), $payload + ['profile_username' => $member->username])->assertRedirect()->assertSessionHasNoErrors();
    $binding = DB::table('campaign_finance_profile_beneficiaries')->first();
    expect((int) $binding->user_id)->toBe($member->id);
    expect($binding->bank_fingerprint)->toHaveLength(64);
    expect(json_encode($binding, JSON_THROW_ON_ERROR))->not->toContain($member->bank_account_number);
});
