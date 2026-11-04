<?php

namespace App\Services\Sms;

use App\Services\Enterprise\EntitlementService;
use App\Services\Enterprise\LegacySmsAccess;

/** Installation access is separate from existing user permissions and provider charges. */
class SmsAddonAccess
{
    public function allowed(): bool
    {
        // Recheck each use, including queued work. A singleton must not retain a
        // prior true result after an entitlement expires or identity changes.
        return app(LegacySmsAccess::class)->allowed()
            || app(EntitlementService::class)->active('sms_messaging');
    }
}
