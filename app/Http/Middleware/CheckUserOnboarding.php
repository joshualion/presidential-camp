<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CheckUserOnboarding
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Step 1: Check if credentials need to be updated
        if ($this->requiresCredentialUpdate($user)) {
            return redirect()->route('update.credentials');
        }

        
        // Step 2: Check if profile is incomplete
        if (!$this->isProfileComplete($user)) {
            return redirect()->route('complete.profile');
        }

        // Allow access to the next middleware or controller
        return $next($request);
    }

    /**
     * Determine if the user's credentials need to be updated.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    protected function requiresCredentialUpdate($user): bool
    {
        // Dynamic checks: email contains @example.com or password is "password"
        $dynamicCheck = strpos($user->email, '@example.com') !== false || 
                        Hash::check('password', $user->password);

        // Static check: requires_update column in the database
        return $dynamicCheck || $user->requires_update;
    }

    /**
     * Determine if the user's profile is complete.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    protected function isProfileComplete($user): bool
    {
        return $user->firstname && $user->lastname && $user->phone;
    }
}
