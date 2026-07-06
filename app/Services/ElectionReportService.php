<?php

namespace App\Services;

use App\Models\Election;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\PollingUnitResult;
use App\Models\PoliticalParty;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ElectionReportService
{
    public function __construct(
        private LocationScopeService $scopeService,
        private PackageGovernanceService $packageGovernanceService
    ) {
    }

    public function pollingUnitResultsQuery(User $user, Election $election, array $filters = []): Builder
    {
        $targetPartyId = (int) $election->party_id;

        $votesSubquery = DB::table('votes')
            ->select(
                'polling_unit_result_id',
                DB::raw("SUM(CASE WHEN party_id = {$targetPartyId} THEN quantity ELSE 0 END) as votes_obtained"),
                DB::raw('SUM(quantity) as total_votes_cast')
            )
            ->where('election_id', $election->id)
            ->whereNotNull('polling_unit_result_id')
            ->groupBy('polling_unit_result_id');

        $incidentSubquery = DB::table('election_incidents')
            ->select(
                'polling_unit_id',
                DB::raw('GROUP_CONCAT(DISTINCT remarks SEPARATOR ", ") as incident_report')
            )
            ->where('election_id', $election->id)
            ->groupBy('polling_unit_id');

        $query = PollingUnitResult::query()
            ->leftJoin('polling_units', 'polling_unit_results.polling_unit_id', '=', 'polling_units.id')
            ->leftJoin('wards', 'polling_unit_results.ward_id', '=', 'wards.id')
            ->leftJoin('local_government_areas', 'polling_unit_results.lga_id', '=', 'local_government_areas.id')
            ->leftJoin('states', 'polling_unit_results.state_id', '=', 'states.id')
            ->leftJoin('regions', 'states.region_id', '=', 'regions.id')
            ->leftJoin('senatorial_districts', 'polling_unit_results.senatorial_district_id', '=', 'senatorial_districts.id')
            ->leftJoin('federal_constituencies', 'polling_unit_results.federal_constituency_id', '=', 'federal_constituencies.id')
            ->leftJoin('users as submitters', 'polling_unit_results.submitted_by', '=', 'submitters.id')
            ->leftJoin('users as verifiers', 'polling_unit_results.verified_by', '=', 'verifiers.id')
            ->leftJoin('users as disputers', 'polling_unit_results.disputed_by', '=', 'disputers.id')
            ->leftJoinSub($votesSubquery, 'vote_totals', 'polling_unit_results.id', '=', 'vote_totals.polling_unit_result_id')
            ->leftJoinSub($incidentSubquery, 'incident_reports', 'polling_unit_results.polling_unit_id', '=', 'incident_reports.polling_unit_id')
            ->where('polling_unit_results.election_id', $election->id)
            ->select([
                'polling_unit_results.id as result_id',
                'polling_unit_results.uuid as result_uuid',
                'polling_unit_results.polling_unit_id',
                'polling_units.name as polling_unit_name',
                'wards.name as ward_name',
                'local_government_areas.name as lga_name',
                'states.name as state_name',
                'regions.name as region_name',
                'senatorial_districts.name as senatorial_district_name',
                'federal_constituencies.name as federal_constituency_name',
                DB::raw('COALESCE(vote_totals.votes_obtained, 0) as votes_obtained'),
                DB::raw('COALESCE(vote_totals.total_votes_cast, 0) as total_votes_cast'),
                'polling_unit_results.result_sheet',
                'polling_unit_results.submitted_at',
                'polling_unit_results.verification_status',
                'polling_unit_results.verified_at',
                'polling_unit_results.verification_notes',
                'polling_unit_results.dispute_status',
                'polling_unit_results.disputed_at',
                'polling_unit_results.dispute_reason',
                DB::raw($this->nameExpression('submitters') . ' as submitted_by_name'),
                DB::raw($this->nameExpression('verifiers') . ' as verified_by_name'),
                DB::raw($this->nameExpression('disputers') . ' as disputed_by_name'),
                'incident_reports.incident_report',
            ]);

        $this->scopeService->applyScope($query, $user, 'polling_unit_results', 'polling_unit_results');
        $this->applyReportFilters($query, $filters);

        return $query;
    }

    public function pollingUnitRows(User $user, Election $election, array $filters = []): Collection
    {
        return $this->pollingUnitResultsQuery($user, $election, $filters)
            ->orderBy('states.name')
            ->orderBy('local_government_areas.name')
            ->orderBy('wards.name')
            ->orderBy('polling_units.name')
            ->get()
            ->map(fn ($row) => $this->formatPollingUnitRow($row));
    }

    public function summary(User $user, Election $election, array $filters = []): array
    {
        $voteQuery = DB::table('votes')
            ->select('party_id', DB::raw('SUM(quantity) as total_votes'))
            ->where('election_id', $election->id)
            ->where('quantity', '>', 0)
            ->groupBy('party_id');

        $this->scopeService->applyScope($voteQuery, $user, 'votes', 'votes');
        $this->applyVoteFilterFallback($voteQuery, $filters);

        $results = $voteQuery->get();
        $parties = PoliticalParty::query()
            ->whereIn('id', $results->pluck('party_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $partySummary = $results
            ->sortByDesc('total_votes')
            ->values()
            ->map(function ($result, int $index) use ($parties) {
                $party = $parties->get($result->party_id);
                $rank = $index + 1;

                return [
                    'sn' => $rank,
                    'party_name' => $party ? $party->name . ' (' . $party->acronym . ')' : 'No Party Assigned',
                    'party_acronym' => $party?->acronym ?? 'Unknown',
                    'total_votes' => (int) $result->total_votes,
                    'rank' => $rank,
                    'comment' => match ($rank) {
                        1 => '1st Place',
                        2 => '2nd Place',
                        3 => '3rd Place',
                        4 => '4th Place',
                        default => 'Below 4th',
                    },
                ];
            });

        $resultStats = $this->resultStats($user, $election, $filters);
        $totalValidVotes = (int) $partySummary->sum('total_votes');
        $leadingParty = $partySummary->first();

        return [
            'summary' => $partySummary,
            'stats' => [
                'total_polling_units' => $this->scopedPollingUnitsCount($user, $filters),
                'submitted_polling_units' => $resultStats['submitted'],
                'reporting_polling_units' => $resultStats['submitted'],
                'total_valid_votes' => $totalValidVotes,
                'total_parties_participated' => (int) $partySummary->where('total_votes', '>', 0)->count(),
                'results_received_percent' => $this->percent($resultStats['submitted'], $this->scopedPollingUnitsCount($user, $filters)),
                'leading_party_share' => $totalValidVotes > 0 && $leadingParty
                    ? round(((int) $leadingParty['total_votes'] / $totalValidVotes) * 100, 1)
                    : 0,
                'verified_results' => $resultStats['verified'],
                'rejected_results' => $resultStats['rejected'],
                'pending_verification' => $resultStats['pending_verification'],
                'disputed_results' => $resultStats['disputed'],
            ],
        ];
    }

    public function resultStats(User $user, Election $election, array $filters = []): array
    {
        $base = PollingUnitResult::query()->where('election_id', $election->id);
        $this->scopeService->applyScope($base, $user, 'polling_unit_results', 'polling_unit_results');
        $this->applyReportFilters($base, $filters);

        return [
            'submitted' => (clone $base)->distinct()->count('polling_unit_id'),
            'verified' => (clone $base)->where('verification_status', 'verified')->distinct()->count('polling_unit_id'),
            'rejected' => (clone $base)->where('verification_status', 'rejected')->distinct()->count('polling_unit_id'),
            'pending_verification' => (clone $base)
                ->where(function (Builder $query) {
                    $query->whereNull('verification_status')
                        ->orWhereIn('verification_status', ['submitted', 'pending']);
                })
                ->distinct()
                ->count('polling_unit_id'),
            'disputed' => (clone $base)->where('dispute_status', 'disputed')->distinct()->count('polling_unit_id'),
        ];
    }

    public function filterOptions(User $user): array
    {
        return [
            'visibility' => $this->filterVisibility($user),
            'elections' => Election::query()->orderByDesc('year')->orderBy('name')->get(['id', 'uuid', 'name', 'year']),
            'regions' => $this->regionOptions($user),
            'states' => $this->stateOptions($user),
            'senatorial_districts' => $this->senatorialDistrictOptions($user),
            'federal_constituencies' => $this->federalConstituencyOptions($user),
            'lgas' => $this->lgaOptions($user),
            'wards' => $this->wardOptions($user),
            'polling_units' => $this->pollingUnitOptions($user),
        ];
    }

    public function latestElection(): ?Election
    {
        return Election::query()->orderByDesc('year')->orderByDesc('id')->first();
    }

    public function indexRows(User $user): Collection
    {
        return Election::query()
            ->with('party')
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get()
            ->map(function (Election $election) use ($user) {
                $voteQuery = DB::table('votes')
                    ->where('election_id', $election->id);
                $this->scopeService->applyScope($voteQuery, $user, 'votes', 'votes');

                $targetVoteQuery = clone $voteQuery;

                $election->scoped_votes_received = (int) $targetVoteQuery
                    ->where('party_id', $election->party_id)
                    ->sum('quantity');
                $election->scoped_total_votes = (int) $voteQuery->sum('quantity');

                return $election;
            });
    }

    public function scopeSnapshot(User $user, Election $election, array $filters = []): array
    {
        $resultStats = $this->resultStats($user, $election, $filters);
        $coverage = $this->coverage($user, $filters);
        $breadcrumb = $this->breadcrumb($user, $filters);
        $scope = collect($breadcrumb)->last();

        return [
            'election' => [
                'name' => $election->name,
                'year' => \Carbon\Carbon::parse($election->year)->year,
                'label' => \Carbon\Carbon::parse($election->year)->year.' - '.$election->name,
            ],
            'scope' => [
                'label' => $scope['label'] ?? $this->scopeLabel($user),
                'base_label' => $this->scopeLabel($user),
            ],
            'coverage' => $coverage,
            'records' => [
                'submitted' => $resultStats['submitted'] ?? 0,
            ],
            'kpis' => [
                'submitted' => $resultStats['submitted'] ?? 0,
                'verified' => $resultStats['verified'] ?? 0,
                'rejected' => $resultStats['rejected'] ?? 0,
                'pending' => $resultStats['pending_verification'] ?? 0,
            ],
            'breadcrumb' => $breadcrumb,
        ];
    }

    public function exportHeadings(): array
    {
        return [
            'State',
            'Senatorial District',
            'Federal Constituency',
            'LGA',
            'Ward',
            'Polling Unit',
            'Votes Obtained',
            'Total Votes Cast',
            'Result Sheet',
            'Incident',
            'Submitted By',
            'Submitted At',
            'Verification Status',
            'Verified By',
            'Verified At',
            'Verification Notes',
            'Dispute Status',
            'Disputed By',
            'Disputed At',
            'Dispute Reason',
        ];
    }

    public function exportRows(User $user, Election $election, array $filters = []): Collection
    {
        return $this->pollingUnitRows($user, $election, $filters)
            ->map(fn (array $row) => [
                $row['state_name'],
                $row['senatorial_district_name'],
                $row['federal_constituency_name'],
                $row['lga_name'],
                $row['ward_name'],
                $row['polling_unit_name'],
                $row['votes_obtained'],
                $row['total_votes_cast'],
                $row['result_sheet'] ?: '',
                $row['incident_report'] ?: '',
                $row['submitted_by'],
                $row['submitted_at'],
                $row['verification_status'],
                $row['verified_by'],
                $row['verified_at'],
                $row['verification_notes'],
                $row['dispute_status'],
                $row['disputed_by'],
                $row['disputed_at'],
                $row['dispute_reason'],
            ]);
    }

    public function summaryExportRows(User $user, Election $election, array $filters = []): Collection
    {
        return collect($this->summary($user, $election, $filters)['summary'])
            ->map(fn (array $row) => [
                $row['rank'],
                $row['party_name'],
                $row['total_votes'],
                $row['comment'],
            ]);
    }

    public function packageContext(): array
    {
        return $this->packageGovernanceService->context();
    }

    public function scopeLabel(User $user): string
    {
        return match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => optional($user->region)->name ?? 'Regional Scope',
            LocationScopeService::STATE => optional($user->state)->name ?? 'State Scope',
            LocationScopeService::SENATORIAL => optional($user->senatorialDistrict)->name ?? 'Senatorial District Scope',
            LocationScopeService::FEDERAL => optional($user->federalConstituency)->name ?? 'Federal Constituency Scope',
            LocationScopeService::LGA => optional($user->lga)->name ?? 'LGA Scope',
            LocationScopeService::WARD => optional($user->ward)->name ?? 'Ward Scope',
            LocationScopeService::POLLING_UNIT => optional($user->pollingUnit)->name ?? 'Polling Unit Scope',
            default => app(CampaignPackageUiService::class)->dashboardScopeTitle(),
        };
    }

    private function applyReportFilters(Builder|QueryBuilder $query, array $filters): void
    {
        $map = [
            'region_id' => 'regions.id',
            'state_id' => 'polling_unit_results.state_id',
            'senatorial_district_id' => 'polling_unit_results.senatorial_district_id',
            'federal_constituency_id' => 'polling_unit_results.federal_constituency_id',
            'lga_id' => 'polling_unit_results.lga_id',
            'ward_id' => 'polling_unit_results.ward_id',
            'polling_unit_id' => 'polling_unit_results.polling_unit_id',
            'verification_status' => 'polling_unit_results.verification_status',
            'dispute_status' => 'polling_unit_results.dispute_status',
        ];

        foreach ($map as $key => $column) {
            if (!empty($filters[$key])) {
                if ($key === 'region_id') {
                    $query
                        ->leftJoin('states as filter_states', 'polling_unit_results.state_id', '=', 'filter_states.id')
                        ->where('filter_states.region_id', $filters[$key]);
                } else {
                    $query->where($column, $filters[$key]);
                }
            }
        }
    }

    private function applyVoteFilterFallback(QueryBuilder $query, array $filters): void
    {
        foreach (['state_id', 'senatorial_district_id', 'federal_constituency_id', 'lga_id', 'ward_id', 'polling_unit_id'] as $key) {
            if (!empty($filters[$key])) {
                $query->where("votes.{$key}", $filters[$key]);
            }
        }

        if (!empty($filters['region_id'])) {
            $query->whereIn('votes.state_id', State::query()->where('region_id', $filters['region_id'])->select('id'));
        }
    }

    private function scopedPollingUnitsCount(User $user, array $filters): int
    {
        $query = PollingUnit::query()
            ->leftJoin('wards', 'polling_units.ward_id', '=', 'wards.id')
            ->leftJoin('local_government_areas', 'wards.lga_id', '=', 'local_government_areas.id')
            ->leftJoin('states', 'local_government_areas.state_id', '=', 'states.id');

        $this->scopeService->applyScope($query, $user, 'polling_units', 'polling_units');

        $this->applyPollingUnitFilters($query, $filters);

        return (int) $query->distinct()->count('polling_units.id');
    }

    private function applyPollingUnitFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['region_id'])) {
            $query->where('states.region_id', $filters['region_id']);
        }
        if (!empty($filters['state_id'])) {
            $query->where('local_government_areas.state_id', $filters['state_id']);
        }
        if (!empty($filters['senatorial_district_id'])) {
            $query->where('polling_units.senatorial_district_id', $filters['senatorial_district_id']);
        }
        if (!empty($filters['federal_constituency_id'])) {
            $query->where('polling_units.federal_constituency_id', $filters['federal_constituency_id']);
        }
        if (!empty($filters['lga_id'])) {
            $query->where('wards.lga_id', $filters['lga_id']);
        }
        if (!empty($filters['ward_id'])) {
            $query->where('polling_units.ward_id', $filters['ward_id']);
        }
        if (!empty($filters['polling_unit_id'])) {
            $query->where('polling_units.id', $filters['polling_unit_id']);
        }
    }

    private function scopedOptions(Builder $query, User $user, string $subject): Collection
    {
        $this->scopeService->applyScope($query, $user, $subject, $query->getModel()->getTable());

        return $query->get(['id', 'name']);
    }

    private function regionOptions(User $user): Collection
    {
        $query = Region::query()->orderBy('name');

        if ($this->scopeService->getScopeType($user) === LocationScopeService::REGION && $user->region_id) {
            $query->where('id', $user->region_id);
        }

        return $query->get(['id', 'name']);
    }

    private function stateOptions(User $user): Collection
    {
        $query = State::query()->orderBy('name');

        match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => $query->where('region_id', $user->region_id),
            LocationScopeService::STATE,
            LocationScopeService::SENATORIAL,
            LocationScopeService::FEDERAL,
            LocationScopeService::LGA,
            LocationScopeService::WARD,
            LocationScopeService::POLLING_UNIT => $user->state_id ? $query->where('id', $user->state_id) : $query->whereRaw('1 = 0'),
            default => null,
        };

        return $query->get(['id', 'name', 'region_id']);
    }

    private function senatorialDistrictOptions(User $user): Collection
    {
        $query = SenatorialDistrict::query()->orderBy('name');

        match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => $query->whereHas('state', fn (Builder $stateQuery) => $stateQuery->where('region_id', $user->region_id)),
            LocationScopeService::STATE => $query->where('state_id', $user->state_id),
            LocationScopeService::SENATORIAL => $query->where('id', $user->senatorial_district_id),
            LocationScopeService::FEDERAL,
            LocationScopeService::LGA,
            LocationScopeService::WARD,
            LocationScopeService::POLLING_UNIT => $user->state_id ? $query->where('state_id', $user->state_id) : $query->whereRaw('1 = 0'),
            default => null,
        };

        return $query->get(['id', 'name', 'state_id']);
    }

    private function federalConstituencyOptions(User $user): Collection
    {
        $query = FederalConstituency::query()->orderBy('name');

        match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => $query->whereHas('state', fn (Builder $stateQuery) => $stateQuery->where('region_id', $user->region_id)),
            LocationScopeService::STATE => $query->where('state_id', $user->state_id),
            LocationScopeService::SENATORIAL => $query->where('senatorial_district_id', $user->senatorial_district_id),
            LocationScopeService::FEDERAL => $query->where('id', $user->federal_constituency_id),
            LocationScopeService::LGA,
            LocationScopeService::WARD,
            LocationScopeService::POLLING_UNIT => $user->state_id ? $query->where('state_id', $user->state_id) : $query->whereRaw('1 = 0'),
            default => null,
        };

        return $query->get(['id', 'name', 'state_id', 'senatorial_district_id']);
    }

    private function lgaOptions(User $user): Collection
    {
        $query = LocalGovernmentArea::query()->orderBy('name');
        $this->scopeService->applyScope($query, $user, 'local_government_areas', 'local_government_areas');

        return $query->get(['id', 'name', 'state_id', 'senatorial_district_id', 'federal_constituency_id']);
    }

    private function wardOptions(User $user): Collection
    {
        $query = Ward::query()
            ->leftJoin('local_government_areas', 'wards.lga_id', '=', 'local_government_areas.id')
            ->orderBy('wards.name')
            ->select([
                'wards.id',
                'wards.name',
                'wards.lga_id',
                'local_government_areas.state_id',
                'local_government_areas.senatorial_district_id',
                'local_government_areas.federal_constituency_id',
            ]);

        $this->scopeService->applyScope($query, $user, 'wards', 'wards');

        return $query->get();
    }

    private function pollingUnitOptions(User $user): Collection
    {
        $query = PollingUnit::query()
            ->leftJoin('wards', 'polling_units.ward_id', '=', 'wards.id')
            ->leftJoin('local_government_areas', 'wards.lga_id', '=', 'local_government_areas.id')
            ->orderBy('polling_units.name')
            ->select([
                'polling_units.id',
                'polling_units.name',
                'polling_units.ward_id',
                'local_government_areas.id as lga_id',
                'local_government_areas.state_id',
                'polling_units.senatorial_district_id',
                'polling_units.federal_constituency_id',
            ]);

        $this->scopeService->applyScope($query, $user, 'polling_units', 'polling_units');

        return $query->get();
    }

    private function filterVisibility(User $user): array
    {
        $packageLevels = match ($this->packageGovernanceService->package()) {
            PackageGovernanceService::PRESIDENTIAL => ['region', 'state', 'senatorial', 'federal', 'lga', 'ward', 'polling_unit'],
            PackageGovernanceService::GOVERNORSHIP => ['senatorial', 'federal', 'lga', 'ward', 'polling_unit'],
            PackageGovernanceService::SENATORIAL => ['federal', 'lga', 'ward', 'polling_unit'],
            PackageGovernanceService::FEDERAL => ['lga', 'ward', 'polling_unit'],
            PackageGovernanceService::CHAIRMANSHIP => ['ward', 'polling_unit'],
        };

        $scopeLevels = match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::NATIONAL => ['state', 'senatorial', 'federal', 'lga', 'ward', 'polling_unit'],
            LocationScopeService::REGION => ['state', 'senatorial', 'federal', 'lga', 'ward', 'polling_unit'],
            LocationScopeService::STATE => ['senatorial', 'federal', 'lga', 'ward', 'polling_unit'],
            LocationScopeService::SENATORIAL,
            LocationScopeService::FEDERAL => ['lga', 'ward', 'polling_unit'],
            LocationScopeService::LGA => ['ward', 'polling_unit'],
            LocationScopeService::WARD => ['polling_unit'],
            default => [],
        };

        if ($this->packageGovernanceService->package() === PackageGovernanceService::PRESIDENTIAL
            && in_array($this->scopeService->getScopeType($user), [LocationScopeService::NATIONAL], true)) {
            $scopeLevels = ['region', ...$scopeLevels];
        }

        return array_values(array_intersect($packageLevels, $scopeLevels));
    }

    private function formatPollingUnitRow(object $row): array
    {
        return [
            'result_id' => $row->result_id,
            'result_uuid' => $row->result_uuid,
            'polling_unit_id' => $row->polling_unit_id,
            'polling_unit_name' => $row->polling_unit_name ?: 'Unknown polling unit',
            'region_name' => $row->region_name ?: '',
            'ward_name' => $row->ward_name ?: 'Unknown ward',
            'lga_name' => $row->lga_name ?: 'Unknown LGA',
            'state_name' => $row->state_name ?: 'Unknown state',
            'senatorial_district_name' => $row->senatorial_district_name ?: '',
            'federal_constituency_name' => $row->federal_constituency_name ?: '',
            'votes_obtained' => (int) $row->votes_obtained,
            'total_votes_cast' => (int) $row->total_votes_cast,
            'result_sheet' => $row->result_sheet,
            'incident_report' => $row->incident_report,
            'submitted_by' => trim((string) $row->submitted_by_name) ?: 'Unknown submitter',
            'submitted_at' => $this->formatDate($row->submitted_at),
            'verification_status' => $row->verification_status ?: 'submitted',
            'verified_by' => trim((string) $row->verified_by_name) ?: '',
            'verified_at' => $this->formatDate($row->verified_at),
            'verification_notes' => $row->verification_notes ?: '',
            'dispute_status' => $row->dispute_status ?: 'normal',
            'disputed_by' => trim((string) $row->disputed_by_name) ?: '',
            'disputed_at' => $this->formatDate($row->disputed_at),
            'dispute_reason' => $row->dispute_reason ?: '',
        ];
    }

    private function formatDate(mixed $date): string
    {
        return $date ? \Carbon\Carbon::parse($date)->format('Y-m-d H:i') : '';
    }

    private function percent(int $part, int $total): float
    {
        return $total > 0 ? round(min(100, ($part / $total) * 100), 2) : 0.0;
    }

    private function nameExpression(string $alias): string
    {
        return "TRIM(CONCAT(COALESCE({$alias}.firstname, ''), ' ', COALESCE({$alias}.lastname, '')))";
    }

    private function coverage(User $user, array $filters): array
    {
        $query = PollingUnit::query()
            ->leftJoin('wards', 'polling_units.ward_id', '=', 'wards.id')
            ->leftJoin('local_government_areas', 'wards.lga_id', '=', 'local_government_areas.id')
            ->leftJoin('states', 'local_government_areas.state_id', '=', 'states.id');

        $this->scopeService->applyScope($query, $user, 'polling_units', 'polling_units');
        $this->applyPollingUnitFilters($query, $filters);

        return [
            'lgas' => (clone $query)->distinct()->count('local_government_areas.id'),
            'wards' => (clone $query)->distinct()->count('wards.id'),
            'polling_units' => (clone $query)->distinct()->count('polling_units.id'),
        ];
    }

    private function breadcrumb(User $user, array $filters): array
    {
        $items = [];

        if ($this->packageGovernanceService->package() !== PackageGovernanceService::PRESIDENTIAL && $user->state_id) {
            $state = State::find($user->state_id);
            if ($state) {
                $items[] = ['key' => 'state_id', 'id' => $state->id, 'label' => $state->name];
            }
        }

        $lookups = [
            'region_id' => [Region::class, 'Region'],
            'state_id' => [State::class, 'State'],
            'senatorial_district_id' => [SenatorialDistrict::class, 'Senatorial District'],
            'federal_constituency_id' => [FederalConstituency::class, 'Federal Constituency'],
            'lga_id' => [LocalGovernmentArea::class, 'LGA'],
            'ward_id' => [Ward::class, 'Ward'],
            'polling_unit_id' => [PollingUnit::class, 'Polling Unit'],
        ];

        foreach ($lookups as $key => [$model, $fallback]) {
            if (empty($filters[$key])) {
                continue;
            }

            $record = $model::find($filters[$key]);
            if ($record && !collect($items)->contains(fn ($item) => $item['key'] === $key && (int) $item['id'] === (int) $record->id)) {
                $items[] = ['key' => $key, 'id' => $record->id, 'label' => $record->name ?? $fallback];
            }
        }

        if (empty($items)) {
            $items[] = ['key' => 'scope', 'id' => null, 'label' => $this->scopeLabel($user)];
        }

        return $items;
    }
}
