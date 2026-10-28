<?php

namespace App\Http\Middleware;

use App\Services\CampaignCapabilityService;
use App\Services\Enterprise\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnterpriseFeature
{
    public function handle(Request $request, Closure $next, string $feature, string $permission): Response
    {
        abort_unless($request->user() && in_array($permission, config('enterprise.permissions', []), true)
            && app(CampaignCapabilityService::class)->allows($request->user(), $permission), 403, 'Permission not assigned.');
        abort_unless(app(EntitlementService::class)->active($feature), 402, 'An active installation add-on is required.');
        // Controllers, jobs and exports must also apply the existing LocationScopeService.
        return $next($request);
    }
}
