<?php

namespace App\Http\Controllers;

use App\Services\OfficialUpdateService;
use Illuminate\Http\Request;

class OfficialUpdateController extends Controller
{
    public function index(Request $request, OfficialUpdateService $updates)
    {
        $status = $updates->status();
        $checkedAt = strtotime((string) ($status['checked_at'] ?? ''));
        $preparedUpdate = null;
        foreach (\Illuminate\Support\Facades\File::glob(storage_path('app/updates/staged/*/journal.json')) as $journalPath) {
            $journal = json_decode(\Illuminate\Support\Facades\File::get($journalPath), true);
            if (is_array($journal) && ($journal['state'] ?? null) === 'prepared' && !empty($journal['version'])) {
                $preparedUpdate = $journal;
                break;
            }
        }

        $activeJob = null;
        foreach (glob(storage_path('app/updates/jobs/*/journal.json')) as $path) {
            $job = json_decode(file_get_contents($path), true);
            if (!is_array($job)) continue;
            if (!empty($job['portal_receipt_pending']) && in_array($job['state'], ['completed','failed','recovered'], true)) {
                try { app(\App\Services\Updates\UpdatePortalClient::class)->finish($job, $job['state'] === 'completed'); $job['portal_receipt_pending'] = false; \App\Services\Updates\UpdatePaths::write($path, $job); } catch (\Throwable) {}
            }
            if (!$activeJob || ($job['created_at'] ?? '') > ($activeJob['created_at'] ?? '')) $activeJob = $job;
        }

        return view('backend.shared.official-updates', [
            'pageTitle' => 'Official Updates', 'profileData' => $request->user(),
            'installedVersion' => $updates->installedVersion(), 'updateStatus' => $status,
            'shouldCheck' => !$checkedAt || $checkedAt < now()->subMinutes(15)->timestamp,
            'preparedUpdate' => $preparedUpdate, 'activeJob' => $activeJob,
        ]);
    }

    public function registration(Request $request)
    {
        return view('backend.shared.installation-registration', [
            'pageTitle' => 'Installation Registration', 'profileData' => $request->user(),
            'registration' => app(\App\Services\DeploymentRegistrationService::class)->summary(),
            'registrationDomain' => parse_url((string) config('app.url'), PHP_URL_HOST),
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

    public function automatic(Request $request, OfficialUpdateService $updates, \App\Services\Updates\AutomaticUpdateFlow $flow)
    {
        return view('backend.shared.automatic-update', $flow->screen() + [
            'pageTitle' => 'Automatic Update', 'profileData' => $request->user(),
            'installedVersion' => $updates->installedVersion(),
        ]);
    }

    public function prepare(Request $request, \App\Services\Updates\AutomaticUpdateFlow $flow)
    {
        $data = $request->validate([
            'version' => ['required', 'regex:/^\\d+\\.\\d+\\.\\d+$/D'],
            'sha256' => ['required', 'regex:/^[a-f0-9]{64}$/D'],
            'update_consent' => 'accepted',
        ]);
        try {
            $job = $flow->start($data['version'], $data['sha256']);

            return \App\Services\Updates\UpdateProgress::response($job, $request);
        } catch (\Throwable $exception) {
            return redirect()->route('superadmin.updates.automatic')->with('update_error',
                $exception instanceof \App\Services\Updates\UpdateException
                    ? $exception->getMessage()
                    : 'The automatic update could not start. Check coverage and server readiness, then retry.');
        }
    }

    public function apply(Request $request, \App\Services\Updates\AutomaticUpdateService $automatic)
    {
        $data = $request->validate(['stage_id' => 'required|uuid', 'update_consent' => 'accepted']);
        try {
            $job = $automatic->start($data['stage_id']);
            return \App\Services\Updates\UpdateProgress::response($job, $request);
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.updates')->with('update_error', $e instanceof \App\Services\Updates\UpdateException ? $e->getMessage() : 'The automatic update could not start. No additional payment is required to retry.');
        }
    }
    public function discard(Request $request, \App\Services\Updates\AutomaticUpdateService $automatic)
    {
        $data = $request->validate(['stage_id' => 'required|uuid']);
        try { $automatic->discard($data['stage_id']); return redirect()->route('superadmin.updates')->with('update_notice', 'The prepared package was removed. Your installation and payment remain unchanged.'); }
        catch (\Throwable $e) { return redirect()->route('superadmin.updates')->with('update_error', 'This package cannot be discarded while an update or recovery is pending.'); }
    }

}

