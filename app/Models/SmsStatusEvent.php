<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsStatusEvent extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['payload' => 'array', 'occurred_at' => 'datetime']; }
    public function message() { return $this->belongsTo(SmsMessage::class, 'sms_message_id'); }
}
