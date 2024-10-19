<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Accesslevel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $access_level): Response
    {
        if (($request->user()->access_level !== $access_level) ) {
            //logout if auth exists but is not admin
            if ($request->user()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $notification = [
                'message' => 'You have been logged out',
                'alert-type' => 'error'
            ];

            return redirect()->route('login')->with($notification);
        }



        return $next($request);
    }
}
