<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SupportGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    //Autocreate UUID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            $group->uuid = (string) Str::uuid();
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_support_group');
    }
}
