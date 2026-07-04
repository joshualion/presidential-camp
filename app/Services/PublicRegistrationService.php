<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Support\SafeDatabase;

class PublicRegistrationService
{
    public function enabled(): bool
    {
        if (!SafeDatabase::hasTable('system_settings')) {
            return false;
        }

        return (bool) SystemSetting::query()->value('frontend_registration');
    }
}
