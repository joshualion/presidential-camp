<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class KuyakEyenIno
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
   
    public function handle(Request $request, Closure $next)
    {
        // Allow localhost to work without activation
        if ($request->getHost() === 'localhost' || $request->getHost() === '127.0.0.1') {
            return $next($request);
        }

        // Check if activation_code is null in system_settings
        $settings = DB::table('system_settings')->first();
        
        if ($settings && $settings->activation_code === null) {
            // Check if the user is logged in and has superadmin access
            if (Auth::check() && Auth::user()->access_level === 'superadmin') {
                // Redirect to activation form if activation_code is null and user is superadmin
                return redirect()->route('activation.form');
            } else {
                // If the user is not a superadmin, show a message that the system is not ready for use
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
       
                $notification = [
                    'message' => 'The system is not ready for use. Please contact the administrator.',
                    'alert-type' => 'error'
                ];
                return redirect()->route('login')->with($notification);
            }
        }

        return $next($request);
    }
}


