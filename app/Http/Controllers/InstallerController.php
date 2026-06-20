<?php

namespace App\Http\Controllers;

use App\Services\Installer\DatabaseSetupService;
use App\Services\Installer\EnvironmentWriterService;
use App\Services\Installer\InstallerService;
use App\Services\Installer\InstallerStateStore;
use App\Services\Installer\RequirementCheckService;
use App\Services\Installer\StorageSetupService;
use App\Services\LicensedLocationProvisioningService;
use App\Services\LocalLicenseService;
use App\Services\RemoteLicenseClient;
use Illuminate\Http\Request;

class InstallerController extends Controller
{
    private array $steps = [
        'welcome' => 'Welcome',
        'requirements' => 'Requirements',
        'environment' => 'Environment',
        'database' => 'Database',
        'mail' => 'Mail',
        'storage' => 'Storage',
        'license' => 'License',
        'admin' => 'Super Admin',
        'run' => 'Run',
        'complete' => 'Complete',
    ];

    private array $previousSteps = [
        'requirements' => 'welcome',
        'environment' => 'requirements',
        'database' => 'environment',
        'mail' => 'database',
        'storage' => 'mail',
        'license' => 'storage',
        'admin' => 'license',
        'run' => 'admin',
    ];

    public function __construct(
        private RequirementCheckService $requirements,
        private EnvironmentWriterService $environmentWriter,
        private DatabaseSetupService $databaseSetup,
        private StorageSetupService $storageSetup,
        private RemoteLicenseClient $remoteLicenseClient,
        private LocalLicenseService $localLicenseService,
        private InstallerService $installerService,
        private InstallerStateStore $stateStore,
        private LicensedLocationProvisioningService $locationProvisioning,
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
            return redirect()->route('install.license');
        }

        return $this->view('storage', ['storageResult' => $result]);
    }

    public function license(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'license_key' => ['required', 'string', 'max:255'],
            ]);

            $payload = [
                'license_key' => $data['license_key'],
                'app_url' => config('app.url'),
                'domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
                'app_version' => config('app.version') ?? env('APP_VERSION'),
                'deployment_mode' => 'self_hosted',
            ];

            $preview = $this->remoteLicenseClient->preview($payload);
            $this->localLicenseService->log(null, $data['license_key'], 'preview', ($preview['ok'] ?? false) ? 'success' : 'failed', $preview['message'] ?? null, $preview);

            if (($preview['ok'] ?? false) !== true) {
                return back()->withInput()->withErrors(['license_key' => $preview['message'] ?? 'License preview failed.']);
            }

            session([
                'installer.license_key' => $data['license_key'],
                'installer.license_preview' => $preview['data'] ?? [],
            ]);
            $this->stateStore->merge([
                'license_key' => $data['license_key'],
                'license_preview' => $preview['data'] ?? [],
            ]);
            $this->completeStep('license');

            return redirect()->route('install.admin')->with('status', 'License entitlement confirmed. Final activation will happen during installation.');
        }

        return $this->view('license', [
            'preview' => data_get($this->currentState(), 'license_preview'),
            'display' => data_get($this->currentState(), 'license_preview') ? $this->localLicenseService->displayPayload(data_get($this->currentState(), 'license_preview')) : null,
        ]);
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
            if ($missing = $this->missingLocationFiles()) {
                return back()
                    ->withErrors(['install' => $this->missingLocationFilesMessage($missing)])
                    ->with('missing_step', 'run');
            }

            $result = $this->installerService->run($this->currentState());

            if (!$result['ok']) {
                return back()
                    ->withErrors(['install' => $result['message']])
                    ->with('missing_step', $result['missing_step'] ?? null);
            }

            session(['installer_complete.location_provisioning' => $result['location_provisioning'] ?? null]);
            session()->forget('installer');
            $this->stateStore->clear();

            return redirect()->route('install.complete');
        }

        return $this->view('run', [
            'display' => data_get($this->currentState(), 'license_preview') ? $this->localLicenseService->displayPayload(data_get($this->currentState(), 'license_preview')) : null,
            'provisioningPreflight' => $this->provisioningPreflight(),
            'provisioningCopy' => $this->provisioningCopy(data_get($this->currentState(), 'license_preview')),
        ]);
    }

    public function complete()
    {
        return $this->view('complete', [
            'provisioningSummary' => session('installer_complete.location_provisioning'),
        ]);
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
            'state' => $state,
            'previousStep' => $this->previousSteps[$step] ?? null,
            'completedSteps' => $this->completedSteps($state),
            'clickableSteps' => $this->clickableSteps($state),
            'welcomeMeta' => $this->welcomeMeta(),
        ]));
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
            'license' => !empty($state['license_key']) && !empty($state['license_preview']),
            'admin' => in_array('admin', $completed, true),
            'run' => false,
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

    private function provisioningPreflight(): array
    {
        $files = $this->locationProvisioning->inspectSource();
        $missing = $this->missingLocationFiles();

        return [
            'source_path' => $this->locationProvisioning->defaultSourcePath(),
            'files' => $files,
            'missing' => $missing,
            'ok' => $missing === [],
            'message' => $missing === []
                ? 'Location data files are ready for licensed provisioning.'
                : $this->missingLocationFilesMessage($missing),
        ];
    }

    private function missingLocationFiles(): array
    {
        return $this->locationProvisioning->missingSourceFiles();
    }

    private function missingLocationFilesMessage(array $missing): string
    {
        return 'Location data files are missing. Please upload the complete Campaign Manager package or contact support. Missing: '.implode(', ', $missing).'.';
    }

    private function provisioningCopy(?array $preview): string
    {
        $package = strtolower((string) data_get($preview, 'license.package_type', data_get($preview, 'package_type', '')));
        $scopeName = data_get($preview, 'scope.scope_name', data_get($preview, 'scope_name'));

        if ($package === 'presidential') {
            return 'The full national location dataset will be installed.';
        }

        if ($package === 'governorship') {
            return 'Only your licensed state and related campaign geography will be installed.'.($scopeName ? ' Licensed state: '.$scopeName.'.' : '');
        }

        return 'The installer will now prepare your licensed campaign geography. Only the region, state, districts, constituencies, LGAs, wards, and polling units included in your license will be installed.';
    }
}
