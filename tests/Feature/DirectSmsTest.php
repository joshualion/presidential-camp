<?php

use App\Jobs\SendDirectSmsBatch;
use App\Models\Country;
use App\Models\Region;
use App\Models\SmsDirectBatch;
use App\Models\SmsProviderAccount;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Sms\Direct\DirectSmsAccess;
use App\Services\Sms\Direct\DirectSmsProvider;
use App\Services\Sms\Direct\DirectSmsService;
use App\Services\Sms\Direct\SmsMessageEstimate;
use App\Services\Sms\PortalSmsClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $entitlements = $this->mock(\App\Services\Enterprise\EntitlementService::class);
    $entitlements->shouldReceive('active')->with('sms_messaging')->andReturn(true);
    $entitlements->shouldReceive('cached')->andReturn(null);
    config(['campaign.deployment_mode' => 'self_hosted', 'queue.default' => 'database', 'cache.default' => 'array']);
    SystemSetting::create(['package' => 'presidential', 'direct_sms_enabled' => true, 'direct_sms_levels' => ['nationaladmin', 'regionaladmin', 'stateadmin', 'lgaadmin']]);
    Country::firstOrCreate(['id' => 1], ['uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    Region::firstOrCreate(['id' => 1], ['name' => 'South South', 'country_id' => 1]);
    foreach ([9 => 'Cross River', 3 => 'Akwa Ibom'] as $id => $name) {
        State::firstOrCreate(['id' => $id], ['name' => $name, 'region_id' => 1]);
    }
    Http::preventStrayRequests();
});
function directAdmin(string $level = 'stateadmin', ?int $state = 9): User
{
    return User::factory()->create(['access_level' => $level, 'status' => 'active', 'state_id' => $state, 'region_id' => 1]);
}
function directAccount(User $user, string $provider = 'termii'): SmsProviderAccount
{
    return SmsProviderAccount::create(['user_id' => $user->id, 'provider' => $provider, 'base_url' => $provider === 'termii' ? 'https://api.ng.termii.com' : 'https://www.bulksmsnigeria.com/api/v2', 'api_key' => 'test-provider-secret', 'sender_id' => 'Campaign', 'is_active' => true]);
}
function directBatch(User $sender, SmsProviderAccount $account, array $states = [9]): SmsDirectBatch
{
    $b = SmsDirectBatch::create(['uuid' => (string) Str::uuid(), 'account_id' => $account->id, 'created_by' => $sender->id, 'provider' => $account->provider, 'sender_id' => 'Campaign', 'message' => 'Meeting notice', 'source' => 'members', 'status' => 'queued', 'recipient_count' => count($states), 'confirmed_at' => now()]);
    foreach ($states as $i => $state) {
        $b->messages()->create(['phone' => '+23480123456'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'state_id' => $state, 'region_id' => 1, 'status' => 'queued']);
    }

    return $b;
}
test('provider access is delegated by level with individual override and never grants member access', function () {
    $access = app(DirectSmsAccess::class);
    $state = directAdmin();
    expect($access->enabled($state))->toBeTrue();
    DB::table('sms_provider_grants')->insert(['user_id' => $state->id, 'enabled' => false]);
    expect($access->enabled($state))->toBeFalse();
    expect($access->enabled(directAdmin('user')))->toBeFalse();
    SystemSetting::first()->update(['direct_sms_enabled' => false]);
    expect($access->canSend(directAdmin('superadmin')))->toBeFalse();
    expect($access->enabled(directAdmin('superadmin')))->toBeTrue();
});
test('provider keys are encrypted hidden and never available through another administrator account', function () {
    $one = directAdmin();
    $two = directAdmin('stateadmin', 3);
    $a = directAccount($one);
    expect($a->getRawOriginal('api_key'))->not->toBe('test-provider-secret')->and($a->toArray())->not->toHaveKey('api_key');
    expect(app(DirectSmsService::class)->account($two))->toBeNull();
});
test('provider endpoint validation rejects redirects private targets and credential exfiltration', function () {
    $p = app(DirectSmsProvider::class);
    foreach (['http://api.ng.termii.com', 'https://127.0.0.1', 'https://api.ng.termii.com.evil.test', 'https://api.ng.termii.com@evil.test', 'https://api.ng.termii.com:444', 'https://api.ng.termii.com?token=1'] as $url) {
        expect(fn () => $p->validateEndpoint('termii', $url))->toThrow(RuntimeException::class);
    }
    expect($p->validateEndpoint('termii', 'https://api.ng.termii.com/api'))->toBe('https://api.ng.termii.com');
});
test('cross state audits stay separated including partial national batches and explicit oversight', function () {
    $national = directAdmin('superadmin', null);
    $cr = directAdmin();
    $ak = directAdmin('stateadmin', 3);
    $batch = directBatch($national, directAccount($national), [9, 3]);
    $access = app(DirectSmsAccess::class);
    expect($access->messages($cr)->count())->toBe(1)->and($access->messages($ak)->count())->toBe(1)->and($access->messages($national)->count())->toBe(2)->and($access->fullBatch($cr, $batch))->toBeFalse();
    DB::table('sms_provider_grants')->insert(['user_id' => $cr->id, 'enabled' => true, 'audit_scope' => 'national']);
    expect($access->messages($cr)->count())->toBe(2)->and($access->scope($cr))->toBe(['state', 9]);
    $ak->update(['state_id' => 9]);
    expect($batch->messages()->where('state_id', 3)->count())->toBe(1);
});
test('regional oversight includes its states but fails closed without a configured boundary', function () {
    $sender = directAdmin('superadmin', null);
    directBatch($sender, directAccount($sender), [9, 3]);
    $regional = directAdmin('regionaladmin', null);
    expect(app(DirectSmsAccess::class)->messages($regional)->count())->toBe(2);
    $regional->update(['region_id' => null]);
    expect(app(DirectSmsAccess::class)->messages($regional)->count())->toBe(0);
});
test('provider send payload uses only the selected administrator account and never calls Portal', function () {
    $cr = directAdmin();
    $a = directAccount($cr);
    Http::fake(['api.ng.termii.com/*' => Http::response(['code' => 'ok', 'message_id' => 'termii-1'])]);
    $r = app(DirectSmsProvider::class)->send($a, 'Campaign', 'Hello', ['+2348012345678']);
    expect($r['ok'])->toBeTrue()->and($r['reference'])->toBe('termii-1');
    Http::assertSent(fn ($r) => $r['api_key'] === 'test-provider-secret' && $r['to'] === ['2348012345678'] && str_ends_with($r->url(), '/sms/send/bulk'));
    Http::assertSentCount(1);
});
test('a worker submits bounded chunks only once and preserves provider accepted status', function () {
    $sender = directAdmin();
    $batch = directBatch($sender, directAccount($sender));
    Http::fake(['api.ng.termii.com/*' => Http::response(['code' => 'ok', 'message_id' => 'id-1'])]);
    $s = app(DirectSmsService::class);
    $s->process($batch->id);
    $s->process($batch->id);
    expect($batch->messages()->first()->status)->toBe('accepted');
    Http::assertSentCount(1);
});
test('unknown send outcomes and interrupted submissions are never resent automatically', function () {
    $sender = directAdmin();
    $batch = directBatch($sender, directAccount($sender));
    Http::fake(['api.ng.termii.com/*' => Http::failedConnection()]);
    $s = app(DirectSmsService::class);
    $s->process($batch->id);
    $s->process($batch->id);
    expect($batch->messages()->first()->status)->toBe('unconfirmed');
    Http::assertSentCount(1);
});
test('revocation cancels unsent work without touching another provider balance', function () {
    $sender = directAdmin();
    $batch = directBatch($sender, directAccount($sender));
    DB::table('sms_provider_grants')->insert(['user_id' => $sender->id, 'enabled' => false]);
    app(DirectSmsService::class)->process($batch->id);
    expect($batch->messages()->first()->status)->toBe('cancelled');
    Http::assertNothingSent();
});
test('provider API acceptance is distinct from authenticated delivery evidence', function () {
    $sender = directAdmin();
    $a = directAccount($sender, 'bulksmsnigeria');
    $batch = directBatch($sender, $a);
    $phone = $batch->messages()->first()->phone;
    $batch->messages()->update(['status' => 'accepted', 'provider_reference' => 'bsn-1', 'submitted_at' => now()]);
    Http::fake(['www.bulksmsnigeria.com/*' => Http::response(['status' => 'success', 'data' => ['reports' => [['recipient' => ltrim($phone, '+'), 'delivery_status' => 'delivrd']]]])]);
    app(DirectSmsService::class)->sync($batch);
    expect($batch->messages()->first()->status)->toBe('delivered');
});
test('scoped pasted numbers cannot introduce unknown contacts outside the stored audience', function () {
    $u = directAdmin();
    expect(fn () => app(DirectSmsService::class)->audience($u, ['recipient_source' => 'manual', 'manual_recipients' => '08012345678']))->toThrow(ValidationException::class);
});
test('SMS stays independent of hosted licence modules and hidden hosted integration performs no HTTP', function () {
    $u = directAdmin();
    expect(app(DirectSmsAccess::class)->enabled($u))->toBeTrue()->and(config('direct_sms.portal_visible'))->toBeFalse();
    $r = app(PortalSmsClient::class)->getWallet();
    expect($r['ok'])->toBeFalse();
    Http::assertNothingSent();
});

test('settings routes hide secrets and reserve delegation for Super Admin', function () {
    $u = directAdmin();
    directAccount($u);
    $this->actingAs($u)->get(route('stateadmin.direct-sms.settings'))->assertOk()->assertDontSee('test-provider-secret')->assertDontSee('Portal SMS')->assertSee('My SMS provider');
    $this->post(route('stateadmin.direct-sms.policy'), ['enabled' => true])->assertForbidden();
    $this->post(route('stateadmin.direct-sms.grant', $u), ['enabled' => 'allow', 'audit_scope' => 'national'])->assertForbidden();
    $this->get(route('superadmin.direct-sms.settings'))->assertForbidden();
});
test('report routes hide other states and partial batch content', function () {
    $n = directAdmin('superadmin', null);
    $cr = directAdmin();
    $a = directAccount($n);
    $mixed = directBatch($n, $a, [9, 3]);
    $mixed->update(['message' => 'Private national message body']);
    $other = directBatch($n, $a, [3]);
    $this->actingAs($cr)->get(route('stateadmin.direct-sms.show', $mixed))->assertOk()->assertSee('only recipients within your oversight')->assertDontSee('Private national message body')->assertDontSee($mixed->messages()->where('state_id', 3)->first()->phone);
    $this->get(route('stateadmin.direct-sms.show', $other))->assertNotFound();
});
test('campaign boundary caps Super Admin and national audit grants', function () {
    SystemSetting::first()->update(['package' => 'governorship', 'campaign_scope_type' => 'state', 'campaign_state_id' => 9]);
    $n = directAdmin('superadmin', null);
    directBatch($n, directAccount($n), [9, 3]);
    $u = directAdmin('stateadmin', 3);
    DB::table('sms_provider_grants')->insert(['user_id' => $u->id, 'enabled' => true, 'audit_scope' => 'national']);
    $a = app(DirectSmsAccess::class);
    expect($a->messages($n)->count())->toBe(1)->and($a->messages($u)->count())->toBe(1);
    SystemSetting::first()->update(['campaign_scope_type' => 'unknown']);
    expect($a->messages($n)->count())->toBe(0);
});
test('manual contacts are normalized scoped deduplicated and filtered for opt outs', function () {
    $u = directAdmin();
    directAdmin('user')->update(['phone' => '08012345678']);
    $svc = app(DirectSmsService::class);
    $selection = ['recipient_source' => 'manual', 'manual_recipients' => '08012345678,2348012345678'];
    $r = $svc->audience($u, $selection);
    expect($r['valid'])->toBe(1)->and($r['duplicates'])->toBe(1)->and($r['recipients'][0]['state_id'])->toBe(9);
    DB::table('sms_direct_suppressions')->insert(['owner_key' => 'campaign', 'phone' => '+2348012345678']);
    $r = $svc->audience($u, $selection);
    expect($r['valid'])->toBe(0)->and($r['suppressed'])->toBe(1);
    directAdmin('user', 3)->update(['phone' => '08099999999']);
    expect(fn () => $svc->audience($u, ['recipient_source' => 'manual', 'manual_recipients' => '08099999999']))->toThrow(ValidationException::class);
});
test('opt outs added after confirmation suppress queued delivery', function () {
    $u = directAdmin();
    $b = directBatch($u, directAccount($u));
    DB::table('sms_direct_suppressions')->insert(['owner_key' => 'user:'.$u->id, 'phone' => $b->messages()->first()->phone]);
    app(DirectSmsService::class)->process($b->id);
    expect($b->messages()->first()->status)->toBe('suppressed')->and($b->fresh()->status)->toBe('submitted');
    Http::assertNothingSent();
});
test('account replacement and future schedules prevent sending', function () {
    $u = directAdmin();
    $a = directAccount($u);
    $b = directBatch($u, $a);
    $b->update(['scheduled_at' => now()->addHour()]);
    $s = app(DirectSmsService::class);
    $s->process($b->id);
    expect($b->messages()->first()->status)->toBe('queued');
    $b->update(['scheduled_at' => null]);
    $a->update(['is_active' => false]);
    $s->process($b->id);
    expect($b->messages()->first()->status)->toBe('cancelled');
    Http::assertNothingSent();
});
test('bounded chunks never resend interrupted recipients or clear unresolved status', function () {
    $u = directAdmin();
    $b = directBatch($u, directAccount($u), array_fill(0, 55, 9));
    $b->messages()->orderBy('id')->first()->update(['status' => 'submitting', 'submitted_at' => now()->subMinutes(6)]);
    Http::fake(['api.ng.termii.com/*' => Http::response(['code' => 'ok', 'message_id' => 'chunk-1'])]);
    $s = app(DirectSmsService::class);
    $s->process($b->id);
    expect($b->messages()->where('status', 'accepted')->count())->toBe(50)->and($b->messages()->where('status', 'queued')->count())->toBe(4);
    $s->process($b->id);
    $s->process($b->id);
    expect($b->fresh()->status)->toBe('reconciliation_required')->and($b->messages()->where('status', 'unconfirmed')->count())->toBe(1);
    Http::assertSentCount(2);
});
test('BulkSMSNigeria uses bearer credentials and its documented payload', function () {
    $a = directAccount(directAdmin(), 'bulksmsnigeria');
    Http::fake(['www.bulksmsnigeria.com/*' => Http::response(['status' => 'success', 'data' => ['message_id' => 'bulk-1']])]);
    $r = app(DirectSmsProvider::class)->send($a, 'Campaign', 'Hello', ['+2348012345678']);
    expect($r['reference'])->toBe('bulk-1');
    Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer test-provider-secret') && $r['to'] === '2348012345678' && $r['body'] === 'Hello' && ! isset($r['api_key']));
});
test('Termii array reports change matching recipients only and leave pending accepted', function () {
    $u = directAdmin();
    $b = directBatch($u, directAccount($u), [9, 9]);
    $rows = $b->messages()->get();
    $b->messages()->update(['status' => 'accepted', 'provider_reference' => 'ref-1', 'submitted_at' => now()]);
    Http::fake(['api.ng.termii.com/*' => Http::response([['receiver' => $rows[0]->phone, 'status' => 'delivered'], ['receiver' => $rows[1]->phone, 'status' => 'pending'], ['receiver' => '+2348099999999', 'status' => 'delivered']])]);
    app(DirectSmsService::class)->sync($b);
    expect($rows[0]->fresh()->status)->toBe('delivered')->and($rows[1]->fresh()->status)->toBe('accepted')->and($rows[1]->fresh()->last_synced_at)->not->toBeNull();
});
test('server errors and missing send references remain unconfirmed', function () {
    $a = directAccount(directAdmin());
    $p = app(DirectSmsProvider::class);
    Http::fake(['*' => Http::sequence()->push(['error' => 'internal'], 503)->push(['code' => 'ok'])]);
    expect($p->send($a, 'Campaign', 'Hello', ['+2348012345678'])['uncertain'])->toBeTrue();
    expect($p->send($a, 'Campaign', 'Hello', ['+2348012345678'])['code'])->toBe('provider_reference_missing');
});
test('message estimates count GSM extensions and Unicode units', function () {
    $class = SmsMessageEstimate::class;
    expect($class::for(str_repeat('a', 160))['segments'])->toBe(1)->and($class::for(str_repeat('a', 161))['segments'])->toBe(2)->and($class::for(str_repeat('^', 81))['segments'])->toBe(2)->and($class::for(str_repeat('漢', 71)))->toMatchArray(['encoding' => 'Unicode', 'segments' => 2])->and($class::for('😀')['units'])->toBe(2);
});
test('preview requires consent and cannot be replayed into duplicate sends', function () {
    Queue::fake();
    $u = directAdmin();
    directAccount($u);
    directAdmin('user')->update(['phone' => '08012345678']);
    $this->actingAs($u);
    $preview = $this->post(route('stateadmin.direct-sms.preview'), ['message' => 'Community meeting', 'recipient_source' => 'manual', 'manual_recipients' => '08012345678']);
    $preview->assertOk();
    $token = $preview->viewData('confirmation')['token'];
    $this->post(route('stateadmin.direct-sms.send'), ['confirmation_token' => $token])->assertSessionHasErrors('send_consent');
    $this->post(route('stateadmin.direct-sms.send'), ['confirmation_token' => $token, 'send_consent' => 1])->assertRedirect();
    expect(SmsDirectBatch::count())->toBe(1);
    Queue::assertPushed(SendDirectSmsBatch::class, 1);
    $this->post(route('stateadmin.direct-sms.send'), ['confirmation_token' => $token, 'send_consent' => 1])->assertStatus(422);
    expect(SmsDirectBatch::count())->toBe(1);
});
test('changed audience requires a fresh preview', function () {
    $u = directAdmin();
    directAccount($u);
    directAdmin('user')->update(['phone' => '08012345678']);
    $this->actingAs($u);
    $preview = $this->post(route('stateadmin.direct-sms.preview'), ['message' => 'Hello', 'recipient_source' => 'manual', 'manual_recipients' => '08012345678']);
    $token = $preview->viewData('confirmation')['token'];
    DB::table('sms_direct_suppressions')->insert(['owner_key' => 'campaign', 'phone' => '+2348012345678']);
    $this->post(route('stateadmin.direct-sms.send'), ['confirmation_token' => $token, 'send_consent' => 1])->assertStatus(409);
    expect(SmsDirectBatch::count())->toBe(0);
});
test('oversight geography is named and accessible only to Super Admin', function () {
    $u = directAdmin('superadmin');
    directAdmin();
    $this->actingAs($u)->get(route('superadmin.direct-sms.settings'))->assertOk()->assertDontSee('boundary ID')->assertSee('Search by name');
    $this->getJson(route('superadmin.direct-sms.boundaries', ['type' => 'state', 'search' => 'Cross']))->assertOk()->assertJsonPath('data.0.name', 'Cross River · South South');
    $this->actingAs(directAdmin())->getJson(route('stateadmin.direct-sms.boundaries', ['type' => 'state']))->assertForbidden();
});

test('a sender moved to another state cannot deliver previously queued state contacts', function () {
    $u = directAdmin();
    $b = directBatch($u, directAccount($u));
    $u->update(['state_id' => 3]);
    app(DirectSmsService::class)->process($b->id);
    expect($b->messages()->first()->status)->toBe('cancelled')->and($b->messages()->first()->diagnostic_code)->toBe('scope_changed');
    Http::assertNothingSent();
});
test('customer SMS migration preserves existing records and can safely resume its additive setup', function () {
    $u = directAdmin();
    $a = directAccount($u);
    $b = directBatch($u, $a);
    $migration = require database_path('migrations/2026_10_19_000002_create_direct_sms_tables.php');
    $migration->up();
    expect($u->fresh()->firstname)->toBe($u->firstname)->and($a->fresh()->api_key)->toBe('test-provider-secret')->and($b->messages()->count())->toBe(1);
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
});
test('provider edits create only the current admins encrypted account revision', function () {
    $u = directAdmin();
    $other = directAdmin('stateadmin', 3);
    $own = directAccount($u);
    $otherAccount = directAccount($other);
    $this->actingAs($u)->post(route('stateadmin.direct-sms.settings.save'), ['provider' => 'termii', 'base_url' => 'https://api.ng.termii.com', 'sender_id' => 'Campaign', 'api_key' => 'new-provider-secret'])->assertRedirect();
    expect($own->fresh()->is_active)->toBeFalse()->and($otherAccount->fresh()->is_active)->toBeTrue()->and(app(DirectSmsService::class)->account($u)->api_key)->toBe('new-provider-secret')->and(app(DirectSmsService::class)->account($other)->api_key)->toBe('test-provider-secret');
});

test('provider keys are not flashed into plaintext session input after validation errors', function () {
    $u = directAdmin();
    $this->actingAs($u)->post(route('stateadmin.direct-sms.settings.save'), ['provider' => 'termii', 'base_url' => 'https://api.ng.termii.com', 'sender_id' => 'x', 'api_key' => 'do-not-flash-this-secret'])->assertSessionHasErrors('sender_id');
    expect(session()->getOldInput('api_key'))->toBeNull();
});

test('recent interrupted claims stay recoverable until they become unconfirmed without resending', function () {
    $u = directAdmin();
    $b = directBatch($u, directAccount($u));
    $b->messages()->update(['status' => 'submitting', 'submitted_at' => now()]);
    $s = app(DirectSmsService::class);
    $s->process($b->id);
    expect($b->fresh()->status)->toBe('queued');
    $this->travel(6)->minutes();
    $s->process($b->id);
    expect($b->fresh()->status)->toBe('reconciliation_required')->and($b->messages()->first()->status)->toBe('unconfirmed');
    Http::assertNothingSent();
});
test('revocation does not describe an uncertain in flight send as cancelled', function () {
    $u = directAdmin();
    $b = directBatch($u, directAccount($u));
    $b->messages()->update(['status' => 'submitting', 'submitted_at' => now()]);
    DB::table('sms_provider_grants')->insert(['user_id' => $u->id, 'enabled' => false]);
    app(DirectSmsService::class)->process($b->id);
    expect($b->fresh()->status)->toBe('reconciliation_required')->and($b->messages()->first()->status)->toBe('unconfirmed');
    Http::assertNothingSent();
});
