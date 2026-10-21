<?php

use App\Services\Updates\BootstrapHttpRequest;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Facade;

// Public entry installed only by the verified one-time bootstrap.
$base = dirname(__DIR__);
$private = $base.'/storage/app/updates/bootstrap';
if (! is_file($private.'/bridge.php')) {
    http_response_code(404);
    exit;
}
require $base.'/vendor/autoload.php';
foreach (glob($private.'/services/*.php') as $file) {
    require_once $file;
}
foreach (glob($private.'/engine/*.php') as $file) {
    require_once $file;
}
$request = BootstrapHttpRequest::capture($base.'/public/index.php');
$app = require $base.'/bootstrap/app.php';
$app->instance('request', $request);
Facade::clearResolvedInstance('request');
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
config(['updates' => require $private.'/updates.php']);
config(['release.signing.public_key' => file_get_contents($private.'/trusted.pem')]);
config(['campaign.portal_api_base' => config('campaign.portal_api_base') ?: 'https://campaignmanager.ng/api/v2', 'campaign.portal_web_base' => config('campaign.portal_web_base') ?: 'https://campaignmanager.ng']);
require $private.'/bridge.php';
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
