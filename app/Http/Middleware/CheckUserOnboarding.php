<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Support\SafeDatabase;

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

            // Step 3: Check if user belongs to at least one volunteer group
        if ($user->supportGroups()->count() === 0) {
            return redirect()->route('select.support.group');
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
         // Example rules: reject test emails or default password
        $badEmail = str_contains($user->email, '@example.com');
        $badPassword = Hash::check('password', $user->password);

        return $badEmail || $badPassword || $user->requires_update;
    }

    /**
     * Determine if the user's profile is complete.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    protected function isProfileComplete($user): bool
    {
        $requireBank = SafeDatabase::hasTable('system_settings')
            ? optional(\App\Models\SystemSetting::first())->require_bank_details
            : false;
        $hasBank = $user->bank && $user->bank_account_number;

        $baseComplete = $user->firstname && $user->lastname && $user->phone;
        $locationComplete = true;

        if ($requireBank && $user->access_level !== 'user') {
            if (!$hasBank) {
                session()->flash('warning', 'Bank details are required. Please update your profile.');
            }
            return $baseComplete && $locationComplete && $hasBank;
        }

        return $baseComplete && $locationComplete;
    }
}
