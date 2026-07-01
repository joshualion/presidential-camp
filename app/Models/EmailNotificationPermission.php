<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailNotificationPermission extends Model
{
    protected $fillable = ['access_level', 'enabled', 'updated_by'];

    protected $casts = ['enabled' => 'boolean'];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
