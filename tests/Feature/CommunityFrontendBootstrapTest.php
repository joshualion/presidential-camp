<?php

use App\Models\LocalLicense;
use App\Models\SystemSetting;
use App\Services\CommunityRealtimeService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

beforeEach(function () {
    config([
        'app.url' => 'http://localhost/senate/public',
        'campaign.deployment_mode' => 'self_hosted',
        'broadcasting.default' => 'log',
    ]);
    URL::forceRootUrl('http://localhost/senate/public');

    SystemSetting::query()->updateOrCreate(['id' => 1], [
        'package' => 'governorship',
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'frontend_community' => 1,
        'frontend_registration' => 0,
        'require_bank_details' => 0,
        'enable_local_storage' => 1,
        'enable_s3_storage' => 0,
    ]);

    $license = LocalLicense::query()->create([
        'license_key' => 'CM-COMMUNITY-BOOTSTRAP-'.Str::upper(Str::random(6)),
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
    ]);

    $license->scope()->create([
        'scope_type' => 'state',
        'scope_name' => 'Cross River',
        'state_id' => 9,
        'state_name' => 'Cross River',
    ]);

    $license->modules()->createMany([
        ['module_key' => 'members', 'enabled' => true],
        ['module_key' => 'community', 'enabled' => true],
    ]);
});

test('community frontend bootstrap exposes base path safe routes and media urls', function () {
    $html = view('frontend.partials.campaign-manager-bootstrap')->render();

    expect($html)->toContain('http:\/\/localhost\/senate\/public')
        ->and($html)->toContain('http:\/\/localhost\/senate\/public\/community\/posts')
        ->and($html)->toContain('http:\/\/localhost\/senate\/public\/posts\/__POST__\/comments')
        ->and($html)->toContain('http:\/\/localhost\/senate\/public\/uploads\/community\/photos')
        ->and($html)->not->toContain('"\/community\/posts"');
});

test('community polling intervals are conservative for self hosted mode', function () {
    $intervals = app(CommunityRealtimeService::class)->pollingIntervals();

    expect($intervals['notifications'])->toBeGreaterThanOrEqual(30000)
        ->and($intervals['feed'])->toBeGreaterThanOrEqual(30000)
        ->and($intervals['message_thread'])->toBeGreaterThanOrEqual(5000)
        ->and($intervals['conversation_list'])->toBeGreaterThanOrEqual(15000)
        ->and($intervals['message_badge'])->toBeGreaterThanOrEqual(30000);
});

test('community vue components no longer use root relative api calls', function () {
    $files = collect(File::files(resource_path('js/components')))
        ->filter(fn (SplFileInfo $file) => $file->getExtension() === 'vue');

    foreach ($files as $file) {
        $contents = File::get($file->getPathname());

        expect($contents)
            ->not->toContain("axios.get('/")
            ->not->toContain("axios.post('/")
            ->not->toContain("axios.put('/")
            ->not->toContain("axios.delete('/")
            ->not->toContain("fetch('/");
    }
});
