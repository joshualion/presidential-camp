<?php

namespace App\Http\Controllers;

use App\Models\BulkSmsSetting;
use App\Jobs\SendSmsBatchJob;
use App\Models\SupportGroup;
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

     
    private function filterRecipients(Request $request)
    {
        $users = User::query()->where('access_level', '!=', 'superadmin')->whereNotNull('phone');                 
       

       
         if ($request->filled('region_id')) {
            // Only users belonging to the selected region
            $users->whereHas('region', function ($query) use ($request) {
                $query->where('id', $request->region_id);
            });
        }elseif ($request->filled('state_id')) {
            // Only users belonging to the selected state
            $users->whereHas('state', function ($query) use ($request) {
                $query->where('id', $request->state_id);
            });
        }elseif ($request->filled('lga_id')) {
            // Only users belonging to the selected LGA
            $users->whereHas('lga', function ($query) use ($request) {
                $query->where('id', $request->lga_id);
            });
        }elseif ($request->filled('ward_id')) {
            // Only users belonging to the selected ward
            $users->whereHas('ward', function ($query) use ($request) {
                $query->where('id', $request->ward_id);
            });
        }elseif ($request->filled('pu_id')) {
            // Only users belonging to the selected polling unit
            $users->whereHas('pollingUnit', function ($query) use ($request) {
                $query->where('id', $request->pu_id);
            });
        } 

        if ($request->filled('support_group_id')) {
            // Additional filter for support group
            $users->whereHas('supportGroups', function ($query) use ($request) {
                $query->where('id', $request->support_group_id);
            });
        }

        // Ensure only users with valid phone numbers are selected
       return $users->pluck('phone')->toArray();
    }



    private function sendSmsApiRequest($data, $apiToken)
    {
        $url = 'https://wiredirectsms.com/api/v3/sms/send';
        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $serverOutput = curl_exec($ch);
        curl_close($ch);

        return $serverOutput;
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
        $supportGroups = SupportGroup::all();
        
        $bulksms = BulkSmsSetting::where('user_id', $profileData->id)->first();
        
        if (!empty($bulksms->sender_id) && !empty($bulksms->sms_api_key)) {            
             return view('backend.'.$profileData->access_level.'.sms.sendsms', compact('profileData', 'bulksms','countries','regions','supportGroups'));
        }else{
            $notification = [
                'message' => 'You need to set up your SMS settings before sending SMS',
                'alert-type' => 'error'
            ];

   
         return redirect()->route($profileData->access_level.'.bulksms.setting')->with($notification);
              
            
        }
        
    }


    private function handleSmsResponse($report, $senderId)
    {
        if (isset($report['message'])) {
            switch ($report['message']) {
                case 'You have exceeded your sending limit.':
                    return [
                        'message' => 'Insufficient SMS credit!',
                        'alert-type' => 'error'
                    ];
                case 'Unauthenticated.':
                    return [
                        'message' => 'Check your API token and try again or contact Support for assistance',
                        'alert-type' => 'error'
                    ];
                case 'No sending server available for your subscribed plan':
                    return [
                        'message' => 'No sending server available; assign a sending server on Wiredirect API Page or contact Support for assistance',
                        'alert-type' => 'error'
                    ];
                case 'Originator ' . $senderId . ' is not authorized to send this message':
                    return [
                        'message' => 'Please register your sender ID at Wiredirectsms.com for approval',
                        'alert-type' => 'error'
                    ];
                case 'Your message was successfully delivered':
                    return [
                        'message' => 'Your message has been sent successfully',
                        'alert-type' => 'success'
                    ];
                default:
                    return [
                        'message' => 'Unexpected response from the SMS server: ' . $report['message'],
                        'alert-type' => 'error'
                    ];
            }
        }

        return [
            'message' => 'Failed to send SMS. Please check your API configuration.',
            'alert-type' => 'error'
        ];
    }


    public function sendSms(Request $request)
    {
        $profileData = $this->getProfileData();
    
        // Retrieve SMS settings
        $smsSetting = BulkSmsSetting::where('user_id', $profileData->id)->first();
    
        if (!$smsSetting) {
            return response()->json(['status' => 'error', 'message' => 'You need to set up your SMS settings before sending SMS']);
        }
    
        // Filter recipients
        $recipients = $this->filterRecipients($request);
    
        if (empty($recipients)) {
            return response()->json(['status' => 'error', 'message' => 'No recipients available']);
        }
    
        $message = $request->input('message');
        if (empty($message)) {
            return response()->json(['status' => 'error', 'message' => 'No message provided']);
        }
    
        // If preview mode is requested, return a summary without sending SMS
        if ($request->boolean('preview', false)) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'recipient_count' => count($recipients),
            ]);
        }
    
        // Process filtered recipients in chunks
        collect($recipients)->chunk(100)->each(function ($chunkedRecipients) use ($message, $smsSetting) {
            SendSmsBatchJob::dispatch($chunkedRecipients->toArray(), $message, $smsSetting->sms_api_key, $smsSetting->sender_id);
        });
    
        return response()->json(['status' => 'success', 'message' => 'Your SMS has been queued and will be sent shortly.']);
    }
    

 



    
    
}
