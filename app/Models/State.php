<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class State extends Model
{
    use HasFactory;


    protected $guarded = [];


    public function region(){
        return $this->belongsTo(Region::class);
    }

    public function localGovernmentAreas(){
        return $this->hasMany(LocalGovernmentArea::class);
    }

    public function users(){
        return $this->hasMany(User::class);
    }


   // Automatically generate UUID when creating a new state
   protected static function boot()
   {
       parent::boot();

       static::creating(function ($state) {
           $state->uuid = (string) Str::uuid();
       });
   }
   



}
