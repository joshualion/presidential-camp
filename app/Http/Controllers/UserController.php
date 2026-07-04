<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Ward;
use App\Models\State;
use App\Models\Region;
use App\Models\Country;
use App\Models\AgeGrade;
use App\Models\Religion;
use App\Models\PollingUnit;
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
use App\Services\MemberCapabilityService;
use App\Services\CampaignPackageLocationFormService;
use App\Services\LocationSelectionValidationService;
use App\Services\MemberVotingBlocService;
use App\Services\AnnouncementAudienceService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;


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
        $this->requireCapability($profileData, MemberCapabilityService::DASHBOARD);

        if ($profileData->access_level !== 'user') {
            abort(403, 'Unauthorized');
        }

        $user = Auth::user();

        // Get assigned polling unit (if any)
        $pu = null;
        if ($user->polling_unit_id) {
            $pu = PollingUnit::find($user->polling_unit_id);
        }

        $votingBlocEnabled = app(MemberCapabilityService::class)->allows($profileData, MemberCapabilityService::VOTING_BLOCK);
        $bloc = $votingBlocEnabled
            ? app(MemberVotingBlocService::class)->report($user)
            : [
                'direct_count' => 0,
                'total_count' => 0,
                'eligible_count' => 0,
                'ineligible_count' => 0,
            ];
        $referralsCount = $bloc['direct_count'];
        $blockStrength = $bloc['total_count'];

        $referralChartData = [
            'labels' => ['Eligible Voters', 'Ineligible Voters'],
            'datasets' => [[
                'data' => [$bloc['eligible_count'], $bloc['ineligible_count']],
                'backgroundColor' => ['#28a745', '#dc3545'],
            ]]
        ];


        $announcements = app(MemberCapabilityService::class)->allows($profileData, MemberCapabilityService::DASHBOARD_ANNOUNCEMENTS)
            ? app(AnnouncementAudienceService::class)->visibleToMember($profileData)
            ->take(5)
            ->get()
            : collect();


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
                'announcements',
                'votingBlocEnabled'
            )
        );
    }

    public function memberReferrals()
    {
        $profileData = $this->getProfileData();
        $user = $profileData;
        $this->requireCapability($profileData, MemberCapabilityService::REFERRALS);

        $referrals = app(MemberVotingBlocService::class)->directReferrals($user);

        return view('backend.user.referrals', compact('profileData', 'user', 'referrals'));
    }

    public function memberBlock(Request $request)
    {
        $profileData = $this->getProfileData();
        $user = $profileData;
        $this->requireCapability($profileData, MemberCapabilityService::VOTING_BLOCK);
        $bloc = app(MemberVotingBlocService::class)->report($user);
        $perPage = 25;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $members = new LengthAwarePaginator(
            $bloc['members']->forPage($page, $perPage)->values(),
            $bloc['members']->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('backend.user.voting-block', compact('profileData', 'user', 'bloc', 'members'));
    }


    public function memberPuDetails()
{
    $user = auth()->user();
    $this->requireCapability($user, MemberCapabilityService::POLLING_UNIT_DETAILS);

    if (!$user->polling_unit_id) {
        abort(404, 'Polling Unit not assigned.');
    }

    $pu = PollingUnit::with(['ward.lga.state'])
        ->findOrFail($user->polling_unit_id);

    return view('backend.user.pu-details', compact('user', 'pu'));
}





    public function memberProfile(){
            $profileData = $this->getProfileData();
            $this->requireCapability($profileData, MemberCapabilityService::PROFILE);
            $pageTitle = 'Profile';
            $locationForms = app(CampaignPackageLocationFormService::class);
            $locationOptions = $locationForms->options($profileData);
            $locationForm = $locationForms->context($profileData, $profileData, 'user');
            $regions = $locationOptions['regions'];
            $states = $locationOptions['states'];
            $lgas = $locationOptions['lgas'];
            $wards = $locationOptions['wards'];
            $pollingUnits = $locationOptions['pollingUnits'];
            $religions = Religion::all();
            $ageGrades = AgeGrade::all();
            $userSupportGroups = $profileData->supportGroups()->pluck('name')->toArray();

            return view('backend.'.$profileData->access_level.'.profile', compact(
                'profileData', 'userSupportGroups', 'pageTitle', 'regions', 'states', 'lgas',
                'wards', 'pollingUnits', 'religions', 'ageGrades', 'locationForm'
            ));

    }

    public function memberProfileStore(Request $request){
         $profileData = $this->getProfileData();
         $this->requireCapability($profileData, MemberCapabilityService::PROFILE);
         $request->merge(app(CampaignPackageLocationFormService::class)->mergeFixedPayload($request->all()));

         $requireBank = optional(SystemSetting::first())->require_bank_details;
         $bankRule = $requireBank ? 'required|string|max:255' : 'nullable|string|max:255';
         $accountRule = $requireBank ? 'required|string|size:10' : 'nullable|string|size:10';

         // Validation rules
         $validatedData = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($profileData->id)],
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

        foreach (['region_id', 'state_id', 'lga_id', 'ward_id', 'polling_unit_id'] as $field) {
            if ((int) $validatedData[$field] !== (int) $profileData->{$field}) {
                throw ValidationException::withMessages([
                    $field => 'Members cannot change voting location from self-service. Contact an administrator.',
                ]);
            }
        }

        $location = app(LocationSelectionValidationService::class)->validateMemberSelection($validatedData);

        $profileData->update([
            'firstname' => $validatedData['firstname'],
            'lastname' => $validatedData['lastname'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'validVoter' => $validatedData['validvoter'],
            'vin' => $validatedData['vin'],
            'age_grade_id' => $validatedData['age_grade_id'],
            'gender' => $validatedData['gender'],
            'region_id' => $location['region_id'],
            'state_id' => $location['state_id'],
            'senatorial_district_id' => $location['senatorial_district_id'],
            'federal_constituency_id' => $location['federal_constituency_id'],
            'lga_id' => $location['lga_id'],
            'ward_id' => $location['ward_id'],
            'polling_unit_id' => $location['polling_unit_id'],
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
        $this->requireCapability($profileData, MemberCapabilityService::ACCOUNT_SECURITY);
        return view('backend.'.$profileData->access_level.'.change_password', compact('pageTitle','profileData'));
    }

    public function memberUpdatePassword(Request $request){
         $this->requireCapability($this->getProfileData(), MemberCapabilityService::ACCOUNT_SECURITY);
         $validateData = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'confirmed', 'different:old_password', Password::min(8)->mixedCase()->letters()->numbers()]
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

    private function requireCapability(User $user, string $capability): void
    {
        $service = app(MemberCapabilityService::class);

        abort_unless($service->allows($user, $capability), 403, $service->denialMessage($capability));
    }


}
