<?php

use App\Models\SmsProviderAccount;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DeploymentRegistrationService;
use App\Services\Enterprise\EntitlementService;
use App\Services\Enterprise\LegacySmsAccess;
use App\Services\Sms\SmsAddonAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function captureSmsLegacyFixture(): void
{
    Schema::dropIfExists('campaign_enterprise_legacy_access');
    $migration = require database_path('migrations/2026_11_04_125004_capture_legacy_sms_access.php');
    $migration->up();
}
function configuredLegacySms(): SmsProviderAccount
{
    SystemSetting::create(['package' => 'governorship', 'system_name' => 'Legacy campaign', 'system_country' => 'Nigeria', 'direct_sms_enabled' => true]);
    $owner = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    return SmsProviderAccount::create(['user_id' => $owner->id, 'provider' => 'termii', 'base_url' => 'https://api.ng.termii.com',
        'api_key' => 'legacy-provider-secret', 'sender_id' => 'Campaign', 'is_active' => true]);
}
beforeEach(function () {
    config(['app.url' => 'https://campaign.example.test']);
});

test('fresh installations remain locked even when the old rollout flag says disabled', function () {
    config(['enterprise.sms_enforcement_enabled' => false]);
    $this->mock(EntitlementService::class)->shouldReceive('active')->with('sms_messaging')->once()->andReturn(false);
    expect((new SmsAddonAccess)->allowed())->toBeFalse();
    expect(DB::table('campaign_enterprise_legacy_access')->value('eligible'))->toBe(0);
});

test('upgrade snapshots preserve configured direct SMS without changing credentials', function () {
    $account = configuredLegacySms();
    $ciphertext = $account->getRawOriginal('api_key');
    captureSmsLegacyFixture();
    expect((new LegacySmsAccess)->allowed())->toBeTrue();
    expect($account->fresh()->getRawOriginal('api_key'))->toBe($ciphertext);
    expect(DB::table('campaign_enterprise_legacy_access')->value('provider_accounts'))->toBe(1);
    $account->update(['is_active' => false]);
    expect((new LegacySmsAccess)->allowed())->toBeTrue();
});

test('provider configuration after a fresh installation never creates legacy access on migration retry', function () {
    configuredLegacySms();
    $migration = require database_path('migrations/2026_11_04_125004_capture_legacy_sms_access.php');
    $migration->up(); $migration->up();
    expect((new LegacySmsAccess)->allowed())->toBeFalse();
    expect(DB::table('campaign_enterprise_legacy_access')->count())->toBe(1);
});

test('disabled and empty provider configurations do not qualify for grandfathering', function () {
    $account = configuredLegacySms();
    $account->update(['is_active' => false]);
    captureSmsLegacyFixture();
    expect((new LegacySmsAccess)->allowed())->toBeFalse();
    $account->update(['is_active' => true]);
    DB::table('sms_provider_accounts')->where('id', $account->id)->update(['api_key' => '']);
    captureSmsLegacyFixture();
    expect((new LegacySmsAccess)->allowed())->toBeFalse();
});

test('legacy grant cannot move to another domain or campaign package', function () {
    configuredLegacySms(); captureSmsLegacyFixture();
    config(['app.url' => 'https://another.example.test']);
    expect((new LegacySmsAccess)->allowed())->toBeFalse();
    config(['app.url' => 'https://campaign.example.test']);
    SystemSetting::query()->update(['package' => 'presidential']);
    expect((new LegacySmsAccess)->allowed())->toBeFalse();
});

test('a registered legacy installation remains bound to that registration identity', function () {
    configuredLegacySms();
    $registration = $this->mock(DeploymentRegistrationService::class);
    $registration->shouldReceive('domain')->andReturn('campaign.example.test');
    $registration->shouldReceive('summary')->once()->andReturn(['registered' => true, 'id' => 'original-installation']);
    captureSmsLegacyFixture();
    $registration->shouldReceive('summary')->andReturn(['registered' => true, 'id' => 'copied-installation']);
    expect((new LegacySmsAccess)->allowed())->toBeFalse();
});

test('SMS access is rechecked rather than retaining a prior grant in a long running process', function () {
    $mock = $this->mock(EntitlementService::class);
    $mock->shouldReceive('active')->with('sms_messaging')->once()->ordered()->andReturn(true);
    $mock->shouldReceive('active')->with('sms_messaging')->once()->ordered()->andReturn(false);
    $access = new SmsAddonAccess;
    expect($access->allowed())->toBeTrue();
    expect($access->allowed())->toBeFalse();
});
