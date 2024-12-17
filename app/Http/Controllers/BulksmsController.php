<?php

namespace App\Http\Controllers;

use App\Models\BulkSmsSetting;
use App\Models\User;
use App\Models\Country;
use App\Models\Region;
use App\Models\State;
use App\Models\LocalGovernmentArea;
use App\Models\Ward;
use App\Models\PollingUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class BulksmsController extends Controller
{
    public function __construct()
    {

        $pageTitle = 'BulkSMS Messaging';
        View::share('pageTitle', $pageTitle);
    }
       // Function to get the profile data
    private function getProfileData()
    {
           $id = Auth::user()->id;
           return User::find($id);
    }

     
    private function filterRecipients($id)
    {
        $profileData = $this->getProfileData();

        if (!$profileData) {
            return collect(); // Return an empty collection if profile data is missing
        }

        // Access level hierarchy
        $accessLevels = [
            'superadmin',
            'nationaladmin',
            'regionaladmin',
            'stateadmin',
            'lgaadmin',
            'wardadmin',
            'pollingunitadmin',
        ];

        $currentLevelIndex = array_search($profileData->access_level, $accessLevels);

        if ($currentLevelIndex === false) {
            return collect(); // Return an empty collection if access level is invalid
        }

        $excludedLevels = array_slice($accessLevels, 0, $currentLevelIndex);
        $recipients = User::query()
            ->whereNotIn('access_level', $excludedLevels);

        // Apply location constraints for admins below the national level
        $locationConstraints = [
            'region_id' => $profileData->region_id,
            'state_id' => $profileData->state_id,
            'lga_id' => $profileData->lga_id,
            'ward_id' => $profileData->ward_id,
            'polling_unit_id' => $profileData->polling_unit_id,
        ];

        foreach ($locationConstraints as $column => $value) {
            if (!empty($value)) {
                $recipients->where($column, $value);
            }
        }

        return $recipients->get();
    }


    public function index()
    {
        $profileData = $this->getProfileData();
        $countries = Country::all();
         $regions = Region::all();
        // $states = State::all();
        // $lgas = LocalGovernmentArea::all();
        // $wards = Ward::all();
        // $pus = PollingUnit::all();
        
        $bulksms = BulkSmsSetting::where('user_id', $profileData->id)->first();
        
        if (!empty($bulksms->sender_id) && !empty($bulksms->sms_api_key)) {            
             return view('backend.'.$profileData->access_level.'.sms.sendsms', compact('profileData', 'bulksms','countries','regions'));
        }else{
            $notification = [
                'message' => 'You need to set up your SMS settings before sending SMS',
                'alert-type' => 'error'
            ];

   
         return redirect()->route($profileData->access_level.'.bulksms.setting')->with($notification);
              
            
        }
        
    }

    
    
}
