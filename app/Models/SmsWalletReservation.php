<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SmsWalletReservation extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['amount' => 'decimal:6', 'debited_amount' => 'decimal:6', 'released_amount' => 'decimal:6', 'settled_at' => 'datetime']; }
    protected static function booted(): void { static::creating(fn (self $row) => $row->reservation_reference ??= (string) Str::uuid()); }
    public function wallet() { return $this->belongsTo(SmsWallet::class, 'sms_wallet_id'); }
}
