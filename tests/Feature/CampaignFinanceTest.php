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
    $this->mock(EntitlementService::class)->shouldReceive('active')->with('campaign_finance')->andReturn(true)->byDefault();
    $country = \App\Models\Country::create(['name' => 'Nigeria', 'uuid' => (string) Str::uuid()]);
    $region = \App\Models\Region::create(['name' => 'Region', 'country_id' => $country->id]);
    \App\Models\State::create(['name' => 'First', 'region_id' => $region->id]);
    \App\Models\State::create(['name' => 'Second', 'region_id' => $region->id]);
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
