<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Ward;
use App\Models\State;
use App\Models\Region;
use App\Models\Country;
use App\Models\AgeGrade;
use App\Models\Referral;
use App\Models\Religion;
use App\Models\PollingUnit;
use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\LocalGovernmentArea;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{


    public function __construct()
    {

        $pageTitle = 'Member Dashboard';
        View::share('pageTitle', $pageTitle);
    }
       // Function to get the profile data
       private function getProfileData()
       {
           $id = Auth::user()->id;
           return User::findOrFail($id);
       }


    //Function to get the system country
    private function getCountries()
    {
         // Retrieve the value of the default_country setting
         $defaultCountry = SystemSetting::find(1);

        // Retrieve the ID of the default country
        return Country::where('name', $defaultCountry->system_country)->first();
    }

    //Function to retrieves regions from set country
    public function getRegions()
    {
        $country_id = $this->getCountries()->id;
        $country = Country::where('id',$country_id)->first();

        if($country->name == 'Nigeria'){
            $region = Region::where('country_id', $country_id)->where('name', '!=', 'No-region')->get();
        }else{
        $region = Region::where('country_id', $country_id)->get();
        }


        return($region);
    }




    public function memberDashboard()
    {
        $pageTitle   = 'Member Dashboard';
        $profileData = $this->getProfileData();

        if ($profileData->access_level !== 'user') {
            abort(403, 'Unauthorized');
        }

        $user = Auth::user();

        // Get assigned polling unit (if any)
        $pu = null;
        if ($user->polling_unit_id) {
            $pu = PollingUnit::find($user->polling_unit_id);
        }

        // Count direct referrals
        $referralsCount = Referral::where('referrer_id', $user->id)->count();

        // Build block strength (referrals + nested)
        $blockStrength = $this->calculateBlockStrength($user);

        // Referral chart data (eligible vs ineligible voters in their downline)
        $eligible = User::where('referred_by', $user->id)
            ->where('validvoter', 'yes')
            ->count();

        $ineligible = User::where('referred_by', $user->id)
            ->where('validvoter', 'no')
            ->count();

        $referralChartData = [
            'labels' => ['Eligible Voters', 'Ineligible Voters'],
            'datasets' => [[
                'data' => [$eligible, $ineligible],
                'backgroundColor' => ['#28a745', '#dc3545'],
            ]]
        ];


        $announcements = Announcement::where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('audience', 'public') // everyone sees these
                ->orWhere(function ($q) use ($user) {
                    $q->where('audience', 'state')->where('state_id', $user->state_id);
                })
                ->orWhere(function ($q) use ($user) {
                    $q->where('audience', 'lga')->where('lga_id', $user->lga_id);
                })
                ->orWhere(function ($q) use ($user) {
                    $q->where('audience', 'ward')->where('ward_id', $user->ward_id);
                })
                ->orWhere(function ($q) use ($user) {
                    $q->where('audience', 'pu')->where('polling_unit_id', $user->polling_unit_id);
                });
            })
            ->latest()
            ->take(5)
            ->get();


        return view(
            'backend.user.dashboard',
            compact(
                'pageTitle',
                'profileData',
                'user',
                'pu',
                'referralsCount',
                'blockStrength',
                'referralChartData',
                'announcements'
            )
        );
    }

    /**
     * Recursively calculate block size (referrals + children).
     */
    protected function calculateBlockStrength(User $user)
    {
        $directReferrals = User::where('referred_by', $user->id)->get();
        $count = $directReferrals->count();

        foreach ($directReferrals as $ref) {
            $count += $this->calculateBlockStrength($ref);
        }

        return $count;
    }

    public function memberReferrals()
    {
        $user = auth()->user();

        // Get all referrals linked to this user
        $referrals = Referral::with('referee')
            ->where('referrer_id', $user->id)
            ->get();

        return view('backend.user.referrals', compact('user', 'referrals'));
    }


    public function memberPuDetails()
{
    $user = auth()->user();

    if (!$user->polling_unit_id) {
        abort(404, 'Polling Unit not assigned.');
    }

    $pu = PollingUnit::with(['ward.lga.state'])
        ->findOrFail($user->polling_unit_id);

    return view('backend.user.pu-details', compact('user', 'pu'));
}





    public function memberProfile(){
            $profileData = $this->getProfileData();
            $pageTitle = 'Profile';
            $roles = Role::all();  // Assuming a member can have multiple roles
            $countries = Country::all();
            $regions = $this->getRegions();
            $states = State::all();
            $lgas = LocalGovernmentArea::all();
            $wards = Ward::all();
            $pollingUnits = PollingUnit::all();
            $religions = Religion::all();
            $ageGrades = AgeGrade::all();
            $userSupportGroups = $profileData->supportGroups()->pluck('name')->toArray();

            return view('backend.'.$profileData->access_level.'.profile', compact(
                'profileData', 'userSupportGroups', 'pageTitle', 'roles', 'countries',
                'regions', 'states', 'lgas', 'wards', 'pollingUnits', 'religions', 'ageGrades'
            ));

    }

    public function memberProfileStore(Request $request){
         $profileData = $this->getProfileData();

         $requireBank = optional(SystemSetting::first())->require_bank_details;
         $bankRule = $requireBank ? 'required|string|max:255' : 'nullable|string|max:255';
         $accountRule = $requireBank ? 'required|string|size:10' : 'nullable|string|size:10';

         // Validation rules
         $validatedData = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:15|unique:users,phone,' .$profileData->id,
            'validvoter' => 'required|in:yes,no',
            'vin' => 'nullable|string|max:255',
            'age_grade_id' => 'required|exists:age_grades,id',
            'gender' => 'required|in:male,female',
            'region_id' => 'required|exists:regions,id',
            'state_id' => 'required|exists:states,id',
            'lga_id' => 'required|exists:local_government_areas,id',
            'ward_id' => 'required|exists:wards,id',
            'polling_unit_id' => 'required|exists:polling_units,id',
            'religion_id' => 'required|exists:religions,id',
            'bank' => $bankRule,
            'bank_account_number' => $accountRule,
            'address' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $profileData->update([
            'firstname' => $validatedData['firstname'],
            'lastname' => $validatedData['lastname'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'validVoter' => $validatedData['validvoter'],
            'vin' => $validatedData['vin'],
            'age_grade_id' => $validatedData['age_grade_id'],
            'gender' => $validatedData['gender'],
            'region_id' => $validatedData['region_id'],
            'state_id' => $validatedData['state_id'],
            'lga_id' => $validatedData['lga_id'],
            'ward_id' => $validatedData['ward_id'],
            'polling_unit_id' => $validatedData['polling_unit_id'],
            'religion_id' => $validatedData['religion_id'],
            'bank' => $validatedData['bank'] ?? null,
            'bank_account_number' => $validatedData['bank_account_number'] ?? null,
            'address' => $validatedData['address'],
            'occupation' => $validatedData['occupation'],
            'qualification' => $validatedData['qualification'],
        ]);

          if ($request->hasFile('photo')) {
              $file = $request->file('photo');
              $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
              $file->move(public_path('uploads/member_images/'), $filename);
              @unlink(public_path('uploads/member_images/') . $profileData->photo);
              $profileData->photo = $filename;
          }



        $profileData->save();

        // Redirect with success message
        $notification = [
            'message' => 'Profile updated successfully!',
            'alert-type' => 'success',
        ];


        return redirect()->route( $profileData->access_level.'.dashboard')->with($notification);

    }

    public function memberChangePassword(){
        $pageTitle = "Change Password";
        $profileData = $this->getProfileData();
        return view('backend.'.$profileData->access_level.'.change_password', compact('pageTitle','profileData'));
    }

    public function memberUpdatePassword(Request $request){
         $validateData = $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed'
        ]);

        $user = $this->getProfileData();
        $hashedPassword = $user->password;
         // Check if the new password is "password"
         if ($request->new_password === 'password') {
            $notification = [
                'message' => 'Password cannot be set to "password".',
                'alert-type' => 'error'
            ];
            return back()->with($notification);
        }

        if (!Hash::check($request->old_password, $hashedPassword)) {
            $notification = [
                'message' => 'Old Password is Invalid',
                'alert-type' => 'error'
            ];
            return back()->with($notification);
        } else {
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            $notification = [
                'message' => 'Password Changed Successfully',
                'alert-type' => 'success'
            ];
            return back()->with($notification);
        }

    }

    public function memberLogout()
    {
        Auth::logout();
        return redirect()->route('login');
    }


}
