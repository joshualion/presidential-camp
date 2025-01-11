<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoEvidence extends Model
{

    protected $table = "video_evidences";
    protected $fillable = ['election_incident_id', 'file_path'];
}
