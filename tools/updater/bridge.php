<?php

use App\Services\DeploymentRegistrationService;
use App\Services\OfficialUpdateService;
use App\Services\Updates\AutomaticUpdateService;
use App\Services\Updates\UpdateException;
use App\Services\Updates\UpdatePortalClient;
use App\Services\Updates\UpdateProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST'], '/campaign-update-bootstrap.php', function (Request $request) use ($private) {
    abort_unless($request->user() && $request->user()->access_level === 'superadmin', 403);
    $updates = app(OfficialUpdateService::class);
    $automatic = app(AutomaticUpdateService::class);
    if ($request->isMethod('post')) {
        try {
            switch ($request->input('action')) {
                case 'register': $request->validate(['registration_consent' => 'accepted']);
                    app(DeploymentRegistrationService::class)->register($request->user(), false);
                    $notice = 'Installation registered. Confirm the contact email sent by the Portal, then continue.';
                    break;
                case 'check': $updates->check();
                    $notice = 'Official update check completed.';
                    break;
                case 'prepare':
                    $v = $request->validate(['version' => 'required|string', 'sha256' => ['required', 'regex:/^[a-f0-9]{64}$/D'], 'update_consent' => 'accepted']);
                    $job = app(\App\Services\Updates\AutomaticUpdateFlow::class)->start($v['version'], $v['sha256']);

                    return UpdateProgress::response($job, $request);
                case 'discard': $v = $request->validate(['stage_id' => 'required|uuid']);
                    $automatic->discard($v['stage_id']);
                    $notice = 'Prepared package removed.';
                    break;
                case 'apply':
                    $v = $request->validate(['stage_id' => 'required|uuid', 'update_consent' => 'accepted']);
                    $job = $automatic->start($v['stage_id']);

                    return UpdateProgress::response($job, $request);
                default: abort(422);
            }

            return redirect('/campaign-update-bootstrap.php')->with('update_notice', $notice);
        } catch (Throwable $e) {
            return redirect('/campaign-update-bootstrap.php')->with('update_error', $e instanceof UpdateException ? $e->getMessage() : 'The operation stopped. Your existing installation is preserved. Check the private server logs.');
        }
    }
    $status = $updates->status();
    $stage = null;
    $entitlement = null;
    $error = null;
    $job = null;
    foreach (glob(storage_path('app/updates/staged/*/journal.json')) as $path) {
        $s = json_decode(file_get_contents($path), true);
        if (($s['state'] ?? '') === 'prepared') {
            $stage = $s;
            break;
        }
    }
    $review = app(\App\Services\Updates\AutomaticUpdateFlow::class)->screen(true);
    $status = $review['status'];
    $entitlement = $review['entitlement'];
    $issue = $review['issue'];
    $error = $issue['message'] ?? null;
    foreach (glob(storage_path('app/updates/jobs/*/journal.json')) as $path) {
        $j = json_decode(file_get_contents($path), true);
        if (! $job || ($j['created_at'] ?? '') > ($job['created_at'] ?? '')) {
            $job = $j;
        }
    }
    $registration = app(DeploymentRegistrationService::class)->summary();
    app('view')->addNamespace('campaignBootstrap', $private);

    return view('campaignBootstrap::screen', compact('status', 'stage', 'entitlement', 'error', 'issue', 'job', 'registration') + ['installedVersion' => $updates->installedVersion()]);
})->middleware(['web', 'auth', 'access_level:superadmin', 'throttle:20,1']);
