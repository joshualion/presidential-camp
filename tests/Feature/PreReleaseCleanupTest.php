<?php

use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\Religion;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ModuleGateService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

test('incomplete release modules are disabled by default', function () {
    config(['campaign.deployment_mode' => 'managed']);

    $gate = app(ModuleGateService::class);

    expect($gate->disabled('sms'))->toBeTrue()
        ->and($gate->disabled('bulk_sms'))->toBeTrue()
        ->and($gate->disabled('finance'))->toBeTrue()
        ->and($gate->disabled('donations'))->toBeTrue()
        ->and($gate->disabled('campaign_store'))->toBeTrue()
        ->and($gate->disabled('sponsored_placements'))->toBeTrue();
});

test('backend menu templates do not render sms navigation links', function () {
    $paths = [
        resource_path('views/backend/template/superadminmenu.blade.php'),
        resource_path('views/backend/template/nationalAdminMenu.blade.php'),
        resource_path('views/backend/template/regionalAdminMenu.blade.php'),
        resource_path('views/backend/template/stateAdminMenu.blade.php'),
        resource_path('views/backend/template/lgaAdminMenu.blade.php'),
        resource_path('views/backend/template/wardAdminMenu.blade.php'),
        resource_path('views/backend/template/puAdminMenu.blade.php'),
    ];

    foreach ($paths as $path) {
        $contents = File::get($path);

        expect($contents)
            ->not->toContain('bulksms', basename($path).' still links to Bulk SMS.')
            ->not->toContain('Send SMS', basename($path).' still renders Send SMS.')
            ->not->toContain('SMS Settings', basename($path).' still renders SMS Settings.');
    }
});

test('direct sms routes return a controlled forbidden response while sms is unavailable', function () {
    config(['campaign.deployment_mode' => 'managed']);

    Country::query()->create(['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    Religion::query()->create(['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'Christianity']);
    AgeGrade::query()->create(['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'Adult']);

    $user = User::factory()->create([
        'access_level' => 'superadmin',
        'status' => 'active',
        'requires_update' => false,
        'country_id' => 1,
        'region_id' => null,
        'state_id' => null,
        'lga_id' => null,
        'ward_id' => null,
        'polling_unit_id' => null,
        'religion_id' => 1,
        'age_grade_id' => 1,
    ]);

    $this->withoutMiddleware()
        ->actingAs($user)
        ->get(route('superadmin.bulksms.send'))
        ->assertForbidden();

    $this->withoutMiddleware()
        ->actingAs($user)
        ->get(route('superadmin.bulksms.setting'))
        ->assertForbidden();
});

test('direct campaign store routes return a controlled forbidden response while store is unavailable', function () {
    config(['campaign.deployment_mode' => 'managed']);

    Country::query()->create(['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'Nigeria']);
    Religion::query()->create(['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'Christianity']);
    AgeGrade::query()->create(['id' => 1, 'uuid' => (string) Str::uuid(), 'name' => 'Adult']);

    $user = User::factory()->create([
        'access_level' => 'superadmin',
        'status' => 'active',
        'requires_update' => false,
        'country_id' => 1,
        'region_id' => null,
        'state_id' => null,
        'lga_id' => null,
        'ward_id' => null,
        'polling_unit_id' => null,
        'religion_id' => 1,
        'age_grade_id' => 1,
    ]);

    $this->withoutMiddleware()
        ->actingAs($user)
        ->get(route('superadmin.products'))
        ->assertForbidden();
});

test('community sidebar renders only safe pre-release widgets', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);

    $settings = SystemSetting::query()->updateOrCreate(['id' => 1], [
        'package' => 'governorship',
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'frontend_community' => 1,
        'frontend_registration' => 0,
        'require_bank_details' => 0,
        'enable_local_storage' => 1,
        'enable_s3_storage' => 0,
    ]);

    $html = view('frontend.right-sidebar', [
        'SystemSetting' => $settings,
    ])->render();

    expect($html)->toContain('Election Watch')
        ->and($html)->toContain('My Campaign Scope')
        ->and($html)->toContain('Community Guidelines')
        ->and($html)->not->toContain('Donate')
        ->and($html)->not->toContain('Sponsored')
        ->and($html)->not->toContain('Campaign Store')
        ->and($html)->not->toContain('Wallet')
        ->and($html)->not->toContain('Finance');
});
