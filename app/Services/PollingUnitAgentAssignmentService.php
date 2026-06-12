<?php

namespace App\Services;

use App\Models\PollingUnit;
use App\Models\PollingUnitAgentAssignment;
use App\Models\PollingUnitAgentAssignmentApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PollingUnitAgentAssignmentService
{
    public const MAX_APPROVED_AGENTS_PER_POLLING_UNIT = 5;

    public function __construct(private readonly PollingUnitAgentApprovalPolicyService $policy)
    {
    }

    public function submitSelfRequest(User $user, PollingUnit $pollingUnit, array $data = []): PollingUnitAgentAssignment
    {
        return $this->createOrUpdatePending($user, $pollingUnit, array_merge($data, [
            'source' => PollingUnitAgentAssignment::SOURCE_SELF_REQUEST,
            'assigned_by' => $user->id,
            'requested_by' => $user->id,
        ]));
    }

    public function request(User $user, PollingUnit $pollingUnit, array $data = []): PollingUnitAgentAssignment
    {
        return $this->submitSelfRequest($user, $pollingUnit, $data);
    }

    public function nominate(User $nominatedUser, PollingUnit $pollingUnit, User $nominatedBy, array $data = []): PollingUnitAgentAssignment
    {
        return $this->createOrUpdatePending($nominatedUser, $pollingUnit, array_merge($data, [
            'source' => $data['source'] ?? PollingUnitAgentAssignment::SOURCE_LEADER_NOMINATION,
            'assigned_by' => $nominatedBy->id,
            'nominated_by' => $nominatedBy->id,
        ]));
    }

    public function directAssign(User $user, PollingUnit $pollingUnit, User $assignedBy, array $data = []): PollingUnitAgentAssignment
    {
        $existingAssignmentId = PollingUnitAgentAssignment::query()
            ->where('user_id', $user->id)
            ->where('polling_unit_id', $pollingUnit->id)
            ->value('id');
        $this->ensurePollingUnitHasCapacity($pollingUnit->id, $existingAssignmentId);
        $registeredPollingUnitId = $user->polling_unit_id;
        $isOverride = $registeredPollingUnitId && (int) $registeredPollingUnitId !== (int) $pollingUnit->id;

        $assignment = PollingUnitAgentAssignment::updateOrCreate(
            [
                'user_id' => $user->id,
                'polling_unit_id' => $pollingUnit->id,
            ],
            array_merge($data, [
                'assigned_by' => $assignedBy->id,
                'source' => PollingUnitAgentAssignment::SOURCE_SUPER_ADMIN_ASSIGNMENT,
                'assignment_type' => $isOverride || !$registeredPollingUnitId
                    ? PollingUnitAgentAssignment::ASSIGNMENT_DEPLOYMENT_OVERRIDE
                    : PollingUnitAgentAssignment::ASSIGNMENT_REGISTERED_POLLING_UNIT,
                'registered_polling_unit_id' => $registeredPollingUnitId,
                'override_reason' => $isOverride || !$registeredPollingUnitId ? ($data['override_reason'] ?? null) : null,
                'override_authorized_by' => $isOverride || !$registeredPollingUnitId ? $assignedBy->id : null,
                'override_authorized_at' => $isOverride || !$registeredPollingUnitId ? now() : null,
                'status' => PollingUnitAgentAssignment::STATUS_APPROVED,
                'workflow_stage' => PollingUnitAgentAssignment::STAGE_APPROVED,
                'approved_by' => $assignedBy->id,
                'approved_at' => now(),
                'final_approved_by' => $assignedBy->id,
                'final_approved_at' => now(),
                'identity_verification_status' => PollingUnitAgentAssignment::IDENTITY_VERIFIED,
                'identity_verified_by' => $assignedBy->id,
                'identity_verified_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'revoked_by' => null,
                'revoked_at' => null,
                'revoke_reason' => null,
            ])
        );

        $this->recordApproval($assignment, $assignedBy, 'direct_assignment', 'approved', $data['notes'] ?? $data['appointment_note'] ?? null, true);
        $this->syncPollingUnitAccess($user, $pollingUnit);

        return $assignment;
    }

    public function approve(PollingUnitAgentAssignment $assignment, User $approvedBy, ?string $notes = null): PollingUnitAgentAssignment
    {
        return $this->approveStage($assignment, $approvedBy, $notes);
    }

    public function verifyIdentity(PollingUnitAgentAssignment $assignment, User $verifiedBy, ?string $note = null): PollingUnitAgentAssignment
    {
        $assignment->forceFill([
            'identity_verification_status' => PollingUnitAgentAssignment::IDENTITY_VERIFIED,
            'identity_verified_by' => $verifiedBy->id,
            'identity_verified_at' => now(),
            'identity_rejected_by' => null,
            'identity_rejected_at' => null,
            'identity_rejection_reason' => null,
            'verification_note' => $note,
            'workflow_stage' => $assignment->workflow_stage === PollingUnitAgentAssignment::STAGE_IDENTITY_SUBMISSION
                ? PollingUnitAgentAssignment::STAGE_POLLING_UNIT_APPROVAL
                : ($assignment->workflow_stage ?: PollingUnitAgentAssignment::STAGE_POLLING_UNIT_APPROVAL),
        ])->save();

        $this->recordApproval($assignment, $verifiedBy, 'identity', 'verified', $note);

        return $assignment;
    }

    public function rejectIdentity(PollingUnitAgentAssignment $assignment, User $rejectedBy, ?string $reason = null): PollingUnitAgentAssignment
    {
        $assignment->forceFill([
            'identity_verification_status' => PollingUnitAgentAssignment::IDENTITY_REJECTED,
            'identity_rejected_by' => $rejectedBy->id,
            'identity_rejected_at' => now(),
            'identity_rejection_reason' => $reason,
            'workflow_stage' => PollingUnitAgentAssignment::STAGE_IDENTITY_SUBMISSION,
        ])->save();

        $this->recordApproval($assignment, $rejectedBy, 'identity', 'rejected', $reason, false);

        return $assignment;
    }

    public function approveStage(PollingUnitAgentAssignment $assignment, User $approvedBy, ?string $notes = null): PollingUnitAgentAssignment
    {
        $nextStage = $this->policy->nextStageAfterApproval($assignment, $approvedBy);
        $isFinal = $nextStage === PollingUnitAgentAssignment::STAGE_APPROVED;

        if ($isFinal) {
            $this->ensurePollingUnitHasCapacity($assignment->polling_unit_id, $assignment->id);
        }

        $assignment->forceFill([
            'status' => $isFinal ? PollingUnitAgentAssignment::STATUS_APPROVED : PollingUnitAgentAssignment::STATUS_PENDING,
            'workflow_stage' => $nextStage,
            'approved_by' => $isFinal ? $approvedBy->id : $assignment->approved_by,
            'approved_at' => $isFinal ? now() : $assignment->approved_at,
            'final_approved_by' => $isFinal ? $approvedBy->id : $assignment->final_approved_by,
            'final_approved_at' => $isFinal ? now() : $assignment->final_approved_at,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'revoked_by' => null,
            'revoked_at' => null,
            'revoke_reason' => null,
            'notes' => $notes ?? $assignment->notes,
        ])->save();

        $this->recordApproval($assignment, $approvedBy, $this->policy->actorApprovalLevel($approvedBy), 'approved', $notes, true);

        if ($isFinal) {
            $assignment->loadMissing('user', 'pollingUnit.ward.localGovernmentArea');
            $this->syncPollingUnitAccess($assignment->user, $assignment->pollingUnit);
        }

        return $assignment;
    }

    public function approveDirectly(PollingUnitAgentAssignment $assignment, User $approvedBy, ?string $notes = null): PollingUnitAgentAssignment
    {
        $this->ensurePollingUnitHasCapacity($assignment->polling_unit_id, $assignment->id);

        $assignment->forceFill([
            'status' => PollingUnitAgentAssignment::STATUS_APPROVED,
            'workflow_stage' => PollingUnitAgentAssignment::STAGE_APPROVED,
            'approved_by' => $approvedBy->id,
            'approved_at' => now(),
            'final_approved_by' => $approvedBy->id,
            'final_approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'revoked_by' => null,
            'revoked_at' => null,
            'revoke_reason' => null,
            'notes' => $notes ?? $assignment->notes,
        ])->save();

        $this->recordApproval($assignment, $approvedBy, $this->policy->actorApprovalLevel($approvedBy), 'approved', $notes, true);

        $assignment->loadMissing('user', 'pollingUnit.ward.localGovernmentArea');
        $this->syncPollingUnitAccess($assignment->user, $assignment->pollingUnit);

        return $assignment;
    }

    public function reject(PollingUnitAgentAssignment $assignment, User $rejectedBy, ?string $reason = null): PollingUnitAgentAssignment
    {
        $assignment->forceFill([
            'status' => PollingUnitAgentAssignment::STATUS_REJECTED,
            'workflow_stage' => PollingUnitAgentAssignment::STAGE_REJECTED,
            'rejected_by' => $rejectedBy->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        $this->recordApproval($assignment, $rejectedBy, $this->policy->actorApprovalLevel($rejectedBy), 'rejected', $reason, false);

        return $assignment;
    }

    public function suspend(PollingUnitAgentAssignment $assignment, User $suspendedBy, ?string $reason = null): PollingUnitAgentAssignment
    {
        return $this->revokeLike($assignment, $suspendedBy, PollingUnitAgentAssignment::STATUS_SUSPENDED, $reason);
    }

    public function revoke(PollingUnitAgentAssignment $assignment, User $revokedBy, ?string $reason = null): PollingUnitAgentAssignment
    {
        return $this->revokeLike($assignment, $revokedBy, PollingUnitAgentAssignment::STATUS_REVOKED, $reason);
    }

    public function scopedQuery(?User $viewer): Builder
    {
        $query = PollingUnitAgentAssignment::query()
            ->with(['user', 'pollingUnit.ward.localGovernmentArea.state', 'registeredPollingUnit.ward.localGovernmentArea.state', 'overrideAuthorizedBy', 'approvals.approver']);

        if (!$viewer || $viewer->access_level === 'superadmin') {
            return $query;
        }

        return $query->whereHas('pollingUnit', function (Builder $pollingUnitQuery) use ($viewer) {
            app(LocationScopeService::class)->applyScope($pollingUnitQuery, $viewer, 'polling_units', 'polling_units');
        });
    }

    public function reactivate(PollingUnitAgentAssignment $assignment, User $reactivatedBy, ?string $notes = null): PollingUnitAgentAssignment
    {
        $this->ensurePollingUnitHasCapacity($assignment->polling_unit_id, $assignment->id);

        $assignment->forceFill([
            'status' => PollingUnitAgentAssignment::STATUS_APPROVED,
            'workflow_stage' => PollingUnitAgentAssignment::STAGE_APPROVED,
            'revoked_by' => null,
            'revoked_at' => null,
            'revoke_reason' => null,
            'approved_by' => $reactivatedBy->id,
            'approved_at' => now(),
            'final_approved_by' => $reactivatedBy->id,
            'final_approved_at' => now(),
            'notes' => $notes ?? $assignment->notes,
        ])->save();

        $this->recordApproval($assignment, $reactivatedBy, $this->policy->actorApprovalLevel($reactivatedBy), 'reactivated', $notes, true);

        return $assignment;
    }

    public function storeIdentityFile(UploadedFile $file, string $type): string
    {
        $folder = match ($type) {
            'passport_photo' => 'agent_passport_photos',
            'voter_evidence_document' => 'agent_voter_evidence',
            default => 'agent_identity_documents',
        };

        return $file->store($folder, 'local');
    }

    private function createOrUpdatePending(User $user, PollingUnit $pollingUnit, array $data): PollingUnitAgentAssignment
    {
        $this->ensureNoActiveOrPendingAssignment($user->id, $pollingUnit->id);

        return PollingUnitAgentAssignment::updateOrCreate(
            [
                'user_id' => $user->id,
                'polling_unit_id' => $pollingUnit->id,
            ],
            array_merge($data, [
                'status' => PollingUnitAgentAssignment::STATUS_PENDING,
                'workflow_stage' => $this->initialWorkflowStage($data),
                'identity_verification_status' => $data['identity_verification_status'] ?? PollingUnitAgentAssignment::IDENTITY_PENDING,
                'registered_polling_unit_id' => $data['registered_polling_unit_id'] ?? $user->polling_unit_id,
                'assignment_type' => $data['assignment_type'] ?? PollingUnitAgentAssignment::ASSIGNMENT_REGISTERED_POLLING_UNIT,
            ])
        );
    }

    private function initialWorkflowStage(array $data): string
    {
        if (empty($data['identity_type']) || empty($data['identity_number']) || empty($data['identity_document']) || empty($data['voter_evidence_document'])) {
            return PollingUnitAgentAssignment::STAGE_IDENTITY_SUBMISSION;
        }

        return PollingUnitAgentAssignment::STAGE_POLLING_UNIT_APPROVAL;
    }

    private function revokeLike(PollingUnitAgentAssignment $assignment, User $actor, string $status, ?string $reason): PollingUnitAgentAssignment
    {
        $assignment->forceFill([
            'status' => $status,
            'workflow_stage' => $status === PollingUnitAgentAssignment::STATUS_SUSPENDED
                ? PollingUnitAgentAssignment::STAGE_SUSPENDED
                : PollingUnitAgentAssignment::STAGE_REVOKED,
            'revoked_by' => $actor->id,
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ])->save();

        $this->recordApproval($assignment, $actor, $this->policy->actorApprovalLevel($actor), $status, $reason, false);

        return $assignment;
    }

    private function recordApproval(
        PollingUnitAgentAssignment $assignment,
        User $actor,
        string $level,
        string $status,
        ?string $comments = null,
        bool $approved = false
    ): void {
        PollingUnitAgentAssignmentApproval::create([
            'assignment_id' => $assignment->id,
            'approver_id' => $actor->id,
            'approval_level' => $level,
            'scope_type' => app(LocationScopeService::class)->getScopeType($actor),
            'scope_id' => app(LocationScopeService::class)->getScopeId($actor),
            'status' => $status,
            'comments' => $comments,
            'approved_at' => $approved ? now() : null,
            'rejected_at' => $approved ? null : (in_array($status, ['rejected', 'suspended', 'revoked'], true) ? now() : null),
        ]);
    }

    private function syncPollingUnitAccess(User $user, PollingUnit $pollingUnit): void
    {
        $pollingUnit->loadMissing('ward.localGovernmentArea');
        $lga = $pollingUnit->ward?->localGovernmentArea;

        $user->forceFill([
            'state_id' => $lga?->state_id ?? $user->state_id,
            'senatorial_district_id' => $pollingUnit->senatorial_district_id ?? $user->senatorial_district_id,
            'federal_constituency_id' => $pollingUnit->federal_constituency_id ?? $user->federal_constituency_id,
            'lga_id' => $pollingUnit->ward?->lga_id ?? $user->lga_id,
            'ward_id' => $pollingUnit->ward_id ?? $user->ward_id,
            'polling_unit_id' => $pollingUnit->id,
        ])->save();
    }

    public function approvedActiveCountForPollingUnit(int $pollingUnitId, ?int $exceptAssignmentId = null): int
    {
        return (int) PollingUnitAgentAssignment::approved()
            ->where('polling_unit_id', $pollingUnitId)
            ->when($exceptAssignmentId, fn ($query) => $query->where('id', '!=', $exceptAssignmentId))
            ->count();
    }

    public function pollingUnitHasCapacity(int $pollingUnitId, ?int $exceptAssignmentId = null): bool
    {
        return $this->approvedActiveCountForPollingUnit($pollingUnitId, $exceptAssignmentId) < self::MAX_APPROVED_AGENTS_PER_POLLING_UNIT;
    }

    public function ensurePollingUnitHasCapacity(int $pollingUnitId, ?int $exceptAssignmentId = null): void
    {
        if (!$this->pollingUnitHasCapacity($pollingUnitId, $exceptAssignmentId)) {
            throw new \RuntimeException('This polling unit already has the maximum number of approved agents.');
        }
    }

    public function ensureNoActiveOrPendingAssignment(int $userId, int $pollingUnitId): void
    {
        $exists = PollingUnitAgentAssignment::query()
            ->where('user_id', $userId)
            ->where('polling_unit_id', $pollingUnitId)
            ->whereIn('status', [
                PollingUnitAgentAssignment::STATUS_PENDING,
                PollingUnitAgentAssignment::STATUS_APPROVED,
            ])
            ->exists();

        if ($exists) {
            throw new \RuntimeException('This user already has an active or pending agent request for this polling unit.');
        }
    }
}
