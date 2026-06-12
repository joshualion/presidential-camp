<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\Religion;
use App\Models\State;
use App\Models\SupportGroup;
use App\Models\SystemSetting;
use App\Models\Ward;
use App\Services\PackageGovernanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileCompletionController extends Controller
{
    /**
     * Retrieve the currently authenticated user's profile data.
     *
     * @return \App\Models\User|null
     */
    private function getProfileData()
    {
        return Auth::user();
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

    /**
     * Show the form for completing the user's profile.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showProfileForm()
{
    $SystemSetting = SystemSetting::find(1);
    $profileData = $this->getProfileData();
    $pageTitle = 'Complete Your Profile';
    $package = app(PackageGovernanceService::class)->normalize($SystemSetting?->package);

    // Fetch additional data required for the profile form
    $countries = Country::all();
    $regions = $this->getRegions();
    $states = State::all();
    $lgas = LocalGovernmentArea::all();
    $wards = Ward::all();
    $pollingUnits = PollingUnit::all();
    $religions = Religion::all();
    $ageGrades = AgeGrade::all();

    // Check for incomplete setup and handle roles and access levels
    if ($lgas->isEmpty() || $wards->isEmpty() || $pollingUnits->isEmpty()) {
        $redirectRoute = null;
        $notification = null;

        switch ($package) {
            case PackageGovernanceService::PRESIDENTIAL:
                if (($profileData->access_level == 'nationaladmin' && $profileData->hasRole('National ICT Director')) || $profileData->access_level == 'superadmin') {
                    $notification = [
                        'type' => 'success',
                        'message' => ' Please add or upload LGAs, Wards, and Polling Units.',
                    ];
                    $redirectRoute = $profileData->access_level.'.location.lgas';
                } else {
                    $notification = 'The system is not completely set up. Please contact the Administrator.';
                }
                break;

            case PackageGovernanceService::GOVERNORSHIP:
                if (($profileData->access_level == 'stateadmin' && $profileData->hasRole('State ICT Director')) || $profileData->access_level == 'superadmin') {
                    $notification = [
                        'type' => 'success',
                        'message' => ' Please add or upload LGAs, Wards & Polling Units.',
                    ];
                    $redirectRoute = $profileData->access_level.'.location.lgas';
                } else {
                    $notification = 'The system is not completely set up. Please contact the State ICT Director.';
                }
                break;

            case PackageGovernanceService::CHAIRMANSHIP:
                if (($profileData->access_level == 'lgaadmin' && $profileData->hasRole('LGA ICT Director')) || $profileData->access_level == 'superadmin') {
                    $notification = [
                        'type' => 'success',
                        'message' => ' Please add or upload Wards and Polling Units.',
                    ];
                    $redirectRoute = $profileData->access_level.'.location.wards';
                } else {
                    $notification = 'The system is not completely set up. Please contact the LGA ICT Director.';
                }
                break;

            case PackageGovernanceService::SENATORIAL:
                if (($profileData->access_level == 'senatorialadmin' && $profileData->hasRole('Senatorial ICT Director')) || $profileData->access_level == 'superadmin') {
                    $notification = [
                        'type' => 'success',
                        'message' => ' Please add or upload LGAs, Wards, and Polling Units for this Senatorial District.',
                    ];
                    $redirectRoute = $profileData->access_level == 'superadmin'
                        ? 'superadmin.location.lgas'
                        : 'senatorialadmin.dashboard';
                } else {
                    $notification = 'The system is not completely set up. Please contact the Senatorial District ICT Director.';
                }
                break;

            case PackageGovernanceService::FEDERAL:
                if (($profileData->access_level == 'federaladmin' && $profileData->hasRole('Federal Constituency ICT Director')) || $profileData->access_level == 'superadmin') {
                    $notification = [
                        'type' => 'success',
                        'message' => ' Please add or upload LGAs, Wards, and Polling Units for this Federal Constituency.',
                    ];
                    $redirectRoute = $profileData->access_level == 'superadmin'
                        ? 'superadmin.location.lgas'
                        : 'federaladmin.dashboard';
                } else {
                    $notification = 'The system is not completely set up. Please contact the Federal Constituency ICT Director.';
                }
                break;

            default:
                $notification = 'Invalid system package. Please contact the Administrator.';
                break;
        }

        // If ICT Director, redirect to the appropriate setup page
        if ($redirectRoute) {
            return redirect()->route($redirectRoute)->with($notification);
        }

        // Otherwise, show an incomplete setup message
        return view('backend.'.$profileData->access_level.'.incomplete-setup', compact('notification', 'pageTitle', 'profileData'));
    }

    // Render the profile completion form
    return view('backend.' . $profileData->access_level . '.complete-profile', compact(
        'profileData', 'pageTitle', 'countries', 'regions', 'states',
        'lgas', 'wards', 'pollingUnits', 'religions', 'ageGrades'
    ));
}


    /**
     * Handle the profile completion process.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function complete(Request $request)
    {
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
            'message' => 'Profile completed successfully!',
            'alert-type' => 'success',
        ];


        return redirect()->route( $profileData->access_level.'.dashboard')->with($notification);
    }

     // Show the form for selecting support groups
     public function selectSupportGroup()
     {
         $profileData = $this->getProfileData();
         $pageTitle = 'Volunteer Groups';
         $supportGroups = SupportGroup::all(); // Fetch all available groups
         $userSupportGroups = $profileData->supportGroups()->pluck('user_support_group.support_group_id')->toArray() ?? []; // Explicitly specify the column

         return view('backend.'.$profileData->access_level.'.select-support-group', compact('supportGroups', 'profileData', 'pageTitle', 'userSupportGroups'));
     }

     // Handle the submission of selected support groups
     public function storeSupportGroup(Request $request)
     {
         $request->validate([
             'support_groups' => 'required|array|min:1',
             'support_groups.*' => 'exists:support_groups,id',
         ]);

         $profileData = $this->getProfileData();

         // Sync the user's selected groups (add/remove as necessary)
         $profileData->supportGroups()->sync($request->input('support_groups'));
         $profileData->save();

         $notification = [
            'message'=> 'Volunteer groups updated successfully!',
            'type'=> 'success',
         ];

         $Setting = SystemSetting::find(1);

         if($Setting->frontend_community==1){
            return redirect()->route('timeline')->with($notification);
         }else{
         return redirect()->route($profileData->access_level.'.dashboard')->with($notification);
         }

        }

}
