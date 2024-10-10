<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'firstname'=> ['required','string'],
            'lastname' => ['required','string'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'username' => $request->name,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);       

        $url ='';
        if (Auth::user()->access_level === 'superadmin') {
            $url = '/superadmin/dashboard';
        }elseif(Auth::user()->access_level === 'nationaladmin') {
            $url = '/national/dashboard';
        }elseif(Auth::user()->access_level === 'regionaladmin') {
            $url = '/regional/dashboard';
        }elseif (Auth::user()->access_level==='stateadmin'){
            $url = '/state/dashboard';
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
                'message' => 'Something went wrong, please try again in a few minutes or conatact Admin',
                'alert-type' => 'error'
            ];

            return redirect('/login')->with($notification);

        }

        $notification = [
            'message' => 'Welcome'.' '.$user->username,
            'alert-type' => 'success'
        ];

        return redirect($url)->with($notification);
    }
}
