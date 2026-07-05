<?php

use App\Http\Middleware\EnsureSystemNotActivated;
use App\Http\Middleware\EnsureCommunityEnabled;
use App\Http\Middleware\KuyakEyenIno;
use App\Http\Middleware\BlockInstallerIfInstalled;
use App\Http\Middleware\RedirectIfNotInstalled;
use App\Http\Middleware\UseInstallerSafeDrivers;
use App\Http\Middleware\ValidateLicense;
use App\Http\Middleware\EnsureCommunityProfileLocationComplete;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Middleware\Accesslevel;
use App\Http\Middleware\CheckUserOnboarding;
use App\Http\Middleware\EnsureEmailNotificationsEnabled;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureMemberCapability;
use App\Http\Middleware\EnsurePublicRegistrationEnabled;
use App\Http\Middleware\EnsureAnnouncementManager;
use App\Http\Middleware\EnsureElectionReportAccess;
use App\Http\Middleware\EnsureSmsAccess;

require_once __DIR__.'/../app/Support/InstallerAppKeyBootstrapper.php';

App\Support\InstallerAppKeyBootstrapper::bootstrap(dirname(__DIR__));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',

        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->web(prepend: [
            UseInstallerSafeDrivers::class,
        ]);
        $middleware->web(append: [
            EnsureActiveUser::class,
            RedirectIfNotInstalled::class,
        ]);

        $middleware->alias([
                         'access_level' => Accesslevel::class,
                         'check_onboarding' => CheckUserOnboarding::class, // Add this alias
                         'kuyak_eyen_ino' => KuyakEyenIno::class,
                         'EnsureSystemNotActivated' => EnsureSystemNotActivated::class,
                         'eyen_ino_no_way' => ValidateLicense::class,
                         'installer.block_if_installed' => BlockInstallerIfInstalled::class,
                         'community.enabled' => EnsureCommunityEnabled::class,
                         'community.profile_complete' => EnsureCommunityProfileLocationComplete::class,
                         'email_notifications.enabled' => EnsureEmailNotificationsEnabled::class,
                         'module.enabled' => EnsureModuleEnabled::class,
            'member.capability' => EnsureMemberCapability::class,
            'public.registration' => EnsurePublicRegistrationEnabled::class,
            'announcement.manager' => EnsureAnnouncementManager::class,
            'election.report.access' => EnsureElectionReportAccess::class,
            'sms.access' => EnsureSmsAccess::class,
                        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {// Custom exception handling
         // Custom exception handling
         $exceptions->render(function (HttpException $e, $request) {
            $status = $e->getStatusCode(); // Get the status code
            $message = $e->getMessage(); // Get the custom message (if any)

            // Default messages for specific status codes
            if (empty($message)) {
                switch ($status) {
                    case 404:
                        $message = 'Page Not Found';
                        break;
                    case 403:
                        $message = 'Forbidden';
                        break;
                    case 500:
                        $message = 'Internal Server Error';
                        break;
                    default:
                        $message = 'Something Went Wrong';
                        break;
                }
            }

            // Return the dynamic error view
            return response()->view('errors.error', [
                'status' => $status,
                'message' => $message,
            ], $status);
        });
    })->create();
