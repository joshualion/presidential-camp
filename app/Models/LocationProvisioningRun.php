<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationProvisioningRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'summary' => 'array',
        'warnings' => 'array',
        'metadata' => 'array',
    ];

    public function localLicense()
    {
        return $this->belongsTo(LocalLicense::class);
    }
}
