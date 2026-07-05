<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsAllocation extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['portal_payload' => 'array', 'amount' => 'decimal:6', 'credited_at' => 'datetime', 'portal_synced_at' => 'datetime']; }
    public function wallet() { return $this->belongsTo(SmsWallet::class, 'sms_wallet_id'); }
}
