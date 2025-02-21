<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidateLicense
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow localhost during development
        if (in_array($request->getHost(), ['localhost', '127.0.0.1'])) {
            return $next($request);
        }

        // Fetch system settings from cache or database
        $settings = Cache::remember('system_settings', now()->addMinutes(30), function () {
            return DB::table('system_settings')->first();
        });

        if (!$settings || !$settings->activation_code || !$settings->api_token) {
            if (Auth::check() && Auth::user()->access_level === 'superadmin') {
                Cache::forget('system_settings'); // Ensure fresh settings
                return redirect()->route('activation.form');
            } else {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();    
                abort(403, 'System not activated. Contact the administrator.');
            }
        }

        // Check if the license is already validated (cache prevents excessive requests)
        if (!Cache::has('license_valid')) {
            $originDomain = $request->getHost();

            
            // Validate activation code with remote server
            $response = Http::post('https://campaignmanager.ng/check-license', [
                'activation_code' => $settings->activation_code,
                'domain' => $originDomain,
                'api_token' => $settings->api_token,
            ]);

            // Log response for debugging
            Log::info('License validation response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if (!$response->successful() || !$response->json('valid')) {
                Cache::forget('license_valid'); // Invalidate cache
                Auth::logout();
                return redirect()->route('activation.form')->withErrors(['activation_code' => 'License validation failed.']);
            }

            // Store validation result in cache for 6 hours
            Cache::put('license_valid', true, now()->addHours(6));
        }

        return $next($request);
    }
}
