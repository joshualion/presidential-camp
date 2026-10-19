<?php

namespace App\Http\Middleware;

use App\Services\ModuleGateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(private ModuleGateService $modules)
    {
    }

    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (in_array($module, ['sms','bulk_sms'], true) && !config('direct_sms.portal_visible')) abort(404);
        $this->modules->requireEnabled($module);

        return $next($request);
    }
}
