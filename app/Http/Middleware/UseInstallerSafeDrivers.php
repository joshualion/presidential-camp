<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class UseInstallerSafeDrivers
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isSelfHostedUninstalled()) {
            Config::set('session.driver', 'file');
            Config::set('cache.default', 'file');
            Config::set('queue.default', 'sync');
        }

        return $next($request);
    }

    private function isSelfHostedUninstalled(): bool
    {
        $mode = strtolower((string) config('campaign.deployment_mode', 'managed'));

        return $mode === 'self_hosted' && !File::exists(storage_path('app/installed.lock'));
    }
}
