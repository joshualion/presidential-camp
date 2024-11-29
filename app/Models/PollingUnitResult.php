<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;

class PollingUnitResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'polling_unit_id',
        'result_sheet',
    ];

    public function votes()
    {
        return $this->hasMany(Vote::class, 'polling_unit_result_id');
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
