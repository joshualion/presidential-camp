<?php

namespace App\Services;

use App\Models\Election;
use App\Models\PollingUnit;
use App\Models\PollingUnitAgentAssignment;
use App\Models\PollingUnitResult;
use App\Models\User;

class PollingUnitResultPermissionService
{
    public const ACTIVE_RESULT_STATUSES = ['submitted', 'pending', 'verified', 'disputed'];
    public const EDITABLE_RESULT_STATUSES = ['submitted', 'pending'];

    public function __construct(private readonly LocationScopeService $scopeService)
    {
    }

    public function userHasApprovedAssignmentForPollingUnit(?User $user, ?int $pollingUnitId): bool
    {
        if (!$user || !$pollingUnitId) {
            return false;
        }

        return PollingUnitAgentAssignment::approved()
            ->where('user_id', $user->id)
            ->where('polling_unit_id', $pollingUnitId)
            ->exists();
    }

    public function userHasAnyApprovedAssignment(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return PollingUnitAgentAssignment::approved()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function firstApprovedPollingUnitId(?User $user): ?int
    {
        if (!$user) {
            return null;
        }

        return PollingUnitAgentAssignment::approved()
            ->where('user_id', $user->id)
            ->oldest()
            ->value('polling_unit_id');
    }

    public function activeOfficialResultQuery(int $electionId, int $pollingUnitId)
    {
        return PollingUnitResult::query()
            ->where('election_id', $electionId)
            ->where('polling_unit_id', $pollingUnitId)
            ->where(function ($query) {
                $query->whereNull('result_status')
                    ->orWhereIn('result_status', self::ACTIVE_RESULT_STATUSES);
            });
    }

    public function activeOfficialResult(?Election $election, ?int $pollingUnitId): ?PollingUnitResult
    {
        if (!$election || !$pollingUnitId) {
            return null;
        }

        return $this->activeOfficialResultQuery($election->id, $pollingUnitId)->first();
    }

    public function canSubmitPollingUnitResult(?User $user, PollingUnit|int|null $pollingUnit, ?Election $election = null): bool
    {
        $pollingUnitId = $pollingUnit instanceof PollingUnit ? $pollingUnit->id : $pollingUnit;

        if (!$this->userHasApprovedAssignmentForPollingUnit($user, $pollingUnitId)) {
            return false;
        }

        if (!$election) {
            return true;
        }

        $activeResult = $this->activeOfficialResult($election, $pollingUnitId);

        return !$activeResult || $this->canEditPollingUnitResult($user, $activeResult);
    }

    public function canEditPollingUnitResult(?User $user, ?PollingUnitResult $result): bool
    {
        if (!$user || !$result || (int) $result->submitted_by !== (int) $user->id) {
            return false;
        }

        return in_array($result->result_status ?: $result->verification_status ?: 'submitted', self::EDITABLE_RESULT_STATUSES, true)
            && in_array($result->verification_status ?: 'submitted', self::EDITABLE_RESULT_STATUSES, true);
    }

    public function canVerifyPollingUnitResult(?User $user, ?PollingUnitResult $result): bool
    {
        return $this->canReviewPollingUnitResult($user, $result);
    }

    public function canDisputePollingUnitResult(?User $user, ?PollingUnitResult $result): bool
    {
        return $this->canReviewPollingUnitResult($user, $result);
    }

    public function canReviewResultsWithinScope(?User $user): bool
    {
        return $user && in_array($user->access_level, [
            'superadmin',
            'nationaladmin',
            'regionaladmin',
            'stateadmin',
            'senatorialadmin',
            'federaladmin',
            'lgaadmin',
            'wardadmin',
        ], true);
    }

    public function approvedAssignmentCountForUserScope(?User $user): int
    {
        $query = PollingUnitAgentAssignment::approved()
            ->whereHas('pollingUnit', function ($pollingUnitQuery) use ($user) {
                $this->scopeService->applyScope($pollingUnitQuery, $user, 'polling_units', 'polling_units');
            });

        return (int) $query->count();
    }

    private function canReviewPollingUnitResult(?User $user, ?PollingUnitResult $result): bool
    {
        if (!$this->canReviewResultsWithinScope($user) || !$result) {
            return false;
        }

        $query = PollingUnitResult::query()->whereKey($result->id);
        $this->scopeService->applyScope($query, $user, 'polling_unit_results', 'polling_unit_results');

        return $query->exists();
    }
}
