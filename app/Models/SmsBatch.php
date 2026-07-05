<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SmsBatch extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['target_scope_metadata' => 'array', 'metadata' => 'array', 'delivery_summary' => 'array', 'estimated_amount' => 'decimal:6', 'reserved_amount' => 'decimal:6', 'debited_amount' => 'decimal:6', 'refunded_amount' => 'decimal:6', 'reversed_amount' => 'decimal:6', 'released_amount' => 'decimal:6', 'submitted_at' => 'datetime', 'last_synced_at' => 'datetime', 'messages_acknowledged_at' => 'datetime']; }
    protected static function booted(): void { static::creating(fn (self $row) => $row->local_batch_reference ??= (string) Str::uuid()); }
    public function wallet() { return $this->belongsTo(SmsWallet::class, 'sms_wallet_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function messages() { return $this->hasMany(SmsMessage::class); }
    public function reservation() { return $this->hasOne(SmsWalletReservation::class, 'local_batch_reference', 'local_batch_reference'); }
}
