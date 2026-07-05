<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

class SmsWalletTransaction extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['metadata' => 'array', 'amount' => 'decimal:6', 'balance_before' => 'decimal:6', 'balance_after' => 'decimal:6', 'reserved_before' => 'decimal:6', 'reserved_after' => 'decimal:6']; }
    protected static function booted(): void
    {
        static::creating(fn (self $row) => $row->transaction_reference ??= (string) Str::uuid());
        static::updating(fn () => throw new LogicException('SMS wallet ledger entries are immutable.'));
        static::deleting(fn () => throw new LogicException('SMS wallet ledger entries are immutable.'));
    }
    public function wallet() { return $this->belongsTo(SmsWallet::class, 'sms_wallet_id'); }
}
