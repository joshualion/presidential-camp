<?php

namespace App\Http\Middleware;

use App\Services\DeploymentModeService;
use App\Services\InstallationStateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotInstalled
{
    public function __construct(
        private DeploymentModeService $deploymentMode,
        private InstallationStateService $installationState,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->deploymentMode->installerShouldRedirect() || $this->installationState->installed()) {
            return $next($request);
        }

        if ($this->isInstallerOrPublicPath($request)) {
            return $next($request);
        }

        return redirect()->route('install.welcome');
    }

    private function isInstallerOrPublicPath(Request $request): bool
    {
        return $request->is(
            'install',
            'install/*',
            'build/*',
            'assets/*',
            'css/*',
            'js/*',
            'images/*',
            'storage/*',
            'favicon.ico',
            'up'
        );
    }
}
