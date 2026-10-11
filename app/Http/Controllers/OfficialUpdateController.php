<?php

namespace App\Http\Controllers;

use App\Services\OfficialUpdateService;
use Illuminate\Http\Request;

class OfficialUpdateController extends Controller
{
    public function index(Request $request, OfficialUpdateService $updates)
    {
        return view('backend.shared.official-updates', [
            'pageTitle' => 'Official Updates', 'profileData' => $request->user(),
            'installedVersion' => $updates->installedVersion(), 'updateStatus' => $updates->status(),
            'registration' => app(\App\Services\DeploymentRegistrationService::class)->summary(),
            'registrationDomain' => parse_url((string) config('app.url'), PHP_URL_HOST),
        ]);
    }

    public function check(OfficialUpdateService $updates)
    {
        try {
            $result = $updates->check();
            return redirect()->route('superadmin.updates')->with('update_notice', $result['message']);
        } catch (\Throwable $exception) {
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
