<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalGovernmentArea extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function state(){
        return $this->belongsTo(State::class);
    }
    public function wards(){
        return $this->hasMany(Ward::class);
    }

    public function users(){
        return $this->hasMany(User::class);
    }

}
