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
        $this->modules->requireEnabled($module);

        return $next($request);
    }
}
