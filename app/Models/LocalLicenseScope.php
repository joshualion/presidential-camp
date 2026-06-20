<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocalLicenseScope extends Model
{
    protected $guarded = [];

    protected $casts = [
        'raw_payload' => 'array',
    ];
}
