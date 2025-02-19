<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class ValidateLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow localhost to work without activation
        if ($request->getHost() === 'localhost' || $request->getHost() === '127.0.0.1') {
            return $next($request);
        }

        $settings = DB::table('system_settings')->first();

        if ($settings && $settings->activation_code) {
            $originDomain = $request->getHost();

            // Decrypt activation code
            $decryptedCode = Crypt::decryptString($settings->activation_code);

            // Validate activation code with remote server
            $response = Http::post('https://campaignmanager.ng/validate', [
                'activation_code' => $decryptedCode,
                'domain' => $originDomain,
            ]);

            if (!$response->successful() || !$response->json('valid')) {
                // Invalidate session and redirect to activation form
                auth()->logout();
                return redirect()->route('activation.form')->withErrors(['activation_code' => 'License validation failed.']);
            }
        }

        return $next($request);
    }
}

