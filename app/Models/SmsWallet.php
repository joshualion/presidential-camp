<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Support\SmsMoney;

class SmsWallet extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['balance' => 'decimal:6', 'reserved_balance' => 'decimal:6', 'last_synced_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $wallet) => $wallet->wallet_reference ??= (string) Str::uuid());
    }

    public function user() { return $this->belongsTo(User::class); }
    public function transactions() { return $this->hasMany(SmsWalletTransaction::class); }
    public function reservations() { return $this->hasMany(SmsWalletReservation::class); }

    public function getAvailableBalanceAttribute(): string
    {
        return SmsMoney::subtract((string) $this->balance, (string) $this->reserved_balance);
    }
}
