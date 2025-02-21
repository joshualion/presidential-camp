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
            // Cache the settings for 2 minutes to reduce DB load
            $settings = Cache::remember('system_settings', 120, function () {
                return DB::table('system_settings')->first();
            });
        } catch (QueryException $e) {
            // Handle cases where the system_settings table does not exist
            return abort(500, 'System configuration error. Please contact support.');
        }

        // If system settings are missing, require activation
        if (!$settings) {
            return $this->handleActivationRequired($request, 'System configuration missing. Please contact support.');
        }

        // Check if the system is activated
        if (!$this->isSystemActivated($settings)) {
            return $this->handleActivationRequired($request, 'System not activated. Contact the administrator.');
        }

        return $next($request);
    }

    /**
     * Check if the system is activated.
     */
    protected function isSystemActivated($settings)
    {
        // Ensure all required fields are present and not null
        return !is_null($settings->activation_code) &&
               !is_null($settings->domain) &&
               !is_null($settings->activated_at);
    }

    /**
     * Handle activation required scenarios.
     */
    protected function handleActivationRequired(Request $request, $message)
    {
        if (Auth::check() && Auth::user()->access_level === 'superadmin') {
            // Clear cache to ensure fresh settings are fetched
            Cache::forget('system_settings');
            return redirect()->route('activation.form');
        } else {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return abort(403, $message);
        }
    }
}