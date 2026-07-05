<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SmsTopup extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['payment_instructions' => 'array', 'amount' => 'decimal:6', 'confirmed_at' => 'datetime']; }
    protected static function booted(): void { static::creating(fn (self $row) => $row->local_reference ??= (string) Str::uuid()); }
    public function wallet() { return $this->belongsTo(SmsWallet::class, 'sms_wallet_id'); }
}
