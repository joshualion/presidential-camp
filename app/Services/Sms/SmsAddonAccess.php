<?php

namespace App\Services\Sms;

use App\Services\Enterprise\EntitlementService;
use App\Services\Enterprise\LegacySmsAccess;

/** Installation access is separate from existing user permissions and provider charges. */
class SmsAddonAccess
{
    public function allowed(bool $refresh = true): bool
    {
        // Recheck each use, including queued work. A singleton must not retain a
        // prior true result after an entitlement expires or identity changes.
        if (app(LegacySmsAccess::class)->allowed()) {
            return true;
        }
        $entitlements = app(EntitlementService::class);
        if ($refresh) {
            return $entitlements->active('sms_messaging');
        }
        // Rendering navigation must not make remote requests. cached() verifies
        // the signature and installation identity before returning any claims.
        $claims = $entitlements->cached();
        $grant = $claims['features']['sms_messaging'] ?? null;
        $now = time();
        return is_array($claims) && is_int($claims['valid_until'] ?? null) && $claims['valid_until'] > $now
            && is_array($grant) && ($grant['active'] ?? null) === true
            && is_int($grant['starts_at'] ?? null) && $grant['starts_at'] <= $now
            && (! isset($grant['expires_at']) || (is_int($grant['expires_at']) && $grant['expires_at'] > $now));
    }
}
