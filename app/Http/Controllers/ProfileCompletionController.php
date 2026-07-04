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
use App\Services\CampaignPackageLocationFormService;
use App\Services\LicensedScopeQueryService;
use App\Services\LocationSelectionValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    private function locationForms(): CampaignPackageLocationFormService
    {
        return app(CampaignPackageLocationFormService::class);
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

    // Fetch additional data required for the profile form
    // An incomplete member has no structural assignment yet. Scope the choices
    // to the installation license until profile completion establishes one.
    $locationActor = $profileData->access_level === 'user' && !$profileData->polling_unit_id
        ? null
        : $profileData;
    $locationOptions = $this->locationForms()->options($locationActor);
    $locationForm = $this->locationForms()->context($profileData, $profileData, $profileData->access_level);
    $countries = Country::all();
    $regions = $locationOptions['regions'];
    $states = $locationOptions['states'];
    $lgas = $locationOptions['lgas'];
    $wards = $locationOptions['wards'];
    $pollingUnits = $locationOptions['pollingUnits'];
    $religions = Religion::all();
    $ageGrades = AgeGrade::all();

    // Render the profile completion form
    return view('backend.' . $profileData->access_level . '.complete-profile', compact(
        'profileData', 'pageTitle', 'countries', 'regions', 'states',
        'lgas', 'wards', 'pollingUnits', 'religions', 'ageGrades', 'locationForm'
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
        $request->merge($this->locationForms()->mergeFixedPayload($request->all()));
        $requireBank = optional(SystemSetting::first())->require_bank_details;
        $bankRule = $requireBank ? 'required|string|max:255' : 'nullable|string|max:255';
        $accountRule = $requireBank ? 'required|string|size:10' : 'nullable|string|size:10';

         // Validation rules
        $locationRules = $this->profileLocationRules($profileData->access_level);

         $validatedData = $request->validate(array_merge([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($profileData->id)],
            'phone' => 'required|string|max:15|unique:users,phone,' .$profileData->id,
            'validvoter' => 'required|in:yes,no',
            'vin' => 'nullable|string|max:255',
            'age_grade_id' => 'required|exists:age_grades,id',
            'gender' => 'required|in:male,female',
            'religion_id' => 'required|exists:religions,id',
            'bank' => $bankRule,
            'bank_account_number' => $accountRule,
            'address' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], $locationRules));

        $scopeErrors = app(LicensedScopeQueryService::class)->payloadErrors([
            'state_id' => $validatedData['state_id'] ?? null,
            'lga_id' => $validatedData['lga_id'] ?? null,
            'ward_id' => $validatedData['ward_id'] ?? null,
            'polling_unit_id' => $validatedData['polling_unit_id'] ?? null,
        ]);

        if ($scopeErrors !== []) {
            throw ValidationException::withMessages($scopeErrors);
        }

        if ($profileData->access_level === 'user') {
            $validatedData = array_replace(
                $validatedData,
                app(LocationSelectionValidationService::class)->validateMemberSelection($validatedData)
            );
        }

        $profileData->update([
            'firstname' => $validatedData['firstname'],
            'lastname' => $validatedData['lastname'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'validVoter' => $validatedData['validvoter'],
            'vin' => $validatedData['vin'] ?? null,
            'age_grade_id' => $validatedData['age_grade_id'],
            'gender' => $validatedData['gender'],
            'region_id' => $validatedData['region_id'] ?? $profileData->region_id,
            'state_id' => $validatedData['state_id'] ?? $profileData->state_id,
            'senatorial_district_id' => $validatedData['senatorial_district_id'] ?? $profileData->senatorial_district_id,
            'federal_constituency_id' => $validatedData['federal_constituency_id'] ?? $profileData->federal_constituency_id,
            'lga_id' => $validatedData['lga_id'] ?? $profileData->lga_id,
            'ward_id' => $validatedData['ward_id'] ?? $profileData->ward_id,
            'polling_unit_id' => $validatedData['polling_unit_id'] ?? $profileData->polling_unit_id,
            'religion_id' => $validatedData['religion_id'],
            'bank' => $validatedData['bank'] ?? null,
            'bank_account_number' => $validatedData['bank_account_number'] ?? null,
            'address' => $validatedData['address'] ?? null,
            'occupation' => $validatedData['occupation'] ?? null,
            'qualification' => $validatedData['qualification'] ?? null,
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

    private function profileLocationRules(string $accessLevel): array
    {
        return match ($accessLevel) {
            'superadmin', 'nationaladmin' => [
                'region_id' => 'nullable|exists:regions,id',
                'state_id' => 'nullable|exists:states,id',
                'lga_id' => 'nullable|exists:local_government_areas,id',
                'ward_id' => 'nullable|exists:wards,id',
                'polling_unit_id' => 'nullable|exists:polling_units,id',
            ],
            'regionaladmin' => [
                'region_id' => 'required|exists:regions,id',
                'state_id' => 'nullable|exists:states,id',
                'lga_id' => 'nullable|exists:local_government_areas,id',
                'ward_id' => 'nullable|exists:wards,id',
                'polling_unit_id' => 'nullable|exists:polling_units,id',
            ],
            'stateadmin' => [
                'region_id' => 'nullable|exists:regions,id',
                'state_id' => 'required|exists:states,id',
                'lga_id' => 'nullable|exists:local_government_areas,id',
                'ward_id' => 'nullable|exists:wards,id',
                'polling_unit_id' => 'nullable|exists:polling_units,id',
            ],
            'senatorialadmin' => [
                'region_id' => 'nullable|exists:regions,id',
                'state_id' => 'nullable|exists:states,id',
                'lga_id' => 'nullable|exists:local_government_areas,id',
                'ward_id' => 'nullable|exists:wards,id',
                'polling_unit_id' => 'nullable|exists:polling_units,id',
            ],
            'federaladmin' => [
                'region_id' => 'nullable|exists:regions,id',
                'state_id' => 'nullable|exists:states,id',
                'lga_id' => 'nullable|exists:local_government_areas,id',
                'ward_id' => 'nullable|exists:wards,id',
                'polling_unit_id' => 'nullable|exists:polling_units,id',
            ],
            'lgaadmin' => [
                'region_id' => 'nullable|exists:regions,id',
                'state_id' => 'nullable|exists:states,id',
                'lga_id' => 'required|exists:local_government_areas,id',
                'ward_id' => 'nullable|exists:wards,id',
                'polling_unit_id' => 'nullable|exists:polling_units,id',
            ],
            'wardadmin' => [
                'region_id' => 'nullable|exists:regions,id',
                'state_id' => 'nullable|exists:states,id',
                'lga_id' => 'nullable|exists:local_government_areas,id',
                'ward_id' => 'required|exists:wards,id',
                'polling_unit_id' => 'nullable|exists:polling_units,id',
            ],
            default => [
                'region_id' => 'nullable|exists:regions,id',
                'state_id' => 'nullable|exists:states,id',
                'lga_id' => 'nullable|exists:local_government_areas,id',
                'ward_id' => 'nullable|exists:wards,id',
                'polling_unit_id' => 'required|exists:polling_units,id',
            ],
        };
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
