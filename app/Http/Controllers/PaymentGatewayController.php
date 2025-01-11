<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use App\Models\PaymentGateway; 
use App\Models\User;

class PaymentGatewayController extends Controller
{


    public function __construct()
    {
        
        $pageTitle = 'Payment Setting';
        View::share('pageTitle', $pageTitle);
    }

    // Function to get the profile data
    private function getProfileData()
    {
        $id = Auth::user()->id;
        return User::find($id);
    }
    // Display a list of payment gateways
    public function getPaymentGateways()
    {
        $gateways = PaymentGateway::all();
        $profileData = $this->getProfileData();
        return view('backend.'.$profileData->access_level.'.settings.payment.gateways', compact('gateways','profileData'));
    }

   
  
    // Update the specified payment gateway in the database
    public function updatePaymentGateway(Request $request)
    {
        $gateway_id = $request->id;
        $gateway = PaymentGateway::findOrFail($gateway_id);

        // Validate the request data here
        $commonAttributes = [
            'name' => $request->name,
            'is_active' => $request->is_active,
        ];

        if ($request->name === 'Flutterwave') {
            $attributes = array_merge($commonAttributes, [
                'flutterwave_live_api_key' => $request->flutterwave_live_api_key,
                'flutterwave_test_api_key' => $request->flutterwave_test_api_key,
                'sandbox_mode' => $request->sandbox_mode,
            ]);

            $notification = [
                'message' => 'Flutterwave Credentials Updated Successfully',
                'alert-type' => 'success',
            ];
        } elseif ($request->name === 'USDT-Direct') {
            $attributes = array_merge($commonAttributes, [
                'usdt_trc20_wallet_address' => $request->usdt_trc20_wallet_address,
            ]);

            $notification = [
                'message' => 'USDT Wallet Address Updated Successfully',
                'alert-type' => 'success',
            ];
        } elseif ($request->name === 'Paystack') {
            $attributes = array_merge($commonAttributes, [
                'paystack_live_api_key' => $request->paystack_live_api_key,
                'paystack_test_api_key' => $request->paystack_test_api_key,
                'sandbox_mode' => $request->sandbox_mode,
            ]);

            $notification = [
                'message' => 'Paystack Credentials Updated Successfully',
                'alert-type' => 'success',
            ];
        } else {
            // Handle unsupported gateway name or other conditions
            $notification = [
                'message' => 'Invalid gateway name or condition',
                'alert-type' => 'error',
            ];

            return redirect()->back()->with($notification);
        }

        $gateway->update($attributes);

        return redirect()->back()->with($notification);
    }



 
    

   
}