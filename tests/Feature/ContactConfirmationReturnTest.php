<?php

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DeploymentRegistrationService;
use App\Services\Updates\AutomaticUpdateFlow;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['app.url' => 'https://campaign.example.test', 'campaign.portal_api_base' => 'https://portal.test/api/v2']);
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Return Test', 'system_country' => 'Nigeria']);
    $this->returnIdentity = ['registered' => true, 'id' => (string) Str::uuid(), 'token' => str_repeat('a', 64),
        'domain' => 'campaign.example.test', 'contact_email' => 'owner@example.test'];
    File::ensureDirectoryExists(storage_path('app/private'));
    File::put(storage_path('app/private/deployment-registration.json'), Crypt::encryptString(json_encode($this->returnIdentity)));
});

afterEach(fn () => File::delete(storage_path('app/private/deployment-registration.json')));

test('contact return requires normal login and stores the exact intended destination', function () {
    Http::fake();
    $destination = route('superadmin.updates.contact-confirmed');
    $this->get($destination)->assertRedirect(route('login'))->assertSessionHas('url.intended', $destination);
    Http::assertNothingSent();
});

test('normal Super Admin login resumes the confirmation screen only for its fixed intended URL', function () {
    $admin = User::factory()->create(['access_level' => 'superadmin', 'password' => bcrypt('test-password')]);
    $destination = route('superadmin.updates.contact-confirmed');
    $this->withSession(['url.intended' => $destination])->post(route('login'), [
        'email' => $admin->email, 'password' => 'test-password',
    ])->assertRedirect($destination);
});

test('every other access level is denied before any Portal request', function (string $level) {
    Http::fake();
    $this->actingAs(User::factory()->create(['access_level' => $level]))
        ->get(route('superadmin.updates.contact-confirmed'))->assertForbidden();
    Http::assertNothingSent();
})->with(['nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user']);

test('confirmation rechecks exact identity then renders review without creating an update intent', function () {
    $identity = $this->returnIdentity;
    Http::fake(['portal.test/*' => Http::response(['registered' => true, 'installation_id' => $identity['id'],
        'domain' => $identity['domain'], 'contact_email' => $identity['contact_email'], 'contact_verified' => true, 'domain_verified' => true])]);
    $this->mock(AutomaticUpdateFlow::class)->shouldReceive('screen')->once()->with(true)
        ->andReturn(['status' => [], 'entitlement' => null, 'issue' => null]);
    $this->actingAs(User::factory()->create(['access_level' => 'superadmin']))
        ->get(route('superadmin.updates.contact-confirmed'))->assertOk()->assertSee('Official Updates')
        ->assertDontSee($identity['token'])->assertSessionMissing('campaign_update_intent');
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && str_ends_with($request->url(), '/'.$identity['id'].'/registration-status')
        && $request->hasHeader('Authorization', 'Bearer '.$identity['token']));
});

test('confirmation fails closed for outage malformed data mismatched domain or missing verification', function (array $changes, int $status) {
    $identity = $this->returnIdentity;
    $payload = array_merge(['registered' => true, 'installation_id' => $identity['id'], 'domain' => $identity['domain'],
        'contact_email' => $identity['contact_email'], 'contact_verified' => true, 'domain_verified' => true], $changes);
    Http::fake(['portal.test/*' => Http::response($payload, $status)]);
    $this->mock(AutomaticUpdateFlow::class)->shouldNotReceive('screen');
    $this->actingAs(User::factory()->create(['access_level' => 'superadmin']))
        ->get(route('superadmin.updates.contact-confirmed'))->assertRedirect(route('superadmin.updates'))->assertSessionHas('update_error');
    expect(app(DeploymentRegistrationService::class)->summary()['contact_verified'])->toBeFalse();
})->with([
    'outage' => [[], 503], 'different installation' => [['installation_id' => 'other'], 200],
    'domain changed' => [['domain' => 'other.example'], 200], 'unverified domain' => [['domain_verified' => false], 200],
    'unverified contact' => [['contact_verified' => false], 200], 'invalid boolean' => [['domain_verified' => 'true'], 200],
]);

test('domain challenge does not disclose the deployment credential and binds to this domain', function () {
    $service = app(DeploymentRegistrationService::class);
    $nonce = str_repeat('b', 64);
    $expected = hash_hmac('sha256', "campaign-manager-domain-v1\n".$this->returnIdentity['id']."\ncampaign.example.test\n".$nonce,
        hash('sha256', $this->returnIdentity['token']));
    $this->get('https://campaign.example.test/.well-known/campaign-manager/installation-binding?challenge='.$nonce)
        ->assertOk()->assertExactJson(['binding' => $expected])->assertDontSee($this->returnIdentity['token']);
    config(['app.url' => 'https://changed.example.test']);
    expect(fn () => $service->domainBinding($nonce))->toThrow(RuntimeException::class);
});
