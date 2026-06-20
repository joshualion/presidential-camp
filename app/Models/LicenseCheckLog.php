<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseCheckLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'checked_at' => 'datetime',
        'raw_payload' => 'array',
    ];
}
