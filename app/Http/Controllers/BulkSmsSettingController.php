<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BulkSmsSetting;
use App\Models\User;
use App\Services\ModuleGateService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class BulkSmsSettingController extends Controller
{

    public function __construct()
    {

        $pageTitle = ' BulkSMS Settings';
        View::share('pageTitle', $pageTitle);
    }
       // Function to get the profile data
       private function getProfileData()
       {
           $id = Auth::user()->id;
           return User::find($id);
       }


    public function index()
    {
        $this->requireSmsModule();

        $profileData = $this->getProfileData();
        $bulksms = BulkSmsSetting::where('user_id', Auth::user()->id)->first();
        if (!empty($bulksms->sender_id) && !empty($bulksms->sms_api_key)) {
                return view('backend.'.$profileData->access_level.'.settings.bulksms.edit-settings', compact('profileData', 'bulksms'));
        }else{
                return view('backend.'.$profileData->access_level.'.settings.bulksms.settings', compact('profileData'));
            }
    }

    public function store(Request $request){
        $this->requireSmsModule();

        $profileData = $this->getProfileData();
       // Validate the request data
       $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'smsgateway_provider' => 'required',
            'sender_id' => 'required',
            'sms_api_key' => 'required',
        ]);

         // If validation fails, return to the previous page with errors
         if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $bulksms = new BulkSmsSetting();
        $bulksms->user_id = Auth::user()->id;
        $bulksms->smsgateway_provider = request()->smsgateway_provider;
        $bulksms->smsgateway_endpoint = request()->smsgateway_endpoint;
        $bulksms->sender_id = request()->sender_id;
        $bulksms->sms_api_key = request()->sms_api_key;
        $bulksms->save();

        $notification = array(
            'message' => 'Bulk SMS Settings Saved Successfully',
            'alert-type' => 'success'
        );
        return redirect()->route($profileData->access_level.'.bulksms.setting')->with($notification);

   }

    public function update(Request $request)
    {
        $this->requireSmsModule();

        $profileData = $this->getProfileData();
        $validator = Validator::make($request->all(), [
              'user_id' => 'required',
              'smsgateway_provider' => 'required',
              'sender_id' => 'required',
              'sms_api_key' => 'required',
         ]);

          // If validation fails, return to the previous page with errors
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

         $bulksms = BulkSmsSetting::where('user_id', Auth::user()->id)->first();
         $bulksms->user_id = Auth::user()->id;
         $bulksms->smsgateway_provider = request()->smsgateway_provider;         
        $bulksms->smsgateway_endpoint = request()->smsgateway_endpoint;
         $bulksms->sender_id = request()->sender_id;
         $bulksms->sms_api_key = request()->sms_api_key;
         $bulksms->save();

         $notification = array(
              'message' => 'Bulk SMS Settings Updated Successfully',
              'alert-type' => 'success'
         );
         return redirect()->route($profileData->access_level.'.bulksms.setting')->with($notification);
    }

    private function requireSmsModule(): void
    {
        abort_unless(
            app(ModuleGateService::class)->enabled('sms') || app(ModuleGateService::class)->enabled('bulk_sms'),
            403,
            'SMS module is not available for this release.'
        );
    }



}
