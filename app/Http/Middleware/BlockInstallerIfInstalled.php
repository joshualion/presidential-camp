<?php

namespace App\Http\Middleware;

use App\Services\InstallationStateService;
use App\Services\DeploymentModeService;
use App\Services\AccessLevelRouteService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockInstallerIfInstalled
{
    public function __construct(
        private InstallationStateService $installationState,
        private DeploymentModeService $deploymentMode,
        private AccessLevelRouteService $accessLevelRoutes,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->deploymentMode->installerShouldRedirect()) {
            return auth()->check() ? redirect()->route($this->dashboardRoute()) : redirect()->route('login');
        }

        if (!$this->installationState->installed() || $request->routeIs('install.complete')) {
            return $next($request);
        }

        return auth()->check()
            ? redirect()->route($this->dashboardRoute())
            : redirect()->route('login');
    }

    private function dashboardRoute(): string
    {
        return $this->accessLevelRoutes->dashboardRouteForUser(auth()->user()) ?? 'login';
    }
}
