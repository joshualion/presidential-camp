<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Concerns\HasLocationScope;

class LocalGovernmentArea extends Model
{
    use HasFactory, HasLocationScope;

    protected $guarded = [];

    public function state(){
        return $this->belongsTo(State::class);
    }

    public function senatorialDistrict(){
        return $this->belongsTo(SenatorialDistrict::class);
    }

    public function federalConstituency(){
        return $this->belongsTo(FederalConstituency::class);
    }

    public function wards(){
        return $this->hasMany(Ward::class, 'lga_id');
    }

    public function users(){
        return $this->hasMany(User::class, 'lga_id');
    }

    public function votes(){
        return $this->hasMany(Vote::class, 'lga_id');
    }

    public function incidents(){
        return $this->hasMany(ElectionIncident::class, 'lga_id');
    }

    // Automatically generate UUID when creating a new LGA
   protected static function boot()
   {
       parent::boot();

       static::creating(function ($lga) {
        $lga->uuid = (string) Str::uuid();
       });
   }

}
