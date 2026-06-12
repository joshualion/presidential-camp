<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use App\Models\Concerns\HasLocationScope;

class PollingUnitResult extends Model
{
    use HasFactory, HasLocationScope;

    protected $fillable = [
        'election_id',
        'polling_unit_id',
        'state_id',
        'senatorial_district_id',
        'federal_constituency_id',
        'lga_id',
        'ward_id',
        'result_sheet',
        'submitted_at',
        'submitted_by',
        'review_status',
        'reviewed_at',
        'reviewed_by',
        'verification_status',
        'verified_at',
        'verified_by',
        'verification_notes',
        'dispute_status',
        'disputed_at',
        'disputed_by',
        'dispute_reason',
        'result_status',
        'voided_by',
        'voided_at',
        'void_reason',
        'replaced_by_result_id',
    ];

    protected $casts = [
        'election_id' => 'integer',
        'polling_unit_id' => 'integer',
        'state_id' => 'integer',
        'senatorial_district_id' => 'integer',
        'federal_constituency_id' => 'integer',
        'lga_id' => 'integer',
        'ward_id' => 'integer',
        'submitted_at' => 'datetime',
        'submitted_by' => 'integer',
        'reviewed_at' => 'datetime',
        'reviewed_by' => 'integer',
        'verified_at' => 'datetime',
        'verified_by' => 'integer',
        'disputed_at' => 'datetime',
        'disputed_by' => 'integer',
        'voided_at' => 'datetime',
        'voided_by' => 'integer',
        'replaced_by_result_id' => 'integer',
    ];

    public function votes()
    {
        return $this->hasMany(Vote::class, 'polling_unit_result_id');
    }

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function pollingUnit()
    {
        return $this->belongsTo(PollingUnit::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function senatorialDistrict()
    {
        return $this->belongsTo(SenatorialDistrict::class);
    }

    public function federalConstituency()
    {
        return $this->belongsTo(FederalConstituency::class);
    }

    public function lga()
    {
        return $this->belongsTo(LocalGovernmentArea::class, 'lga_id');
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function disputedBy()
    {
        return $this->belongsTo(User::class, 'disputed_by');
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function replacedByResult()
    {
        return $this->belongsTo(self::class, 'replaced_by_result_id');
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
