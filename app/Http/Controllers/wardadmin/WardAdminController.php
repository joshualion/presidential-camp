<?php

namespace App\Http\Controllers\wardadmin;

use App\Http\Controllers\Controller;
use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\Religion;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use App\Models\Region;
use App\Models\State;
use App\Models\Ward;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Services\LocationDashboardMetricsService;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class WardAdminController extends Controller
{


    public function __construct()
    {

        $pageTitle = 'Ward Admin Dashboard';
        View::share('pageTitle', $pageTitle);
    }

    // Function to get the current User profile data
    private function getProfileData()
    {
        $id = Auth::user()->id;
        return User::find($id);
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

    public function wardAdminDashBoard()
    {
        $pageTitle    = 'Ward Members/Agents Dashboard';
        $profileData  = $this->getProfileData();

        if ($profileData->access_level == 'wardadmin') {
            $ward = Ward::where('id', $profileData->ward_id)->first();
        } else {
            abort(403);
        }

          // Get the total count of wards in the LGAs (wardCount)
          $puCount = PollingUnit::where('ward_id', $ward->id)->count();

          // Get the count of wards that have associated users (wardWithUsersCount)
          $puWithUsersCount =PollingUnit::where('ward_id', $ward->id)
              ->whereHas('users') // Ensures only wards with users are counted
              ->count();

            $eligibleVotersCount = User ::where('ward_id', $ward->id)
            ->where('access_level', '!=', 'superadmin')
            ->where('validvoter', 'yes')->count();

            $inEligibleVotersCount = User ::where('ward_id', $ward->id)
            ->where('access_level', '!=', 'superadmin')
            ->where('validvoter', 'No')->count();


        $excoCount = User::where('ward_id', $ward->id)
            ->where('access_level', '!=', 'user')
            ->where('access_level', '!=', 'superadmin')
            ->where('access_level', '!=', 'puadmin')
            ->count();

        $memberCount = User::where('ward_id', $ward->id)
            ->where('access_level', 'user')
            ->count();

        $userCount = User::where('ward_id', $ward->id)
            ->where('access_level', '!=', 'superadmin')
            ->count();

        $newMembers = User::where('ward_id', $ward->id)
            ->where('access_level', '!=', 'superadmin')
            ->where('created_at', '>=', Carbon::now()->subDays(1))
            ->count();

        $dashboardMetrics = app(LocationDashboardMetricsService::class)->forUser($profileData);

        return view(
            'backend.' . $profileData->access_level . '.dashboard',
            compact(
                'profileData',
                'pageTitle',
                'ward',
                'puCount',
                'puWithUsersCount',
                'eligibleVotersCount',
                'inEligibleVotersCount',
                'excoCount',
                'memberCount',
                'userCount',
                'newMembers',
                'dashboardMetrics'
            )
        );
    }




    //Function for admin profile
    public function wardadminProfile()
    {
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

      //Function for admin profile update
      public function wardadminProfileStore(Request $request)
      {
          $profileData = $this->getProfileData();

          $requireBank = optional(SystemSetting::first())->require_bank_details;
          $bankRule = $requireBank ? 'required|string|max:255' : 'nullable|string|max:255';
          $accountRule = $requireBank ? 'required|string|size:10' : 'nullable|string|size:10';

          // Validation rules
         $validatedData = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (str_ends_with($value, '@example.com')) {
                        $fail('Email cannot be a placeholder domain like @example.com.');
                    }
                },
            ],
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
            'bank' => $validatedData['bank'],
            'bank_account_number' => $validatedData['bank_account_number'],
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
          $notification = [
              'message' => 'Profile Updated Successfully',
              'alert-type' => 'success'
          ];
          return redirect()->back()->with($notification);
      }



          //Function for admin change password
    public function wardadminChangePassword()
    {
        $pageTitle = "Change Password";
        $profileData = $this->getProfileData();
        return view('backend.'.$profileData->access_level.'.change_password', compact('pageTitle','profileData'));
    }

    //Function for admin update password
    public function wardadminUpdatePassword(Request $request)
    {
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


    public function wardadminLogout(){
        Auth::logout();
        return redirect()->route('login');
    }






    public function RegionDashBoard($uuid)
    {
        app(\App\Services\DashboardDrilldownService::class)->region($uuid);

        abort(404);
    }

    public function StateDashBoard($uuid)
    {
        app(\App\Services\DashboardDrilldownService::class)->state($uuid);

        abort(404);
    }

    public function localGovernmentDashBoard($uuid)
    {
        $pageTitle = 'Local Government Dashboard';
        $profileData = $this->getProfileData();
        $lga = app(\App\Services\DashboardDrilldownService::class)->lga($uuid);

        $wardCount = Ward::where('lga_id', $lga->id)->count();
        $wardWithUsersCount = Ward::where('lga_id', $lga->id)->whereHas('users')->count();
        $wards = Ward::where('lga_id', $lga->id)->pluck('id');
        $puCount = PollingUnit::whereIn('ward_id', $wards)->count();
        $puWithUsersCount = PollingUnit::whereIn('ward_id', $wards)->whereHas('users')->count();
        $excoCount = User::where('lga_id', $lga->id)->where('access_level', '!=', 'user')->where('access_level', '!=', 'superadmin')->where('access_level', '!=', 'puadmin')->count();
        $memberCount = User::where('lga_id', $lga->id)->where('access_level', '!=', 'superadmin')->where('access_level', 'user')->count();
        $userCount = User::where('lga_id', $lga->id)->where('access_level', '!=', 'superadmin')->count();
        $newMembers = User::where('lga_id', $lga->id)->where('access_level', '!=', 'superadmin')->where('created_at', '>=', Carbon::now()->subDays(1))->count();
        $dashboardMetrics = app(LocationDashboardMetricsService::class)->forLocalGovernmentArea($lga, $profileData);

        return view('backend.'.$profileData->access_level.'.lga.dashboard',
            compact('profileData', 'pageTitle', 'lga', 'dashboardMetrics', 'wardCount', 'wardWithUsersCount', 'puCount', 'puWithUsersCount', 'excoCount', 'memberCount', 'userCount', 'newMembers'));
    }

    public function wardDashBoard($uuid)

    {
        $pageTitle = 'Ward Dashboard';
        $profileData  = $this->getProfileData();
        $ward = app(\App\Services\DashboardDrilldownService::class)->ward($uuid);

        if(!$ward){
            $notification = [
                'message' => 'Ward not found',
                'alert-type' => 'error'
            ];

            return redirect()->back()->with($notification);
        }
        app(\App\Services\LicensedScopeQueryService::class)->abortIfWardNotAllowed($ward->id);



         // Get the total count of wards in the LGAs (wardCount)
         $puCount = PollingUnit::where('ward_id', $ward->id)->count();

         // Get the count of wards that have associated users (wardWithUsersCount)
         $puWithUsersCount =PollingUnit::where('ward_id', $ward->id)
             ->whereHas('users') // Ensures only wards with users are counted
             ->count();


             $excoCount = User::where('ward_id', $ward->id)
             ->where('access_level', '!=', 'user')
             ->where('access_level', '!=', 'superadmin')
            ->where('access_level', '!=', 'puadmin')->count();

             $memberCount = User ::where('ward_id', $ward->id)
             ->where('access_level', '!=', 'superadmin')
             ->where('access_level', 'user')->count();

             $eligibleVotersCount = User ::where('ward_id', $ward->id)
             ->where('access_level', '!=', 'superadmin')
             ->where('validvoter', 'yes')->count();

             $inEligibleVotersCount = User ::where('ward_id', $ward->id)
             ->where('access_level', '!=', 'superadmin')
             ->where('validvoter', 'No')->count();

             $userCount = User ::where('ward_id', $ward->id)
             ->where('access_level', '!=', 'superadmin')->count();

             $newMembers = User ::where('ward_id', $ward->id)
             ->where('access_level', '!=', 'superadmin')
             ->where('created_at', '>=', Carbon::now()->subDays(1))
             ->count();



            $dashboardMetrics = app(LocationDashboardMetricsService::class)->forWard($ward, $profileData);

            return view('backend.'.$profileData->access_level.'.ward.dashboard',
                    compact(
                        'profileData',
                        'pageTitle',
                        'ward',
                        'dashboardMetrics',
                        'puCount',
                        'puWithUsersCount',
                        'excoCount',
                        'memberCount',
                        'eligibleVotersCount',
                        'inEligibleVotersCount',
                        'userCount',
                        'newMembers'
                    ));


    }

    public function puDashBoard($uuid)

    {
        $pageTitle = 'Polling Unit Dashboard';
        $profileData  = $this->getProfileData();
        $pu = app(\App\Services\DashboardDrilldownService::class)->pollingUnit($uuid);

                if(!$pu){
                    $notification = [
                        'message' => 'Polling Unit not found',
                        'alert-type' => 'error'
                    ];

                    return redirect()->back()->with($notification);
                }
        app(\App\Services\LicensedScopeQueryService::class)->abortIfPollingUnitNotAllowed($pu->id);

             $excoCount = User::where('polling_unit_id', $pu->id)
             ->where('access_level', '!=', 'user')
             ->where('access_level', '!=', 'superadmin')
            ->where('access_level', '!=', 'puadmin')->count();

             $memberCount = User ::where('polling_unit_id', $pu->id)
             ->where('access_level', '!=', 'superadmin')
             ->where('access_level', 'user')->count();


             $eligibleVotersCount = User ::where('polling_unit_id', $pu->id)
             ->where('access_level', '!=', 'superadmin')
             ->where('validvoter', 'yes')->count();

             $inEligibleVotersCount = User ::where('polling_unit_id', $pu->id)
             ->where('access_level', '!=', 'superadmin')
             ->where('validvoter', 'No')->count();

             $userCount = User ::where('polling_unit_id', $pu->id)
             ->where('access_level', '!=', 'superadmin')->count();

             $newMembers = User ::where('polling_unit_id', $pu->id)
             ->where('access_level', '!=', 'superadmin')
             ->where('created_at', '>=', Carbon::now()->subDays(1))
             ->count();



            $dashboardMetrics = app(LocationDashboardMetricsService::class)->forPollingUnit($pu, $profileData);

            return view('backend.'.$profileData->access_level.'.pu.dashboard',
                    compact(
                        'profileData',
                        'pageTitle',
                        'pu',
                        'dashboardMetrics',
                        'excoCount',
                        'memberCount',
                        'eligibleVotersCount',
                        'inEligibleVotersCount',
                        'userCount',
                        'newMembers'
                    ));


    }




}
