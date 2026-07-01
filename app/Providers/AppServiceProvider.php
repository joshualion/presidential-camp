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
use App\Services\EmailNotificationPermissionService;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('send-email-notifications', fn (User $user) => app(EmailNotificationPermissionService::class)->canSend($user));

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
                $mailer = strtolower(trim((string) $smtpSettings->mailer)) ?: 'smtp';
                if (!array_key_exists($mailer, (array) Config::get('mail.mailers', []))) {
                    $mailer = 'smtp';
                }

                Config::set('mail.default', $mailer);

                if ($mailer === 'smtp') {
                    $smtpConfig = (array) Config::get('mail.mailers.smtp', []);
                    foreach ([
                        'host' => $smtpSettings->host,
                        'username' => $smtpSettings->username,
                        'password' => $smtpSettings->password,
                        'encryption' => $smtpSettings->encryption,
                    ] as $key => $value) {
                        if ($value !== null && trim((string) $value) !== '') {
                            $smtpConfig[$key] = $value;
                        }
                    }
                    if (is_numeric($smtpSettings->port)) {
                        $smtpConfig['port'] = (int) $smtpSettings->port;
                    }
                    $smtpConfig['transport'] = 'smtp';
                    Config::set('mail.mailers.smtp', $smtpConfig);
                }

                if (filter_var($smtpSettings->from_address, FILTER_VALIDATE_EMAIL)) {
                    Config::set('mail.from.address', $smtpSettings->from_address);
                }
                Config::set('mail.from.name', $SystemSetting->system_name ?: config('app.name'));
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
