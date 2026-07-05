<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use App\Services\Sms\SmsAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSmsAccess
{
    public function __construct(private SmsAccessService $access) {}

    public function handle(Request $request, Closure $next, string $permission = 'sms.view'): Response
    {
        abort_unless($this->access->allows($request->user(), $permission), 403, 'You do not have access to this SMS function.');
        $settingsRoute = $request->routeIs('superadmin.sms.settings.*');
        if (!$settingsRoute) {
            $settings = SystemSetting::query()->first();
            abort_unless($settings?->portal_sms_enabled, 503, 'SMS is not configured. Ask a super administrator to complete portal SMS setup.');
        }
        return $next($request);
    }
}
