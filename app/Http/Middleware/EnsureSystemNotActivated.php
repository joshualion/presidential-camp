<?php

namespace App\Http\Middleware;

use App\Services\DeploymentModeService;
use App\Services\InstallationStateService;
use App\Services\LocalLicenseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Support\SafeDatabase;

class EnsureSystemNotActivated
{
    public function __construct(
        private DeploymentModeService $deploymentMode,
        private InstallationStateService $installationState,
        private LocalLicenseService $localLicenseService,
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        if ($this->localLicenseService->isLocallyActivated()) {
            return redirect()
                ->route('admin.license.show')
                ->with('status', 'This installation is already activated.');
        }

        if ($this->deploymentMode->isSelfHosted() && $this->installationState->installed()) {
            return redirect()
                ->route('admin.license.show')
                ->with('status', 'Community installations do not require commercial license activation.');
        }

        if (!SafeDatabase::hasTable('system_settings')) {
            return $next($request);
        }

        // Fetch system settings (cached for 1 minutes)
        $settings = Cache::remember('system_settings', 60, function () {
            return DB::table('system_settings')->first();
        });

        // Check if the system is activated
        if ($settings && $this->isSystemActivated($settings)) {
            // System is already activated, deny access
            $notification = [
                'message' => 'The system is already activated.',
                'alert-type' => 'error'
            ];
            return redirect()->route('superadmin.dashboard')->with($notification);
        }

        // System is not activated, allow access to the activation form
        return $next($request);
    }

    /**
     * Check if the system is activated.
     */
    protected function isSystemActivated($settings)
    {
        return !is_null($settings->activation_code) &&
               !is_null($settings->domain) &&
               !is_null($settings->activated_at);
    }
}
