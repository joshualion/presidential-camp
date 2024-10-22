<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PollingUnit extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function ward(){
        return $this->belongsTo(Ward::class);
    }


    public function users(){
        return $this->hasMany(User::class);
    }

    //boot method for uuid

    public static function boot()
    {
        parent::boot();
        self::creating(function ($pu) {
            $pu->uuid = (string) Str::uuid();
        });
    }

}
