<?php

namespace App\Services\Sms;

use App\Services\Enterprise\EntitlementService;

/**
 * SMS subscription gate is independent of the existing SMS roles and wallet.
 * It is staged off until existing installations have a purchase/upgrade path.
 */
class SmsAddonAccess
{
    private ?bool $allowed = null;

    public function allowed(): bool
    {
        if (! config('enterprise.sms_enforcement_enabled', false)) {
            return true;
        }

        return $this->allowed ??= app(EntitlementService::class)->active('sms_messaging');
    }
}
