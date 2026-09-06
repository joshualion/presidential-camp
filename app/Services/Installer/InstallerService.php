<?php

namespace App\Services\Installer;

use App\Models\User;
use App\Services\CampaignInstallationSettingsService;
use App\Services\InstallationStateService;
use App\Services\LocationProvisioningException;
use App\Services\LocationProvisioningIntegrityService;
use App\Services\LocationProvisioningImportService;
use App\Services\LocalLicenseService;
use App\Services\LocalLicenseScopeReconciliationService;
use App\Services\PackageGovernanceService;
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

    private const MAIL_KEYS = [
        'MAIL_MAILER',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
    ];

    public function __construct(
        private EnvironmentWriterService $environmentWriter,
        private DatabaseSetupService $databaseSetup,
        private InstallerMailSettingsService $mailSettings,
        private RemoteLicenseClient $remoteLicenseClient,
        private LocalLicenseService $localLicenseService,
        private LocalLicenseScopeReconciliationService $scopeReconciliation,
        private PackageGovernanceService $packageGovernance,
        private LocationProvisioningImportService $locationProvisioning,
        private LocationProvisioningIntegrityService $locationIntegrity,
        private ServerFingerprintService $fingerprintService,
        private InstallationStateService $installationState,
        private CampaignInstallationSettingsService $campaignInstallationSettings,
        private BaseCampaignGeographyBootstrapper $baseGeographyBootstrapper,
    ) {
    }

    public function run(array $state): array
    {
        $environment = $this->environmentFromStateOrFallback($state);
        $database = $this->databaseFromStateOrFallback($state);
        $mail = $this->mailFromStateOrFallback($state);
        $licenseKey = trim((string) ($state['license_key'] ?? ''));
        $licenseKey = $licenseKey !== '' ? $licenseKey : null;
        $licensePreview = $state['license_preview'] ?? [];
        $communityInstall = $licenseKey === null;
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
                $licensePreview = $this->localLicenseService->normalizePortalPayload($preview['data'] ?? []);
            }
        }

        $stateError = $this->validateInstallerState($environment, $database, $licenseKey, $licensePreview, $admin, $state);
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
            $this->runStep('installer seed data', fn () => $this->seedCoreData($mail));

            $campaignScope = $this->campaignScopeFromState($state, $environment);
            $campaignGeography = $state['campaign_geography'] ?? [];
            $campaignIdentity = $state['campaign_identity'] ?? [];
            $baseGeography = $this->runStep(
                'base campaign geography bootstrap',
                fn () => $this->baseGeographyBootstrapper->bootstrap($campaignScope, $campaignGeography)
            );
            $this->runStep('community installation sync', fn () => $this->campaignInstallationSettings->persist($campaignScope, $baseGeography, $campaignIdentity));

            if ($communityInstall) {
                $packageType = $campaignScope['package_type'];
                $scopeType = $campaignScope['scope_type'];
                $scopeName = $baseGeography['scope_name'] ?? $campaignScope['scope_name'] ?? $this->campaignInstallationSettings->defaultScopeName($scopeType);

                $this->runStep('first super admin creation', fn () => $this->createSuperAdmin($admin));

                $locationProvisioning = [
                    'status' => 'skipped',
                    'package_type' => $packageType,
                    'deployment_mode' => 'self_hosted',
                    'scope_type' => $scopeType,
                    'scope_name' => $scopeName,
                    'data_profile' => 'manual',
                    'summary' => [],
                    'warnings' => ['Community installation completed without paid QuickStart geography provisioning. Configure geography manually or provision later when a provisioning entitlement is available.'],
                ];

                $this->runStep('install lock write', fn () => $this->installationState->create([
                    'installation_mode' => 'community',
                    'source' => 'local',
                    'remote_validation_required' => false,
                    'deployment_mode' => 'self_hosted',
                    'package_type' => $packageType,
                    'scope_type' => $scopeType,
                    'license_key' => null,
                    'location_provisioning' => $this->compactProvisioningSummary($locationProvisioning),
                ]));

                return [
                    'ok' => true,
                    'message' => 'Community setup complete. Sign in with the super admin account and configure geography manually.',
                    'installation_complete' => true,
                    'location_provisioning' => $this->compactProvisioningSummary($locationProvisioning),
                ];
            }

            $scopeError = $this->quickStartScopeError($state, $licensePreview);
            if ($scopeError) {
                return ['ok' => false, 'stage' => 'geography_setup', 'message' => $scopeError];
            }

            $activationRequired = $this->localLicenseService->activationRequired($licensePreview);
            $localLicense = $this->localLicenseService->activatedForLicense($licenseKey);

            if (!$localLicense) {
                if (!$activationRequired) {
                    $activationPayload = $this->localLicenseService->normalizePortalPayload($licensePreview);
                    $localLicense = $this->runStep('QuickStart provisioning key persistence', fn () => $this->localLicenseService->persistActivation($licenseKey, $activationPayload));
                    $this->localLicenseService->log($localLicense, $licenseKey, 'quickstart_entitlement', 'success', 'QuickStart provisioning key validated during installation.', ['data' => $activationPayload]);
                } else {
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
                        return ['ok' => false, 'stage' => 'activation', 'message' => $this->localLicenseService->customerMessage($activation['message'] ?? null, 'Activation failed for this legacy self-hosted licence.')];
                    }

                    $activationData = $this->localLicenseService->normalizePortalPayload($activation['data'] ?? []);
                    $activationPayload = array_replace_recursive(
                        $this->localLicenseService->normalizePortalPayload($licensePreview),
                        $activationData
                    );

                    $activationRequired = $this->localLicenseService->activationRequired($activationPayload);
                    if ($activationRequired && !$this->localLicenseService->activationId($activationPayload)) {
                        $message = 'The portal confirmed activation but did not return an activation reference. Retry activation. If the problem continues, contact support.';
                        $this->localLicenseService->log(null, $licenseKey, 'activate', 'failed', $message, $activation);

                        return ['ok' => false, 'stage' => 'activation', 'message' => $message];
                    }

                    $localLicense = $this->runStep('local license persistence', fn () => $this->localLicenseService->persistActivation($licenseKey, $activationPayload));
                    $this->localLicenseService->log(
                        $localLicense,
                        $licenseKey,
                        $activationRequired ? 'activate' : 'quickstart_entitlement',
                        'success',
                        $activationRequired ? 'License activated during installation.' : 'QuickStart provisioning key validated during installation.',
                        $activation
                    );
                }
            }

            $this->runStep('system package sync', fn () => $this->syncPackageSetting($localLicense->package_type));
            try {
                $run = $this->locationProvisioning->startOrResume($localLicense->fresh(['scope', 'modules']));
            } catch (LocationProvisioningException $e) {
                if ($e->status !== 0) {
                    throw $e;
                }

                return [
                    'ok' => true,
                    'message' => 'Core setup complete. The Campaign Manager portal was unreachable, so full location provisioning can be completed later with php artisan campaign:locations:provision.',
                    'location_provisioning_deferred' => $e->getMessage(),
                ];
            }

            return [
                'ok' => true,
                'message' => 'Core setup complete. Continue to provision QuickStart geography data.',
                'provisioning_run_id' => $run->id,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'stage' => 'install', 'message' => $this->cleanMessage($e->getMessage())];
        }
    }

    public function finishAfterProvisioning(array $state, array $locationProvisioning): array
    {
        $licenseKey = $state['license_key'] ?? null;
        $admin = $state['admin'] ?? [];
        $localLicense = \App\Models\LocalLicense::query()->with('scope')->latest('id')->first();

        if (!$localLicense) {
            return ['ok' => false, 'message' => 'QuickStart provisioning setup is incomplete. Return to Run Installation and try again.'];
        }

        if (!$admin || empty($admin['email']) || empty($admin['password']) || empty($admin['name'])) {
            return ['ok' => false, 'message' => 'Super admin details are missing. Return to the Super Admin step and save them again.'];
        }

        try {
            if (($locationProvisioning['status'] ?? null) === 'completed') {
                $localLicense = $localLicense->fresh('scope');
                $this->runStep('licensed scope reconciliation', fn () => $this->scopeReconciliation->reconcile($localLicense));
                $localLicense = $localLicense->fresh('scope');
                $this->runStep('campaign scope settings reconciliation', fn () => $this->campaignInstallationSettings->syncFromLocalLicense($localLicense));
            }

            $integrity = $this->locationIntegrity->verifyBeforeInstallLock($localLicense, $locationProvisioning);
            if (($integrity['ok'] ?? false) !== true) {
                return ['ok' => false, 'message' => $integrity['message'] ?? 'Campaign location setup is incomplete.'];
            }

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
                'message' => 'Installation completed.',
                'location_provisioning' => $this->compactProvisioningSummary($locationProvisioning),
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $this->cleanMessage($e->getMessage())];
        }
    }

    private function validateInstallerState(array $environment, array $database, ?string $licenseKey, array $licensePreview, array $admin, array $state): ?array
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

        $campaignScope = $state['campaign_scope'] ?? [];
        if (empty($campaignScope['package_type']) || empty($campaignScope['scope_type'])) {
            return [
                'missing_step' => 'campaign_scope',
                'message' => 'Campaign scope is missing. Return to Campaign Scope and save again.',
            ];
        }

        if (!$this->campaignInstallationSettings->completeGeography($campaignScope, $state['campaign_geography'] ?? [])) {
            return [
                'missing_step' => 'campaign_geography',
                'message' => 'Campaign geography is incomplete. Return to Relevant Geography and save again.',
            ];
        }

        $campaignIdentity = $state['campaign_identity'] ?? [];
        if (empty($campaignIdentity['system_name'])) {
            return [
                'missing_step' => 'campaign_identity',
                'message' => 'Campaign identity is missing. Return to Campaign Identity and save again.',
            ];
        }

        $geographySetup = $state['geography_setup']['method'] ?? null;
        if (!in_array($geographySetup, ['manual', 'quickstart'], true)) {
            return [
                'missing_step' => 'geography_setup',
                'message' => 'Geography setup method is missing. Return to Geography Setup and choose manual setup or QuickStart provisioning.',
            ];
        }

        if ($licenseKey && !$licensePreview) {
            return [
                'missing_step' => 'geography_setup',
                'message' => 'QuickStart preview is missing. Return to Geography Setup and preview the provisioning key again before running installation.',
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

    private function quickStartScopeError(array $state, array $licensePreview): ?string
    {
        $payload = $this->localLicenseService->normalizePortalPayload($licensePreview);
        if (!$this->localLicenseService->isQuickStartProvisioning($payload)) {
            return null;
        }

        $campaignScope = $state['campaign_scope'] ?? [];
        $campaignGeography = $state['campaign_geography'] ?? [];
        $expectedPackage = isset($campaignScope['package_type']) ? $this->packageGovernance->normalize($campaignScope['package_type']) : null;
        $actualPackageValue = $this->localLicenseService->packageType($payload);
        $actualPackage = $actualPackageValue ? $this->packageGovernance->normalize($actualPackageValue) : null;

        if ($expectedPackage && $actualPackage && $expectedPackage !== $actualPackage) {
            return 'QuickStart provisioning key does not match the campaign scope selected in the installer.';
        }

        $expectedScopeType = $campaignScope['scope_type'] ?? null;
        $actualScopeType = data_get($payload, 'scope.scope_type', data_get($payload, 'scope.type'));
        if ($expectedScopeType && $actualScopeType && $expectedScopeType !== $actualScopeType) {
            return 'QuickStart provisioning key does not match the campaign geography selected in the installer.';
        }

        foreach ($this->quickStartBoundaryPairs($expectedScopeType) as [$previewKey, $installerKey, $previewNameKey, $installerNameKey]) {
            $expectedId = $campaignGeography[$installerKey] ?? null;
            $actualId = data_get($payload, 'scope.'.$previewKey);

            if ($expectedId && $actualId && (string) $expectedId !== (string) $actualId) {
                return 'QuickStart provisioning key does not match the campaign geography selected in the installer.';
            }

            $expectedName = $campaignGeography[$installerNameKey] ?? null;
            $actualName = data_get($payload, 'scope.'.$previewNameKey);
            if (!$expectedId && !$actualId && $expectedName && $actualName && $this->normal($expectedName) !== $this->normal($actualName)) {
                return 'QuickStart provisioning key does not match the campaign geography selected in the installer.';
            }
        }

        return null;
    }

    private function quickStartBoundaryPairs(?string $scopeType): array
    {
        $state = ['campaign_state_id', 'portal_state_id', 'state_name', 'state_name'];

        return match ($scopeType) {
            'state' => [$state],
            'senatorial_district' => [
                $state,
                ['campaign_senatorial_district_id', 'portal_senatorial_district_id', 'senatorial_district_name', 'senatorial_district_name'],
            ],
            'federal_constituency' => [
                $state,
                ['campaign_federal_constituency_id', 'portal_federal_constituency_id', 'federal_constituency_name', 'federal_constituency_name'],
            ],
            'lga' => [
                $state,
                ['campaign_lga_id', 'portal_lga_id', 'lga_name', 'lga_name'],
            ],
            default => [],
        };
    }

    private function normal(?string $value): string
    {
        return str((string) $value)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
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

    private function mailFromStateOrFallback(array $state): array
    {
        return array_replace(
            $this->environmentWriter->read(self::MAIL_KEYS),
            $state['mail'] ?? []
        );
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

    private function seedCoreData(array $mail): void
    {
        foreach ([
            \Database\Seeders\CountrySeeder::class => 'countries',
            \Database\Seeders\RegionSeeder::class => 'regions',
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
        $this->mailSettings->persist($mail);
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

        $systemPackage = $this->packageGovernance->normalize($packageType);
        DB::table('system_settings')->updateOrInsert(['id' => 1], ['package' => $systemPackage, 'updated_at' => now()]);
    }

    private function syncCommunityInstallationSetting(string $packageType, string $scopeType, ?string $scopeName): void
    {
        if (!Schema::hasTable('system_settings')) {
            return;
        }

        $values = [
            'package' => $this->packageGovernance->normalize($packageType),
            'updated_at' => now(),
        ];

        foreach ([
            'installation_mode' => 'community',
            'campaign_scope_type' => $scopeType,
            'campaign_scope_name' => $scopeName,
        ] as $column => $value) {
            if (Schema::hasColumn('system_settings', $column)) {
                $values[$column] = $value;
            }
        }

        DB::table('system_settings')->updateOrInsert(['id' => 1], $values);
    }

    private function campaignScopeFromState(array $state, array $environment): array
    {
        $packageType = $this->communityPackageType($state, $environment);
        $scopeType = $state['campaign_scope']['scope_type'] ?? $this->campaignInstallationSettings->scopeTypeForPackage($packageType);

        return [
            'package_type' => $packageType,
            'scope_type' => $scopeType,
            'scope_name' => $state['campaign_scope']['scope_name'] ?? $this->campaignInstallationSettings->defaultScopeName($scopeType),
        ];
    }
    private function communityPackageType(array $state, array $environment): string
    {
        return $this->packageGovernance->normalize(
            $state['campaign_scope']['package_type']
                ?? $state['package_type']
                ?? $environment['CAMPAIGN_PACKAGE_TYPE']
                ?? env('CAMPAIGN_PACKAGE_TYPE', PackageGovernanceService::PRESIDENTIAL)
        );
    }

    private function scopeTypeForPackage(?string $packageType): string
    {
        return match ($this->packageGovernance->normalize($packageType)) {
            PackageGovernanceService::PRESIDENTIAL => 'national',
            PackageGovernanceService::GOVERNORSHIP => 'state',
            PackageGovernanceService::SENATORIAL => 'senatorial_district',
            PackageGovernanceService::FEDERAL => 'federal_constituency',
            PackageGovernanceService::CHAIRMANSHIP => 'lga',
            default => 'national',
        };
    }

    private function defaultScopeName(?string $scopeType): ?string
    {
        return match ($scopeType) {
            'national' => 'National',
            'state' => 'Selected State',
            'senatorial_district' => 'Selected Senatorial District',
            'federal_constituency' => 'Selected Federal Constituency',
            'lga' => 'Selected LGA',
            default => null,
        };
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
            'data_profile' => $result['data_profile'] ?? null,
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

        return 'Campaign geography provisioned for '.($result['scope_name'] ?? 'the selected scope').' ('.implode(', ', $parts).').';
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

        $packageType = $this->packageGovernance->normalize(env('CAMPAIGN_PACKAGE_TYPE', PackageGovernanceService::PRESIDENTIAL));
        $values = [
            'package' => $packageType,
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
        ];

        if (Schema::hasColumn('system_settings', 'installation_mode')) {
            $values['installation_mode'] = 'community';
        }

        if (Schema::hasColumn('system_settings', 'campaign_scope_type')) {
            $scopeType = $this->scopeTypeForPackage($packageType);
            $values['campaign_scope_type'] = $scopeType;
            $values['campaign_scope_name'] = $this->defaultScopeName($scopeType);
        }

        DB::table('system_settings')->updateOrInsert(['id' => 1], $values);
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
        $message = preg_replace('/(password=|DB_PASSWORD=|MAIL_PASSWORD=)[^;\s]+/i', '$1******', $message) ?: 'Installation failed.';

        return $this->localLicenseService->customerMessage($message, 'Installation failed.');
    }
}


