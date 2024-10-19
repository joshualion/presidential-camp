<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ward extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function localGovernmentArea(){
        return $this->belongsTo(LocalGovernmentArea::class,'lga_id');
    }


    public function pollingUnits(){
        return $this->hasMany(PollingUnit::class);
    }

    public function users(){
        return $this->hasMany(User::class);
    }

    // Automatically generate UUID when creating a new state
   protected static function boot()
   {
       parent::boot();

       static::creating(function ($ward) {
        $ward->uuid = (string) Str::uuid();
       });
   }

}
