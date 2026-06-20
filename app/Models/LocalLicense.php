<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocalLicense extends Model
{
    protected $guarded = [];

    protected $casts = [
        'activated_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'grace_until' => 'datetime',
        'maintenance_expires_at' => 'datetime',
        'updates_until' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function scope()
    {
        return $this->hasOne(LocalLicenseScope::class);
    }

    public function modules()
    {
        return $this->hasMany(LocalLicenseModule::class);
    }
}
