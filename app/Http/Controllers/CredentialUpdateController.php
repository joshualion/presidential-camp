<?php 
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CredentialUpdateController extends Controller
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

    /**
     * Show the form for updating email and password.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showUpdateForm()
    {
        $profileData = $this->getProfileData();
        $pageTitle = "Security Patch";

        // Check if the user still requires credential updates
        if (!$profileData->requires_update) {
            return redirect()->route($profileData->access_level.'.dashboard');
        }

        return view('backend.' . $profileData->access_level . '.update-credentials', compact('profileData','pageTitle'));
    }

    /**
     * Handle the credential update process.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $profileData = $this->getProfileData();

        // Ensure the user still requires updates to prevent unnecessary updates
        if (!$profileData->requires_update) {
            return redirect()->route($profileData->access_level.'.dashboard');
        }

         // Validate the request
    $validator = Validator::make($request->all(), [
        'email' => [
            'required',
            'email',
            'unique:users,email,' . $profileData->id,
            function ($attribute, $value, $fail) {
                if (strpos($value, 'example.com') !== false) {
                    $fail('The email domain cannot contain "example.com".');
                }
            },
        ],
        'password' => [
            'required',
            'min:8',
            'confirmed',
            function ($attribute, $value, $fail) {
                if ($value === 'password') {
                    $fail('The password cannot be "password".');
                }
            },
        ],
    ]);

        if ($validator->fails()) {           
            return back()->withErrors($validator)->withInput();
        }

        // Update the user's credentials
        $profileData->email = $request->input('email');
        $profileData->password = Hash::make($request->input('password'));
        $profileData->requires_update = false; // Mark update as complete
        $profileData->save();

       
        $SystemSetting = SystemSetting::find(1);
        //Redirect to Super admins to the loction routes
        if($profileData->access_level == 'nationaladmin' && $SystemSetting->package =='national') {
            $notification = [
                'type'=> 'success',
                'message'=> 'Credentials updated successfully. Please add or upload LGAs, Wards and Polling Units'
            ];
    
            // Redirect to the profile completion step
        return redirect()->route('nationaladmin.location.lgas')->with($notification);

        }elseif($profileData->access_level == 'regionaladmin' && $SystemSetting->package =='regional') {
            $notification = [
                'type'=> 'success',
                'message'=> 'Credentials updated successfully. Please add or upload LGAs, Wards and Polling Units'
            ]; // Redirect to the profile completion step
        return redirect()->route('regionaladmin.location.lgas')->with($notification);
        }elseif($profileData->access_level == 'stateadmin' && $SystemSetting->package =='state') {
            $notification = [
                'type'=> 'success',
                'message'=> 'Credentials updated successfully. Please add or upload LGAs, Wards and Polling Units'
            ]; // Redirect to the profile completion step
        return redirect()->route('stateadmin.location.lgas')->with($notification);
        }elseif($profileData->access_level == 'lgaadmin' && $SystemSetting->package =='state') {
            $notification = [
                'type'=> 'success',
                'message'=> 'Credentials updated successfully. Please add or upload Wards and polling'
            ];// Redirect to the profile completion step
        return redirect()->route('lgaadmin.location.wards')->with($notification);
        }else{
            $notification = [
                'type'=> 'success',
                'message'=> 'Credentials updated successfully. Please complete your profile.'
            ];
    
        // Redirect to the profile completion step
        return redirect()->route('complete.profile')->with($notification);
        }
    }

}
