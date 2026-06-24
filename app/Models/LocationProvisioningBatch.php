<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationProvisioningBatch extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'session_token',
    ];

    protected $casts = [
        'scope_payload' => 'array',
        'summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
