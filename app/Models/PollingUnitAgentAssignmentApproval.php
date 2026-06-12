<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollingUnitAgentAssignmentApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'approver_id',
        'approval_level',
        'scope_type',
        'scope_id',
        'status',
        'comments',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(PollingUnitAgentAssignment::class, 'assignment_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
