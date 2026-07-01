<?php

namespace App\Http\Middleware;

use App\Services\EmailNotificationPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailNotificationsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            app(EmailNotificationPermissionService::class)->canSend($request->user()),
            403,
            'Your access level is not permitted to send email notifications.'
        );

        return $next($request);
    }
}
