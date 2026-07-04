<?php

namespace App\Http\Middleware;

use App\Services\PublicRegistrationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            app(PublicRegistrationService::class)->enabled(),
            404,
            'Public registration is not enabled.'
        );

        return $next($request);
    }
}
