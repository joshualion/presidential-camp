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
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use App\Services\AccessLevelRouteService;

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
    public function store(Request $request, AccessLevelRouteService $accessLevelRoutes): RedirectResponse
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
            'referral_code' => [
                'nullable',
                'string',
                Rule::exists('users', 'referral_code')->where(fn ($query) => $query
                    ->where('access_level', 'user')
                    ->where('status', 'active')),
            ],
        ]);

        // Find the role by its Name
        $role = Role::query()->whereIn('name', ['Member', 'member'])->firstOrFail();
        //get the default country id
        $country= SystemSetting::find(1)->system_country;
        $country_id = Country::where('name',$country)->first()->id;


        $referrer = $request->filled('referral_code')
            ? User::query()
                ->where('referral_code', $request->referral_code)
                ->where('access_level', 'user')
                ->where('status', 'active')
                ->first()
            : null;

        $user = DB::transaction(function () use ($request, $country_id, $role, $referrer) {
            $user = User::create([
                'country_id' => $country_id,
                'username' => $request->username,
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'access_level' => 'user',
                'status' => 'active',
                'referred_by' => $referrer?->id,
            ]);

            $user->roles()->attach($role->id);

            if ($referrer) {
                Referral::updateOrCreate(
                    ['referee_id' => $user->id],
                    [
                        'referrer_id' => $referrer->id,
                        'accepted_at' => now(),
                    ]
                );
            }

            return $user;
        });



        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        $notification = [
            'message' => 'Registration successful. Welcome '.$user->username.'. Please complete your profile.',
            'alert-type' => 'success'
        ];

        return redirect()->route($accessLevelRoutes->sharedRouteForUser($user, 'account.complete-profile'))
            ->with('success', $notification['message'])
            ->with($notification);
    }
}
