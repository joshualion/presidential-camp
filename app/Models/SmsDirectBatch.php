<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsDirectBatch extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'confirmed_at' => 'datetime'];
    }

    public function account()
    {
        return $this->belongsTo(SmsProviderAccount::class, 'account_id');
    }

    public function messages()
    {
        return $this->hasMany(SmsDirectMessage::class, 'batch_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
