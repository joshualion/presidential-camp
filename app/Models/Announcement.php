<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
   protected $fillable = [
        'title', 'message', 'audience', 'state_id', 'lga_id', 'ward_id', 'polling_unit_id', 'is_active'
    ];

    public function state() { return $this->belongsTo(State::class); }
    public function lga() { return $this->belongsTo(LocalGovernmentArea::class); }
    public function ward() { return $this->belongsTo(Ward::class); }
    public function pollingUnit() { return $this->belongsTo(PollingUnit::class); }
}
