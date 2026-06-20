<?php

namespace App\Http\Controllers\nationaladmin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\SystemSetting;
use App\Models\SMTPSetting;
use App\Models\User;
class NationalSystemSettingController extends Controller
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
                's3_key' => 'required_if:enable_s3_storage,1',
                's3_secret' => 'required_if:enable_s3_storage,1',
                's3_region' => 'required_if:enable_s3_storage,1',
                's3_bucket' => 'required_if:enable_s3_storage,1',
                's3_endpoint'=> 'required_if:enable_s3_storage,1',
                'bank' => 'nullable|string|max:255',
                'bank_account_number' => 'nullable|string|size:10',
                'bank_account_name' => 'nullable|string|max:255',
            ]);


             // Enforce mutual exclusivity
                $enableLocalStorage = $request->has('enable_local_storage') && $request->enable_local_storage;
                $enableS3Storage = $request->has('enable_s3_storage') && $request->enable_s3_storage;

                if ($enableLocalStorage && $enableS3Storage) {
                    $enableLocalStorage = !$enableS3Storage;
                }
                                $site_id = $request->id;
                                $SystemSetting= $this->getSystemSettings();

                        if($request->hasFile('logo')) {
                                    $file = $request->file('logo');
                                    $logofilename = date('YmdHi') . $file->getClientOriginalName();
                                    $file->move(public_path('uploads/system_images/'), $logofilename);
                                    @unlink(public_path('uploads/system_images/') . $SystemSetting->logo);
                                    $SystemSetting->logo = $logofilename;
                        }else{
                            $logofilename = $SystemSetting->logo;
                        }

                        if($request->hasfile('login_page_background')){
                            $file = $request->file('login_page_background');
                            $loginPageBg = date('YmdHi') . $file->getClientOriginalName();
                            $file->move(public_path('uploads/system_images/'), $loginPageBg);
                            @unlink(public_path('uploads/system_images/') . $SystemSetting->login_page_background);
                            $SystemSetting->login_page_background = $loginPageBg;
                        }else{
                              // Keep the current login page background if no new file is uploaded
                            $loginPageBg = $SystemSetting->login_page_background;
                        }

                        if($request->hasFile('favicon')) {
                            $file = $request->file('favicon');
                            $favicon = date('YmdHi') . $file->getClientOriginalName();
                            $file->move(public_path('uploads/system_images/'), $favicon);
                            @unlink(public_path('uploads/system_images/') . $SystemSetting->favicon);
                            $SystemSetting->favicon = $favicon;

                        }else{
                            $favicon = $SystemSetting->favicon;
                        }

                                    $communityAvailable = app(\App\Services\CommunityRealtimeService::class)->moduleAvailable();

                                    SystemSetting::findOrFail($site_id)->update([
                                        'package' => $request->package,
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
                                        'facebook' => $request->facebook,
                                        'twitter' => $request->twitter,
                                        'instagram' => $request->instagram,
                                        'linkedin' => $request->linkedin,
                                        'youtube' => $request->youtube,
                                        'activation_code' => $request->activation_code,
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
                                    ]);

                                    $notification = array(
                                            'message' => 'System Settings Updated Successfully',
                                            'alert-type' => 'success'
                                        );

                                        return redirect()->back()->with($notification);



        }// End Method

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





}
