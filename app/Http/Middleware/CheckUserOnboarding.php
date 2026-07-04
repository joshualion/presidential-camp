<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Support\SafeDatabase;
use App\Services\AccessLevelRouteService;

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
            return redirect()->route($this->accountRoute($user, 'account.update-credentials'));
        }


        // Step 2: Check if profile is incomplete
        if (!$this->isProfileComplete($user)) {
            return redirect()->route($this->accountRoute($user, 'account.complete-profile'));
        }

            // Step 3: Check if user belongs to at least one volunteer group
        if ($user->supportGroups()->count() === 0) {
            return redirect()->route($this->accountRoute($user, 'account.select-support-group'));
        }

        // Allow access to the next middleware or controller
        return $next($request);
    }

    private function accountRoute($user, string $route): string
    {
        return app(AccessLevelRouteService::class)->sharedRouteForUser($user, $route) ?? 'login';
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
        $locationComplete = match ($user->access_level) {
            'regionaladmin' => !empty($user->region_id),
            'stateadmin' => !empty($user->state_id),
            'senatorialadmin' => !empty($user->senatorial_district_id),
            'federaladmin' => !empty($user->federal_constituency_id),
            'lgaadmin' => !empty($user->lga_id),
            'wardadmin' => !empty($user->ward_id),
            'puadmin', 'user' => !empty($user->polling_unit_id),
            default => true,
        };

        if ($requireBank && $user->access_level !== 'user') {
            if (!$hasBank) {
                session()->flash('warning', 'Bank details are required. Please update your profile.');
            }
            return $baseComplete && $locationComplete && $hasBank;
        }

        return $baseComplete && $locationComplete;
    }
}
