<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\AccessLevelRouteService;
use Symfony\Component\HttpFoundation\Response;

class EnsureCommunityProfileLocationComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $this->hasRequiredLocation($user)) {
            return $next($request);
        }

        $profileRoute = $this->profileRouteFor((string) $user->access_level);
        $profileUrl = route($profileRoute);
        $message = 'Please update your voting location before using the Community Forum.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'profile_url' => $profileUrl,
            ], 409);
        }

        return redirect()->route($profileRoute)->with('warning', $message);
    }

    private function hasRequiredLocation($user): bool
    {
        return !empty($user->region_id)
            && !empty($user->state_id)
            && !empty($user->lga_id)
            && !empty($user->ward_id)
            && !empty($user->polling_unit_id);
    }

    private function profileRouteFor(string $accessLevel): string
    {
        $route = $accessLevel.'.profile';

        return Route::has($route)
            ? $route
            : app(AccessLevelRouteService::class)->sharedRouteForUser(request()->user(), 'account.complete-profile');
    }
}
