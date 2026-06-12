<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Concerns\HasLocationScope;

class PollingUnit extends Model
{
    use HasFactory, HasLocationScope;
    protected $guarded = [];


    public function ward(){
        return $this->belongsTo(Ward::class);
    }

    public function senatorialDistrict(){
        return $this->belongsTo(SenatorialDistrict::class);
    }

    public function federalConstituency(){
        return $this->belongsTo(FederalConstituency::class);
    }


    public function users(){
        return $this->hasMany(User::class);
    }

    public function agentAssignments()
    {
        return $this->hasMany(PollingUnitAgentAssignment::class);
    }

    public function approvedAgentAssignments()
    {
        return $this->hasMany(PollingUnitAgentAssignment::class)->approved();
    }

    public function votes(){
        return $this->hasMany(Vote::class);
    }

    public function incidents(){
        return $this->hasMany(ElectionIncident::class);
    }

    //boot method for uuid

    public static function boot()
    {
        parent::boot();
        self::creating(function ($pu) {
            $pu->uuid = (string) Str::uuid();
        });
    }

}
