<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardStatSnapshot extends Model
{
    protected $fillable = [
        'package',
        'access_level',
        'scope_type',
        'scope_id',
        'scope_key',
        'payload',
        'refreshed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'scope_id' => 'integer',
        'refreshed_at' => 'datetime',
    ];
}
