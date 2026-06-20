<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocalLicenseModule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'expires_at' => 'datetime',
        'raw_payload' => 'array',
    ];
}
