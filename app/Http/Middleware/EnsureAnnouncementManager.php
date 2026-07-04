<?php

namespace App\Http\Middleware;

use App\Services\AnnouncementAudienceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnnouncementManager
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app(AnnouncementAudienceService::class)->canPublish($request->user()), 403);

        return $next($request);
    }
}
