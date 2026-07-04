<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Accesslevel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$accessLevels): Response
    {
        $user = $request->user();

        // Normalize accepted access levels from the variadic args
        $allowedLevels = collect($accessLevels)
            ->filter()
            ->flatten()
            ->map(fn ($level) => trim((string) $level))
            ->filter()
            ->values()
            ->all();

        if (!$user || empty($allowedLevels) || !in_array($user->access_level, $allowedLevels, true)) {
            abort(403, 'You are not authorized to access this area.');
        }



        return $next($request);
    }
}
