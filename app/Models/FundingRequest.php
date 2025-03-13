<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundingRequest extends Model
{
    protected $guarded = [];

    /**
     * The user who requested the funding.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id')->withDefault();
    }

    /**
     * The admin who approved/rejected the funding request.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id')->withDefault();
    }

    /**
     * Location relationships
     */
    public function country()
    {
        return $this->belongsTo(Country::class)->withDefault();
    }

    public function region()
    {
        return $this->belongsTo(Region::class)->withDefault();
    }

    public function state()
    {
        return $this->belongsTo(State::class)->withDefault();
    }

    public function lga()
    {
        return $this->belongsTo(LocalGovernmentArea::class, 'lga_id')->withDefault();
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class)->withDefault();
    }

    public function pollingUnit()
    {
        return $this->belongsTo(PollingUnit::class, 'pu_id')->withDefault();
    }
}
