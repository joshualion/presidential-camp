<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\Religion;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\Ward;
use App\Services\CampaignPackageLocationFormService;
use App\Services\LicensedScopeQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

trait ManagesAdminProfile
{
    protected function showAdminProfile()
    {
        $profileData = $this->getProfileData();
        $pageTitle = 'Profile';
        $roles = Role::all();
        $locationOptions = app(CampaignPackageLocationFormService::class)->options($profileData);
        $locationForm = app(CampaignPackageLocationFormService::class)->context($profileData, $profileData, $profileData->access_level);
        $countries = Country::all();
        $regions = $locationOptions['regions'];
        $states = $locationOptions['states'];
        $lgas = $locationOptions['lgas'];
        $wards = $locationOptions['wards'];
        $pollingUnits = $locationOptions['pollingUnits'];
        $religions = Religion::all();
        $ageGrades = AgeGrade::all();
        $userSupportGroups = $profileData->supportGroups()->pluck('name')->toArray();

        return view('backend.'.$profileData->access_level.'.profile', compact(
            'profileData',
            'userSupportGroups',
            'pageTitle',
            'roles',
            'countries',
            'regions',
            'states',
            'lgas',
            'wards',
            'pollingUnits',
            'religions',
            'ageGrades',
            'locationForm'
        ));
    }

    protected function storeAdminProfile(Request $request)
    {
        $profileData = $this->getProfileData();
        $request->merge(app(CampaignPackageLocationFormService::class)->mergeFixedPayload($request->all()));
        $requireBank = optional(SystemSetting::first())->require_bank_details;
        $bankRule = $requireBank ? 'required|string|max:255' : 'nullable|string|max:255';
        $accountRule = $requireBank ? 'required|string|size:10' : 'nullable|string|size:10';

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
            'phone' => 'required|string|max:15|unique:users,phone,'.$profileData->id,
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

        $scopeErrors = app(LicensedScopeQueryService::class)->payloadErrors([
            'state_id' => $validatedData['state_id'] ?? null,
            'lga_id' => $validatedData['lga_id'] ?? null,
            'ward_id' => $validatedData['ward_id'] ?? null,
            'polling_unit_id' => $validatedData['polling_unit_id'] ?? null,
        ]);

        if ($scopeErrors !== []) {
            throw ValidationException::withMessages($scopeErrors);
        }

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
            $filename = (string) Str::uuid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/member_images/'), $filename);
            @unlink(public_path('uploads/member_images/').$profileData->photo);
            $profileData->photo = $filename;
        }

        $profileData->save();

        return redirect()->back()->with([
            'message' => 'Profile Updated Successfully',
            'alert-type' => 'success',
        ]);
    }

    protected function showAdminChangePassword()
    {
        $pageTitle = 'Change Password';
        $profileData = $this->getProfileData();

        return view('backend.'.$profileData->access_level.'.change_password', compact('pageTitle', 'profileData'));
    }

    protected function updateAdminPassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed',
        ]);

        $user = $this->getProfileData();

        if ($request->new_password === 'password') {
            return back()->with([
                'message' => 'Password cannot be set to "password".',
                'alert-type' => 'error',
            ]);
        }

        if (!Hash::check($request->old_password, $user->password)) {
            return back()->with([
                'message' => 'Old Password is Invalid',
                'alert-type' => 'error',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with([
            'message' => 'Password Changed Successfully',
            'alert-type' => 'success',
        ]);
    }

    protected function logoutAdmin(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function getCountries()
    {
        $defaultCountry = SystemSetting::find(1);

        return Country::where('name', $defaultCountry->system_country)->first();
    }

    public function getRegions()
    {
        $country = $this->getCountries();

        if (!$country) {
            return collect();
        }

        if ($country->name == 'Nigeria') {
            return Region::where('country_id', $country->id)->where('name', '!=', 'No-region')->get();
        }

        return Region::where('country_id', $country->id)->get();
    }
}
