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
            return back()->with('update_error', 'Registration could not be confirmed. Check Portal connectivity and your application URL. Existing installations remain available; new setup requires a confirmed receipt.');
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

    public function domainChange(DeploymentRegistrationService $registration)
    {
        try {$registration->requestDomainChange();return back()->with('update_notice','Confirm the domain change through the verified contact email, then re-register this installation.');}
        catch (\Throwable $exception) {return back()->with('update_error','Confirm the existing contact and retry the domain change later.');}
    }
    public function resend(DeploymentRegistrationService $registration)
    {
        try {$registration->resendVerification();return back()->with('update_notice','Contact confirmation requested.');}
        catch (\Throwable $exception) {return back()->with('update_error','Contact confirmation could not be sent. Retry later.');}
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
