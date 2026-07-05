<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;
    protected $table = 'system_settings';
    protected $guarded =[];
    protected $hidden = ['portal_sms_secret'];

    protected function casts(): array
    {
        return [
            'portal_sms_enabled' => 'boolean',
            'portal_sms_secret' => 'encrypted',
            'portal_sms_system_route_enabled' => 'boolean',
            'portal_sms_sender_id_request_enabled' => 'boolean',
            'portal_sms_sending_enabled' => 'boolean',
            'portal_sms_topup_enabled' => 'boolean',
            'portal_sms_credit_request_enabled' => 'boolean',
            'portal_sms_last_synced_at' => 'datetime',
        ];
    }

}
