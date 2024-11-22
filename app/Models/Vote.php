<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function election(){
        return $this->belongsTo(Election::class);
    }

    public function politicalParty(){
        return $this->belongsTo(PoliticalParty::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    //belong to region, state, lga, ward, polling_unit

    public function region(){
        return $this->belongsTo(Region::class);
    }

    public function state(){
        return $this->belongsTo(State::class);
    }

    public function lga(){
        return $this->belongsTo(LocalGovernmentArea::class);
    }

    public function ward(){
        return $this->belongsTo(Ward::class);
    }

    public function pollingUnit(){
        return $this->belongsTo(PollingUnit::class);
    }
}
