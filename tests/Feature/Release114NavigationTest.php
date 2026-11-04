<?php

use App\Models\Country;
use App\Models\Region;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AnnouncementAudienceService;
use App\Services\OfficialUpdateService;
use App\Services\Sms\Direct\DirectSmsAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $entitlements = $this->mock(\App\Services\Enterprise\EntitlementService::class);
    $entitlements->shouldReceive('active')->with('sms_messaging')->andReturn(true);
    $entitlements->shouldReceive('cached')->andReturn(null);
    config(['direct_sms.portal_visible' => false, 'campaign.deployment_mode' => 'self_hosted']);
    SystemSetting::create(['package' => 'presidential', 'direct_sms_enabled' => true, 'direct_sms_levels' => DirectSmsAccess::LEVELS]);
    Country::firstOrCreate(['id' => 1], ['uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    Region::firstOrCreate(['id' => 1], ['uuid' => (string) Str::uuid(), 'name' => 'South South', 'country_id' => 1]);
    State::firstOrCreate(['id' => 1], ['uuid' => (string) Str::uuid(), 'name' => 'Cross River', 'region_id' => 1]);
    $this->mock(AnnouncementAudienceService::class, fn ($mock) => $mock->shouldReceive('canPublish')->andReturn(true));
    $this->mock(OfficialUpdateService::class, fn ($mock) => $mock->shouldReceive('status')->andReturn([]));
});

test('SMS and announcements nest under Messaging while provider settings remain separate', function (string $level, string $menu) {
    $actor = User::factory()->create(['access_level' => $level, 'status' => 'active', 'region_id' => 1, 'state_id' => 1]);
    $this->actingAs($actor);
    $html = view('backend.template.'.$menu, ['profileData' => $actor])->render();
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $messaging = "//a[p[normalize-space(.)='Messaging']]/following-sibling::ul[1]";
    expect($xpath->query($messaging."//a[contains(@href,'/sms/direct') and not(contains(@href,'settings'))]")->length)->toBe(1);
    expect($xpath->query($messaging."//a[p[normalize-space(.)='Announcements']]")->length)->toBe(1);
    expect($xpath->query($messaging."//a[contains(@href,'/sms/direct/settings')]")->length)->toBe(0);
    expect($xpath->query("//a[p[normalize-space(.)='Settings']]/following-sibling::ul[1]//a[contains(@href,'/sms/direct/settings')]")->length)->toBe(1);
    expect($html)->toContain('Messenger')->not->toContain('Internal Communication', 'superadmin/community/forum');

    if ($level !== 'superadmin') {
        DB::table('sms_provider_grants')->insert(['user_id' => $actor->id, 'enabled' => false]);
        $html = view('backend.template.'.$menu, ['profileData' => $actor])->render();
        expect($html)->not->toContain('/sms/direct/settings', '<p>Settings');
        $this->get(route($level.'.direct-sms.settings'))->assertForbidden();
    }
})->with([
    ['superadmin', 'superadminmenu'], ['nationaladmin', 'nationalAdminMenu'],
    ['regionaladmin', 'regionalAdminMenu'], ['stateadmin', 'stateAdminMenu'],
    ['senatorialadmin', 'senatorialAdminMenu'], ['federaladmin', 'federalAdminMenu'],
    ['lgaadmin', 'lgaAdminMenu'], ['wardadmin', 'wardAdminMenu'], ['puadmin', 'puAdminMenu'],
]);
