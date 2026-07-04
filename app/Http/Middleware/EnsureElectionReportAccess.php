<?php

namespace App\Http\Middleware;

use App\Services\MemberCapabilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureElectionReportAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $admin = $user && in_array($user->access_level, [
            'superadmin', 'nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin',
            'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin',
        ], true);
        $member = $user && app(MemberCapabilityService::class)->allows($user, MemberCapabilityService::ELECTION_REPORTS);

        abort_unless($admin || $member, 403);

        return $next($request);
    }
}
