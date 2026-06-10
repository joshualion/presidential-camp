<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SenatorialDistrict extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function federalConstituencies()
    {
        return $this->hasMany(FederalConstituency::class);
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

        static::creating(function ($senatorialDistrict) {
            if (empty($senatorialDistrict->uuid)) {
                $senatorialDistrict->uuid = (string) Str::uuid();
            }
        });
    }
}
