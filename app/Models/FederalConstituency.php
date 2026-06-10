<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FederalConstituency extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function senatorialDistrict()
    {
        return $this->belongsTo(SenatorialDistrict::class);
    }

    public function localGovernmentAreas()
    {
        return $this->hasMany(LocalGovernmentArea::class);
    }

    public function pollingUnits()
    {
        return $this->hasMany(PollingUnit::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($federalConstituency) {
            if (empty($federalConstituency->uuid)) {
                $federalConstituency->uuid = (string) Str::uuid();
            }
        });
    }
}
