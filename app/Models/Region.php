<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Region extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function states()
    {
        return $this->hasMany(State::class);
    }

    public function users(){
        return $this->hasMany(User::class);
    }

     // Automatically generate UUID when creating a new region
     protected static function boot()
     {
         parent::boot();
 
         static::creating(function ($region) {
             $region->uuid = (string) Str::uuid();
         });
     }

}
