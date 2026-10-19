<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsProviderAccount extends Model
{
    protected $guarded = [];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return ['api_key' => 'encrypted', 'is_active' => 'boolean'];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
