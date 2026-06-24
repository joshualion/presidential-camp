<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrityStatus extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'manifest_present' => 'boolean',
        'manifest_signature_valid' => 'boolean',
        'protected_files_valid' => 'boolean',
        'tampered' => 'boolean',
        'tamper_details' => 'array',
    ];
}
