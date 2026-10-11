<?php

namespace App\Http\Controllers;

use App\Services\DeploymentRegistrationService;
use Illuminate\Http\Request;

class DeploymentRegistrationController extends Controller
{
    public function register(Request $request, DeploymentRegistrationService $registration)
    {
        $data = $request->validate(['registration_consent' => 'required|accepted', 'marketing_opt_in' => 'nullable|boolean']);
        try {
            $registration->register($request->user(), (bool) ($data['marketing_opt_in'] ?? false));
            return back()->with('update_notice', 'Deployment registered. Confirm the contact email sent by the Portal. Marketing remains a separate optional choice.');
        } catch (\Throwable $exception) {
            return back()->with('update_error', 'Registration could not be confirmed. Check Portal connectivity and your application URL. The application remains available without registration.');
        }
    }

    public function sync(DeploymentRegistrationService $registration)
    {
        try {
            $registration->sync();
            return back()->with('update_notice', 'Portal messages refreshed.');
        } catch (\Throwable $exception) {
            return back()->with('update_error', 'The Portal could not be reached. Cached messages and the application remain available.');
        }
    }

    public function preferences(Request $request, DeploymentRegistrationService $registration)
    {
        $data = $request->validate(['marketing_opt_in' => 'required|boolean']);
        try {
            $registration->preferences((bool) $data['marketing_opt_in']);
            return back()->with('update_notice', 'Your marketing preference has been updated.');
        } catch (\Throwable $exception) {
            return back()->with('update_error', 'The Portal could not confirm the preference change. Try again when connected.');
        }
    }

    public function revoke(DeploymentRegistrationService $registration)
    {
        try {
            $registration->revoke();
            return back()->with('update_notice', 'Deployment registration removed. The application remains free to use.');
        } catch (\Throwable $exception) {
            return back()->with('update_notice', 'Local reporting has stopped. Portal deletion will be retried when its connection is restored.');
        }
    }
}
