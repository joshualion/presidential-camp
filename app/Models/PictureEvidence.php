<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PictureEvidence extends Model
{
    protected $table = "picture_evidences";
    protected $fillable = ['election_incident_id', 'file_path'];
}
