<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\SystemSetting;
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
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $setting = SystemSetting::find(1);

        $url ='';
        if($setting?->frontend_community == 1 && Auth::user()->access_level === 'user'){
            $url = '/community/feed';
        }elseif(Auth::user()->access_level === 'superadmin') {
            $url = '/superadmin/dashboard';
        }elseif(Auth::user()->access_level === 'nationaladmin') {
            $url = '/national/dashboard';
        }elseif(Auth::user()->access_level === 'regionaladmin') {
            $url = '/regional/dashboard';
        }elseif (Auth::user()->access_level==='stateadmin'){
            $url = '/state/dashboard';
        }elseif (Auth::user()->access_level==='senatorialadmin'){
            $url = '/senatorial/dashboard';
        }elseif (Auth::user()->access_level==='federaladmin'){
            $url = '/federal/dashboard';
        }elseif (Auth::user()->access_level==='lgaadmin'){
            $url = '/lga/dashboard';
        }elseif (Auth::user()->access_level==='wardadmin'){
            $url = '/ward/dashboard';
        }elseif (Auth::user()->access_level==='puadmin'){
            $url = '/pu/dashboard';
        }elseif (Auth::user()->access_level==='user') {
            $url = '/dashboard';
        }else{
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


        return redirect($url)->with($notification);

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
