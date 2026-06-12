<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PollingUnitAgentAssignment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REVOKED = 'revoked';

    public const SOURCE_SELF_REQUEST = 'self_request';
    public const SOURCE_LEADER_NOMINATION = 'leader_nomination';
    public const SOURCE_ADMIN_APPOINTMENT = 'admin_appointment';
    public const SOURCE_SUPER_ADMIN_ASSIGNMENT = 'super_admin_assignment';

    public const ASSIGNMENT_REGISTERED_POLLING_UNIT = 'registered_polling_unit';
    public const ASSIGNMENT_DEPLOYMENT_OVERRIDE = 'deployment_override';

    public const IDENTITY_PENDING = 'pending';
    public const IDENTITY_VERIFIED = 'verified';
    public const IDENTITY_REJECTED = 'rejected';

    public const STAGE_IDENTITY_SUBMISSION = 'pending_identity_submission';
    public const STAGE_POLLING_UNIT_APPROVAL = 'pending_polling_unit_approval';
    public const STAGE_WARD_APPROVAL = 'pending_ward_approval';
    public const STAGE_LGA_APPROVAL = 'pending_lga_approval';
    public const STAGE_APPROVED = 'approved';
    public const STAGE_REJECTED = 'rejected';
    public const STAGE_SUSPENDED = 'suspended';
    public const STAGE_REVOKED = 'revoked';

    protected $fillable = [
        'user_id',
        'polling_unit_id',
        'registered_polling_unit_id',
        'assigned_by',
        'requested_by',
        'nominated_by',
        'status',
        'source',
        'assignment_type',
        'workflow_stage',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'revoked_by',
        'revoked_at',
        'revoke_reason',
        'notes',
        'identity_verification_status',
        'identity_type',
        'identity_number',
        'identity_document',
        'voter_evidence_document',
        'passport_photo',
        'current_address',
        'availability_confirmed',
        'willingness_statement',
        'verification_note',
        'identity_verified_by',
        'identity_verified_at',
        'identity_rejected_by',
        'identity_rejected_at',
        'identity_rejection_reason',
        'final_approved_by',
        'final_approved_at',
        'appointment_note',
        'override_reason',
        'override_authorized_by',
        'override_authorized_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'revoked_at' => 'datetime',
        'availability_confirmed' => 'boolean',
        'identity_verified_at' => 'datetime',
        'identity_rejected_at' => 'datetime',
        'final_approved_at' => 'datetime',
        'override_authorized_at' => 'datetime',
    ];

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pollingUnit()
    {
        return $this->belongsTo(PollingUnit::class);
    }

    public function registeredPollingUnit()
    {
        return $this->belongsTo(PollingUnit::class, 'registered_polling_unit_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function nominatedBy()
    {
        return $this->belongsTo(User::class, 'nominated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function identityVerifiedBy()
    {
        return $this->belongsTo(User::class, 'identity_verified_by');
    }

    public function identityRejectedBy()
    {
        return $this->belongsTo(User::class, 'identity_rejected_by');
    }

    public function finalApprovedBy()
    {
        return $this->belongsTo(User::class, 'final_approved_by');
    }

    public function overrideAuthorizedBy()
    {
        return $this->belongsTo(User::class, 'override_authorized_by');
    }

    public function approvals()
    {
        return $this->hasMany(PollingUnitAgentAssignmentApproval::class, 'assignment_id')->latest();
    }

    protected static function booted(): void
    {
        static::creating(function (self $assignment) {
            if (empty($assignment->uuid)) {
                $assignment->uuid = (string) Str::uuid();
            }
        });
    }
}
