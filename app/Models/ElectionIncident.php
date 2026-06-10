<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Concerns\HasLocationScope;

class ElectionIncident extends Model
{
    use HasFactory, HasLocationScope;

    protected $guarded  = [];

    protected $casts = [
        'election_id' => 'integer',
        'agent_id' => 'integer',
        'region_id' => 'integer',
        'state_id' => 'integer',
        'senatorial_district_id' => 'integer',
        'federal_constituency_id' => 'integer',
        'lga_id' => 'integer',
        'ward_id' => 'integer',
        'polling_unit_id' => 'integer',
        'resolved_at' => 'datetime',
        'resolved_by' => 'integer',
    ];

    public function election(){
        return $this->belongsTo(Election::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

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


    // Relationship to PictureEvidences
    public function pictureEvidences()
    {
        return $this->hasMany(PictureEvidence::class);
    }

    // Relationship to VideoEvidences
    public function videoEvidences()
    {
        return $this->hasMany(VideoEvidence::class);
    }

    //You can also define the relationship to the agent, if needed
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }


    //Auto generate UUID
    public static function boot()
{
    parent::boot();
    static::creating(function ($model) {
        $model->uuid = (string) Str::uuid();
    });
}

}
