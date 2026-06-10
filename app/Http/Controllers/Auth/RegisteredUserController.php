<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Country;
use App\Models\Referral;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\RateLimiter;

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
        //Security check 1
        $key = 'register:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429, 'Too many attempts. Slow down, cowboy.');
        }
        RateLimiter::hit($key, 60); // 1 minute cooldown

        //2. Security check 2
        if (!empty($request->input('nickname'))) {
            abort(403, 'Bot detected');
        }

        //Security check 3
        $request->validate([
            'logic_question' => ['required', function ($attribute, $value, $fail) {
                $expected = session('logic_question_answer');
                if ((int) trim($value) !== $expected) {
                    $fail('Incorrect answer to the logic question.');
                }
            }],
        ]);



        $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'firstname'=> ['required','string'],
            'lastname' => ['required','string'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'referral_code' => ['nullable|string|exists:users,referral_code'],
        ]);

        // Find the role by its Name
        $role = Role::where('name', 'member')->first();
        //get the default country id
        $country= SystemSetting::find(1)->system_country;
        $country_id = Country::where('name',$country)->first()->id;


        $user = User::create([
            'country_id' => $country_id,
            'username' => $request->username,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        //assign role
        $user->roles()->attach($role->id);

        // Handle referral logic
        if ($request->filled('referral_code')) {
            $referrer = User::where('referral_code', $request->referral_code)->first();

            if ($referrer) {
                // Mirror relation in referrals table
                Referral::firstOrCreate([
                    'referrer_id' => $referrer->id,
                    'referee_id'  => $user->id, // <-- must match migration column
                ]);

                // Set parent directly in users table
                $user->update([
                    'referred_by' => $referrer->id,
                ]);
            }
        }



        event(new Registered($user));

        Auth::login($user);

        $setting = SystemSetting::find(1);

        $url ='';
        if($setting->frontend_community == 1){
            $url = '/community/feed';
        }elseif (Auth::user()->access_level === 'superadmin') {
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
