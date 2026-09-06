<?php

namespace App\Http\Controllers;

use App\Services\Installer\DatabaseSetupService;
use App\Services\Installer\EnvironmentWriterService;
use App\Services\Installer\CampaignGeographyDirectoryClient;
use App\Services\Installer\InstallerService;
use App\Services\Installer\InstallerStateStore;
use App\Services\Installer\RequirementCheckService;
use App\Services\CampaignInstallationSettingsService;
use App\Services\Installer\StorageSetupService;
use App\Services\LocationProvisioningImportService;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\SenatorialDistrict;
use App\Models\State;
use Illuminate\Support\Facades\Schema;
use App\Services\LocalLicenseService;
use App\Services\RemoteLicenseClient;
use Illuminate\Http\Request;
use Throwable;

class InstallerController extends Controller
{
    private array $steps = [
        'welcome' => 'Welcome',
        'requirements' => 'Requirements',
        'environment' => 'Environment',
        'database' => 'Database',
        'mail' => 'Mail',
        'storage' => 'Storage',
        'campaign_scope' => 'Campaign Scope',
        'campaign_geography' => 'Relevant Geography',
        'campaign_identity' => 'Campaign Identity',
        'geography_setup' => 'Geography Setup',
        'admin' => 'Super Admin',
        'run' => 'Run',
        'provision' => 'Provision Location Data',
        'complete' => 'Complete',
    ];

    private array $stepGroups = [
        'Technical Setup' => ['welcome', 'requirements', 'environment', 'database', 'mail', 'storage'],
        'Campaign Setup' => ['campaign_scope', 'campaign_geography', 'campaign_identity'],
        'Administrator' => ['admin'],
        'Geography Setup' => ['geography_setup', 'run', 'provision', 'complete'],
    ];

    private array $previousSteps = [
        'requirements' => 'welcome',
        'environment' => 'requirements',
        'database' => 'environment',
        'mail' => 'database',
        'storage' => 'mail',
        'campaign_scope' => 'storage',
        'campaign_geography' => 'campaign_scope',
        'campaign_identity' => 'campaign_geography',
        'geography_setup' => 'campaign_identity',
        'admin' => 'geography_setup',
        'run' => 'admin',
        'provision' => 'run',
    ];
    public function __construct(
        private RequirementCheckService $requirements,
        private EnvironmentWriterService $environmentWriter,
        private DatabaseSetupService $databaseSetup,
        private StorageSetupService $storageSetup,
        private RemoteLicenseClient $remoteLicenseClient,
        private CampaignGeographyDirectoryClient $geographyDirectory,
        private LocalLicenseService $localLicenseService,
        private InstallerService $installerService,
        private InstallerStateStore $stateStore,
        private LocationProvisioningImportService $locationProvisioning,
        private CampaignInstallationSettingsService $campaignInstallationSettings,
    ) {
    }

    public function welcome()
    {
        return $this->view('welcome');
    }

    public function requirements(Request $request)
    {
        $checks = $this->requirements->checks();

        if ($request->isMethod('post')) {
            if ($this->requirements->hasCriticalFailures($checks)) {
                return back()->withErrors(['requirements' => 'Resolve critical requirement failures before continuing.']);
            }

            $this->completeStep('requirements');

            return redirect()->route('install.environment');
        }

        return $this->view('requirements', [
            'checks' => $checks,
            'sessionWarning' => $this->firstRunDriverWarning(),
        ]);
    }

    public function environment(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'APP_NAME' => ['required', 'string', 'max:120'],
                'APP_URL' => ['required', 'url', 'max:255'],
                'APP_ENV' => ['required', 'in:production,local,staging'],
                'APP_DEBUG' => ['required', 'boolean'],
                'APP_TIMEZONE' => ['required', 'string', 'max:80'],
                'DEPLOYMENT_MODE' => ['required', 'in:self_hosted'],
            ]);

            $data['APP_KEY'] = config('app.key');
            $data['SESSION_DRIVER'] = 'file';
            $data['CACHE_STORE'] = 'file';
            $data['QUEUE_CONNECTION'] = 'sync';
            session(['installer.environment' => $data]);
            $this->environmentWriter->write($data);
            $this->stateStore->merge([
                'environment' => $data,
                'deployment_mode' => $data['DEPLOYMENT_MODE'],
                'app_url' => $data['APP_URL'],
            ]);
            $this->completeStep('environment');

            return redirect()->route('install.database')->with('status', 'Environment settings saved.');
        }

        return $this->view('environment');
    }

    public function database(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'DB_CONNECTION' => ['required', 'in:mysql'],
                'DB_HOST' => ['required', 'string', 'max:255'],
                'DB_PORT' => ['required', 'numeric'],
                'DB_DATABASE' => ['required', 'string', 'max:255'],
                'DB_USERNAME' => ['required', 'string', 'max:255'],
                'DB_PASSWORD' => ['nullable', 'string', 'max:255'],
            ]);

            $test = $this->databaseSetup->test($data);
            if (!$test['ok']) {
                return back()->withInput($request->except('DB_PASSWORD'))->withErrors(['database' => $test['message']]);
            }

            session(['installer.database' => $data]);
            $this->environmentWriter->write($data);
            $this->stateStore->merge([
                'database' => $data,
                'database_configured' => true,
            ]);
            $this->completeStep('database');

            return redirect()->route('install.mail')->with('status', $test['message']);
        }

        return $this->view('database');
    }

    public function mail(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'MAIL_MAILER' => ['nullable', 'string', 'max:80'],
                'MAIL_HOST' => ['nullable', 'string', 'max:255'],
                'MAIL_PORT' => ['nullable', 'numeric'],
                'MAIL_USERNAME' => ['nullable', 'string', 'max:255'],
                'MAIL_PASSWORD' => ['nullable', 'string', 'max:255'],
                'MAIL_ENCRYPTION' => ['nullable', 'string', 'max:40'],
                'MAIL_FROM_ADDRESS' => ['nullable', 'email', 'max:255'],
                'MAIL_FROM_NAME' => ['nullable', 'string', 'max:120'],
            ]);

            session(['installer.mail' => $data]);
            $this->environmentWriter->write($data);
            $this->stateStore->merge(['mail' => $data]);
            $this->completeStep('mail');

            return redirect()->route('install.storage')->with('status', 'Mail settings saved. You can test mail after installation.');
        }

        return $this->view('mail');
    }

    public function storage(Request $request)
    {
        $result = $this->storageSetup->inspectAndLink();

        if ($request->isMethod('post')) {
            session(['installer.storage' => $result]);
            $this->stateStore->merge(['storage' => $result]);
            $this->completeStep('storage');
            return redirect()->route('install.campaign_scope');
        }

        return $this->view('storage', ['storageResult' => $result]);
    }

    public function campaignScope(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'package_type' => ['required', 'string'],
            ]);

            $packageType = $data['package_type'];
            $option = $this->campaignInstallationSettings->option($packageType);
            if (!$option) {
                return back()->withInput()->withErrors(['package_type' => 'Choose a supported campaign scope.']);
            }

            $scope = [
                'package_type' => $packageType,
                'scope_type' => $option['scope_type'],
                'scope_name' => $option['default_scope_name'],
            ];

            session(['installer.campaign_scope' => $scope]);
            $this->stateStore->merge([
                'campaign_scope' => $scope,
                'campaign_geography' => [],
            ]);
            $this->completeStep('campaign_scope');

            return redirect()->route('install.campaign_geography')->with('status', 'Campaign scope saved.');
        }

        return $this->view('campaign_scope', [
            'scopeOptions' => $this->campaignInstallationSettings->options(),
        ]);
    }

    public function campaignGeography(Request $request)
    {
        $state = $this->currentState();
        $campaignScope = $state['campaign_scope'] ?? [];
        $packageType = $campaignScope['package_type'] ?? null;
        $option = $packageType ? $this->campaignInstallationSettings->option($packageType) : null;

        if (!$option) {
            return redirect()->route('install.campaign_scope')->withErrors(['campaign_scope' => 'Choose a campaign scope before selecting geography.']);
        }

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'state_id' => ['nullable', 'integer'],
                'senatorial_district_id' => ['nullable', 'integer'],
                'federal_constituency_id' => ['nullable', 'integer'],
                'lga_id' => ['nullable', 'integer'],
                'scope_name' => ['nullable', 'string', 'max:160'],
            ]);

            $resolved = $this->resolveCampaignGeography($option['scope_type'], $data);
            if (!$resolved['ok']) {
                return back()->withInput()->withErrors(['campaign_geography' => $resolved['message']]);
            }

            session(['installer.campaign_geography' => $resolved['data']]);
            $this->stateStore->merge(['campaign_geography' => $resolved['data']]);
            $this->completeStep('campaign_geography');

            return redirect()->route('install.campaign_identity')->with('status', 'Campaign geography saved.');
        }

        return $this->view('campaign_geography', array_merge($this->geographyOptions($option['scope_type']), [
            'campaignScope' => $campaignScope,
            'scopeOption' => $option,
        ]));
    }

    public function campaignIdentity(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'system_name' => ['required', 'string', 'max:160'],
                'campaign_slogan' => ['nullable', 'string', 'max:255'],
            ]);

            session(['installer.campaign_identity' => $data]);
            $this->stateStore->merge(['campaign_identity' => $data]);
            $this->completeStep('campaign_identity');

            return redirect()->route('install.geography_setup')->with('status', 'Campaign identity saved.');
        }

        return $this->view('campaign_identity');
    }

    public function geographySetup(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'method' => ['required', 'in:manual,quickstart'],
                'license_key' => ['nullable', 'string', 'max:255'],
            ]);

            if ($data['method'] === 'manual') {
                session([
                    'installer.geography_setup' => ['method' => 'manual'],
                    'installer.license_key' => null,
                    'installer.license_preview' => [],
                    'installer.community_install' => true,
                ]);
                $this->stateStore->merge([
                    'geography_setup' => ['method' => 'manual'],
                    'license_key' => null,
                    'license_preview' => [],
                    'community_install' => true,
                ]);
                $this->completeStep('geography_setup');

                return redirect()->route('install.admin')->with('status', 'Manual geography setup selected. No key or portal connection is required.');
            }

            $licenseKey = trim((string) ($data['license_key'] ?? ''));
            if ($licenseKey === '') {
                return back()->withInput()->withErrors(['license_key' => 'Enter a QuickStart Provisioning Key or choose manual geography setup.']);
            }

            $payload = [
                'license_key' => $licenseKey,
                'app_url' => config('app.url'),
                'domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
                'app_version' => config('app.version') ?? env('APP_VERSION'),
                'deployment_mode' => 'self_hosted',
            ];

            $preview = $this->remoteLicenseClient->preview($payload);
            $this->localLicenseService->log(null, $licenseKey, 'preview', ($preview['ok'] ?? false) ? 'success' : 'failed', $preview['message'] ?? null, $preview);

            if (($preview['ok'] ?? false) !== true) {
                return back()->withInput()->withErrors(['license_key' => $this->localLicenseService->customerMessage($preview['message'] ?? null, 'QuickStart provisioning key preview failed.')]);
            }

            $previewData = $this->localLicenseService->normalizePortalPayload($preview['data'] ?? []);

            session([
                'installer.geography_setup' => ['method' => 'quickstart'],
                'installer.license_key' => $licenseKey,
                'installer.license_preview' => $previewData,
                'installer.community_install' => false,
            ]);
            $this->stateStore->merge([
                'geography_setup' => ['method' => 'quickstart'],
                'license_key' => $licenseKey,
                'license_preview' => $previewData,
                'community_install' => false,
            ]);
            $this->completeStep('geography_setup');

            return redirect()->route('install.admin')->with('status', 'QuickStart provisioning key confirmed. Geography provisioning will happen during installation.');
        }

        return $this->view('geography_setup', [
            'preview' => data_get($this->currentState(), 'license_preview'),
            'display' => data_get($this->currentState(), 'license_preview') ? $this->localLicenseService->displayPayload(data_get($this->currentState(), 'license_preview')) : null,
            'geographySummary' => $this->geographySummary(),
            'quickstartOrderUrl' => $this->quickstartOrderUrl(),
        ]);
    }

    public function license(Request $request)
    {
        if ($request->isMethod('post') && !$request->has('method')) {
            $request->merge([
                'method' => trim((string) $request->input('license_key')) === '' ? 'manual' : 'quickstart',
            ]);
        }

        return $this->geographySetup($request);
    }

    public function admin(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:160'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:40'],
                'password' => ['required', 'confirmed', 'min:8'],
            ]);

            session(['installer.admin' => $data]);
            $this->stateStore->merge(['admin' => [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]]);
            $this->completeStep('admin');

            return redirect()->route('install.run');
        }

        return $this->view('admin');
    }

    public function run(Request $request)
    {
        if ($request->isMethod('post')) {
            $result = $this->installerService->run($this->currentState());

            if (!$result['ok']) {
                return back()
                    ->withErrors(['install' => $result['message']])
                    ->with('missing_step', $result['missing_step'] ?? null);
            }

            if (($result['installation_complete'] ?? false) === true) {
                session(['installer_complete.location_provisioning' => $result['location_provisioning'] ?? null]);
                session()->forget('installer');
                $this->stateStore->clear();

                return redirect()->route('install.complete')->with('status', $result['message'] ?? 'Community setup complete.');
            }

            $deferred = $result['location_provisioning_deferred'] ?? null;
            session([
                'installer.provisioning_started' => $deferred === null,
                'installer.location_provisioning_deferred' => $deferred,
            ]);
            $this->stateStore->merge([
                'provisioning_started' => $deferred === null,
                'location_provisioning_deferred' => $deferred,
            ]);

            return redirect()->route('install.provision')->with('status', $result['message'] ?? 'Core setup complete.');
        }

        return $this->view('run', [
            'display' => data_get($this->currentState(), 'license_preview') ? $this->localLicenseService->displayPayload(data_get($this->currentState(), 'license_preview')) : null,
            'provisioningCopy' => $this->provisioningCopy(data_get($this->currentState(), 'license_preview')),
        ]);
    }

    public function provision(Request $request)
    {
        if (data_get($this->currentState(), 'location_provisioning_deferred') && $request->isMethod('post')) {
            $result = $this->installerService->finishAfterProvisioning($this->currentState(), [
                'status' => 'deferred',
                'data_profile' => 'deferred',
                'warnings' => [
                    'The Campaign Manager portal was unreachable during installation. Complete full location provisioning later with php artisan campaign:locations:provision.',
                ],
            ]);

            if (!$result['ok']) {
                return back()->withErrors(['install' => $result['message']]);
            }

            session(['installer_complete.location_provisioning' => $result['location_provisioning'] ?? null]);
            session()->forget('installer');
            $this->stateStore->clear();

            return redirect()->route('install.complete');
        }

        return $this->view('provision', [
            'display' => data_get($this->currentState(), 'license_preview') ? $this->localLicenseService->displayPayload(data_get($this->currentState(), 'license_preview')) : null,
            'provisioningCopy' => $this->provisioningCopy(data_get($this->currentState(), 'license_preview')),
            'provisioningStarted' => (bool) data_get($this->currentState(), 'provisioning_started'),
            'provisioningDeferred' => data_get($this->currentState(), 'location_provisioning_deferred'),
        ]);
    }

    public function provisionLocationsStep()
    {
        $licenseKey = data_get($this->currentState(), 'license_key');
        $license = $licenseKey
            ? $this->localLicenseService->activatedForLicense($licenseKey)
            : $this->localLicenseService->current();

        if (!$license) {
            return response()->json([
                'ok' => false,
                'status' => 'failed',
                'message' => 'QuickStart provisioning setup is incomplete. Return to Run Installation and retry before provisioning locations.',
            ], 409);
        }

        if ($this->localLicenseService->activationRequired($license->raw_payload ?: []) && !$license->activation_id) {
            return response()->json([
                'ok' => false,
                'status' => 'failed',
                'message' => 'Legacy licence activation is incomplete. Return to Run Installation and retry activation before provisioning locations.',
            ], 409);
        }

        try {
            $run = $this->locationProvisioning->startOrResume($license);
            $result = $this->locationProvisioning->processNextBatch($run, (int) config('campaign.installer_location_batch_size', 500));

            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'status' => 'failed',
                'message' => 'Location setup could not continue. Please check server permissions and try again.',
            ], 422);
        }
    }

    public function finishProvisioning()
    {
        $run = $this->locationProvisioning->latestRun();
        if (!$run || $run->status !== 'completed') {
            return response()->json([
                'ok' => false,
                'message' => 'Location setup is still in progress.',
            ], 409);
        }

        $result = $this->installerService->finishAfterProvisioning($this->currentState(), [
            'status' => $run->status,
            'package_type' => $run->package_type,
            'deployment_mode' => 'self_hosted',
            'scope_type' => data_get($run->scope_payload ?: [], 'scope_type'),
            'scope_name' => data_get($run->scope_payload ?: [], 'scope_name'),
            'data_profile' => 'portal',
            'summary' => $run->summary ?: [],
            'warnings' => [],
        ]);

        if (!$result['ok']) {
            return response()->json($result, 422);
        }

        session(['installer_complete.location_provisioning' => $result['location_provisioning'] ?? null]);
        session()->forget('installer');
        $this->stateStore->clear();

        return response()->json([
            'ok' => true,
            'redirect' => route('install.complete'),
        ]);
    }

    public function complete()
    {
        return $this->view('complete', [
            'provisioningSummary' => session('installer_complete.location_provisioning'),
        ]);
    }


    private function geographyOptions(string $scopeType): array
    {
        try {
            $states = $this->geographyDirectory->states();
            return [
                'states' => $states,
                'senatorialDistricts' => $scopeType === 'senatorial_district' ? $this->directoryChildren($states, 'senatorialDistricts') : collect(),
                'federalConstituencies' => $scopeType === 'federal_constituency' ? $this->directoryChildren($states, 'federalConstituencies') : collect(),
                'lgas' => $scopeType === 'lga' ? $this->directoryChildren($states, 'lgas') : collect(),
                'directoryError' => null,
                'directorySource' => 'portal',
            ];
        } catch (Throwable $e) {
            $local = [
                'states' => $this->tableExists('states') ? State::query()->orderBy('name')->get(['id', 'name']) : collect(),
                'senatorialDistricts' => $this->tableExists('senatorial_districts') ? SenatorialDistrict::query()->orderBy('name')->get(['id', 'state_id', 'name']) : collect(),
                'federalConstituencies' => $this->tableExists('federal_constituencies') ? FederalConstituency::query()->orderBy('name')->get(['id', 'state_id', 'name']) : collect(),
                'lgas' => $this->tableExists('local_government_areas') ? LocalGovernmentArea::query()->orderBy('name')->get(['id', 'state_id', 'name']) : collect(),
                'directoryError' => $e->getMessage(),
                'directorySource' => 'local',
            ];

            return $local;
        }

        return [
            'states' => $this->tableExists('states') ? State::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'senatorialDistricts' => $this->tableExists('senatorial_districts') ? SenatorialDistrict::query()->orderBy('name')->get(['id', 'state_id', 'name']) : collect(),
            'federalConstituencies' => $this->tableExists('federal_constituencies') ? FederalConstituency::query()->orderBy('name')->get(['id', 'state_id', 'name']) : collect(),
            'lgas' => $this->tableExists('local_government_areas') ? LocalGovernmentArea::query()->orderBy('name')->get(['id', 'state_id', 'name']) : collect(),
            'directoryError' => null,
            'directorySource' => 'local',
        ];
    }

    private function directoryChildren($states, string $method)
    {
        return collect($states)->flatMap(function ($state) use ($method) {
            return $this->geographyDirectory->{$method}($state->id)
                ->map(fn ($item) => tap($item, fn ($row) => $row->state_id = (int) $state->id));
        })->values();
    }

    private function resolveCampaignGeography(string $scopeType, array $data): array
    {
        if ($scopeType === 'national') {
            return ['ok' => true, 'data' => ['country_name' => 'Nigeria', 'scope_name' => 'Nigeria', 'state_id' => null, 'senatorial_district_id' => null, 'federal_constituency_id' => null, 'lga_id' => null]];
        }

        if (!$this->tableExists('states') || State::query()->count() === 0) {
            $scopeName = trim((string) ($data['scope_name'] ?? ''));
            if ($scopeName === '') {
                return $this->resolveCampaignGeographyFromDirectory($scopeType, $data);
            }

            return ['ok' => true, 'data' => ['scope_name' => $scopeName, 'state_id' => null, 'senatorial_district_id' => null, 'federal_constituency_id' => null, 'lga_id' => null]];
        }

        $state = State::query()->find($data['state_id'] ?? null);
        if (!$state) {
            return $this->resolveCampaignGeographyFromDirectory($scopeType, $data);
        }

        $result = [
            'scope_name' => $state->name,
            'state_name' => $state->name,
            'state_code' => $state->inec_state_code ?? null,
            'state_id' => $state->id,
            'senatorial_district_id' => null,
            'federal_constituency_id' => null,
            'lga_id' => null,
        ];

        if ($scopeType === 'state') {
            return ['ok' => true, 'data' => $result];
        }

        if ($scopeType === 'senatorial_district') {
            $district = $this->tableExists('senatorial_districts') ? SenatorialDistrict::query()->whereKey($data['senatorial_district_id'] ?? null)->where('state_id', $state->id)->first() : null;
            if (!$district) {
                return $this->resolveCampaignGeographyFromDirectory($scopeType, $data);
            }

            $result['scope_name'] = $district->name;
            $result['senatorial_district_id'] = $district->id;
            $result['senatorial_district_name'] = $district->name;
            $result['senatorial_district_code'] = $district->inec_senatorial_district_code ?? null;
            return ['ok' => true, 'data' => $result];
        }

        if ($scopeType === 'federal_constituency') {
            $constituency = $this->tableExists('federal_constituencies') ? FederalConstituency::query()->whereKey($data['federal_constituency_id'] ?? null)->where('state_id', $state->id)->first() : null;
            if (!$constituency) {
                return $this->resolveCampaignGeographyFromDirectory($scopeType, $data);
            }

            $result['scope_name'] = $constituency->name;
            $result['federal_constituency_id'] = $constituency->id;
            $result['federal_constituency_name'] = $constituency->name;
            $result['federal_constituency_code'] = $constituency->inec_federal_constituency_code ?? null;
            return ['ok' => true, 'data' => $result];
        }

        if ($scopeType === 'lga') {
            $lga = $this->tableExists('local_government_areas') ? LocalGovernmentArea::query()->whereKey($data['lga_id'] ?? null)->where('state_id', $state->id)->first() : null;
            if (!$lga) {
                return $this->resolveCampaignGeographyFromDirectory($scopeType, $data);
            }

            $result['scope_name'] = $lga->name;
            $result['lga_id'] = $lga->id;
            $result['lga_name'] = $lga->name;
            $result['lga_code'] = $lga->inec_lga_code ?? null;
            return ['ok' => true, 'data' => $result];
        }

        return ['ok' => false, 'message' => 'Choose a supported campaign scope.'];
    }

    private function resolveCampaignGeographyFromDirectory(string $scopeType, array $data): array
    {
        $stateId = $data['state_id'] ?? null;
        if (!$stateId) {
            return ['ok' => false, 'message' => 'Choose the state for this campaign scope.'];
        }

        try {
            $state = $this->geographyDirectory->states()->firstWhere('id', (int) $stateId);
            if (!$state) {
                return ['ok' => false, 'message' => 'Choose a valid state from the Campaign Manager geography directory.'];
            }

            $result = [
                'scope_name' => $state->name,
                'state_name' => $state->name,
                'state_code' => $this->directoryCode($state),
                'state_id' => null,
                'portal_state_id' => (int) $state->id,
                'senatorial_district_id' => null,
                'federal_constituency_id' => null,
                'lga_id' => null,
            ];

            if ($scopeType === 'state') {
                return ['ok' => true, 'data' => $result];
            }

            if ($scopeType === 'senatorial_district') {
                $district = $this->geographyDirectory->senatorialDistricts($state->id)->firstWhere('id', (int) ($data['senatorial_district_id'] ?? 0));
                if (!$district) {
                    return ['ok' => false, 'message' => 'Choose a senatorial district that belongs to the selected state.'];
                }

                $result['scope_name'] = $district->name;
                $result['senatorial_district_name'] = $district->name;
                $result['senatorial_district_code'] = $this->directoryCode($district);
                $result['portal_senatorial_district_id'] = (int) $district->id;
                return ['ok' => true, 'data' => $result];
            }

            if ($scopeType === 'federal_constituency') {
                $constituency = $this->geographyDirectory->federalConstituencies($state->id)->firstWhere('id', (int) ($data['federal_constituency_id'] ?? 0));
                if (!$constituency) {
                    return ['ok' => false, 'message' => 'Choose a federal constituency that belongs to the selected state.'];
                }

                $result['scope_name'] = $constituency->name;
                $result['federal_constituency_name'] = $constituency->name;
                $result['federal_constituency_code'] = $this->directoryCode($constituency);
                $result['portal_federal_constituency_id'] = (int) $constituency->id;
                return ['ok' => true, 'data' => $result];
            }

            if ($scopeType === 'lga') {
                $lga = $this->geographyDirectory->lgas($state->id)->firstWhere('id', (int) ($data['lga_id'] ?? 0));
                if (!$lga) {
                    return ['ok' => false, 'message' => 'Choose an LGA that belongs to the selected state.'];
                }

                $result['scope_name'] = $lga->name;
                $result['lga_name'] = $lga->name;
                $result['lga_code'] = $this->directoryCode($lga);
                $result['portal_lga_id'] = (int) $lga->id;
                return ['ok' => true, 'data' => $result];
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return ['ok' => false, 'message' => 'Choose a supported campaign scope.'];
    }

    private function directoryCode(object $item): ?string
    {
        $code = $item->inec_code ?? $item->code ?? null;

        return $code !== null && trim((string) $code) !== '' ? trim((string) $code) : null;
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private function campaignSummary(): array
    {
        $state = $this->currentState();

        return [
            'scope' => $state['campaign_scope'] ?? [],
            'geography' => $state['campaign_geography'] ?? [],
            'identity' => $state['campaign_identity'] ?? [],
            'setup' => $state['geography_setup'] ?? [],
        ];
    }
    private function view(string $step, array $data = [])
    {
        if (request()->isMethod('get') && $redirect = $this->guardStep($step)) {
            return $redirect;
        }

        $state = $this->currentState();

        return view('installer.step', array_merge($data, [
            'step' => $step,
            'steps' => $this->steps,
            'stepGroups' => $this->stepGroups,
            'state' => $state,
            'previousStep' => $this->previousSteps[$step] ?? null,
            'completedSteps' => $this->completedSteps($state),
            'clickableSteps' => $this->clickableSteps($state),
            'welcomeMeta' => $this->welcomeMeta(),
        ]));
    }

    private function geographySummary(): array
    {
        $state = $this->currentState();
        $scopeType = data_get($state, 'campaign_scope.scope_type', 'national');
        $geography = $state['campaign_geography'] ?? [];

        try {
            return $this->geographyDirectory->summary($scopeType, $geography);
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'scope_name' => $geography['scope_name'] ?? $geography['state_name'] ?? 'selected campaign geography',
                'counts' => [],
                'message' => 'The geography summary is temporarily unavailable. Manual setup can continue; QuickStart provisioning requires Portal connectivity.',
                'detail' => $e->getMessage(),
            ];
        }
    }

    private function quickstartOrderUrl(): string
    {
        $state = $this->currentState();
        $scope = $state['campaign_scope'] ?? [];
        $geography = $state['campaign_geography'] ?? [];
        $scopeType = $scope['scope_type'] ?? null;
        $base = rtrim((string) config('campaign.portal_web_base', 'https://campaignmanager.ng'), '/');
        $query = [
            'service' => 'quickstart_geography',
            'package' => $this->portalPackageType($scope['package_type'] ?? null),
            'scope_type' => $scopeType,
        ];

        if (in_array($scopeType, ['state', 'senatorial_district', 'federal_constituency', 'lga'], true)) {
            $query['campaign_state_id'] = $geography['portal_state_id'] ?? null;
        }

        if ($scopeType === 'senatorial_district') {
            $query['campaign_senatorial_district_id'] = $geography['portal_senatorial_district_id'] ?? null;
        }

        if ($scopeType === 'federal_constituency') {
            $query['campaign_federal_constituency_id'] = $geography['portal_federal_constituency_id'] ?? null;
        }

        if ($scopeType === 'lga') {
            $query['campaign_lga_id'] = $geography['portal_lga_id'] ?? null;
        }

        $query = array_filter($query, fn ($value) => $value !== null && $value !== '');

        return $base.'/order/campaign-manager'.($query ? '?'.http_build_query($query) : '');
    }

    private function portalPackageType(?string $packageType): ?string
    {
        return $packageType === 'chairmanship' ? 'lga' : $packageType;
    }

    private function currentState(): array
    {
        return array_replace_recursive($this->stateStore->all(), session('installer', []));
    }

    private function completeStep(string $step): void
    {
        $completed = session('installer.completed_steps', []);
        $completed[] = $step;
        session([
            'installer.completed_steps' => array_values(array_unique($completed)),
            'installer.current_step' => $step,
        ]);
        $this->stateStore->markCompleted($step);
    }

    private function guardStep(string $step): mixed
    {
        if (in_array($step, ['welcome', 'requirements', 'complete'], true)) {
            return null;
        }

        $firstIncomplete = $this->firstIncompleteBefore($step, $this->currentState());

        if (!$firstIncomplete) {
            return null;
        }

        return redirect()
            ->route('install.'.$firstIncomplete)
            ->withErrors(['navigation' => 'Complete '.$this->steps[$firstIncomplete].' before continuing to '.$this->steps[$step].'.']);
    }

    private function firstIncompleteBefore(string $targetStep, array $state): ?string
    {
        foreach (array_keys($this->steps) as $candidate) {
            if ($candidate === $targetStep) {
                return null;
            }

            if (in_array($candidate, ['welcome', 'complete'], true)) {
                continue;
            }

            if (!$this->stepComplete($candidate, $state)) {
                return $candidate;
            }
        }

        return null;
    }

    private function completedSteps(array $state): array
    {
        return array_values(array_filter(array_keys($this->steps), fn (string $step) => $this->stepComplete($step, $state)));
    }

    private function clickableSteps(array $state): array
    {
        return array_values(array_filter(array_keys($this->steps), fn (string $step) => $step !== 'complete' && !$this->firstIncompleteBefore($step, $state)));
    }

    private function stepComplete(string $step, array $state): bool
    {
        $completed = $state['completed_steps'] ?? [];

        return match ($step) {
            'welcome' => true,
            'requirements', 'mail', 'storage' => in_array($step, $completed, true),
            'environment' => $this->hasKeys($state['environment'] ?? [], ['APP_NAME', 'APP_URL', 'APP_ENV', 'APP_DEBUG', 'APP_TIMEZONE', 'DEPLOYMENT_MODE']),
            'database' => $this->hasKeys($state['database'] ?? [], ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME']),
            'campaign_scope' => !empty($state['campaign_scope']['package_type']) && !empty($state['campaign_scope']['scope_type']),
            'campaign_geography' => $this->campaignInstallationSettings->completeGeography($state['campaign_scope'] ?? [], $state['campaign_geography'] ?? []),
            'campaign_identity' => !empty($state['campaign_identity']['system_name']),
            'geography_setup' => ($state['geography_setup']['method'] ?? null) === 'manual' || (($state['geography_setup']['method'] ?? null) === 'quickstart' && !empty($state['license_key']) && !empty($state['license_preview'])),
            'admin' => in_array('admin', $completed, true),
            'run' => (bool) ($state['installation_complete'] ?? $state['provisioning_started'] ?? $state['location_provisioning_deferred'] ?? false),
            'provision' => (bool) ($state['provisioning_started'] ?? $state['location_provisioning_deferred'] ?? false),
            'complete' => false,
            default => false,
        };
    }

    private function hasKeys(array $values, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $values) || $values[$key] === null || $values[$key] === '') {
                return false;
            }
        }

        return true;
    }

    private function welcomeMeta(): array
    {
        $appUrl = config('app.url');

        return [
            'app_url' => $appUrl,
            'detected_domain' => parse_url((string) $appUrl, PHP_URL_HOST) ?: request()->getHost(),
            'deployment_mode' => config('campaign.deployment_mode'),
            'session_driver' => config('session.driver'),
            'cache_store' => config('cache.default'),
            'queue_connection' => config('queue.default'),
        ];
    }

    private function firstRunDriverWarning(): ?string
    {
        $envDrivers = $this->environmentWriter->read(['SESSION_DRIVER', 'CACHE_STORE', 'QUEUE_CONNECTION']);
        $sessionDriver = $envDrivers['SESSION_DRIVER'] ?? config('session.driver');
        $cacheStore = $envDrivers['CACHE_STORE'] ?? config('cache.default');
        $queueConnection = $envDrivers['QUEUE_CONNECTION'] ?? config('queue.default');

        if ($sessionDriver !== 'database' && $cacheStore !== 'database' && $queueConnection !== 'database') {
            return null;
        }

        return 'First-run setup should use SESSION_DRIVER=file, CACHE_STORE=file, and QUEUE_CONNECTION=sync. The Environment step will write these safe installer defaults before continuing.';
    }

    private function provisioningCopy(?array $preview): string
    {
        $package = strtolower((string) data_get($preview, 'license.package_type', data_get($preview, 'package_type', '')));
        $scopeName = data_get($preview, 'scope.scope_name', data_get($preview, 'scope_name'));

        if ($package === 'presidential') {
            return 'National campaign location setup may take a few minutes. Progress is saved automatically, so you can continue if interrupted.';
        }

        if ($package === 'governorship') {
            return 'Only the selected state and related campaign geography will be installed.'.($scopeName ? ' Selected state: '.$scopeName.'.' : '');
        }

        return 'The installer will prepare the campaign geography included with your provisioning key.';
    }
}

