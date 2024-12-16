<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\SMTPSetting;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemAdapter;
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

            if ($SystemSetting->enable_s3_storage) {
                Storage::extend('s3', function ($app, $config) use ($SystemSetting) {
                    return new \League\Flysystem\AwsS3V3\AwsS3V3Adapter(
                        new \Aws\S3\S3Client([
                            'credentials' => [
                                'key' => $SystemSetting->s3_key,
                                'secret' => $SystemSetting->s3_secret,
                            ],
                            'region' => $SystemSetting->s3_region,
                            'version' => 'latest',
                        ]),
                        $SystemSetting->s3_bucket
                    );
                });
            }

        }// End if
    }
}
