<?php

use App\Models\EnterpriseAiSetting;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Enterprise\EntitlementService;
use App\Services\Enterprise\WorkforceAiService;
use App\Services\Enterprise\WorkforceAiUnavailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;


function workforceAiSettings(array $overrides = []): EnterpriseAiSetting
{
    return EnterpriseAiSetting::create(array_merge([
        'id' => 1, 'enabled' => true, 'api_key' => 'sk-synthetic-never-a-real-key',
        'model' => 'gpt-4.1-mini', 'daily_requests' => 5, 'daily_tokens' => 20000,
        'installation_daily_requests' => 25, 'installation_daily_tokens' => 100000,
        'max_output_tokens' => 1000,
    ], $overrides));
}

function workforceAiResponse(): array
{
    return ['status' => 'completed', 'output' => [
        ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => 'Coverage is incomplete. Review staffing records.']]],
    ], 'usage' => ['total_tokens' => 300]];
}

test('AI uses a fixed provider endpoint with scoped aggregate input and encrypted credentials', function () {
    config(['campaign.deployment_mode' => 'managed']);
    $settings = workforceAiSettings();
    $viewer = User::factory()->create(['access_level' => 'superadmin']);
    $member = User::factory()->create(['firstname' => 'PrivatePerson', 'email' => 'private@example.test', 'phone' => '+2348123456789']);
    $entitlements = $this->mock(EntitlementService::class);
    $entitlements->shouldReceive('active')->with('sms_messaging')->andReturn(false);
    $entitlements->shouldReceive('cached')->andReturn(null);
    $entitlements->shouldReceive('active')->with('advanced_reports', true)->once()->andReturn(true);
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(workforceAiResponse())]);
    $text = app(WorkforceAiService::class)->generate($viewer, []);
    expect($text)->toContain('Coverage is incomplete')
        ->and(DB::table('enterprise_ai_settings')->value('api_key'))->not->toContain('sk-synthetic')
        ->and($settings->toArray())->not->toHaveKey('api_key');
    Http::assertSent(function ($request) use ($member) {
        return $request->url() === 'https://api.openai.com/v1/responses'
            && $request['store'] === false && $request['max_output_tokens'] === 1000
            && str_contains($request['input'], '"members":1')
            && !str_contains($request['input'], $member->email)
            && !str_contains($request['input'], $member->phone)
            && !str_contains($request['input'], 'PrivatePerson');
    });
    $audit = DB::table('enterprise_ai_audits')->first();
    expect($audit->status)->toBe('completed')->and($audit->actual_tokens)->toBe(300);
    expect(json_encode($audit))->not->toContain('sk-synthetic')->not->toContain('private@example.test');
});

test('AI requires separate permission and fresh online authorization', function () {
    workforceAiSettings();
    $user = User::factory()->create(['access_level' => 'stateadmin']);
    Http::fake();
    try {
        app(WorkforceAiService::class)->generate($user, []);
        $this->fail('Missing AI capability accepted.');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
    $user->givePermissionTo(Permission::findOrCreate('reports.ai.generate', 'web'));
    $entitlements = $this->mock(EntitlementService::class);
    $entitlements->shouldReceive('active')->with('sms_messaging')->andReturn(false);
    $entitlements->shouldReceive('cached')->andReturn(null);
    $entitlements->shouldReceive('active')->with('advanced_reports', true)->once()->andReturn(false);
    try {
        app(WorkforceAiService::class)->generate($user, []);
        $this->fail('Offline AI authorization accepted.');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
        expect($exception->getStatusCode())->toBe(402);
    }
    Http::assertNothingSent();
});

test('AI enforces both per-user and installation-wide daily reservations', function () {
    workforceAiSettings(['daily_requests' => 1, 'installation_daily_requests' => 1]);
    $entitlements = $this->mock(EntitlementService::class);
    $entitlements->shouldReceive('active')->with('sms_messaging')->andReturn(false);
    $entitlements->shouldReceive('cached')->andReturn(null);
    $entitlements->shouldReceive('active')->with('advanced_reports', true)->andReturn(true);
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(workforceAiResponse())]);
    $first = User::factory()->create(['access_level' => 'superadmin']);
    $second = User::factory()->create(['access_level' => 'superadmin']);
    app(WorkforceAiService::class)->generate($first, []);
    foreach ([$first, $second] as $user) {
        expect(fn () => app(WorkforceAiService::class)->generate($user, []))
            ->toThrow(WorkforceAiUnavailable::class, 'daily AI usage limit');
    }
    Http::assertSentCount(1);
    expect(DB::table('enterprise_ai_usage')->where('user_id', 0)->value('requests'))->toBe(1);
    expect(DB::table('enterprise_ai_usage')->where('user_id', $second->id)->count())->toBe(0);
});

test('insufficient AI token allowance makes no provider request', function () {
    workforceAiSettings(['daily_tokens' => 1]);
    $entitlements = $this->mock(EntitlementService::class);
    $entitlements->shouldReceive('active')->with('sms_messaging')->andReturn(false);
    $entitlements->shouldReceive('cached')->andReturn(null);
    $entitlements->shouldReceive('active')->with('advanced_reports', true)->andReturn(true);
    Http::fake();
    expect(fn () => app(WorkforceAiService::class)->generate(User::factory()->create(['access_level' => 'superadmin']), []))
        ->toThrow(WorkforceAiUnavailable::class, 'daily AI usage limit');
    Http::assertNothingSent();
    expect(DB::table('enterprise_ai_audits')->count())->toBe(0);
});

test('provider failure is sanitized and retains cost reservations', function () {
    workforceAiSettings();
    $entitlements = $this->mock(EntitlementService::class);
    $entitlements->shouldReceive('active')->with('sms_messaging')->andReturn(false);
    $entitlements->shouldReceive('cached')->andReturn(null);
    $entitlements->shouldReceive('active')->with('advanced_reports', true)->andReturn(true);
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(['error' => 'sk-synthetic-never-a-real-key'], 503)]);
    $user = User::factory()->create(['access_level' => 'superadmin']);
    try {
        app(WorkforceAiService::class)->generate($user, []);
        $this->fail('Provider failure accepted.');
    } catch (WorkforceAiUnavailable $exception) {
        expect($exception->getMessage())->not->toContain('sk-synthetic')->toContain('standard reports remain available');
    }
    expect(DB::table('enterprise_ai_audits')->value('status'))->toBe('unavailable');
    expect(DB::table('enterprise_ai_usage')->where('user_id', $user->id)->value('reserved_tokens'))->toBeGreaterThan(1000);
    Http::assertSentCount(1);
});

test('AI settings hide keys and remain restricted to Super Admin', function () {
    workforceAiSettings();
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Reports', 'system_country' => 'Nigeria']);
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $entitlements = $this->mock(EntitlementService::class);
    $entitlements->shouldReceive('active')->with('sms_messaging')->andReturn(false);
    $entitlements->shouldReceive('cached')->andReturn(null);
    $entitlements->shouldReceive('active')->with('advanced_reports')->andReturn(true);
    $admin = User::factory()->create(['access_level' => 'superadmin']);
    $this->actingAs($admin)->get(route('enterprise.reports.ai.settings'))->assertOk()->assertDontSee('sk-synthetic');
    $other = User::factory()->create(['access_level' => 'stateadmin']);
    $other->givePermissionTo(Permission::findOrCreate('reports.settings.manage', 'web'));
    $this->actingAs($other)->get(route('enterprise.reports.ai.settings'))->assertForbidden();
});

test('AI report submission requires explicit aggregate-sharing consent and escapes provider text', function () {
    workforceAiSettings();
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Reports', 'system_country' => 'Nigeria']);
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $entitlements = $this->mock(EntitlementService::class);
    $entitlements->shouldReceive('active')->with('sms_messaging')->andReturn(false);
    $entitlements->shouldReceive('cached')->andReturn(null);
    $entitlements->shouldReceive('active')->with('advanced_reports')->andReturn(true);
    $entitlements->shouldReceive('active')->with('advanced_reports', true)->andReturn(true);
    $admin = User::factory()->create(['access_level' => 'superadmin']);
    $payload = workforceAiResponse();
    $payload['output'][0]['content'][0]['text'] = '<script>bad()</script>';
    Http::fake(['https://api.openai.com/v1/responses' => Http::response($payload)]);
    $this->actingAs($admin)->post(route('enterprise.reports.workforce.analyze'))->assertSessionHasErrors('share_aggregates');
    Http::assertNothingSent();
    $this->post(route('enterprise.reports.workforce.analyze'), ['share_aggregates' => '1'])->assertOk()
        ->assertSee('&lt;script&gt;bad()&lt;/script&gt;', false)->assertDontSee('<script>bad()</script>', false);
});

test('AI provider settings are saved through the interface without flashing or displaying secrets', function () {
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Reports', 'system_country' => 'Nigeria']);
    $this->withoutMiddleware([\App\Http\Middleware\CheckUserOnboarding::class]);
    $entitlements = $this->mock(EntitlementService::class);
    $entitlements->shouldReceive('active')->with('advanced_reports')->andReturn(true);
    $admin = User::factory()->create(['access_level' => 'superadmin']);
    $payload = [
        'enabled' => '1', 'api_key' => 'sk-new-synthetic-provider-key',
        'model' => 'gpt-4.1-mini', 'daily_requests' => 3, 'daily_tokens' => 20000,
        'installation_daily_requests' => 20, 'installation_daily_tokens' => 100000, 'max_output_tokens' => 1000,
    ];
    $this->actingAs($admin)->put(route('enterprise.reports.ai.settings.update'), $payload)
        ->assertRedirect(route('enterprise.reports.ai.settings'));
    expect(EnterpriseAiSetting::find(1)->api_key)->toBe('sk-new-synthetic-provider-key');
    expect(DB::table('enterprise_ai_settings')->value('api_key'))->not->toContain('sk-new-synthetic');
    expect(DB::table('enterprise_ai_audits')->value('status'))->toBe('settings_updated');
    $this->put(route('enterprise.reports.ai.settings.update'), array_merge($payload, ['api_key' => '', 'model' => 'gpt-4.1']))
        ->assertRedirect(route('enterprise.reports.ai.settings'));
    expect(EnterpriseAiSetting::find(1)->api_key)->toBe('sk-new-synthetic-provider-key');
    $this->put(route('enterprise.reports.ai.settings.update'), array_merge($payload, ['model' => 'invalid/model']))
        ->assertSessionHasErrors('model')->assertSessionMissing('_old_input.api_key');
});

test('AI transmits only the current geographic administrators aggregate records', function () {
    config(['campaign.deployment_mode' => 'managed']);
    workforceAiSettings();
    $country = \App\Models\Country::create(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'name' => 'Nigeria']);
    $region = \App\Models\Region::create(['name' => 'Fixture Region', 'country_id' => $country->id]);
    $allowed = \App\Models\State::create(['name' => 'Authorized State', 'region_id' => $region->id]);
    $other = \App\Models\State::create(['name' => 'Confidential Other State', 'region_id' => $region->id]);
    $user = User::factory()->create(['access_level' => 'stateadmin', 'state_id' => $allowed->id]);
    $user->givePermissionTo(Permission::findOrCreate('reports.ai.generate', 'web'));
    User::factory()->create(['state_id' => $other->id]);
    $this->mock(EntitlementService::class)->shouldReceive('active')->with('advanced_reports', true)->andReturn(true);
    Http::fake(['https://api.openai.com/v1/responses' => Http::response(workforceAiResponse())]);
    app(WorkforceAiService::class)->generate($user, []);
    Http::assertSent(fn ($request) => str_contains($request['input'], 'Authorized State')
        && !str_contains($request['input'], 'Confidential Other State')
        && str_contains($request['input'], '"members":1'));
});
