<?php

namespace App\Providers;

use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\PollingUnitResult;
use App\Models\User;
use App\Models\VideoEvidence;
use App\Models\Vote;
use App\Models\PollingUnitAgentAssignment;
use App\Observers\CampaignNotificationObserver;
use App\Observers\DashboardStatSnapshotObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\SMTPSetting;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use League\Flysystem\FilesystemAdapter;
use App\Services\DeploymentModeService;
use App\Services\InstallationStateService;
use App\Services\CampaignPackageUiService;
use App\Support\SafeDatabase;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $deployment = new DeploymentModeService();
        $installation = new InstallationStateService();

        if ($deployment->isSelfHosted() && !$installation->installed()) {
            Config::set('session.driver', 'file');
        }

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer([
            'backend.*.dashboard',
            'backend.shared.dashboard-election-stat-section',
            'backend.shared.dashboard-stat-grid',
            'backend.shared.dashboard-stat-box',
            'backend.shared.dashboard-metrics-cards',
        ], function ($view) {
            $ui = app(CampaignPackageUiService::class);

            $view->with('packageUi', $ui);
            $view->with('packageLabels', $ui->labels());
        });

        User::observe(DashboardStatSnapshotObserver::class);
        Vote::observe(DashboardStatSnapshotObserver::class);
        PollingUnitResult::observe(DashboardStatSnapshotObserver::class);
        ElectionIncident::observe(DashboardStatSnapshotObserver::class);
        PictureEvidence::observe(DashboardStatSnapshotObserver::class);
        VideoEvidence::observe(DashboardStatSnapshotObserver::class);

        User::observe(CampaignNotificationObserver::class);
        PollingUnitResult::observe(CampaignNotificationObserver::class);
        ElectionIncident::observe(CampaignNotificationObserver::class);
        PictureEvidence::observe(CampaignNotificationObserver::class);
        VideoEvidence::observe(CampaignNotificationObserver::class);
        PollingUnitAgentAssignment::observe(CampaignNotificationObserver::class);

        if(SafeDatabase::hasTable('s_m_t_p_settings') && SafeDatabase::hasTable('system_settings')){
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

            if ($SystemSetting?->enable_s3_storage) {
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
