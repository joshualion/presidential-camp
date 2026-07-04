<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Announcement extends Model
{
   protected $fillable = [
        'created_by', 'title', 'message', 'audience', 'scope_type', 'region_id', 'state_id',
        'senatorial_district_id', 'federal_constituency_id', 'lga_id', 'ward_id',
        'polling_unit_id', 'is_active', 'priority', 'published_at', 'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function region() { return $this->belongsTo(Region::class); }

    public function state() { return $this->belongsTo(State::class); }
    public function lga() { return $this->belongsTo(LocalGovernmentArea::class); }
    public function ward() { return $this->belongsTo(Ward::class); }
    public function pollingUnit() { return $this->belongsTo(PollingUnit::class); }
    public function senatorialDistrict() { return $this->belongsTo(SenatorialDistrict::class); }
    public function federalConstituency() { return $this->belongsTo(FederalConstituency::class); }

    protected static function booted(): void
    {
        static::creating(function (Announcement $announcement) {
            $announcement->uuid ??= (string) Str::uuid();
        });

        static::retrieved(function (Announcement $announcement) {
            if (blank($announcement->uuid) && Schema::hasColumn($announcement->getTable(), 'uuid')) {
                $announcement->forceFill(['uuid' => (string) Str::uuid()])->saveQuietly();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
