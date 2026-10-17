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

        return view('backend.shared.official-updates', [
            'pageTitle' => 'Official Updates', 'profileData' => $request->user(),
            'installedVersion' => $updates->installedVersion(), 'updateStatus' => $status,
            'shouldCheck' => !$checkedAt || $checkedAt < now()->subMinutes(15)->timestamp,
            'preparedUpdate' => $preparedUpdate,
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

    public function prepare(OfficialUpdateService $updates)
    {
        try {
            $updates->prepare();
            return redirect()->route('superadmin.updates')->with('update_notice', 'The signed update is staged for review. Application files, configuration, uploads and the database have not been replaced.');
        } catch (\Throwable $exception) {
            return redirect()->route('superadmin.updates')->with('update_error', 'Update preparation stopped. The package could not be verified, the server cannot stage it, or another package is already staged. Your installed version remains in place.');
        }
    }
}

