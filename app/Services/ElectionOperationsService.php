<?php

namespace App\Services;

use App\Models\Election;
use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\PollingUnit;
use App\Models\PollingUnitResult;
use App\Models\User;
use App\Models\VideoEvidence;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ElectionOperationsService
{
    public function __construct(private LocationScopeService $scopeService)
    {
    }

    public function forUser(?User $user, int $activityLimit = 20): array
    {
        $activeElection = $this->activeElection();

        return [
            'active_election' => $activeElection,
            'submission_coverage' => $this->submissionCoverage($user, $activeElection),
            'polling_unit_activity' => $this->pollingUnitActivity($user, $activeElection),
            'incidents' => $this->incidentMetrics($user, $activeElection),
            'evidence' => $this->evidenceMetrics($user, $activeElection),
            'latest_results' => $this->latestResultSubmissions($user, 10, $activeElection),
            'latest_incidents' => $this->latestIncidents($user, 10, $activeElection),
            'recent_activity' => $this->recentActivity($user, $activityLimit, $activeElection),
        ];
    }

    public function activeElection(): ?Election
    {
        return Election::query()
            ->where('status', 'ongoing')
            ->orWhereDate('year', now()->toDateString())
            ->latest('updated_at')
            ->first();
    }

    public function submissionCoverage(?User $user, ?Election $election = null): array
    {
        $totalPollingUnits = $this->scopedPollingUnits($user)->count('polling_units.id');
        $submittedPollingUnits = $this->scopedResultQuery($user, $election)
            ->distinct()
            ->count('polling_unit_id');

        $pendingPollingUnits = max(0, $totalPollingUnits - $submittedPollingUnits);
        $verifiedPollingUnits = $this->scopedResultQuery($user, $election)
            ->where('verification_status', 'verified')
            ->distinct()
            ->count('polling_unit_id');
        $disputedPollingUnits = $this->scopedResultQuery($user, $election)
            ->where('dispute_status', 'disputed')
            ->distinct()
            ->count('polling_unit_id');

        return [
            'total_polling_units' => (int) $totalPollingUnits,
            'submitted_polling_units' => (int) $submittedPollingUnits,
            'pending_polling_units' => (int) $pendingPollingUnits,
            'verified_polling_units' => (int) $verifiedPollingUnits,
            'disputed_polling_units' => (int) $disputedPollingUnits,
            'coverage_percent' => $totalPollingUnits > 0
                ? round(($submittedPollingUnits / $totalPollingUnits) * 100, 2)
                : 0.0,
        ];
    }

    public function latestResultSubmissions(?User $user, int $limit = 10, ?Election $election = null): Collection
    {
        $limit = max(1, $limit);

        return $this->scopedResultQuery($user, $election)
            ->with(['pollingUnit.ward.localGovernmentArea', 'submittedBy', 'votes.agent'])
            ->orderByRaw('COALESCE(submitted_at, updated_at, created_at) DESC')
            ->limit($limit)
            ->get()
            ->map(function (PollingUnitResult $result) {
                $pollingUnit = $result->pollingUnit;
                $ward = $pollingUnit?->ward;
                $lga = $ward?->localGovernmentArea;
                $submitter = $result->submittedBy ?? $result->votes->first()?->agent;

                return [
                    'type' => 'result',
                    'title' => 'Result submitted',
                    'polling_unit' => $pollingUnit?->name ?? 'Unknown polling unit',
                    'ward' => $ward?->name ?? 'Unknown ward',
                    'lga' => $lga?->name ?? 'Unknown LGA',
                    'actor' => $this->displayName($submitter),
                    'time' => $result->submitted_at ?? $result->updated_at ?? $result->created_at,
                    'description' => 'Polling unit result submission',
                ];
            });
    }

    public function latestIncidents(?User $user, int $limit = 10, ?Election $election = null): Collection
    {
        $limit = max(1, $limit);

        return $this->scopedIncidentQuery($user, $election)
            ->with(['pollingUnit.ward.localGovernmentArea', 'agent'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (ElectionIncident $incident) {
                $pollingUnit = $incident->pollingUnit;
                $ward = $pollingUnit?->ward;
                $lga = $ward?->localGovernmentArea;

                return [
                    'type' => 'incident',
                    'title' => $this->incidentTitle($incident),
                    'polling_unit' => $pollingUnit?->name ?? 'Unknown polling unit',
                    'ward' => $ward?->name ?? 'Unknown ward',
                    'lga' => $lga?->name ?? 'Unknown LGA',
                    'actor' => $this->displayName($incident->agent),
                    'time' => $incident->created_at,
                    'description' => $incident->remarks ?: 'Incident reported',
                ];
            });
    }

    public function latestVoteUploads(?User $user, int $limit = 10, ?Election $election = null): Collection
    {
        $limit = max(1, $limit);

        return $this->scopedVoteQuery($user, $election)
            ->with(['pollingUnit.ward.localGovernmentArea', 'agent'])
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(function (Vote $vote) {
                $pollingUnit = $vote->pollingUnit;
                $ward = $pollingUnit?->ward;
                $lga = $ward?->localGovernmentArea;

                return [
                    'type' => 'vote',
                    'title' => 'Vote uploaded',
                    'polling_unit' => $pollingUnit?->name ?? 'Unknown polling unit',
                    'ward' => $ward?->name ?? 'Unknown ward',
                    'lga' => $lga?->name ?? 'Unknown LGA',
                    'actor' => $this->displayName($vote->agent),
                    'time' => $vote->updated_at ?? $vote->created_at,
                    'description' => 'Vote upload recorded',
                ];
            });
    }

    public function pollingUnitSituationRoom(?User $user): array
    {
        $activeElection = $this->activeElection();
        $latestVotes = $this->latestVoteUploads($user, 1, $activeElection);
        $latestIncidents = $this->latestIncidents($user, 1, $activeElection);
        $latestEvidence = $this->latestEvidenceActivity($user, 1, $activeElection);
        $latestResults = $this->latestResultSubmissions($user, 1, $activeElection);
        $recentActivity = $latestVotes
            ->merge($latestIncidents)
            ->merge($latestEvidence)
            ->sortByDesc(fn (array $item) => optional($item['time'])->timestamp ?? 0)
            ->values();

        return [
            'active_election' => $activeElection,
            'status' => [
                'result_submitted' => $this->scopedResultQuery($user, $activeElection)->exists(),
                'vote_uploaded' => $this->scopedVoteQuery($user, $activeElection)->exists(),
                'incident_reported' => $this->scopedIncidentQuery($user, $activeElection)->exists(),
                'evidence_uploaded' => $latestEvidence->isNotEmpty(),
                'last_activity_at' => $recentActivity->first()['time'] ?? null,
            ],
            'latest_vote' => $latestVotes->first(),
            'latest_incident' => $latestIncidents->first(),
            'latest_evidence' => $latestEvidence->first(),
            'latest_result' => $latestResults->first(),
            'recent_activity' => $recentActivity,
        ];
    }

    public function evidenceMetrics(?User $user, ?Election $election = null): array
    {
        $incidentIds = $this->scopedIncidentQuery($user, $election)->select('election_incidents.id');

        $pictureCount = PictureEvidence::query()
            ->whereIn('election_incident_id', clone $incidentIds)
            ->count();

        $videoCount = VideoEvidence::query()
            ->whereIn('election_incident_id', clone $incidentIds)
            ->count();

        $incidentsWithEvidence = $this->scopedIncidentQuery($user, $election)
            ->where(function (Builder $query) {
                $query->whereHas('pictureEvidences')
                    ->orWhereHas('videoEvidences');
            })
            ->count();

        $totalIncidents = $this->scopedIncidentQuery($user, $election)->count();

        return [
            'pictures' => (int) $pictureCount,
            'videos' => (int) $videoCount,
            'incidents_with_evidence' => (int) $incidentsWithEvidence,
            'incidents_without_evidence' => max(0, (int) $totalIncidents - (int) $incidentsWithEvidence),
            'pending_verification' => (int) PictureEvidence::query()
                ->whereIn('election_incident_id', clone $incidentIds)
                ->where(function (Builder $query) {
                    $query->whereNull('verification_status')
                        ->orWhere('verification_status', 'pending');
                })
                ->count()
                + (int) VideoEvidence::query()
                    ->whereIn('election_incident_id', clone $incidentIds)
                    ->where(function (Builder $query) {
                        $query->whereNull('verification_status')
                            ->orWhere('verification_status', 'pending');
                    })
                    ->count(),
            'accepted' => (int) PictureEvidence::query()
                ->whereIn('election_incident_id', clone $incidentIds)
                ->where('verification_status', 'accepted')
                ->count()
                + (int) VideoEvidence::query()
                    ->whereIn('election_incident_id', clone $incidentIds)
                    ->where('verification_status', 'accepted')
                    ->count(),
            'rejected' => (int) PictureEvidence::query()
                ->whereIn('election_incident_id', clone $incidentIds)
                ->where('verification_status', 'rejected')
                ->count()
                + (int) VideoEvidence::query()
                    ->whereIn('election_incident_id', clone $incidentIds)
                    ->where('verification_status', 'rejected')
                    ->count(),
        ];
    }

    public function incidentMetrics(?User $user, ?Election $election = null): array
    {
        $evidence = $this->evidenceMetrics($user, $election);

        return [
            'total' => (int) $this->scopedIncidentQuery($user, $election)->count(),
            'with_evidence' => $evidence['incidents_with_evidence'],
            'without_evidence' => $evidence['incidents_without_evidence'],
            'open' => (int) $this->scopedIncidentQuery($user, $election)
                ->where(function (Builder $query) {
                    $query->whereNull('status')->orWhere('status', 'open');
                })
                ->count(),
            'in_progress' => (int) $this->scopedIncidentQuery($user, $election)
                ->where('status', 'in_progress')
                ->count(),
            'resolved' => (int) $this->scopedIncidentQuery($user, $election)
                ->where('status', 'resolved')
                ->count(),
            'closed' => (int) $this->scopedIncidentQuery($user, $election)
                ->where('status', 'closed')
                ->count(),
        ];
    }

    public function pollingUnitActivity(?User $user, ?Election $election = null): array
    {
        $totalPollingUnits = $this->scopedPollingUnits($user)->count('polling_units.id');

        $resultPollingUnitIds = $this->scopedResultQuery($user, $election)
            ->whereNotNull('polling_unit_id')
            ->distinct()
            ->pluck('polling_unit_id');

        $votePollingUnitIds = $this->scopedVoteQuery($user, $election)
            ->whereNotNull('polling_unit_id')
            ->distinct()
            ->pluck('polling_unit_id');

        $incidentPollingUnitIds = $this->scopedIncidentQuery($user, $election)
            ->whereNotNull('polling_unit_id')
            ->distinct()
            ->pluck('polling_unit_id');

        $activePollingUnits = $resultPollingUnitIds
            ->merge($votePollingUnitIds)
            ->merge($incidentPollingUnitIds)
            ->filter()
            ->unique()
            ->count();

        return [
            'active_polling_units' => (int) $activePollingUnits,
            'silent_polling_units' => max(0, (int) $totalPollingUnits - (int) $activePollingUnits),
        ];
    }

    public function recentActivity(?User $user, int $limit = 20, ?Election $election = null): Collection
    {
        $limit = max(1, $limit);

        return $this->latestResultSubmissions($user, $limit, $election)
            ->merge($this->latestIncidents($user, $limit, $election))
            ->merge($this->latestEvidenceActivity($user, $limit, $election))
            ->sortByDesc(fn (array $item) => optional($item['time'])->timestamp ?? 0)
            ->take($limit)
            ->values();
    }

    public function latestEvidenceActivity(?User $user, int $limit, ?Election $election = null): Collection
    {
        $pictureEvidence = PictureEvidence::query()
            ->with(['incident.pollingUnit.ward.localGovernmentArea', 'incident.agent', 'uploadedBy'])
            ->whereIn('election_incident_id', $this->scopedIncidentQuery($user, $election)->select('election_incidents.id'))
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (PictureEvidence $evidence) => $this->evidenceActivityItem($evidence, 'picture'));

        $videoEvidence = VideoEvidence::query()
            ->with(['incident.pollingUnit.ward.localGovernmentArea', 'incident.agent', 'uploadedBy'])
            ->whereIn('election_incident_id', $this->scopedIncidentQuery($user, $election)->select('election_incidents.id'))
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (VideoEvidence $evidence) => $this->evidenceActivityItem($evidence, 'video'));

        return $pictureEvidence->merge($videoEvidence);
    }

    private function evidenceActivityItem(PictureEvidence|VideoEvidence $evidence, string $type): array
    {
        $incident = $evidence->incident;
        $pollingUnit = $incident?->pollingUnit;
        $ward = $pollingUnit?->ward;
        $lga = $ward?->localGovernmentArea;

        return [
            'type' => 'evidence',
            'title' => ucfirst($type).' evidence uploaded',
            'polling_unit' => $pollingUnit?->name ?? 'Unknown polling unit',
            'ward' => $ward?->name ?? 'Unknown ward',
            'lga' => $lga?->name ?? 'Unknown LGA',
            'actor' => $this->displayName($evidence->uploadedBy ?? $incident?->agent),
            'time' => $evidence->created_at,
            'description' => 'Evidence attached to incident',
        ];
    }

    private function scopedPollingUnits(?User $user): Builder
    {
        $query = PollingUnit::query();
        $this->scopeService->applyScope($query, $user, 'polling_units', 'polling_units');

        return $query;
    }

    private function scopedResultQuery(?User $user, ?Election $election = null): Builder
    {
        $query = PollingUnitResult::query();

        $election
            ? $query->where('election_id', $election->id)
            : $query->whereRaw('1 = 0');

        $this->scopeService->applyScope($query, $user, 'polling_unit_results', 'polling_unit_results');

        return $query;
    }

    private function scopedVoteQuery(?User $user, ?Election $election = null): Builder
    {
        $query = Vote::query();

        $election
            ? $query->where('election_id', $election->id)
            : $query->whereRaw('1 = 0');

        $this->scopeService->applyScope($query, $user, 'votes', 'votes');

        return $query;
    }

    private function scopedIncidentQuery(?User $user, ?Election $election = null): Builder
    {
        $query = ElectionIncident::query();

        $election
            ? $query->where('election_id', $election->id)
            : $query->whereRaw('1 = 0');

        $this->scopeService->applyScope($query, $user, 'election_incidents', 'election_incidents');

        return $query;
    }

    private function incidentTitle(ElectionIncident $incident): string
    {
        if ($incident->incident_type) {
            return Str::headline((string) $incident->incident_type);
        }

        return $incident->remarks
            ? Str::limit((string) $incident->remarks, 40)
            : 'Incident reported';
    }

    private function displayName(?User $user): string
    {
        if (!$user) {
            return 'Unknown reporter';
        }

        return trim(($user->firstname ?? '').' '.($user->lastname ?? '')) ?: ($user->name ?? $user->email ?? 'Unknown reporter');
    }
}
