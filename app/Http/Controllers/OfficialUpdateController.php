<?php

namespace App\Http\Controllers;

use App\Services\DeploymentRegistrationService;
use App\Services\OfficialUpdateService;
use App\Services\Updates\AutomaticUpdateFlow;
use App\Services\Updates\AutomaticUpdateService;
use App\Services\Updates\UpdateException;
use App\Services\Updates\UpdateFlowException;
use App\Services\Updates\UpdatePaths;
use App\Services\Updates\UpdatePortalClient;
use App\Services\Updates\UpdateProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class OfficialUpdateController extends Controller
{
    public function index(Request $request, OfficialUpdateService $updates)
    {
        $status = $updates->status();
        $activeJob = null;
        foreach (glob(storage_path('app/updates/jobs/*/journal.json')) as $path) {
            $job = json_decode(file_get_contents($path), true);
            if (! is_array($job)) {
                continue;
            }
            if (! empty($job['portal_receipt_pending']) && in_array($job['state'], ['completed', 'failed', 'recovered'], true)) {
                try {
                    app(UpdatePortalClient::class)->finish($job, $job['state'] === 'completed');
                    $job['portal_receipt_pending'] = false;
                    UpdatePaths::write($path, $job);
                } catch (\Throwable) {
                }
            }
            if (! $activeJob || ($job['created_at'] ?? '') > ($activeJob['created_at'] ?? '')) {
                $activeJob = $job;
            }
        }

        if ($request->boolean('progress') && ! empty($activeJob['status_token']) && ! empty($activeJob['id'])) {
            return UpdateProgress::response($activeJob, $request);
        }

        return view('backend.shared.official-updates', [
            'pageTitle' => 'Official Updates', 'profileData' => $request->user(),
            'installedVersion' => $updates->installedVersion(), 'updateStatus' => $status,
            'activeJob' => $activeJob,
        ]);
    }

    public function contactConfirmed(Request $request, AutomaticUpdateFlow $flow)
    {
        abort_unless($request->user()?->access_level === 'superadmin', 403);
        try {
            app(DeploymentRegistrationService::class)->refreshVerification();
        } catch (\Throwable) {
            return redirect()->route('superadmin.updates')->with('update_error',
                'Installation confirmation could not be verified. Check registration details or try again.');
        }
        // Review only: no update intent, payment navigation, preparation or installation.
        return response()->view('backend.shared.official-updates', [
            'pageTitle' => 'Official Updates', 'profileData' => $request->user(),
            'installedVersion' => app(OfficialUpdateService::class)->installedVersion(),
            'updateStatus' => [], 'activeJob' => null, 'confirmationReview' => $flow->screen(true),
        ])->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
    }

    public function registration(Request $request)
    {
        $service = app(DeploymentRegistrationService::class);
        $summary = $service->summary();
        if (! empty($summary['registered']) && empty($summary['contact_verified'])) {
            try {
                $service->sync();
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return view('backend.shared.installation-registration', [
            'pageTitle' => 'Installation Registration', 'profileData' => $request->user(),
            'registration' => app(DeploymentRegistrationService::class)->summary(),
            'registrationDomain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            'verificationRequired' => $request->boolean('verify'),
        ]);
    }

    public function check(OfficialUpdateService $updates, Request $request)
    {
        try {
            $result = $updates->check();
            if ($request->expectsJson()) {
                return response()->json(['checked' => true]);
            }

            return redirect()->route('superadmin.updates')->with('update_notice', $result['message']);
        } catch (\Throwable $exception) {
            if ($request->expectsJson()) {
                return response()->json(['checked' => false], 503);
            }

            return redirect()->route('superadmin.updates')->with('update_error', 'The update check could not be completed or verified. Check server connectivity and the trusted release key.');
        }
    }

    public function automatic(Request $request, OfficialUpdateService $updates, AutomaticUpdateFlow $flow)
    {
        if ($request->isMethod('post')) {
            try {
                $review = $flow->review(true);
                $request->session()->put('campaign_update_intent', ['version' => $review['status']['latest_version'], 'sha256' => $review['status']['manifest']['checksum_sha256'], 'expires' => now()->addHour()->timestamp]);
                if (empty($review['entitlement']['entitled'])) {
                    return redirect()->away($review['entitlement']['checkout_url']);
                }

                return redirect()->route('superadmin.updates.automatic');
            } catch (\Throwable $exception) {
                report($exception);
                if ($exception instanceof UpdateFlowException && $exception->reason === 'registration') {
                    return redirect()->route('superadmin.deployment.settings', ['verify' => 1]);
                }

                return redirect()->route('superadmin.updates')->with('update_error', $exception instanceof UpdateException ? $exception->getMessage() : 'Automatic updating is temporarily unavailable. Please try again or contact support.');
            }
        }
        if (! UpdatePortalClient::validBrowserReturn($request)) {
            return redirect()->route('superadmin.updates')->with('update_error', 'This payment return has expired. Choose Update Automatically to continue safely.');
        }
        $intent = $request->session()->get('campaign_update_intent');
        if (! is_array($intent) || ($intent['expires'] ?? 0) < now()->timestamp) {
            return redirect()->route('superadmin.updates');
        }

        return view('backend.shared.automatic-update', $flow->screen(true) + [
            'pageTitle' => 'Automatic Update', 'profileData' => $request->user(),
            'installedVersion' => $updates->installedVersion(),
        ]);
    }

    public function prepare(Request $request, AutomaticUpdateFlow $flow)
    {
        $data = $request->validate([
            'version' => ['required', 'regex:/^\\d+\\.\\d+\\.\\d+$/D'],
            'sha256' => ['required', 'regex:/^[a-f0-9]{64}$/D'],
            'update_consent' => 'accepted',
        ]);
        try {
            $job = $flow->start($data['version'], $data['sha256']);

            return UpdateProgress::response($job, $request);
        } catch (\Throwable $exception) {
            return redirect()->route('superadmin.updates.automatic')->with('update_error',
                $exception instanceof UpdateException
                    ? $exception->getMessage()
                    : 'The update could not start safely. Please try again or contact support.');
        }
    }

    public function apply(Request $request, AutomaticUpdateFlow $flow)
    {
        $data = $request->validate(['stage_id' => 'required|uuid', 'update_consent' => 'accepted']);
        try {
            $job = $flow->startPrepared($data['stage_id']);

            return UpdateProgress::response($job, $request);
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.updates')->with('update_error', $e instanceof UpdateException ? $e->getMessage() : 'The automatic update could not start. No additional payment is required to retry.');
        }
    }

    public function discard(Request $request, AutomaticUpdateService $automatic)
    {
        $data = $request->validate(['stage_id' => 'required|uuid']);
        try {
            $automatic->discard($data['stage_id']);

            return redirect()->route('superadmin.updates')->with('update_notice', 'The prepared package was removed. Your installation and payment remain unchanged.');
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.updates')->with('update_error', 'This package cannot be discarded while an update or recovery is pending.');
        }
    }
}
