<?php

namespace App\Http\Middleware;

use App\Services\CampaignCapabilityService;
use Closure;
use Illuminate\Http\Request;

class EnsureCampaignCapability
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $route = $request->route();
        if ($user && $route instanceof \Illuminate\Routing\Route) {
            $service = app(CampaignCapabilityService::class);
            $permission = $service->routePermission($route);
            if ($permission) {
                abort_unless(collect(explode('|', $permission))->contains(fn ($name) => $service->allows($user, $name)), 403, 'This action is not permitted for your account.');
            }
        }

        return $next($request);
    }
}
