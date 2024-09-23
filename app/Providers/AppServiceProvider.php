<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\SMTPSetting;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Config;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if(Schema::hasTable('s_m_t_p_settings')){
            $smtpSettings = SMTPSetting::first();
            $SystemSetting = SystemSetting::first();

            if($smtpSettings && $SystemSetting ){

                $data = [
                    'driver' => $smtpSettings->mailer,
                    'host' => $smtpSettings->host,
                    'port' => $smtpSettings->port,
                    'username' => $smtpSettings->username,
                    'password' => $smtpSettings->password,
                    'encryption' => $smtpSettings->encryption,
                    'from' => [
                                'address' => $smtpSettings->from_address,

                                'name' => $SystemSetting->system_name,
                             ]

                    ];
                    Config::set('mail',$data);
            }

        }// End if
    }
}
