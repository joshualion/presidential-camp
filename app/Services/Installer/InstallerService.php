<?php

namespace App\Services\Installer;

use App\Models\User;
use App\Services\InstallationStateService;
use App\Services\LicensedLocationProvisioningService;
use App\Services\LocalLicenseService;
use App\Services\RemoteLicenseClient;
use App\Services\ServerFingerprintService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class InstallerService
{
    private const ENVIRONMENT_KEYS = [
        'APP_NAME',
        'APP_URL',
        'APP_ENV',
        'APP_DEBUG',
        'APP_TIMEZONE',
        'DEPLOYMENT_MODE',
    ];

    private const DATABASE_KEYS = [
        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
    ];

    public function __construct(
        private EnvironmentWriterService $environmentWriter,
        private DatabaseSetupService $databaseSetup,
        private RemoteLicenseClient $remoteLicenseClient,
        private LocalLicenseService $localLicenseService,
        private LicensedLocationProvisioningService $locationProvisioning,
        private ServerFingerprintService $fingerprintService,
        private InstallationStateService $installationState,
    ) {
    }

    public function run(array $state): array
    {
        $environment = $this->environmentFromStateOrFallback($state);
        $database = $this->databaseFromStateOrFallback($state);
        $mail = $state['mail'] ?? [];
        $licenseKey = $state['license_key'] ?? null;
        $licensePreview = $state['license_preview'] ?? [];
        $admin = $state['admin'] ?? [];

        if ($licenseKey && !$licensePreview) {
            $preview = $this->remoteLicenseClient->preview([
                'license_key' => $licenseKey,
                'app_url' => $environment['APP_URL'] ?? config('app.url'),
                'domain' => parse_url((string) ($environment['APP_URL'] ?? config('app.url')), PHP_URL_HOST),
                'app_version' => config('app.version') ?? env('APP_VERSION'),
                'deployment_mode' => 'self_hosted',
            ]);

            if (($preview['ok'] ?? false) === true) {
                $licensePreview = $preview['data'] ?? [];
            }
        }

        $stateError = $this->validateInstallerState($environment, $database, $licenseKey, $licensePreview, $admin);
        if ($stateError) {
            return array_merge(['ok' => false, 'stage' => 'validation'], $stateError);
        }

        $databaseTest = $this->databaseSetup->test($database);
        if (!$databaseTest['ok']) {
            return ['ok' => false, 'stage' => 'database', 'message' => $databaseTest['message']];
        }

        try {
            $this->environmentWriter->write(array_merge($environment, $database, $mail, [
                'DEPLOYMENT_MODE' => 'self_hosted',
            ]));

            $this->applyRuntimeConfig($environment, $database, $mail);

            $this->runStep('database migrations', fn () => Artisan::call('migrate', ['--force' => true]));
            $this->ensureLocalLicenseTablesExist();
            $this->runStep('installer seed data', fn () => $this->seedCoreData());

            $activation = $this->remoteLicenseClient->activate([
                'license_key' => $licenseKey,
                'app_url' => config('app.url'),
                'domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
                'server_fingerprint' => $this->fingerprintService->make(),
                'app_version' => config('app.version') ?? env('APP_VERSION'),
                'deployment_mode' => 'self_hosted',
            ]);

            if (($activation['ok'] ?? false) !== true) {
                $this->localLicenseService->log(null, $licenseKey, 'activate', 'failed', $activation['message'] ?? null, $activation);
                return ['ok' => false, 'stage' => 'activation', 'message' => $activation['message'] ?? 'License activation failed.'];
            }

            $activationPayload = array_replace_recursive($licensePreview, $activation['data'] ?? []);
            $localLicense = $this->runStep('local license persistence', fn () => $this->localLicenseService->persistActivation($licenseKey, $activationPayload));
            $this->localLicenseService->log($localLicense, $licenseKey, 'activate', 'success', 'License activated during installation.', $activation);
            $this->runStep('system package sync', fn () => $this->syncPackageSetting($localLicense->package_type));
            $locationProvisioning = $this->runStep('licensed location provisioning', fn () => $this->locationProvisioning->provision($localLicense->fresh(['scope', 'modules']), false));
            $this->runStep('first super admin creation', fn () => $this->createSuperAdmin($admin));

            $this->runStep('install lock write', fn () => $this->installationState->create([
                'deployment_mode' => 'self_hosted',
                'package_type' => $localLicense->package_type,
                'scope_type' => $localLicense->scope?->scope_type,
                'license_key' => $this->installationState->maskLicenseKey($licenseKey),
                'location_provisioning' => $this->compactProvisioningSummary($locationProvisioning),
            ]));

            return [
                'ok' => true,
                'message' => 'Installation completed. '.$this->provisioningMessage($locationProvisioning),
                'location_provisioning' => $this->compactProvisioningSummary($locationProvisioning),
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'stage' => 'install', 'message' => $this->cleanMessage($e->getMessage())];
        }
    }

    private function validateInstallerState(array $environment, array $database, ?string $licenseKey, array $licensePreview, array $admin): ?array
    {
        if ($missing = $this->missingRequired($environment, self::ENVIRONMENT_KEYS)) {
            return [
                'missing_step' => 'environment',
                'message' => 'Environment settings could not be found in the installer session or .env. Missing: '.implode(', ', $missing).'. Return to Environment Setup and save again.',
            ];
        }

        if ($missing = $this->missingRequired($database, self::DATABASE_KEYS, ['DB_PASSWORD'])) {
            return [
                'missing_step' => 'database',
                'message' => 'Database settings could not be found in the installer session or .env. Missing: '.implode(', ', $missing).'. Return to Database Setup and test the connection again.',
            ];
        }

        if (!$licenseKey || !$licensePreview) {
            return [
                'missing_step' => 'license',
                'message' => 'License preview is missing. Return to the License step and preview the license again before running installation.',
            ];
        }

        if (!$admin || empty($admin['email']) || empty($admin['password']) || empty($admin['name'])) {
            return [
                'missing_step' => 'admin',
                'message' => 'Super admin details are missing. Return to the Super Admin step and save them again. For security, the admin password is kept only in the browser session.',
            ];
        }

        return null;
    }

    private function environmentFromStateOrFallback(array $state): array
    {
        $environment = array_filter($state['environment'] ?? [], fn ($value) => $value !== null && $value !== '');

        if (!$this->missingRequired($environment, self::ENVIRONMENT_KEYS)) {
            return $environment;
        }

        $fromEnv = $this->environmentWriter->read(self::ENVIRONMENT_KEYS);

        return array_filter(array_replace([
            'APP_NAME' => config('app.name'),
            'APP_URL' => config('app.url'),
            'APP_ENV' => config('app.env'),
            'APP_DEBUG' => config('app.debug') ? 'true' : 'false',
            'APP_TIMEZONE' => config('app.timezone'),
            'DEPLOYMENT_MODE' => config('campaign.deployment_mode'),
        ], $fromEnv), fn ($value) => $value !== null && $value !== '');
    }

    private function databaseFromStateOrFallback(array $state): array
    {
        $database = $state['database'] ?? [];

        if (!$this->missingRequired($database, self::DATABASE_KEYS, ['DB_PASSWORD']) && array_key_exists('DB_PASSWORD', $database)) {
            return $database;
        }

        $fromEnv = $this->environmentWriter->read(self::DATABASE_KEYS);

        return array_replace([
            'DB_CONNECTION' => config('database.default'),
            'DB_HOST' => config('database.connections.'.config('database.default').'.host'),
            'DB_PORT' => config('database.connections.'.config('database.default').'.port'),
            'DB_DATABASE' => config('database.connections.'.config('database.default').'.database'),
            'DB_USERNAME' => config('database.connections.'.config('database.default').'.username'),
            'DB_PASSWORD' => config('database.connections.'.config('database.default').'.password'),
        ], $fromEnv);
    }

    private function missingRequired(array $values, array $required, array $allowEmpty = []): array
    {
        return array_values(array_filter($required, function (string $key) use ($values, $allowEmpty) {
            if (!array_key_exists($key, $values)) {
                return true;
            }

            return !in_array($key, $allowEmpty, true) && ($values[$key] === null || $values[$key] === '');
        }));
    }

    private function applyRuntimeConfig(array $environment, array $database, array $mail): void
    {
        Config::set('app.name', $environment['APP_NAME'] ?? 'Campaign Manager');
        Config::set('app.url', $environment['APP_URL'] ?? config('app.url'));
        Config::set('app.env', $environment['APP_ENV'] ?? 'production');
        Config::set('app.debug', filter_var($environment['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
        Config::set('app.timezone', $environment['APP_TIMEZONE'] ?? 'Africa/Lagos');
        Config::set('campaign.deployment_mode', 'self_hosted');

        Config::set('database.default', $database['DB_CONNECTION'] ?? 'mysql');
        foreach ($database as $key => $value) {
            $configKey = strtolower(str_replace('DB_', '', $key));
            Config::set('database.connections.'.($database['DB_CONNECTION'] ?? 'mysql').'.'.$configKey, $value);
        }

        Config::set('mail.default', $mail['MAIL_MAILER'] ?? config('mail.default'));
        DB::purge();
        DB::reconnect();
    }

    private function seedCoreData(): void
    {
        foreach ([
            \Database\Seeders\CountrySeeder::class => 'countries',
            \Database\Seeders\AgeGradeTableSeeder::class => 'age_grades',
            \Database\Seeders\SupportGroupSeeder::class => 'support_groups',
            \Database\Seeders\ReligionTableSeeder::class => 'religions',
            \Database\Seeders\PoliticalPartiesSeeder::class => 'political_parties',
            \Database\Seeders\ProductCategoriesSeeder::class => 'product_categories',
            \Database\Seeders\ProductsSeeder::class => 'products',
        ] as $seeder => $table) {
            if (Schema::hasTable($table) && DB::table($table)->count() === 0) {
                Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
            }
        }

        $this->seedSystemSettings();
        $this->seedSmtpSettings();
        $this->seedPaymentGateways();
        $this->seedRoles();
        $this->seedPermissions();

        if (Schema::hasTable('system_settings') && Schema::hasColumn('system_settings', 'community_guidelines')) {
            Artisan::call('db:seed', ['--class' => \Database\Seeders\CommunityRulesSeeder::class, '--force' => true]);
        }
    }

    private function createSuperAdmin(array $admin): User
    {
        $nameParts = preg_split('/\s+/', trim((string) $admin['name']), 2);
        $firstname = $nameParts[0] ?: 'Super';
        $lastname = $nameParts[1] ?? 'Admin';

        return User::updateOrCreate(
            ['email' => $admin['email']],
            [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'username' => $this->uniqueUsername($admin['email']),
                'phone' => $admin['phone'] ?? null,
                'password' => Hash::make($admin['password']),
                'access_level' => 'superadmin',
                'status' => 'active',
                'requires_update' => false,
                'email_verified_at' => now(),
            ]
        );
    }

    private function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@')) ?: 'superadmin';
        $username = $base;
        $index = 1;

        while (Schema::hasTable('users') && User::where('username', $username)->where('email', '!=', $email)->exists()) {
            $username = $base.$index++;
        }

        return $username;
    }

    private function syncPackageSetting(?string $packageType): void
    {
        if (!$packageType || !Schema::hasTable('system_settings')) {
            return;
        }

        $systemPackage = $packageType === 'lga' ? 'chairmanship' : $packageType;
        DB::table('system_settings')->updateOrInsert(['id' => 1], ['package' => $systemPackage, 'updated_at' => now()]);
    }

    private function ensureLocalLicenseTablesExist(): void
    {
        foreach (['local_licenses', 'local_license_scopes', 'local_license_modules', 'license_check_logs'] as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException("Migration completed, but required local license table {$table} is missing.");
            }
        }
    }

    private function compactProvisioningSummary(array $result): array
    {
        return [
            'status' => $result['status'] ?? null,
            'package_type' => $result['package_type'] ?? null,
            'deployment_mode' => $result['deployment_mode'] ?? null,
            'scope_type' => $result['scope_type'] ?? null,
            'scope_name' => $result['scope_name'] ?? null,
            'summary' => $result['summary'] ?? [],
            'warnings' => $result['warnings'] ?? [],
        ];
    }

    private function provisioningMessage(array $result): string
    {
        $summary = $result['summary'] ?? [];
        $parts = [];

        foreach (['regions', 'states', 'senatorial_districts', 'federal_constituencies', 'lgas', 'wards', 'polling_units'] as $key) {
            $counts = $summary[$key] ?? [];
            $total = ($counts['created'] ?? 0) + ($counts['updated'] ?? 0);
            $parts[] = str_replace('_', ' ', $key).': '.$total.' provisioned';
        }

        return 'Licensed location data provisioned for '.($result['scope_name'] ?? 'the licensed scope').' ('.implode(', ', $parts).').';
    }

    private function runStep(string $stage, callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            throw new RuntimeException(ucfirst($stage).' failed: '.$this->cleanMessage($e->getMessage()), 0, $e);
        }
    }

    private function seedSystemSettings(): void
    {
        if (!Schema::hasTable('system_settings')) {
            return;
        }

        DB::table('system_settings')->updateOrInsert(['id' => 1], [
            'package' => env('CAMPAIGN_PACKAGE_TYPE', 'presidential'),
            'logo' => '',
            'favicon'=> 'favicon.png',
            'login_page_background' => '',
            'dark_theme_color' => '#008751',
            'light_theme_color' => '#f7f7f7',
            'sidebar_theme_mode' => 'light',
            'system_name' => 'Political Campaign Management Solution',
            'campaign_slogan' => 'Promoting Good Governance, Transparency and Trust',
            'system_email' => 'info@campaignmanager.ng',
            'system_country' => 'Nigeria',
            'system_currency' => 'NGN',
            'company_address' => '123 Almond St, Lagos, Nigeria',
            'company_phone' => '+1234567890',
            'require_bank_details' => false,
            'activation_code' => '',
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function seedSmtpSettings(): void
    {
        if (!Schema::hasTable('s_m_t_p_settings')) {
            return;
        }

        DB::table('s_m_t_p_settings')->updateOrInsert(['id' => 1], [
            'mailer' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => '587',
            'username' => 'youremail@gmail.com',
            'password' => 'your-app-password',
            'encryption' => 'tls',
            'from_address' => 'youremail@gmail.com',
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function seedPaymentGateways(): void
    {
        if (!Schema::hasTable('payment_gateways')) {
            return;
        }

        foreach (['Flutterwave', 'USDT-Direct', 'Paystack'] as $name) {
            DB::table('payment_gateways')->updateOrInsert(['name' => $name], [
                'is_active' => false,
                'sandbox_mode' => true,
                'gateway_specific_settings' => json_encode([]),
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    private function seedRoles(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        foreach ($this->roleRows() as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name'], 'guard_name' => 'web'],
                array_merge($role, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach ($this->permissionRows() as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                array_merge($permission, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }

    private function roleRows(): array
    {
        return [
            ['name' => 'Presidential Candidate', 'guard_name' => 'web', 'group_name' => 'nationaladmin'],
            ['name' => 'Vice Presidential Candidate', 'guard_name' => 'web', 'group_name' => 'nationaladmin'],
            ['name' => 'National Coordinator', 'guard_name' => 'web', 'group_name' => 'nationaladmin'],
            ['name' => 'National Secretary', 'guard_name' => 'web', 'group_name' => 'nationaladmin'],
            ['name' => 'National Financial Secretary', 'guard_name' => 'web', 'group_name' => 'nationaladmin'],
            ['name' => 'National Treasurer', 'guard_name' => 'web', 'group_name' => 'nationaladmin'],
            ['name' => 'National Women Leader', 'guard_name' => 'web', 'group_name' => 'nationaladmin'],
            ['name' => 'National Youth Leader', 'guard_name' => 'web', 'group_name' => 'nationaladmin'],
            ['name' => 'National ICT Director', 'guard_name' => 'web', 'group_name' => 'nationaladmin'],
            ['name' => 'Regional Coordinator', 'guard_name' => 'web', 'group_name' => 'regionaladmin'],
            ['name' => 'Regional Secretary', 'guard_name' => 'web', 'group_name' => 'regionaladmin'],
            ['name' => 'Regional Financial Secretary', 'guard_name' => 'web', 'group_name' => 'regionaladmin'],
            ['name' => 'Regional Treasurer', 'guard_name' => 'web', 'group_name' => 'regionaladmin'],
            ['name' => 'Regional Women Leader', 'guard_name' => 'web', 'group_name' => 'regionaladmin'],
            ['name' => 'Regional Youth Leader', 'guard_name' => 'web', 'group_name' => 'regionaladmin'],
            ['name' => 'Regional ICT Director', 'guard_name' => 'web', 'group_name' => 'regionaladmin'],
            ['name' => 'State Coordinator', 'guard_name' => 'web', 'group_name' => 'stateadmin'],
            ['name' => 'State Secretary', 'guard_name' => 'web', 'group_name' => 'stateadmin'],
            ['name' => 'State Financial Secretary', 'guard_name' => 'web', 'group_name' => 'stateadmin'],
            ['name' => 'State Women Leader', 'guard_name' => 'web', 'group_name' => 'stateadmin'],
            ['name' => 'State Youth Leader', 'guard_name' => 'web', 'group_name' => 'stateadmin'],
            ['name' => 'State ICT Director', 'guard_name' => 'web', 'group_name' => 'stateadmin'],
            ['name' => 'Senatorial District Coordinator', 'guard_name' => 'web', 'group_name' => 'senatorialadmin'],
            ['name' => 'Senatorial District Secretary', 'guard_name' => 'web', 'group_name' => 'senatorialadmin'],
            ['name' => 'Senatorial District Financial Secretary', 'guard_name' => 'web', 'group_name' => 'senatorialadmin'],
            ['name' => 'Senatorial District Treasurer', 'guard_name' => 'web', 'group_name' => 'senatorialadmin'],
            ['name' => 'Senatorial District Women Leader', 'guard_name' => 'web', 'group_name' => 'senatorialadmin'],
            ['name' => 'Senatorial District Youth Leader', 'guard_name' => 'web', 'group_name' => 'senatorialadmin'],
            ['name' => 'Senatorial ICT Director', 'guard_name' => 'web', 'group_name' => 'senatorialadmin'],
            ['name' => 'Federal Constituency Coordinator', 'guard_name' => 'web', 'group_name' => 'federaladmin'],
            ['name' => 'Federal Constituency Secretary', 'guard_name' => 'web', 'group_name' => 'federaladmin'],
            ['name' => 'Federal Constituency Financial Secretary', 'guard_name' => 'web', 'group_name' => 'federaladmin'],
            ['name' => 'Federal Constituency Treasurer', 'guard_name' => 'web', 'group_name' => 'federaladmin'],
            ['name' => 'Federal Constituency Women Leader', 'guard_name' => 'web', 'group_name' => 'federaladmin'],
            ['name' => 'Federal Constituency Youth Leader', 'guard_name' => 'web', 'group_name' => 'federaladmin'],
            ['name' => 'Federal Constituency ICT Director', 'guard_name' => 'web', 'group_name' => 'federaladmin'],
            ['name' => 'LGA Coordinator', 'guard_name' => 'web', 'group_name' => 'lgaadmin'],
            ['name' => 'LGA Secretary', 'guard_name' => 'web', 'group_name' => 'lgaadmin'],
            ['name' => 'LGA Financial Secretary', 'guard_name' => 'web', 'group_name' => 'lgaadmin'],
            ['name' => 'LGA Treasurer', 'guard_name' => 'web', 'group_name' => 'lgaadmin'],
            ['name' => 'LGA Women Leader', 'guard_name' => 'web', 'group_name' => 'lgaadmin'],
            ['name' => 'LGA Youth Leader', 'guard_name' => 'web', 'group_name' => 'lgaadmin'],
            ['name' => 'LGA ICT Director', 'guard_name' => 'web', 'group_name' => 'lgaadmin'],
            ['name' => 'Ward Coordinator', 'guard_name' => 'web', 'group_name' => 'wardadmin'],
            ['name' => 'Ward Treasurer', 'guard_name' => 'web', 'group_name' => 'wardadmin'],
            ['name' => 'Ward Women Leader', 'guard_name' => 'web', 'group_name' => 'wardadmin'],
            ['name' => 'Ward Youth Leader', 'guard_name' => 'web', 'group_name' => 'wardadmin'],
            ['name' => 'Ward ICT Director', 'guard_name' => 'web', 'group_name' => 'wardadmin'],
            ['name' => 'PU Agent', 'guard_name' => 'web', 'group_name' => 'puadmin'],
            ['name' => 'PU Voter Mobilizer', 'guard_name' => 'web', 'group_name' => 'puadmin'],
            ['name' => 'PU ICT Director', 'guard_name' => 'web', 'group_name' => 'puadmin'],
            ['name' => 'Member', 'guard_name' => 'web', 'group_name' => 'user'],
        ];
    }

    private function permissionRows(): array
    {
        $rows = [];
        foreach (['national', 'regional', 'state', 'senatorial', 'federal', 'lga', 'ward', 'pu'] as $group) {
            foreach (['Create', 'Read', 'Update', 'Delete'] as $action) {
                $rows[] = ['name' => "{$group}_{$action}", 'guard_name' => 'web', 'group_name' => $group.'admin'];
            }
        }
        $rows[] = ['name' => 'user_CRUD', 'guard_name' => 'web', 'group_name' => 'user'];
        foreach (['Create', 'Read', 'Update', 'Delete'] as $action) {
            $rows[] = ['name' => "super_{$action}", 'guard_name' => 'web', 'group_name' => 'superadmin'];
        }

        return $rows;
    }

    private function cleanMessage(string $message): string
    {
        return preg_replace('/(password=|DB_PASSWORD=|MAIL_PASSWORD=)[^;\s]+/i', '$1******', $message) ?: 'Installation failed.';
    }
}
