<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use App\Models\SystemSetting;
use App\Models\SMTPSetting;
use App\Models\User;
use App\Services\PackageGovernanceService;
class SystemSettingController extends Controller
{
    public function __construct()
    {

        $pageTitle = 'System Setting';


        View::share('pageTitle', $pageTitle);
    }

    // Function to get the profile data
    private function getProfileData()
    {
        $id = Auth::user()->id;

        return User::find($id);
    }


        private function getSystemSettings(){

            $SystemSetting = SystemSetting::find(1);
            return $SystemSetting ;

        }// End Method

        public function systemSettings(){

                $SystemSetting = SystemSetting::first();
                $countries = Country::all();
                $profileData = $this->getProfileData();
            return view('backend.'.$profileData->access_level.'.settings.system.general',compact('SystemSetting','countries','profileData'));

        }// End Method

        public function updateSystemsSettings(Request $request){

            $request->validate([
                'enable_s3_storage' => 'boolean',
                'enable_local_storage'=> 'boolean',
                'frontend_registration' => 'required|boolean',
                's3_key' => 'required_if:enable_s3_storage,1',
                's3_secret' => 'required_if:enable_s3_storage,1',
                's3_region' => 'required_if:enable_s3_storage,1',
                's3_bucket' => 'required_if:enable_s3_storage,1',
                's3_endpoint'=> 'required_if:enable_s3_storage,1',
                'bank' => 'nullable|string|max:255',
                'bank_account_number' => 'nullable|string|size:10',
                'bank_account_name' => 'nullable|string|max:255',
                'community_post_max_length' => 'nullable|integer|min:50|max:5000',
                'community_comment_max_length' => 'nullable|integer|min:50|max:3000',
                'community_daily_post_limit' => 'nullable|integer|min:1|max:500',
                'community_daily_comment_limit' => 'nullable|integer|min:1|max:2000',
                'community_attachment_max_mb' => 'nullable|integer|min:1|max:20',
                'community_image_max_mb' => 'nullable|integer|min:1|max:10',
                'community_video_max_mb' => 'nullable|integer|min:1|max:20',
                'community_allow_images' => 'nullable|boolean',
                'community_allow_videos' => 'nullable|boolean',
                'community_guidelines' => 'nullable|string',
                'require_bank_details' => 'sometimes|boolean',
                'package' => 'nullable|string',
            ]);


             // Enforce mutual exclusivity
                $enableLocalStorage = $request->has('enable_local_storage') && $request->enable_local_storage;
                $enableS3Storage = $request->has('enable_s3_storage') && $request->enable_s3_storage;

                if ($enableLocalStorage && $enableS3Storage) {
                    $enableLocalStorage = !$enableS3Storage;
                }
                                $site_id = $request->id;
                                $SystemSetting= $this->getSystemSettings();
                                $systemPackage = $request->filled('package')
                                    ? app(PackageGovernanceService::class)->normalize($request->package)
                                    : app(PackageGovernanceService::class)->normalize($SystemSetting?->package);

                        if($request->hasFile('logo')) {
                                    $file = $request->file('logo');
                                    $logofilename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
                                    $file->move(public_path('uploads/system_images/'), $logofilename);
                                    @unlink(public_path('uploads/system_images/') . $SystemSetting->logo);
                                    $SystemSetting->logo = $logofilename;
                        }else{
                            $logofilename = $SystemSetting->logo;
                        }

                        if($request->hasfile('login_page_background')){
                            $file = $request->file('login_page_background');
                            $loginPageBg = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
                            $file->move(public_path('uploads/system_images/'), $loginPageBg);
                            @unlink(public_path('uploads/system_images/') . $SystemSetting->login_page_background);
                            $SystemSetting->login_page_background = $loginPageBg;
                        }else{
                              // Keep the current login page background if no new file is uploaded
                            $loginPageBg = $SystemSetting->login_page_background;
                        }

                        if($request->hasFile('favicon')) {
                            $file = $request->file('favicon');
                            $favicon = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
                            $file->move(public_path('uploads/system_images/'), $favicon);
                            @unlink(public_path('uploads/system_images/') . $SystemSetting->favicon);
                            $SystemSetting->favicon = $favicon;

                        }else{
                            $favicon = $SystemSetting->favicon;
                        }

                                    $communityAvailable = app(\App\Services\CommunityRealtimeService::class)->moduleAvailable();

                                    SystemSetting::findOrFail($site_id)->update([
                                        'package' => $systemPackage,
                                        'system_country' => $request->system_country,
                                        'system_name' => $request->system_name,
                                        'campaign_slogan' => $request->campaign_slogan,
                                        'light_theme_color' => $request->light_theme_color,
                                        'dark_theme_color' => $request->dark_theme_color,
                                        'system_email' => $request->system_email,
                                        'frontend_community' => $communityAvailable ? $request->frontend_community : 0,
                                        'frontend_registration' => $request->frontend_registration,
                                        'system_currency' => $request->system_currency,
                                        'exchange_rate' => $request->exchange_rate,
                                        'company_address' => $request->company_address,
                                        'company_phone' => $request->company_phone,
                                        'require_bank_details' => $request->has('require_bank_details') ? 1 : 0,
                                        'facebook' => $request->facebook,
                                        'twitter' => $request->twitter,
                                        'instagram' => $request->instagram,
                                        'linkedin' => $request->linkedin,
                                        'youtube' => $request->youtube,
                                        'copyright' => $request->copyright,
                                        'disclaimer' => $request->disclaimer,
                                        'privacy_policy' => $request->privacy_policy,
                                        'tos' => $request->tos,
                                        'logo' => $logofilename,
                                        'login_page_background' => $loginPageBg,
                                        'favicon' => $favicon,
                                        'enable_local_storage' => $enableLocalStorage ,
                                        'enable_s3_storage' => $enableS3Storage ,
                                        's3_key' => $request->s3_key,
                                        's3_secret' => $request->s3_secret,
                                        's3_region' => $request->s3_region,
                                        's3_bucket' => $request->s3_bucket,
                                        's3_endpoint' => $request->s3_endpoint,                                        
                                        'bank' =>$request->bank,
                                        'bank_account_name'=> $request->bank_account_name,
                                        'bank_account_number'=> $request->bank_account_number,
                                        'community_post_max_length' => $request->community_post_max_length,
                                        'community_comment_max_length' => $request->community_comment_max_length,
                                        'community_daily_post_limit' => $request->community_daily_post_limit,
                                        'community_daily_comment_limit' => $request->community_daily_comment_limit,
                                        'community_attachment_max_mb' => $request->community_attachment_max_mb,
                                        'community_image_max_mb' => $request->community_image_max_mb ?: $request->community_attachment_max_mb,
                                        'community_video_max_mb' => $request->community_video_max_mb,
                                        'community_allow_images' => $request->community_allow_images ? 1 : 0,
                                        'community_allow_videos' => $request->community_allow_videos ? 1 : 0,
                                        'community_guidelines' => $request->community_guidelines,
                                    ]);

                                    $notification = array(
                                            'message' => 'System Settings Updated Successfully',
                                            'alert-type' => 'success'
                                        );

                                        return redirect()->back()->with($notification);



        }// End Method

        /**
         * Persist sidebar theme mode globally for all users.
         */
        public function updateSidebarTheme(Request $request)
        {
            $request->validate([
                'mode' => 'required|in:light,brand',
            ]);

            $SystemSetting = $this->getSystemSettings();

            if (!$SystemSetting) {
                return response()->json([
                    'message' => 'System settings not found',
                ], 404);
            }

            $SystemSetting->sidebar_theme_mode = $request->mode;
            $SystemSetting->save();

            return response()->json([
                'status' => 'ok',
                'mode' => $request->mode,
            ]);
        }

        public function SmtpSettings(){
                $profileData = $this->getProfileData();
                $smtpsettings = SMTPSetting::find(1);
                return view('backend.'.$profileData->access_level.'.settings.smtp.update_smtp', compact('smtpsettings','profileData'));
        }

        public function UpdateSmtpSettings(Request $request){
            $smtp_id = $request->id;

            SMTPSetting::findOrFail($smtp_id)->update([

                'mailer' => $request->mailer,
                'host' => $request->host,
                'port' => $request->port,
                'username' => $request->username,
                'password' => $request->password,
                'encryption' => $request->encryption,
                'from_address' => $request->from_address,
                ]);

                $notification = array(
                    'message' => 'SMTP Settings Updated Successfully',
                    'alert-type' => 'success'
                );

                return redirect()->back()->with($notification);

        }

        public function PrivacySetting(){

            $privacy = SystemSetting::find(1);
            $profileData = $this->getProfileData();
            return view('superadmin.settings.privacy',compact('privacy', 'profileData'));

        }// End Method

        public function UpdatePrivacyPolicy(Request $request){

                    $site_id = $request->id;
                    SystemSetting::findOrFail($site_id)->update([
                        'privacy_policy' => $request->privacy_policy,
                    ]);
                        $notification = array(
                            'message' => 'Privacy policy Updated Successfully',
                            'alert-type' => 'success'
                        );

                        return redirect()->back()->with($notification);

        }// End Method


        public function TosSetting(){

            $Tos = SystemSetting::find(1);
            $profileData = $this->getProfileData();
            return view('superadmin.settings.terms',compact('Tos', 'profileData'));

        }// End Method

        public function UpdateTos(Request $request){

                    $site_id = $request->id;
                    SystemSetting::findOrFail($site_id)->update([
                        'tos' => $request->tos,
                    ]);
                        $notification = array(
                            'message' => 'Terms & Conditions Updated Successfully',
                            'alert-type' => 'success'
                        );

                        return redirect()->back()->with($notification);

        }// End Method



     
        public function showActivationForm()
            {
                if ($redirect = $this->redirectForLocalLicenseFlow()) {
                    return $redirect;
                }

                $profileData = $this->getProfileData();
                $pageTitle = 'Activate License';

                return view('backend.' . $profileData->access_level . '.settings.activation.form', compact('profileData', 'pageTitle'));
            }
        
          
   
    public function validateActivationCode(Request $request)
    {
        if ($redirect = $this->redirectForLocalLicenseFlow()) {
            return $redirect;
        }

        $request->validate([
            'activation_code' => 'required|string',
            'package' => 'required|string',
        ]);

        $activationCode = $request->input('activation_code');
        $systemPackage = app(PackageGovernanceService::class)->normalize($request->input('package'));
        $originDomain = $request->getHost();
        $clientIP = $request->ip(); // Rate limit per IP

        // Prevent repeated failed attempts (Rate Limiting)
        $cacheKey = 'activation_attempts_' . $clientIP;
        if (Cache::get($cacheKey, 0) >= 5) {
            return back()->with([
                'message' => 'Too many activation attempts. Please try again later.',
                'alert-type' => 'error'
            ]);
        }

        // Call License Verification API on the Ordering Portal (Script 2)
        $response = Http::post('https://campaignmanager.ng/api/validate', [
            'activation_code' => $activationCode,
            'package' =>  $systemPackage,
            'domain' => $originDomain,
        ]);

        // Log the activation attempt
        Log::info('Sending activation request to licensing server', [
            'url' => 'https://campaignmanager.ng/api/validate',
            'data' => [
                'activation_code' => $activationCode,
                'package' =>  $systemPackage,
                'domain' => $originDomain,
            ]
        ]);
        

        if ($response->successful() && $response->json('valid')) {
            Log::info('License Server Response:', $response->json()); // Debugging
            $apiToken = $response->json('api_token'); // Get token from response
        
            DB::table('system_settings')->update([
                'activation_code' => $activationCode,
                'domain' => $originDomain,
                'api_token' => $apiToken,
                'activated_at' => now(),
            ]);
            
            
        
            return redirect()->route('superadmin.dashboard')->with([
                'message' => 'Activation successful!',
                'alert-type' => 'success'
            ]);
        } else {
            // Increase failed attempts
            Cache::increment($cacheKey);
            Cache::put($cacheKey, Cache::get($cacheKey, 0), now()->addMinutes(5));

            // Log failure details
            Log::warning('License Activation Failed', [
                'ip' => $clientIP,
                'activation_code' => $activationCode,
                'domain' => $originDomain,
                'error' => $response->json('message', 'Unknown error'),
            ]);

            return back()->with([
                'message' => 'Invalid activation code or domain mismatch.',
                'alert-type' => 'error'
            ]);
        }
    }

    private function redirectForLocalLicenseFlow()
    {
        $deployment = app(\App\Services\DeploymentModeService::class);
        $installation = app(\App\Services\InstallationStateService::class);
        $licenses = app(\App\Services\LocalLicenseService::class);

        if ($licenses->isLocallyActivated()) {
            return redirect()
                ->route('admin.license.show')
                ->with('status', 'This installation is already activated.');
        }

        if ($deployment->isSelfHosted() && $installation->installed()) {
            return redirect()
                ->route('admin.license.show')
                ->withErrors(['license' => 'No active local license was found. Please contact support or rerun the installer/reset procedure.']);
        }

        return null;
    }


}
