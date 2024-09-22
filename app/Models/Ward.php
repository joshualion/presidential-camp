<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function localGovernmentArea(){
        return $this->belongsTo(LocalGovernmentArea::class);
    }


    public function pollingUnits(){
        return $this->hasMany(PollingUnit::class);
    }

    public function users(){
        return $this->hasMany(User::class);
    }

}
