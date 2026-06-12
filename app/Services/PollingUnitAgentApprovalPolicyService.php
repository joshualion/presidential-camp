<?php

namespace App\Services;

use App\Models\PollingUnit;
use App\Models\PollingUnitAgentAssignment;
use App\Models\User;

class PollingUnitAgentApprovalPolicyService
{
    public function __construct(private readonly LocationScopeService $scopeService)
    {
    }

    public function canView(?User $viewer, ?PollingUnitAgentAssignment $assignment): bool
    {
        if (!$viewer || !$assignment) {
            return false;
        }

        if ((int) $assignment->user_id === (int) $viewer->id) {
            return true;
        }

        return $this->pollingUnitIsInScope($viewer, $assignment->polling_unit_id);
    }

    public function canViewDocument(?User $viewer, ?PollingUnitAgentAssignment $assignment): bool
    {
        return $this->canView($viewer, $assignment)
            && ($viewer->access_level === 'superadmin' || $this->isReviewer($viewer) || (int) $assignment->user_id === (int) $viewer->id);
    }

    public function canRequest(?User $user): bool
    {
        return (bool) $user;
    }

    public function canNominate(?User $actor, ?PollingUnit $pollingUnit = null): bool
    {
        if (!$actor || !$this->isReviewer($actor)) {
            return false;
        }

        return !$pollingUnit || $this->pollingUnitIsInScope($actor, $pollingUnit->id);
    }

    public function canVerifyIdentity(?User $actor, ?PollingUnitAgentAssignment $assignment): bool
    {
        return $this->canManageWithinScope($actor, $assignment)
            && ((int) $assignment->user_id !== (int) $actor->id || $actor->access_level === 'superadmin');
    }

    public function canApproveCurrentStage(?User $actor, ?PollingUnitAgentAssignment $assignment): bool
    {
        if (!$this->canManageWithinScope($actor, $assignment)) {
            return false;
        }

        if ((int) $assignment->user_id === (int) $actor->id && $actor->access_level !== 'superadmin') {
            return false;
        }

        if ($assignment->identity_verification_status !== PollingUnitAgentAssignment::IDENTITY_VERIFIED) {
            return false;
        }

        if ($this->canApproveDirectly($actor, $assignment)) {
            return true;
        }

        return match ($assignment->workflow_stage) {
            PollingUnitAgentAssignment::STAGE_POLLING_UNIT_APPROVAL => $actor->access_level === 'puadmin'
                && (int) $actor->polling_unit_id === (int) $assignment->polling_unit_id,
            PollingUnitAgentAssignment::STAGE_WARD_APPROVAL => $actor->access_level === 'wardadmin'
                && $this->pollingUnitIsInScope($actor, $assignment->polling_unit_id),
            PollingUnitAgentAssignment::STAGE_LGA_APPROVAL => $actor->access_level === 'lgaadmin'
                && $this->pollingUnitIsInScope($actor, $assignment->polling_unit_id),
            default => false,
        };
    }

    public function canApproveDirectly(?User $actor, ?PollingUnitAgentAssignment $assignment): bool
    {
        if (!$this->canManageWithinScope($actor, $assignment)) {
            return false;
        }

        if ((int) $assignment->user_id === (int) $actor->id && $actor->access_level !== 'superadmin') {
            return false;
        }

        if ($assignment->identity_verification_status !== PollingUnitAgentAssignment::IDENTITY_VERIFIED) {
            return false;
        }

        return in_array($actor->access_level, ['superadmin', 'nationaladmin', 'regionaladmin', 'stateadmin'], true);
    }

    public function canReject(?User $actor, ?PollingUnitAgentAssignment $assignment): bool
    {
        return $this->canManageWithinScope($actor, $assignment)
            && ((int) $assignment->user_id !== (int) $actor->id || $actor->access_level === 'superadmin');
    }

    public function canDirectAssign(?User $actor, ?PollingUnit $pollingUnit = null): bool
    {
        return $actor
            && $actor->access_level === 'superadmin'
            && (!$pollingUnit || $this->pollingUnitIsInScope($actor, $pollingUnit->id));
    }

    public function canSuspendOrRevoke(?User $actor, ?PollingUnitAgentAssignment $assignment): bool
    {
        return $this->canManageWithinScope($actor, $assignment);
    }

    public function canReactivate(?User $actor, ?PollingUnitAgentAssignment $assignment): bool
    {
        return $this->canManageWithinScope($actor, $assignment)
            && in_array($assignment->status, [
                PollingUnitAgentAssignment::STATUS_SUSPENDED,
                PollingUnitAgentAssignment::STATUS_REVOKED,
            ], true);
    }

    public function nextStageAfterApproval(PollingUnitAgentAssignment $assignment, User $actor): string
    {
        if ($this->canApproveDirectly($actor, $assignment)) {
            return PollingUnitAgentAssignment::STAGE_APPROVED;
        }

        return match ($assignment->workflow_stage) {
            PollingUnitAgentAssignment::STAGE_POLLING_UNIT_APPROVAL => PollingUnitAgentAssignment::STAGE_WARD_APPROVAL,
            PollingUnitAgentAssignment::STAGE_WARD_APPROVAL => PollingUnitAgentAssignment::STAGE_LGA_APPROVAL,
            PollingUnitAgentAssignment::STAGE_LGA_APPROVAL => PollingUnitAgentAssignment::STAGE_APPROVED,
            default => $assignment->workflow_stage ?: PollingUnitAgentAssignment::STAGE_POLLING_UNIT_APPROVAL,
        };
    }

    public function actorApprovalLevel(User $actor): string
    {
        return match ($actor->access_level) {
            'superadmin' => 'super_admin',
            'nationaladmin' => 'national',
            'regionaladmin' => 'regional',
            'stateadmin' => 'state',
            'senatorialadmin' => 'senatorial',
            'federaladmin' => 'federal',
            'lgaadmin' => 'lga',
            'wardadmin' => 'ward',
            'puadmin' => 'polling_unit',
            default => 'user',
        };
    }

    private function canManageWithinScope(?User $actor, ?PollingUnitAgentAssignment $assignment): bool
    {
        if (!$actor || !$assignment || !$this->isReviewer($actor)) {
            return false;
        }

        return $this->pollingUnitIsInScope($actor, $assignment->polling_unit_id);
    }

    private function isReviewer(User $actor): bool
    {
        return in_array($actor->access_level, [
            'superadmin',
            'nationaladmin',
            'regionaladmin',
            'stateadmin',
            'senatorialadmin',
            'federaladmin',
            'lgaadmin',
            'wardadmin',
            'puadmin',
        ], true);
    }

    private function pollingUnitIsInScope(User $user, ?int $pollingUnitId): bool
    {
        if (!$pollingUnitId) {
            return false;
        }

        if ($user->access_level === 'superadmin') {
            return true;
        }

        $query = PollingUnit::query();
        $this->scopeService->applyScope($query, $user, 'polling_units', 'polling_units');

        return $query->where('polling_units.id', $pollingUnitId)->exists();
    }
}
