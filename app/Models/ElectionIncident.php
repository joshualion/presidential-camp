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


    //Auto generate UUID
    public static function boot()
{
    parent::boot();
    static::creating(function ($model) {
        $model->uuid = (string) Str::uuid();
    });
}

}
