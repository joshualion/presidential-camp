<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ElectionIncident extends Model
{
    use HasFactory;

    protected $guarded  = [];

    public function election(){
        return $this->belongsTo(Election::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function pictures()
    {
        return $this->hasMany(PictureEvidence::class);
    }

    public function videos()
    {
        return $this->hasMany(VideoEvidence::class);
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
