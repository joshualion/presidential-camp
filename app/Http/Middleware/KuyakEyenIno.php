<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;

class KuyakEyenIno
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Cache the settings for 5 minutes to reduce DB load
            $settings = Cache::remember('system_settings', 300, function () {
                return DB::table('system_settings')->first();
            });
        } catch (QueryException $e) {
            // Handle cases where the system_settings table does not exist
            return abort(500, 'System configuration error. Please contact support.');
        }

        // If system settings are missing or activation_code is null, require activation
        if (!$settings || is_null($settings->activation_code)) {
            if (Auth::check() && Auth::user()->access_level === 'superadmin') {
                return redirect()->route('activation.form');
            } else {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return abort(403, 'System not activated. Contact the administrator.');
            }
        }

        return $next($request);
    }
}
