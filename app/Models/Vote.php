<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Concerns\HasLocationScope;

class Vote extends Model
{
    use HasFactory, HasLocationScope;

    protected $guarded = [];

    protected $casts = [
        'election_id' => 'integer',
        'party_id' => 'integer',
        'agent_id' => 'integer',
        'region_id' => 'integer',
        'state_id' => 'integer',
        'senatorial_district_id' => 'integer',
        'federal_constituency_id' => 'integer',
        'lga_id' => 'integer',
        'ward_id' => 'integer',
        'polling_unit_id' => 'integer',
        'polling_unit_result_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function election(){
        return $this->belongsTo(Election::class);
    }

    public function politicalParty(){
        return $this->belongsTo(PoliticalParty::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    //belong to region, state, lga, ward, polling_unit

    public function region(){
        return $this->belongsTo(Region::class);
    }

    public function state(){
        return $this->belongsTo(State::class);
    }

    public function senatorialDistrict(){
        return $this->belongsTo(SenatorialDistrict::class);
    }

    public function federalConstituency(){
        return $this->belongsTo(FederalConstituency::class);
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

    public function pollingUnitResult()
{
    return $this->belongsTo(PollingUnitResult::class, 'polling_unit_result_id');
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
