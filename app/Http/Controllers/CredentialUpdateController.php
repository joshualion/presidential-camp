<?php 
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        if (!$this->requiresCredentialUpdate($profileData)) {
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
        if (!$this->requiresCredentialUpdate($profileData)) {
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

        return redirect()->route($profileData->access_level.'.dashboard')->with([
            'message' => 'Credentials updated successfully.',
            'alert-type' => 'success',
        ]);
    }

    private function requiresCredentialUpdate(User $user): bool
    {
        $badEmail = str_contains($user->email, '@example.com');
        $badPassword = Hash::check('password', $user->password);

        return $badEmail || $badPassword || $user->requires_update;
    }

}
