<?php

namespace App\Http\Middleware;

use App\Services\Sms\SmsAddonAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSmsAddon
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app(SmsAddonAccess::class)->allowed(), 402,
            'SMS Messaging requires an active installation add-on. Open Add-ons to purchase or refresh the licence.');
        return $next($request);
    }
}
