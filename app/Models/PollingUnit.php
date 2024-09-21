<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

}
