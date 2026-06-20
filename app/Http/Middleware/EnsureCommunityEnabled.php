<?php

namespace App\Http\Middleware;

use App\Services\CommunityRealtimeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCommunityEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app(CommunityRealtimeService::class)->enabled()) {
            return $next($request);
        }

        $message = 'Community Forum is currently disabled for this installation.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        if (!$request->user()) {
            return redirect()->route('login')->with([
                'message' => $message,
                'alert-type' => 'warning',
            ]);
        }

        $route = match ($request->user()->access_level) {
            'superadmin' => 'superadmin.dashboard',
            'nationaladmin' => 'nationaladmin.dashboard',
            'regionaladmin' => 'regionaladmin.dashboard',
            'stateadmin' => 'stateadmin.dashboard',
            'senatorialadmin' => 'senatorialadmin.dashboard',
            'federaladmin' => 'federaladmin.dashboard',
            'lgaadmin' => 'lgaadmin.dashboard',
            'wardadmin' => 'wardadmin.dashboard',
            'puadmin' => 'puadmin.dashboard',
            default => 'user.dashboard',
        };

        return redirect()->route($route)->with([
            'message' => $message,
            'alert-type' => 'warning',
        ]);
    }
}
