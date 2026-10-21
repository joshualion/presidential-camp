<?php

use App\Services\DeploymentRegistrationService;
use App\Services\OfficialUpdateService;
use App\Services\Updates\AutomaticUpdateFlow;
use App\Services\Updates\UpdateException;
use App\Services\Updates\UpdateFlowException;
use App\Services\Updates\UpdatePortalClient;
use App\Services\Updates\UpdateProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

Route::match(['GET', 'POST'], '/campaign-update-bootstrap.php', function (Request $request) use ($private) {
    abort_unless($request->user() && $request->user()->access_level === 'superadmin', 403);
    $updates = app(OfficialUpdateService::class);
    $registrationService = app(DeploymentRegistrationService::class);
    $flow = app(AutomaticUpdateFlow::class);
    $url = url('/campaign-update-bootstrap.php');
    $activeJob = UpdateProgress::latest();
    if ($request->isMethod('get') && $request->boolean('progress') && ! empty($activeJob['status_token']) && ! empty($activeJob['id'])) {
        return UpdateProgress::response($activeJob, $request);
    }
    if ($request->isMethod('post')) {
        try {
            switch ($request->input('action')) {
                case 'register':
                    $request->validate(['registration_consent' => 'accepted']);
                    $registrationService->register($request->user(), false);

                    return redirect($url.'?registration=1&verify=1')->with('update_notice', 'Installation connected. Open the verification email once to enable purchases.');
                case 'verify-contact':
                    $registrationService->resendVerification();

                    return redirect($url.'?registration=1&verify=1')->with('update_notice', 'Check the registered administrator inbox for the verification email.');
                case 'messages':
                    $registrationService->sync();

                    return redirect($url.'?registration=1')->with('update_notice', 'Portal messages checked.');
                case 'domain-change':
                    $registrationService->requestDomainChange();

                    return redirect($url.'?registration=1')->with('update_notice', 'Approve the domain change through the registered email.');
                case 'check':
                    $updates->check();

                    return redirect($url);
                case 'automatic':
                    $review = $flow->review(true);
                    $request->session()->put('campaign_update_intent', ['version' => $review['status']['latest_version'], 'sha256' => $review['status']['manifest']['checksum_sha256'], 'expires' => now()->addHour()->timestamp]);

                    return empty($review['entitlement']['entitled']) ? redirect()->away($review['entitlement']['checkout_url']) : redirect($url);
                case 'prepare':
                    $data = $request->validate(['version' => 'required|regex:/^\d+\.\d+\.\d+$/D', 'sha256' => 'required|regex:/^[a-f0-9]{64}$/D', 'update_consent' => 'accepted']);

                    return UpdateProgress::response($flow->start($data['version'], $data['sha256']), $request);
                default:
                    abort(422);
            }
        } catch (ValidationException|HttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            if ($exception instanceof UpdateFlowException && $exception->reason === 'registration') {
                return redirect($url.'?registration=1&verify=1');
            }

            return redirect($url)->with('update_error', $exception instanceof UpdateException ? $exception->getMessage() : 'The update could not continue safely. Please try again or contact support.');
        }
    }
    if (! UpdatePortalClient::validBrowserReturn($request)) {
        return redirect($url)->with('update_error', 'This payment return has expired. Choose Update Automatically to continue safely.');
    }
    $status = $updates->status();
    $entitlement = null;
    $issue = null;
    $intent = $request->session()->get('campaign_update_intent');
    if (is_array($intent) && ($intent['expires'] ?? 0) >= now()->timestamp && $request->query('registration') !== '1') {
        $review = $flow->screen(true);
        $status = $review['status'];
        $entitlement = $review['entitlement'];
        $issue = $review['issue'];
    }
    $registration = $registrationService->summary();
    if ($request->query('registration') === '1' && ! empty($registration['registered']) && empty($registration['contact_verified'])) {
        try {
            $registrationService->sync();
            $registration = $registrationService->summary();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
    app('view')->addNamespace('campaignBootstrap', $private);

    return view('campaignBootstrap::screen', [
        'status' => $status, 'entitlement' => $entitlement, 'issue' => $issue, 'registration' => $registration,
        'registrationDomain' => parse_url(config('app.url'), PHP_URL_HOST), 'verificationRequired' => $request->boolean('verify'),
        'showRegistration' => $request->query('registration') === '1', 'bootstrap' => true, 'paymentReturn' => is_array($intent),
        'installedVersion' => $updates->installedVersion(), 'activeJob' => $activeJob,
        'automaticAction' => $url, 'confirmAction' => $url, 'checkAction' => $url,
        'registrationUrl' => $url.'?registration=1&verify=1', 'updatesUrl' => $url,
        'registrationActions' => ['connect' => $url, 'verify' => $url, 'messages' => $url, 'domain' => $url],
        'manualUrl' => ! empty($status['latest_version']) && preg_match('/^\d+\.\d+\.\d+$/D', $status['latest_version']) ? 'https://github.com/joshualion/Campaign-Manager/releases/tag/v'.$status['latest_version'] : 'https://github.com/joshualion/Campaign-Manager/releases',
    ]);
})->middleware(['web', 'auth', 'access_level:superadmin', 'throttle:20,1']);
