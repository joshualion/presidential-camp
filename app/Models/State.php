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

    public function senatorialDistricts(){
        return $this->hasMany(SenatorialDistrict::class);
    }

    public function federalConstituencies(){
        return $this->hasMany(FederalConstituency::class);
    }

    public function users(){
        return $this->hasMany(User::class);
    }

    public function votes(){
        return $this->hasMany(Vote::class);
    }

    public function incidents(){
        return $this->hasMany(ElectionIncident::class);
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
