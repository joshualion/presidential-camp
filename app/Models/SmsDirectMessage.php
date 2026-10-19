<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsDirectMessage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'last_synced_at' => 'datetime'];
    }

    public function batch()
    {
        return $this->belongsTo(SmsDirectBatch::class, 'batch_id');
    }
}
