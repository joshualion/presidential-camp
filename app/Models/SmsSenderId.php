<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSenderId extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['portal_payload' => 'array', 'last_synced_at' => 'datetime']; }
}
