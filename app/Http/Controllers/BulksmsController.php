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
        $users = User::query()->where('access_level', '!=', 'superadmin');

        if ($request->filled('region_id')) {
            $users->whereHas('region', function ($query) use ($request) {
                $query->where('id', $request->region_id);
            });
        }

        if ($request->filled('state_id')) {
            $users->whereHas('state', function ($query) use ($request) {
                $query->orwhere('id', $request->state_id);
            });
        }

        if ($request->filled('lga_id')) {
            $users->whereHas('lga', function ($query) use ($request) {
                $query->orwhere('id', $request->lga_id);
            });
        }

        if ($request->filled('ward_id')) {
            $users->whereHas('ward', function ($query) use ($request) {
                $query->orwhere('id', $request->ward_id);
            });
        }

        if ($request->filled('pu_id')) {
            $users->whereHas('pollingUnit', function ($query) use ($request) {
                $query->orwhere('id', $request->pu_id);
            });
        }

        if ($request->filled('support_group_id')) {
            $users->whereHas('supportGroups', function ($query) use ($request) {
                $query->where('id', $request->support_group_id);
            });
        }

        return $users->whereNotNull('phone')
            ->pluck('phone')
            ->toArray();
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
            $notification = [
                'message' => 'You need to set up your SMS settings before sending SMS',
                'alert-type' => 'error'
            ];
            return view('backend.' . $profileData->access_level . 'settings.bulksms.settings', compact('profileData'))->with($notification);
        }

        $apiToken = $smsSetting->sms_api_key;
        $senderId = $smsSetting->sender_id;

        // Filter recipients
        $recipients = $this->filterRecipients($request);

        if (empty($recipients)) {
            $notification = [
                'message' => 'No recipients available',
                'alert-type' => 'error'
            ];
            return redirect()->back()->with($notification);
        }

        $message = $request->input('message');
        if (empty($message)) {
            $notification = [
                'message' => 'No message available',
                'alert-type' => 'error'
            ];
            return redirect()->back()->with($notification);
        }

        // Process recipients in chunks to avoid memory issues
        User::chunk(100, function ($users) use ($message, $apiToken, $senderId) {
            $recipients = $users->pluck('phone')->toArray();
            // Dispatch a job for each chunk
            SendSmsBatchJob::dispatch($recipients, $message, $apiToken, $senderId);
        });

        $notification = [
            'message' => 'Your SMS has been queued and will be sent shortly.',
            'alert-type' => 'success'
        ];

        return redirect()->back()->with($notification);
    }


   


 



    
    
}
