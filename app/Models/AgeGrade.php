<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AgeGrade extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function users(){
        return $this->hasMany(User::class);
    }

    
    //Auto generate UUID when creating a new religion
    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }
}
