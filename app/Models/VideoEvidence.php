<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoEvidence extends Model
{
    protected $fillable = ['election_incident_id', 'file_path'];
}
