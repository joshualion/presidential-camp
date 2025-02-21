<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EnsureSystemNotActivated
{
    public function handle(Request $request, Closure $next)
    {
        // Fetch system settings (cached for 1 minutes)
        $settings = Cache::remember('system_settings', 60, function () {
            return DB::table('system_settings')->first();
        });

        // Check if the system is activated
        if ($settings && $this->isSystemActivated($settings)) {
            // System is already activated, deny access
            $notification = [
                'message' => 'The system is already activated.',
                'alert-type' => 'error'
            ];
            return redirect()->route('superadmin.dashboard')->with($notification);
        }

        // System is not activated, allow access to the activation form
        return $next($request);
    }

    /**
     * Check if the system is activated.
     */
    protected function isSystemActivated($settings)
    {
        return !is_null($settings->activation_code) &&
               !is_null($settings->domain) &&
               !is_null($settings->activated_at);
    }
}