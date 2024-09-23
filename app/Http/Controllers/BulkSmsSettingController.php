<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BulkSmsSetting;
use App\Models\User;
use DeepCopy\f001\B;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class BulkSmsSettingController extends Controller
{

    public function __construct()
    {
        $this->middleware(['auth','role:admin']);
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
        $profileData = $this->getProfileData();
        $bulksms = BulkSmsSetting::where('user_id', Auth::user()->id)->first();
        if (!empty($bulksms->sender_id) && !empty($bulksms->sms_api_key)) {
                return view('admin.bulksms.editSettings', compact('profileData', 'bulksms'));
        }else{
                return view('admin.bulksms.settings', compact('profileData'));
            }
    }

    public function store(){
        $this->validate(request(), [
            'user_id' => 'required',
            'smsgateway_provider' => 'required',
            'sender_id' => 'required',
            'sms_api_key' => 'required',
        ]);

        $bulksms = new BulkSmsSetting();
        $bulksms->user_id = Auth::user()->id;
        $bulksms->smsgateway_provider = request()->smsgateway_provider;
        $bulksms->sender_id = request()->sender_id;
        $bulksms->sms_api_key = request()->sms_api_key;
        $bulksms->save();

        $notification = array(
            'message' => 'Bulk SMS Settings Saved Successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('admin.bulksms.setting')->with($notification);

   }

    public function update(Request $request)
    {
         $this->validate(request(), [
              'user_id' => 'required',
              'smsgateway_provider' => 'required',
              'sender_id' => 'required',
              'sms_api_key' => 'required',
         ]);
    
         $bulksms = BulkSmsSetting::where('user_id', Auth::user()->id)->first();
         $bulksms->user_id = Auth::user()->id;
         $bulksms->smsgateway_provider = request()->smsgateway_provider;
         $bulksms->sender_id = request()->sender_id;
         $bulksms->sms_api_key = request()->sms_api_key;
         $bulksms->save();
    
         $notification = array(
              'message' => 'Bulk SMS Settings Updated Successfully',
              'alert-type' => 'success'
         );
         return redirect()->route('admin.bulksms.setting')->with($notification);
    }   

  
    
}
