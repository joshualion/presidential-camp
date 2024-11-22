<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PoliticalParty extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function elections(){
        return $this->hasMany(Election::class);
    }
    public function votes(){
        return $this->hasMany(Vote::class);
    }
    //Automate UUID creation
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }
}
