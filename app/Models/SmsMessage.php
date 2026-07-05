<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SmsMessage extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['refund_eligible' => 'boolean', 'refunded' => 'boolean', 'billing_review_required' => 'boolean', 'client_charge_amount' => 'decimal:6', 'estimated_amount' => 'decimal:6', 'delivered_at' => 'datetime', 'failed_at' => 'datetime', 'refunded_at' => 'datetime', 'last_synced_at' => 'datetime']; }
    protected static function booted(): void { static::creating(fn (self $row) => $row->local_message_reference ??= (string) Str::uuid()); }
    public function batch() { return $this->belongsTo(SmsBatch::class, 'sms_batch_id'); }
    public function events() { return $this->hasMany(SmsStatusEvent::class); }
}
