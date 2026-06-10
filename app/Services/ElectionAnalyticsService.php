<?php

namespace App\Services;

use App\Models\Election;
use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\PollingUnit;
use App\Models\PollingUnitResult;
use App\Models\User;
use App\Models\VideoEvidence;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ElectionAnalyticsService
{
    public function __construct(
        private LocationScopeService $scopeService,
        private ElectionOperationsService $operationsService
    ) {
    }

    public function forUser(?User $user): array
    {
        $activeElection = $this->operationsService->activeElection();
        $coverage = $this->operationsService->submissionCoverage($user, $activeElection);
        $incidents = $this->operationsService->incidentMetrics($user, $activeElection);
        $coverageBySubdivision = $this->coverageBySubdivision($user, $activeElection);

        return [
            'active_election' => $activeElection,
            'submission_coverage' => [
                'labels' => ['Submitted', 'Pending'],
                'data' => [
                    (int) ($coverage['submitted_polling_units'] ?? 0),
                    (int) ($coverage['pending_polling_units'] ?? 0),
                ],
            ],
            'coverage_by_subdivision' => $coverageBySubdivision,
            'reporting_progress' => $coverageBySubdivision,
            'incident_evidence' => [
                'labels' => ['With Evidence', 'Without Evidence'],
                'data' => [
                    (int) ($incidents['with_evidence'] ?? 0),
                    (int) ($incidents['without_evidence'] ?? 0),
                ],
            ],
            'activity_timeline' => $this->activityTimeline($user, $activeElection),
            'limitations' => [
                'incident_type' => 'incident_type is available for future incident category analytics.',
                'submitted_at' => 'submitted_at is used for result timelines with updated_at/created_at fallback.',
                'reported_at' => 'election_incidents has no reported_at column; created_at is used.',
            ],
        ];
    }

    private function coverageBySubdivision(?User $user, ?Election $election): array
    {
        $pollingUnits = $this->scopedPollingUnits($user)
            ->with([
                'ward.localGovernmentArea.state',
                'senatorialDistrict',
                'federalConstituency',
            ])
            ->get();

        $submittedPollingUnitIds = $this->scopedResultQuery($user, $election)
            ->whereNotNull('polling_unit_id')
            ->distinct()
            ->pluck('polling_unit_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        return collect($this->subdivisionDefinitions($user))
            ->map(fn (array $definition) => $this->buildSubdivisionChart($pollingUnits, $submittedPollingUnitIds, $definition))
            ->values()
            ->all();
    }

    private function subdivisionDefinitions(?User $user): array
    {
        return match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::STATE => [
                [
                    'title' => 'Coverage by Senatorial District',
                    'empty' => 'Unassigned Senatorial District',
                    'resolver' => fn (PollingUnit $pollingUnit) => [
                        $pollingUnit->senatorial_district_id,
                        $pollingUnit->senatorialDistrict?->name,
                    ],
                ],
                [
                    'title' => 'Coverage by Federal Constituency',
                    'empty' => 'Unassigned Federal Constituency',
                    'resolver' => fn (PollingUnit $pollingUnit) => [
                        $pollingUnit->federal_constituency_id,
                        $pollingUnit->federalConstituency?->name,
                    ],
                ],
                [
                    'title' => 'Coverage by LGA',
                    'empty' => 'Unassigned LGA',
                    'resolver' => fn (PollingUnit $pollingUnit) => [
                        $pollingUnit->ward?->lga_id,
                        $pollingUnit->ward?->localGovernmentArea?->name,
                    ],
                ],
            ],
            LocationScopeService::SENATORIAL, LocationScopeService::FEDERAL => [
                [
                    'title' => 'Coverage by LGA',
                    'empty' => 'Unassigned LGA',
                    'resolver' => fn (PollingUnit $pollingUnit) => [
                        $pollingUnit->ward?->lga_id,
                        $pollingUnit->ward?->localGovernmentArea?->name,
                    ],
                ],
            ],
            LocationScopeService::LGA => [
                [
                    'title' => 'Coverage by Ward',
                    'empty' => 'Unassigned Ward',
                    'resolver' => fn (PollingUnit $pollingUnit) => [
                        $pollingUnit->ward_id,
                        $pollingUnit->ward?->name,
                    ],
                ],
            ],
            LocationScopeService::WARD => [
                [
                    'title' => 'Coverage by Polling Unit',
                    'empty' => 'Unassigned Polling Unit',
                    'resolver' => fn (PollingUnit $pollingUnit) => [
                        $pollingUnit->id,
                        $pollingUnit->name,
                    ],
                ],
            ],
            LocationScopeService::POLLING_UNIT => [
                [
                    'title' => 'Polling Unit Coverage',
                    'empty' => 'Assigned Polling Unit',
                    'resolver' => fn (PollingUnit $pollingUnit) => [
                        $pollingUnit->id,
                        $pollingUnit->name,
                    ],
                ],
            ],
            default => [
                [
                    'title' => 'Coverage by State',
                    'empty' => 'Unassigned State',
                    'resolver' => fn (PollingUnit $pollingUnit) => [
                        $pollingUnit->ward?->localGovernmentArea?->state_id,
                        $pollingUnit->ward?->localGovernmentArea?->state?->name,
                    ],
                ],
            ],
        };
    }

    private function buildSubdivisionChart(Collection $pollingUnits, Collection $submittedPollingUnitIds, array $definition): array
    {
        $groups = [];

        foreach ($pollingUnits as $pollingUnit) {
            [$id, $name] = $definition['resolver']($pollingUnit);
            $key = $id ?: 'unassigned';
            $label = $name ?: $definition['empty'];

            $groups[$key] ??= [
                'label' => $label,
                'submitted' => 0,
                'pending' => 0,
            ];

            if ($submittedPollingUnitIds->has((int) $pollingUnit->id)) {
                $groups[$key]['submitted']++;
            } else {
                $groups[$key]['pending']++;
            }
        }

        $rows = collect($groups)
            ->sortBy('label')
            ->values();

        return [
            'title' => $definition['title'],
            'labels' => $rows->pluck('label')->all(),
            'submitted' => $rows->pluck('submitted')->map(fn ($value) => (int) $value)->all(),
            'pending' => $rows->pluck('pending')->map(fn ($value) => (int) $value)->all(),
        ];
    }

    private function activityTimeline(?User $user, ?Election $election): array
    {
        $start = now()->startOfHour()->subHours(11);
        $end = now()->endOfHour();
        $buckets = collect();

        for ($hour = 0; $hour < 12; $hour++) {
            $time = $start->copy()->addHours($hour);
            $buckets->put($time->format('Y-m-d H:00:00'), [
                'label' => $time->format('H:00'),
                'results' => 0,
                'incidents' => 0,
                'evidence' => 0,
            ]);
        }

        $this->hourlyCountsByExpression(
            $this->scopedResultQuery($user, $election),
            'COALESCE(submitted_at, updated_at, created_at)',
            $start,
            $end
        )
            ->each(function (int $total, string $bucket) use ($buckets) {
                if ($buckets->has($bucket)) {
                    $row = $buckets->get($bucket);
                    $row['results'] = $total;
                    $buckets->put($bucket, $row);
                }
            });

        $this->hourlyCounts($this->scopedIncidentQuery($user, $election), 'created_at', $start, $end)
            ->each(function (int $total, string $bucket) use ($buckets) {
                if ($buckets->has($bucket)) {
                    $row = $buckets->get($bucket);
                    $row['incidents'] = $total;
                    $buckets->put($bucket, $row);
                }
            });

        $this->evidenceHourlyCounts($user, $election, $start, $end)
            ->each(function (int $total, string $bucket) use ($buckets) {
                if ($buckets->has($bucket)) {
                    $row = $buckets->get($bucket);
                    $row['evidence'] = $total;
                    $buckets->put($bucket, $row);
                }
            });

        return [
            'labels' => $buckets->pluck('label')->all(),
            'results' => $buckets->pluck('results')->all(),
            'incidents' => $buckets->pluck('incidents')->all(),
            'evidence' => $buckets->pluck('evidence')->all(),
        ];
    }

    private function hourlyCounts(Builder $query, string $column, Carbon $start, Carbon $end): Collection
    {
        return $query
            ->whereBetween($column, [$start, $end])
            ->selectRaw("DATE_FORMAT({$column}, '%Y-%m-%d %H:00:00') as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket')
            ->map(fn ($total) => (int) $total);
    }

    private function hourlyCountsByExpression(Builder $query, string $expression, Carbon $start, Carbon $end): Collection
    {
        return $query
            ->whereRaw("{$expression} BETWEEN ? AND ?", [$start, $end])
            ->selectRaw("DATE_FORMAT({$expression}, '%Y-%m-%d %H:00:00') as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket')
            ->map(fn ($total) => (int) $total);
    }

    private function evidenceHourlyCounts(?User $user, ?Election $election, Carbon $start, Carbon $end): Collection
    {
        $incidentIds = $this->scopedIncidentQuery($user, $election)->select('election_incidents.id');

        $pictureCounts = PictureEvidence::query()
            ->whereIn('election_incident_id', clone $incidentIds)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $videoCounts = VideoEvidence::query()
            ->whereIn('election_incident_id', clone $incidentIds)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        return $pictureCounts
            ->keys()
            ->merge($videoCounts->keys())
            ->unique()
            ->mapWithKeys(fn ($bucket) => [
                $bucket => (int) ($pictureCounts[$bucket] ?? 0) + (int) ($videoCounts[$bucket] ?? 0),
            ]);
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

    private function scopedIncidentQuery(?User $user, ?Election $election = null): Builder
    {
        $query = ElectionIncident::query();

        $election
            ? $query->where('election_id', $election->id)
            : $query->whereRaw('1 = 0');

        $this->scopeService->applyScope($query, $user, 'election_incidents', 'election_incidents');

        return $query;
    }
}
