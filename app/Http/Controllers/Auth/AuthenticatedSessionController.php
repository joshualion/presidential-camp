<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\SystemSetting;
use App\Services\AccessLevelRouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, AccessLevelRouteService $accessLevelRoutes): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $setting = SystemSetting::find(1);

        $url = null;
        if($setting?->frontend_community == 1 && Auth::user()->access_level === 'user'){
            $url = '/community/feed';
        }

        $dashboardRoute = $accessLevelRoutes->dashboardRouteForUser(Auth::user());

        if (!$url && !$dashboardRoute) {
            //logout User something has gone wrong
           Auth::guard('web')->logout();
            $request->session()->invalidate();
           $request->session()->regenerateToken();
            $notification =[
                'message' => 'Something went wrong, please try again in a few minutes',
                'alert-type' => 'error'
            ];

            return redirect()->back()->with($notification);

        }

        $notification = [
            'message' => 'Welcome back '.Auth::user()->firstname,
            'alert-type' => 'success'
        ];


        return ($url ? redirect($url) : redirect()->route($dashboardRoute))->with($notification);

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
