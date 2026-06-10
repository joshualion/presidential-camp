<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoEvidence extends Model
{

    protected $table = "video_evidences";
    protected $fillable = [
        'election_incident_id',
        'file_path',
        'uploaded_by',
        'verification_status',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'election_incident_id' => 'integer',
        'uploaded_by' => 'integer',
        'verified_at' => 'datetime',
        'verified_by' => 'integer',
    ];

    public function incident()
    {
        return $this->belongsTo(ElectionIncident::class, 'election_incident_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
