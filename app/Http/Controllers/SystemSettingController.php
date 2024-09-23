<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\SystemSetting;
use App\Models\SMTPSetting;
use App\Models\User;
use intervention\Image\Facades\Image;
class SystemSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','role:admin']);
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

                $SystemSetting = SystemSetting::find(1);
                $profileData = $this->getProfileData();
            return view('admin.settings.general_settings',compact('SystemSetting','profileData'));

        }// End Method

        public function updateSystemsSettings(Request $request){

                                $site_id = $request->id;
                                $SystemSetting= $this->getSystemSettings();

                        if ($request->hasFile('logo')) {
                                    $file = $request->file('logo');
                                    $filename = date('YmdHi') . $file->getClientOriginalName();
                                    $file->move(public_path('uploads/logo/'), $filename);
                                    @unlink(public_path('uploads/admin_images/') . $SystemSetting->logo);
                                    $SystemSetting->logo = $filename;

                                    SystemSetting::findOrFail($site_id)->update([
                                        'system_name' => $request->system_name,
                                        'admin_email' => $request->admin_email,
                                        'system_currency' => $request->system_currency,
                                        'exchange_rate' => $request->exchange_rate,
                                        'company_address' => $request->company_address,
                                        'company_phone' => $request->company_phone,
                                        'facebook' => $request->facebook,
                                        'twitter' => $request->twitter,
                                        'copyright' => $request->copyright,
                                        'disclaimer' => $request->disclaimer,
                                        'logo' => $filename,
                                    ]);

                                    $notification = array(
                                            'message' => 'SystemSetting Updated with Image Successfully',
                                            'alert-type' => 'success'
                                        );

                                        return redirect()->back()->with($notification);

                                }else{


                                  SystemSetting::findOrFail($site_id)->update([
                                        'system_name' => $request->system_name,
                                        'admin_email' => $request->admin_email,
                                        'system_currency' => $request->system_currency,
                                        'exchange_rate' => $request->exchange_rate,
                                        'company_address' => $request->company_address,
                                        'company_phone' => $request->company_phone,
                                        'facebook' => $request->facebook,
                                        'twitter' => $request->twitter,
                                        'copyright' => $request->copyright,
                                        'disclaimer' => $request->disclaimer,
                                            ]);

                                                $notification = array(
                                                    'message' => 'SystemSetting Updated without Image Successfully',
                                                    'alert-type' => 'success'
                                                );

                                         return redirect()->back()->with($notification);

                                }

        }// End Method

        public function SmtpSettings(){
                $profileData = $this->getProfileData();
                $smtpsettings = SMTPSetting::find(1);
                return view('admin.settings.smtp_update', compact('smtpsettings','profileData'));
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
            return view('admin.settings.privacy',compact('privacy', 'profileData'));

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
            return view('admin.settings.terms',compact('Tos', 'profileData'));

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

        public function getLogo()
        {
            $systemLogo = SystemSetting::first();

            if ($systemLogo && !empty($systemLogo->logo)) {
                // If the logo exists in your system settings and has a path
                $logoUrl = asset('uploads/logo/'.$systemLogo->logo_path);
            } else {
                // If no logo is found, provide a default URL or a placeholder image
                $logoUrl = asset('logo.png');
            }

            return view('emails.custom', compact('logoUrl'));
        }



    
}
