<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Election extends Model
{
    use HasFactory;

    protected $table = 'elections';

    protected $guarded = [];

    public function party()
    {
        return $this->belongsTo(PoliticalParty::class, 'party_id');
    }

    public function votes(){
        return $this->hasMany(Vote::class);
    }

    public function incidents(){
        return $this->hasMany(ElectionIncident::class);
    }


    public function getDynamicStatusAttribute()
    {
        $electionDate = \Carbon\Carbon::parse($this->year);

        if ($electionDate->isToday()) {
            return 'Ongoing';
        } elseif ($electionDate->isPast() && $this->status == 'ongoing') {
            return 'Inconclusive';
        }

        return $this->status;
    }

    //Automate UUID creation
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    const STATUSES = [
        'pending' => 'Pending',
        'ongoing' => 'Ongoing',
        'inconclusive' => 'Inconclusive',
        'concluded' => 'Concluded',
        'cancelled' => 'Cancelled',
    ];
}
