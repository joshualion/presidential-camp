<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ActivationController extends Controller
{
    public function checkRemoteActivation()
    {
   
            $originDomain = request()->getHost();

            // Query remote server to check if domain is already activated
            $response = Http::get('https://campaignmanager.ng/check-domain', [
                'domain' => $originDomain,
            ]);

            if ($response->successful() && $response->json('activated')) {
                // Get the activation code from the remote server
                $activationCode = $response->json('activation_code');

                // Encrypt and store the activation code locally
                $encryptedCode = Crypt::encryptString($activationCode);
                DB::table('system_settings')->update([
                    'activation_code' => $encryptedCode,
                ]);

                return redirect()->route('home')->with('success', 'License activated remotely!');
            }

            return back()->withErrors(['activation_code' => 'No remote activation found.']);
    }
}