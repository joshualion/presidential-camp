<?php

use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\LocalLicense;
use App\Models\Region;
use App\Models\Religion;
use App\Models\SupportGroup;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Installer\DatabaseSetupService;
use App\Services\Installer\EnvironmentWriterService;
use App\Services\Installer\InstallerService;
use App\Services\Installer\InstallerStateStore;
use App\Services\InstallationStateService;
use App\Services\LicensedScopeQueryService;
use App\Services\ModuleGateService;
use App\Services\PackageScopeService;
use App\Services\RemoteLicenseClient;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['campaign.deployment_mode' => 'self_hosted', 'campaign.installer_enabled' => true]);
    File::ensureDirectoryExists(storage_path('app'));
    if (File::exists(storage_path('app/installed.lock'))) {
        File::delete(storage_path('app/installed.lock'));
    }
    app(InstallerStateStore::class)->clear();
});

afterEach(function () {
    if (File::exists(storage_path('app/installed.lock'))) {
        File::delete(storage_path('app/installed.lock'));
    }
    app(InstallerStateStore::class)->clear();
});

function communityCompatibilityRows(array $settings = []): void
{
    SystemSetting::query()->updateOrCreate(['id' => 1], array_merge([
        'package' => 'presidential',
        'installation_mode' => 'community',
        'campaign_scope_type' => 'national',
        'campaign_scope_name' => 'National',
        'system_name' => 'Campaign Manager',
        'system_country' => 'Nigeria',
        'frontend_community' => 0,
        'require_bank_details' => 0,
        'enable_local_storage' => 1,
        'enable_s3_storage' => 0,
        'activation_code' => null,
        'domain' => null,
        'api_token' => null,
        'activated_at' => null,
    ], $settings));

    Country::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Nigeria',
    ]);

    Region::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'South South',
        'country_id' => 1,
    ]);

    Religion::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Christianity',
    ]);

    AgeGrade::query()->updateOrCreate(['id' => 1], [
        'uuid' => (string) Str::uuid(),
        'name' => 'Adult',
    ]);
}

function communityCompatibilityUser(): User
{
    $user = User::withoutEvents(fn () => User::factory()->create([
        'firstname' => 'Community',
        'lastname' => 'Admin',
        'username' => 'community_admin',
        'email' => 'community-admin@example.test',
        'phone' => '+2348099004400',
        'password' => Hash::make('secret-password'),
        'access_level' => 'superadmin',
        'status' => 'active',
        'requires_update' => false,
        'country_id' => null,
        'region_id' => null,
        'state_id' => null,
        'lga_id' => null,
        'ward_id' => null,
        'polling_unit_id' => null,
        'religion_id' => 1,
        'age_grade_id' => 1,
    ]));

    $group = SupportGroup::query()->create(['name' => 'Community Volunteers']);
    $user->supportGroups()->sync([$group->id]);

    return $user;
}

test('installer license step accepts blank key as community setup without portal preview', function () {
    app()->instance(DatabaseSetupService::class, new class extends DatabaseSetupService {
        public function test(array $values): array
        {
            return ['ok' => false, 'message' => 'Database unavailable for validation test.'];
        }
    });

    app()->instance(RemoteLicenseClient::class, new class extends RemoteLicenseClient {
        public function preview(array $payload): array
        {
            throw new RuntimeException('Portal preview should not be called for Community setup.');
        }
    });

    $this->post(route('install.license'), ['license_key' => ''])
        ->assertRedirect(route('install.admin'));

    $state = app(InstallerStateStore::class)->all();

    expect($state['community_install'] ?? null)->toBeTrue()
        ->and(array_key_exists('license_key', $state) ? $state['license_key'] : null)->toBeNull();
});

test('installer service validation no longer requires a license preview for community setup', function () {
    app()->instance(DatabaseSetupService::class, new class extends DatabaseSetupService {
        public function test(array $values): array
        {
            return ['ok' => false, 'message' => 'Database unavailable for validation test.'];
        }
    });

    app()->instance(RemoteLicenseClient::class, new class extends RemoteLicenseClient {
        public function preview(array $payload): array
        {
            throw new RuntimeException('Portal preview should not be called for Community setup.');
        }

        public function activate(array $payload): array
        {
            throw new RuntimeException('Portal activation should not be called for Community setup.');
        }
    });

    $result = app(InstallerService::class)->run([
        'environment' => [
            'APP_NAME' => 'Campaign Manager',
            'APP_URL' => 'http://localhost',
            'APP_ENV' => 'testing',
            'APP_DEBUG' => false,
            'APP_TIMEZONE' => 'Africa/Lagos',
            'DEPLOYMENT_MODE' => 'self_hosted',
        ],
        'database' => [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'campaign_manager_test',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
        ],
        'mail' => [],
        'license_key' => null,
        'license_preview' => [],
        'campaign_scope' => [
            'package_type' => 'presidential',
            'scope_type' => 'national',
            'scope_name' => 'Nigeria',
        ],
        'campaign_geography' => [
            'scope_name' => 'Nigeria',
        ],
        'campaign_identity' => [
            'system_name' => 'Campaign Manager',
            'campaign_slogan' => null,
        ],
        'geography_setup' => [
            'method' => 'manual',
        ],
        'admin' => [
            'name' => 'Community Admin',
            'email' => 'community-installer@example.test',
            'password' => 'secret-password',
        ],
    ]);

    expect($result['ok'])->toBeFalse()
        ->and($result['stage'])->toBe('database')
        ->and($result['missing_step'] ?? null)->toBeNull()
        ->and($result['message'])->not->toContain('License preview is missing');
});

test('installed community core reaches guarded superadmin and manual geography routes without commercial license', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    File::put(storage_path('app/installed.lock'), json_encode(['installation_mode' => 'community', 'remote_validation_required' => false]));
    communityCompatibilityRows();
    $user = communityCompatibilityUser();

    Http::fake(fn () => throw new RuntimeException('Portal validation should not be called for Community runtime.'));

    $this->actingAs($user)->get('/superadmin/dashboard')->assertOk();
    $this->actingAs($user)->get(route('superadmin.location.regions'))->assertOk();

    expect(LocalLicense::query()->count())->toBe(0);
});

test('campaign scope resolves from local system settings without license records', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    communityCompatibilityRows([
        'package' => 'governorship',
        'campaign_scope_type' => 'state',
        'campaign_scope_name' => 'Cross River',
        'campaign_state_id' => 9,
    ]);

    $scope = app(PackageScopeService::class)->current();

    expect(LocalLicense::query()->count())->toBe(0)
        ->and($scope->package_type)->toBe('governorship')
        ->and($scope->scope_type)->toBe('state')
        ->and($scope->scope_name)->toBe('Cross River')
        ->and($scope->state_id)->toBe(9)
        ->and($scope->license_status)->toBeNull();
});

test('scope aware service uses configured campaign scope without requiring a license', function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    communityCompatibilityRows([
        'package' => 'governorship',
        'campaign_scope_type' => 'state',
        'campaign_scope_name' => 'Cross River',
        'campaign_state_id' => 9,
    ]);

    $service = app(LicensedScopeQueryService::class);

    expect($service->applies())->toBeTrue()
        ->and($service->payloadErrors(['state_id' => 9]))->toBe([])
        ->and($service->payloadErrors(['state_id' => 10]))->toHaveKey('state_id');
});

test('normal modules are enabled for community installs without local license while paid modules stay opt in', function () {
    communityCompatibilityRows();
    config(['campaign.modules.sms' => false]);

    $modules = app(ModuleGateService::class);

    expect(LocalLicense::query()->count())->toBe(0)
        ->and($modules->enabled('members'))->toBeTrue()
        ->and($modules->enabled('elections'))->toBeTrue()
        ->and($modules->enabled('sms'))->toBeFalse();
});

test('legacy local license remains authoritative when present', function () {
    communityCompatibilityRows([
        'package' => 'presidential',
        'campaign_scope_type' => 'national',
        'campaign_scope_name' => 'National',
    ]);

    $license = LocalLicense::query()->create([
        'license_key' => 'CM-LEGACY-ACTIVE',
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
        'app_url' => 'http://localhost',
        'domain' => 'localhost',
        'activation_id' => 'act_legacy',
        'activated_at' => now(),
        'last_checked_at' => now(),
    ]);

    $license->scope()->create([
        'scope_type' => 'state',
        'scope_name' => 'Cross River',
        'state_id' => 9,
        'state_name' => 'Cross River',
    ]);

    $scope = app(PackageScopeService::class)->current();

    expect($scope->package_type)->toBe('governorship')
        ->and($scope->scope_type)->toBe('state')
        ->and($scope->scope_name)->toBe('Cross River')
        ->and($scope->license_status)->toBe('active');
});

