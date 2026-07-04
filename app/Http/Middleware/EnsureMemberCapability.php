<?php

namespace App\Http\Middleware;

use App\Services\MemberCapabilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberCapability
{
    public function __construct(private MemberCapabilityService $capabilities)
    {
    }

    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $user = $request->user();

        if (!$this->capabilities->isMember($user)) {
            return $next($request);
        }

        abort_unless(
            $this->capabilities->allows($user, $capability),
            403,
            $this->capabilities->denialMessage($capability)
        );

        return $next($request);
    }
}
