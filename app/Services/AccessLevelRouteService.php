<?php

namespace App\Services;

use App\Models\User;

class AccessLevelRouteService
{
    private const PREFIXES = [
        'superadmin' => 'superadmin',
        'nationaladmin' => 'national',
        'regionaladmin' => 'regional',
        'stateadmin' => 'state',
        'senatorialadmin' => 'senatorial',
        'federaladmin' => 'federal',
        'lgaadmin' => 'lga',
        'wardadmin' => 'ward',
        'puadmin' => 'pu',
        'user' => 'member',
    ];

    private const DASHBOARD_ROUTES = [
        'superadmin' => 'superadmin.dashboard',
        'nationaladmin' => 'nationaladmin.dashboard',
        'regionaladmin' => 'regionaladmin.dashboard',
        'stateadmin' => 'stateadmin.dashboard',
        'senatorialadmin' => 'senatorialadmin.dashboard',
        'federaladmin' => 'federaladmin.dashboard',
        'lgaadmin' => 'lgaadmin.dashboard',
        'wardadmin' => 'wardadmin.dashboard',
        'puadmin' => 'puadmin.dashboard',
        'user' => 'user.dashboard',
    ];

    public function prefixFor(string $accessLevel): ?string
    {
        return self::PREFIXES[$accessLevel] ?? null;
    }

    public static function prefixes(): array
    {
        return self::PREFIXES;
    }

    public function dashboardRouteFor(string $accessLevel): ?string
    {
        return self::DASHBOARD_ROUTES[$accessLevel] ?? null;
    }

    public function dashboardRouteForUser(?User $user): ?string
    {
        return $user ? $this->dashboardRouteFor((string) $user->access_level) : null;
    }

    public function sharedRouteForUser(?User $user, string $route): ?string
    {
        if (!$user || !isset(self::PREFIXES[$user->access_level])) {
            return null;
        }

        return $user->access_level.'.'.ltrim($route, '.');
    }
}
