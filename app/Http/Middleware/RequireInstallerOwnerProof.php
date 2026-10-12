<?php

namespace App\Http\Middleware;

use App\Services\InstallationStateService;
use App\Services\Installer\InstallerAccessService;
use Closure;
use Illuminate\Http\Request;

class RequireInstallerOwnerProof
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('install.access', 'install.access.verify')) {
            return $next($request);
        }
        if (app(InstallationStateService::class)->installed()) {
            return (is_string($request->session()->get('installer.owner_proof')) && hash_equals(app(InstallerAccessService::class)->proof(), $request->session()->get('installer.owner_proof'))) || $request->user()?->access_level === 'superadmin'
                ? $next($request) : redirect()->route('login');
        }
        $proof = $request->session()->get('installer.owner_proof');
        if (!is_string($proof) || !hash_equals(app(InstallerAccessService::class)->proof(), $proof)) {
            return $request->expectsJson() ? response()->json(['message' => 'Server setup ownership must be confirmed.'], 403) : redirect()->route('install.access');
        }
        return $next($request);
    }
}
