<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnterpriseAiSetting extends Model
{
    protected $guarded = [];
    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return ['api_key' => 'encrypted', 'enabled' => 'boolean'];
    }
}

