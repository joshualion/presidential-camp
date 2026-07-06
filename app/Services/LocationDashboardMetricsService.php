<?php

namespace App\Services;

use App\Models\Election;
use App\Models\ElectionIncident;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PictureEvidence;
use App\Models\PollingUnit;
use App\Models\PollingUnitAgentAssignment;
use App\Models\PollingUnitResult;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\VideoEvidence;
use App\Models\Vote;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LocationDashboardMetricsService
{
    public function __construct(
        private LocationScopeService $scopeService,
        private PackageVisibilityService $packageVisibilityService
    )
    {
    }

    public function forUser(?User $user): array
    {
        $metrics = [
            'scope' => [
                'type' => $this->scopeService->getScopeType($user),
                'id' => $this->scopeService->getScopeId($user),
                'label' => $this->scopeLabel($user),
                'title' => $this->dashboardTitle($user),
            ],
            'geography' => $this->geography($user),
            'coverage' => $this->coverage($user),
            'people' => $this->people($user),
            'membership_activity' => $this->membershipActivity($user),
            'election' => $this->electionOperations($user),
            'drilldowns' => $this->drilldowns($user),
        ];

        $metrics['card_groups'] = $this->dashboardCardGroups($user, $metrics);
        $metrics['active_election'] = $metrics['election']['active_election'] ?? null;

        return $metrics;
    }

    public function forSenatorialDistrict(SenatorialDistrict $district, ?User $viewer = null): array
    {
        $scopeUser = $this->scopeProxyUser($viewer, 'senatorialadmin', [
            'state_id' => $district->state_id,
            'senatorial_district_id' => $district->id,
        ]);
        $scopeUser->setRelation('senatorialDistrict', $district);

        return $this->forUser($scopeUser);
    }

    public function forFederalConstituency(FederalConstituency $constituency, ?User $viewer = null): array
    {
        $scopeUser = $this->scopeProxyUser($viewer, 'federaladmin', [
            'state_id' => $constituency->state_id,
            'senatorial_district_id' => $constituency->senatorial_district_id,
            'federal_constituency_id' => $constituency->id,
        ]);
        $scopeUser->setRelation('federalConstituency', $constituency);

        return $this->forUser($scopeUser);
    }

    public function forLocalGovernmentArea(LocalGovernmentArea $lga, ?User $viewer = null): array
    {
        $scopeUser = $this->scopeProxyUser($viewer, 'lgaadmin', [
            'state_id' => $lga->state_id,
            'senatorial_district_id' => $lga->senatorial_district_id,
            'federal_constituency_id' => $lga->federal_constituency_id,
            'lga_id' => $lga->id,
        ]);
        $scopeUser->setRelation('lga', $lga);

        return $this->forUser($scopeUser);
    }

    public function forWard(Ward $ward, ?User $viewer = null): array
    {
        $ward->loadMissing('localGovernmentArea');

        $scopeUser = $this->scopeProxyUser($viewer, 'wardadmin', [
            'state_id' => $ward->localGovernmentArea?->state_id,
            'senatorial_district_id' => $ward->localGovernmentArea?->senatorial_district_id,
            'federal_constituency_id' => $ward->localGovernmentArea?->federal_constituency_id,
            'lga_id' => $ward->lga_id,
            'ward_id' => $ward->id,
        ]);
        $scopeUser->setRelation('ward', $ward);

        return $this->forUser($scopeUser);
    }

    public function forPollingUnit(PollingUnit $pollingUnit, ?User $viewer = null): array
    {
        $pollingUnit->loadMissing('ward.localGovernmentArea');

        $scopeUser = $this->scopeProxyUser($viewer, 'puadmin', [
            'state_id' => $pollingUnit->ward?->localGovernmentArea?->state_id,
            'senatorial_district_id' => $pollingUnit->senatorial_district_id,
            'federal_constituency_id' => $pollingUnit->federal_constituency_id,
            'lga_id' => $pollingUnit->ward?->lga_id,
            'ward_id' => $pollingUnit->ward_id,
            'polling_unit_id' => $pollingUnit->id,
        ]);
        $scopeUser->setRelation('pollingUnit', $pollingUnit);

        return $this->forUser($scopeUser);
    }

    private function scopeProxyUser(?User $viewer, string $accessLevel, array $attributes): User
    {
        $scopeUser = $viewer ? $viewer->replicate() : new User();
        $scopeUser->access_level = $accessLevel;

        foreach ($attributes as $key => $value) {
            $scopeUser->{$key} = $value;
        }

        return $scopeUser;
    }

    public function statCardPayloadForUser(?User $user): array
    {
        return $this->cachedStatCardPayloadForUser($user);
    }

    public function cachedStatCardPayloadForUser(?User $user): array
    {
        $packageScope = app(PackageScopeService::class)->current();
        $cacheKey = implode(':', [
            'dashboard-stat-cards',
            $this->packageVisibilityService->package(),
            $packageScope->scope_type ?? 'scope-none',
            $packageScope->state_id ?? 'state-all',
            $packageScope->senatorial_district_id ?? 'senatorial-all',
            $packageScope->federal_constituency_id ?? 'federal-all',
            $packageScope->lga_id ?? 'lga-all',
            $user?->access_level ?? 'guest',
            $this->scopeService->getScopeType($user),
            $this->scopeService->getScopeId($user) ?? 'all',
        ]);

        return Cache::remember($cacheKey, now()->addSeconds(15), fn () => $this->rawStatCardPayloadForUser($user));
    }

    public function rawStatCardPayloadForUser(?User $user): array
    {
        $metrics = $this->forUser($user);

        return [
            'scope' => $metrics['scope'] ?? [],
            'card_groups' => collect($metrics['card_groups'] ?? [])
                ->map(fn (array $cards) => collect($cards)
                    ->map(fn (array $card) => $this->serializeStatCard($card))
                    ->values()
                    ->all())
                ->all(),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    public function geography(?User $user): array
    {
        return [
            'regions' => $this->countRegions($user),
            'states' => $this->countStates($user),
            'senatorial_districts' => $this->countSenatorialDistricts($user),
            'federal_constituencies' => $this->countFederalConstituencies($user),
            'lgas' => $this->countLgas($user),
            'wards' => $this->countWards($user),
            'polling_units' => $this->countPollingUnits($user),
        ];
    }

    public function people(?User $user): array
    {
        $usersQuery = User::query()->where('access_level', '!=', 'superadmin');
        $this->scopeService->applyScope($usersQuery, $user, 'users', 'users');

        $membersQuery = User::query()
            ->where('access_level', 'user')
            ->whereDoesntHave('roles', function (Builder $roleQuery) {
                $roleQuery->where('name', '!=', 'Member');
            });
        $this->scopeService->applyScope($membersQuery, $user, 'users', 'users');

        $executivesQuery = User::query()
            ->where('access_level', '!=', 'superadmin')
            ->where('access_level', '!=', 'puadmin')
            ->whereDoesntHave('roles', function (Builder $roleQuery) {
                $roleQuery->where('name', 'like', '%agent%');
            })
            ->where(function (Builder $query) {
                $query->where('access_level', '!=', 'user')
                    ->orWhereHas('roles', function (Builder $roleQuery) {
                        $roleQuery->where('name', '!=', 'Member');
                    });
            });
        $this->scopeService->applyScope($executivesQuery, $user, 'users', 'users');

        $newMembersQuery = User::query()
            ->where('access_level', '!=', 'superadmin')
            ->where('created_at', '>=', Carbon::now()->subDay());
        $this->scopeService->applyScope($newMembersQuery, $user, 'users', 'users');

        $usersCount = (int) (clone $usersQuery)->count();
        $eligibleVotersCount = (int) (clone $usersQuery)
            ->whereIn('validVoter', ['yes', 'Yes', 'YES'])
            ->count();
        $ineligibleVotersCount = max(0, $usersCount - $eligibleVotersCount);
        $executivesCount = (int) $executivesQuery->count();

        return [
            'users' => $usersCount,
            'members' => (int) $membersQuery->count(),
            'agents' => $this->approvedAgentAssignmentCount($user),
            'coordinators' => $executivesCount,
            'executives' => $executivesCount,
            'new_members' => (int) $newMembersQuery->count(),
            'eligible_voters' => $eligibleVotersCount,
            'ineligible_voters' => $ineligibleVotersCount,
        ];
    }

    public function membershipActivity(?User $user): array
    {
        return [
            'new_today' => $this->newMemberCount($user, Carbon::now()->subDay()),
            'new_this_week' => $this->newMemberCount($user, Carbon::now()->subWeek()),
            'new_this_month' => $this->newMemberCount($user, Carbon::now()->subMonth()),
        ];
    }

    public function coverage(?User $user): array
    {
        return [
            'regions_with_users' => $this->distinctUserLocationCount($user, 'region_id'),
            'states_with_users' => $this->distinctUserLocationCount($user, 'state_id'),
            'senatorial_districts_with_users' => $this->coveredElectoralBoundaryCount($user, 'senatorial_district_id'),
            'federal_constituencies_with_users' => $this->coveredElectoralBoundaryCount($user, 'federal_constituency_id'),
            'lgas_with_users' => $this->distinctUserLocationCount($user, 'lga_id'),
            'wards_with_users' => $this->distinctUserLocationCount($user, 'ward_id'),
            'polling_units_with_users' => $this->distinctUserLocationCount($user, 'polling_unit_id'),
        ];
    }

    public function electionOperations(?User $user): array
    {
        $scopeType = $this->scopeService->getScopeType($user);
        $activeElection = Election::query()
            ->where('status', 'ongoing')
            ->orWhereDate('year', now()->toDateString())
            ->latest('updated_at')
            ->first();

        $votesQuery = Vote::query();
        if ($activeElection) {
            $votesQuery->where('election_id', $activeElection->id);
        }
        $this->scopeService->applyScope($votesQuery, $user, 'votes', 'votes');

        $resultsQuery = PollingUnitResult::query();
        if ($activeElection) {
            $resultsQuery->where('election_id', $activeElection->id);
        }
        $this->scopeService->applyScope($resultsQuery, $user, 'polling_unit_results', 'polling_unit_results');

        $incidentsQuery = ElectionIncident::query();
        if ($activeElection) {
            $incidentsQuery->where('election_id', $activeElection->id);
        }
        $this->scopeService->applyScope($incidentsQuery, $user, 'election_incidents', 'election_incidents');

        $resultsSubmitted = (int) $resultsQuery->distinct()->count('polling_unit_id');
        $verifiedResults = (int) (clone $resultsQuery)->where('verification_status', 'verified')->distinct()->count('polling_unit_id');
        $disputedResults = (int) (clone $resultsQuery)->where('dispute_status', 'disputed')->distinct()->count('polling_unit_id');
        $pollingUnits = $this->countPollingUnits($user);
        $resultsPending = max(0, $pollingUnits - $resultsSubmitted);
        $incidentsReported = (int) (clone $incidentsQuery)->count();

        return [
            'title' => app(CampaignPackageUiService::class)->electionStatisticsTitleForScope($scopeType),
            'active_election' => $activeElection ? [
                'id' => $activeElection->id,
                'uuid' => $activeElection->uuid,
                'name' => $activeElection->name,
            ] : null,
            'elections' => (int) Election::count(),
            'votes' => (int) (clone $votesQuery)->sum('quantity'),
            'vote_rows' => (int) (clone $votesQuery)->count(),
            'vote_totals' => (int) (clone $votesQuery)->sum('quantity'),
            'votes_captured' => (int) (clone $votesQuery)->sum('quantity'),
            'polling_unit_results' => $resultsSubmitted,
            'results_submitted' => $resultsSubmitted,
            'verified_results' => $verifiedResults,
            'disputed_results' => $disputedResults,
            'results_pending' => $resultsPending,
            'submission_coverage_percent' => $pollingUnits > 0 ? round(($resultsSubmitted / $pollingUnits) * 100, 2) : 0,
            'election_incidents' => $incidentsReported,
            'incidents_reported' => $incidentsReported,
            'incidents_resolved' => 0,
            'incidents_pending' => $incidentsReported,
            'pictures_uploaded' => $this->evidenceCount($user, PictureEvidence::class),
            'videos_uploaded' => $this->evidenceCount($user, VideoEvidence::class),
        ];
    }

    public function drilldowns(?User $user): array
    {
        return array_filter([
            'state_to_senatorial' => $this->packageVisibilityService->canSeeStateModules() && $this->packageVisibilityService->canSeeSenatorialModules()
                ? $this->stateSenatorialDrilldowns($user)
                : [],
            'state_to_federal' => $this->packageVisibilityService->canSeeStateModules() && $this->packageVisibilityService->canSeeFederalModules()
                ? $this->stateFederalDrilldowns($user)
                : [],
            'senatorial_to_lga' => $this->packageVisibilityService->canSeeSenatorialModules() && $this->packageVisibilityService->canSeeLgaModules()
                ? $this->senatorialLgaDrilldowns($user)
                : [],
            'federal_to_lga' => $this->packageVisibilityService->canSeeFederalModules() && $this->packageVisibilityService->canSeeLgaModules()
                ? $this->federalLgaDrilldowns($user)
                : [],
        ], fn (array $items) => !empty($items));
    }

    public function dashboardCardGroups(?User $user, ?array $metrics = null): array
    {
        $metrics ??= $this->forUser($user);
        $scopeType = $metrics['scope']['type'];
        $geography = $metrics['geography'];
        $coverage = $metrics['coverage'];
        $people = $metrics['people'];
        $election = $metrics['election'];
        $activity = $metrics['membership_activity'];

        return [
            'coverage' => $this->coverageCards($user, $scopeType, $geography, $coverage),
            'people' => array_filter([
                $this->metricCard('Regular Members', $people['members'] ?? 0, 'bg-primary', 'fas fa-user', 'People metric', $this->safeRoute($user?->access_level.'.regulars', $this->scopeRouteParameter($user))),
                $this->metricCard('Polling Unit Agents', $people['agents'] ?? 0, 'bg-warning', 'fas fa-user-shield', 'People metric', $this->peopleMetricUrl($user, 'agents')),
                $this->metricCard('Coordinators / Admins', $people['executives'] ?? 0, 'bg-dark', 'fas fa-id-badge', 'People metric', $this->safeRoute($user?->access_level.'.leaders', $this->scopeRouteParameter($user))),
                $this->metricCard('Eligible Voters', $people['eligible_voters'] ?? 0, 'bg-success', 'fas fa-id-card', 'Voter readiness', $this->peopleMetricUrl($user, 'eligible-voters'), $people['users'] ?? 0),
                $this->metricCard('Without Voter Card', $people['ineligible_voters'] ?? 0, 'bg-danger', 'fas fa-user-times', 'Follow-up required', $this->peopleMetricUrl($user, 'without-voter-card'), $people['users'] ?? 0),
            ]),
            'election_operations' => [
                $this->metricCard('Elections', $election['elections'] ?? 0, 'bg-primary', 'fas fa-award', 'Election metric', $this->safeRoute($user?->access_level.'.elections')),
                $this->metricCard('Total Votes', $election['votes'] ?? 0, 'bg-success', 'fas fa-poll', 'View vote intelligence', $this->electionResultUrl($user, $election)),
                $this->metricCard('Results Submitted', $election['polling_unit_results'] ?? 0, 'bg-info', 'fas fa-file-alt', 'View submitted results', $this->resultReportUrl($user, $election, ['verification_status' => 'submitted'])),
                $this->metricCard('Verified Results', $election['verified_results'] ?? 0, 'bg-success', 'fas fa-check-circle', 'View verified results', $this->resultReportUrl($user, $election, ['verification_status' => 'verified'])),
                $this->metricCard('Disputed Results', $election['disputed_results'] ?? 0, 'bg-danger', 'fas fa-flag', 'View disputed results', $this->resultReportUrl($user, $election, ['dispute_status' => 'disputed'])),
                $this->metricCard('Results Pending', $election['results_pending'] ?? 0, 'bg-warning', 'fas fa-hourglass-half', 'Open situation room', $this->electionOperationsUrl($user, ['focus' => 'pending-results'])),
                $this->metricCard('Incidents Reported', $election['election_incidents'] ?? 0, 'bg-danger', 'fas fa-exclamation-triangle', 'Open incident reports', $this->electionOperationsUrl($user, ['focus' => 'incidents'])),
                $this->metricCard('Pictures Uploaded', $election['pictures_uploaded'] ?? 0, 'bg-secondary', 'fas fa-image', 'Open evidence reports', $this->electionOperationsUrl($user, ['focus' => 'pictures'])),
                $this->metricCard('Videos Uploaded', $election['videos_uploaded'] ?? 0, 'bg-dark', 'fas fa-video', 'Open evidence reports', $this->electionOperationsUrl($user, ['focus' => 'videos'])),
            ],
            'membership_activity' => [
                $this->metricCard('New Today', $activity['new_today'] ?? 0, 'bg-danger', 'fas fa-user-plus', 'Membership activity', $this->peopleMetricUrl($user, 'new-today')),
                $this->metricCard('New This Week', $activity['new_this_week'] ?? 0, 'bg-info', 'fas fa-calendar-week', 'Membership activity', $this->peopleMetricUrl($user, 'new-this-week')),
                $this->metricCard('New This Month', $activity['new_this_month'] ?? 0, 'bg-success', 'fas fa-calendar-alt', 'Membership activity', $this->peopleMetricUrl($user, 'new-this-month')),
            ],
        ];
    }

    private function coverageCards(?User $user, string $scopeType, array $geography, array $coverage): array
    {
        $visibleKeys = match ($scopeType) {
            LocationScopeService::REGION => ['states', 'senatorial_districts', 'federal_constituencies', 'lgas', 'wards', 'polling_units'],
            LocationScopeService::STATE => ['senatorial_districts', 'federal_constituencies', 'lgas', 'wards', 'polling_units'],
            LocationScopeService::SENATORIAL,
            LocationScopeService::FEDERAL => ['lgas', 'wards', 'polling_units'],
            LocationScopeService::LGA => ['wards', 'polling_units'],
            LocationScopeService::WARD => ['polling_units'],
            LocationScopeService::POLLING_UNIT => [],
            default => ['regions', 'states', 'senatorial_districts', 'federal_constituencies', 'lgas', 'wards', 'polling_units'],
        };

        $moduleByKey = [
            'regions' => 'regional',
            'states' => 'state',
            'senatorial_districts' => 'senatorial',
            'federal_constituencies' => 'federal',
            'lgas' => 'lga',
            'wards' => 'ward',
            'polling_units' => 'polling_unit',
        ];

        $visibleKeys = array_values(array_filter(
            $visibleKeys,
            fn (string $key) => $this->packageVisibilityService->canSeeModule($moduleByKey[$key] ?? $key)
        ));

        $definitions = [
            'regions' => ['Regions', 'regions_with_users', 'regions', 'bg-danger', 'fas fa-map', $this->safeRoute($user?->access_level.'.members.region')],
            'states' => ['States', 'states_with_users', 'states', 'bg-primary', 'fas fa-map', $this->safeRoute($user?->access_level.'.members.byState', $this->scopeRouteParameter($user))],
            'senatorial_districts' => ['Senatorial Districts', 'senatorial_districts_with_users', 'senatorial_districts', 'bg-primary', 'fas fa-landmark', $this->safeRoute($user?->access_level.'.members.bySenatorialDistrict', $this->scopeRouteParameter($user))],
            'federal_constituencies' => ['Federal Constituencies', 'federal_constituencies_with_users', 'federal_constituencies', 'bg-purple', 'fas fa-university', $this->safeRoute($user?->access_level.'.members.byFederalConstituency', $this->scopeRouteParameter($user))],
            'lgas' => ['LGAs', 'lgas_with_users', 'lgas', 'bg-success', 'fas fa-layer-group', $this->safeRoute($user?->access_level.'.members.byLga', $this->scopeRouteParameter($user))],
            'wards' => ['Wards', 'wards_with_users', 'wards', 'bg-info', 'fas fa-map-marker-alt', $this->safeRoute($user?->access_level.'.members.byWard', $this->scopeRouteParameter($user))],
            'polling_units' => ['Polling Units', 'polling_units_with_users', 'polling_units', 'bg-danger', 'fas fa-vote-yea', $this->safeRoute($user?->access_level.'.members.byPu', $this->scopeRouteParameter($user))],
        ];

        return collect($visibleKeys)
            ->map(function (string $key) use ($definitions, $coverage, $geography) {
                [$label, $valueKey, $totalKey, $color, $icon, $url] = $definitions[$key];

                return $this->metricCard($label, $coverage[$valueKey] ?? 0, $color, $icon, 'Coverage', $url, $geography[$totalKey] ?? 0);
            })
            ->filter(fn (array $card) => (int) ($card['total'] ?? 0) > 0)
            ->values()
            ->all();
    }

    private function metricCard(string $label, int $value, string $color, string $icon, string $footerText, ?string $footerUrl = null, ?int $total = null): array
    {
        $key = Str::slug($label, '_');

        return compact('key', 'label', 'value', 'total', 'color', 'icon', 'footerText', 'footerUrl');
    }

    private function serializeStatCard(array $card): array
    {
        $value = $card['value'] ?? 0;
        $total = $card['total'] ?? null;
        $suffix = $card['suffix'] ?? '';
        $decimalPlaces = $card['decimalPlaces'] ?? ($suffix === '%' ? 2 : 1);
        $percent = !is_null($total) && (int) $total > 0
            ? number_format(((int) $value / (int) $total) * 100, 2)
            : '0.00';

        return [
            'key' => $card['key'] ?? Str::slug((string) ($card['label'] ?? 'metric'), '_'),
            'label' => $card['label'] ?? 'Metric',
            'value' => $value,
            'total' => $total,
            'suffix' => $suffix,
            'display_value' => is_numeric($value)
                ? (floor((float) $value) != (float) $value ? number_format((float) $value, $decimalPlaces) : number_format((int) $value))
                : $value,
            'display_total' => is_null($total) ? null : number_format((int) $total),
            'percent' => $percent,
        ];
    }

    private function newMemberCount(?User $user, Carbon $since): int
    {
        $query = User::query()
            ->where('access_level', '!=', 'superadmin')
            ->where('created_at', '>=', $since);

        $this->scopeService->applyScope($query, $user, 'users', 'users');

        return (int) $query->count();
    }

    private function countRegions(?User $user): int
    {
        if ($this->licensedScope()->applies()) {
            return $this->licensedScope()->enforcementSummary()['state_id'] ? 1 : 0;
        }

        return match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => 1,
            LocationScopeService::STATE,
            LocationScopeService::SENATORIAL,
            LocationScopeService::FEDERAL,
            LocationScopeService::LGA,
            LocationScopeService::WARD,
            LocationScopeService::POLLING_UNIT => $this->regionIdForScopedUser($user) ? 1 : 0,
            default => Region::where('name', '!=', 'No-region')->count(),
        };
    }

    private function countStates(?User $user): int
    {
        $query = State::query();

        match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => $query->where('region_id', $user?->region_id),
            LocationScopeService::STATE,
            LocationScopeService::SENATORIAL,
            LocationScopeService::FEDERAL,
            LocationScopeService::LGA,
            LocationScopeService::WARD,
            LocationScopeService::POLLING_UNIT => $this->stateIdForScopedUser($user)
                ? $query->where('id', $this->stateIdForScopedUser($user))
                : $query->whereRaw('1 = 0'),
            default => $query,
        };

        $this->licensedScope()->applyToStatesQuery($query);

        return (int) $query->count();
    }

    private function countSenatorialDistricts(?User $user): int
    {
        $query = SenatorialDistrict::query();

        return match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => (int) $this->licensedScope()->applyToSenatorialDistrictsQuery($query->whereHas('state', fn (Builder $stateQuery) => $stateQuery->where('region_id', $user?->region_id)))->count(),
            LocationScopeService::STATE => (int) $this->licensedScope()->applyToSenatorialDistrictsQuery($query->where('state_id', $user?->state_id))->count(),
            LocationScopeService::SENATORIAL => (int) $this->licensedScope()->applyToSenatorialDistrictsQuery($query->where('id', $user?->senatorial_district_id))->count(),
            LocationScopeService::FEDERAL => (int) $this->licensedScope()->applyToSenatorialDistrictsQuery($query->whereIn('id', FederalConstituency::where('id', $user?->federal_constituency_id)->select('senatorial_district_id')))->count(),
            LocationScopeService::LGA => (int) $this->licensedScope()->applyToSenatorialDistrictsQuery($query->whereIn('id', LocalGovernmentArea::where('id', $user?->lga_id)->select('senatorial_district_id')))->count(),
            LocationScopeService::WARD,
            LocationScopeService::POLLING_UNIT => $this->distinctPollingUnitBoundaryCount($user, 'senatorial_district_id'),
            default => (int) $this->licensedScope()->applyToSenatorialDistrictsQuery($query)->count(),
        };
    }

    private function countFederalConstituencies(?User $user): int
    {
        $query = FederalConstituency::query();

        return match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => (int) $this->licensedScope()->applyToFederalConstituenciesQuery($query->whereHas('state', fn (Builder $stateQuery) => $stateQuery->where('region_id', $user?->region_id)))->count(),
            LocationScopeService::STATE => (int) $this->licensedScope()->applyToFederalConstituenciesQuery($query->where('state_id', $user?->state_id))->count(),
            LocationScopeService::SENATORIAL => (int) $this->licensedScope()->applyToFederalConstituenciesQuery($query->where('senatorial_district_id', $user?->senatorial_district_id))->count(),
            LocationScopeService::FEDERAL => (int) $this->licensedScope()->applyToFederalConstituenciesQuery($query->where('id', $user?->federal_constituency_id))->count(),
            LocationScopeService::LGA => (int) $this->licensedScope()->applyToFederalConstituenciesQuery($query->whereIn('id', LocalGovernmentArea::where('id', $user?->lga_id)->select('federal_constituency_id')))->count(),
            LocationScopeService::WARD,
            LocationScopeService::POLLING_UNIT => $this->distinctPollingUnitBoundaryCount($user, 'federal_constituency_id'),
            default => (int) $this->licensedScope()->applyToFederalConstituenciesQuery($query)->count(),
        };
    }

    private function countLgas(?User $user): int
    {
        $scopeType = $this->scopeService->getScopeType($user);

        if (in_array($scopeType, [LocationScopeService::SENATORIAL, LocationScopeService::FEDERAL], true)) {
            return $this->scopedPollingUnits($user)
                ->join('wards', 'polling_units.ward_id', '=', 'wards.id')
                ->whereNotNull('wards.lga_id')
                ->distinct('wards.lga_id')
                ->count('wards.lga_id');
        }

        $query = LocalGovernmentArea::query();
        $this->scopeService->applyScope($query, $user, 'local_government_areas', 'local_government_areas');

        return (int) $query->count();
    }

    private function countWards(?User $user): int
    {
        $query = Ward::query();
        $this->scopeService->applyScope($query, $user, 'wards', 'wards');

        return (int) $query->count();
    }

    private function countPollingUnits(?User $user): int
    {
        return (int) $this->scopedPollingUnits($user)->count('polling_units.id');
    }

    private function approvedAgentAssignmentCount(?User $user): int
    {
        return (int) PollingUnitAgentAssignment::approved()
            ->whereHas('pollingUnit', function (Builder $query) use ($user) {
                $this->scopeService->applyScope($query, $user, 'polling_units', 'polling_units');
            })
            ->count();
    }

    private function scopedPollingUnits(?User $user): Builder
    {
        $query = PollingUnit::query();
        $this->scopeService->applyScope($query, $user, 'polling_units', 'polling_units');

        return $query;
    }

    private function licensedScope(): LicensedScopeQueryService
    {
        return app(LicensedScopeQueryService::class);
    }

    private function distinctUserLocationCount(?User $user, string $column): int
    {
        $query = User::query()
            ->where('access_level', '!=', 'superadmin')
            ->whereNotNull($column);

        $this->scopeService->applyScope($query, $user, 'users', 'users');

        return (int) $query->distinct($column)->count($column);
    }

    private function coveredElectoralBoundaryCount(?User $user, string $column): int
    {
        $baseQuery = User::query()->where('access_level', '!=', 'superadmin');
        $this->scopeService->applyScope($baseQuery, $user, 'users', 'users');

        $directIds = (clone $baseQuery)
            ->whereNotNull("users.{$column}")
            ->distinct()
            ->pluck("users.{$column}");

        $lgaIds = (clone $baseQuery)
            ->join('local_government_areas as coverage_lgas', 'users.lga_id', '=', 'coverage_lgas.id')
            ->whereNotNull("coverage_lgas.{$column}")
            ->distinct()
            ->pluck("coverage_lgas.{$column}");

        $wardIds = (clone $baseQuery)
            ->join('wards as coverage_wards', 'users.ward_id', '=', 'coverage_wards.id')
            ->join('local_government_areas as coverage_ward_lgas', 'coverage_wards.lga_id', '=', 'coverage_ward_lgas.id')
            ->whereNotNull("coverage_ward_lgas.{$column}")
            ->distinct()
            ->pluck("coverage_ward_lgas.{$column}");

        $pollingUnitIds = (clone $baseQuery)
            ->join('polling_units as coverage_polling_units', 'users.polling_unit_id', '=', 'coverage_polling_units.id')
            ->whereNotNull("coverage_polling_units.{$column}")
            ->distinct()
            ->pluck("coverage_polling_units.{$column}");

        return $directIds
            ->merge($lgaIds)
            ->merge($wardIds)
            ->merge($pollingUnitIds)
            ->filter()
            ->unique()
            ->count();
    }

    private function evidenceCount(?User $user, string $modelClass): int
    {
        $incidentIdsQuery = ElectionIncident::query()->select('election_incidents.id');
        $this->scopeService->applyScope($incidentIdsQuery, $user, 'election_incidents', 'election_incidents');

        return (int) $modelClass::query()
            ->whereIn('election_incident_id', $incidentIdsQuery)
            ->count();
    }

    private function distinctPollingUnitBoundaryCount(?User $user, string $column): int
    {
        return (int) $this->scopedPollingUnits($user)
            ->whereNotNull("polling_units.{$column}")
            ->distinct("polling_units.{$column}")
            ->count("polling_units.{$column}");
    }

    private function stateSenatorialDrilldowns(?User $user): array
    {
        $query = SenatorialDistrict::query()->with('state')->orderBy('name');
        $scopeType = $this->scopeService->getScopeType($user);

        if ($scopeType === LocationScopeService::REGION) {
            $query->whereHas('state', fn (Builder $stateQuery) => $stateQuery->where('region_id', $user?->region_id));
        } elseif ($scopeType === LocationScopeService::STATE) {
            $query->where('state_id', $user?->state_id);
        } elseif ($scopeType === LocationScopeService::SENATORIAL) {
            $query->where('id', $user?->senatorial_district_id);
        } elseif (!in_array($scopeType, [LocationScopeService::NATIONAL], true)) {
            return [];
        }

        $this->licensedScope()->applyToSenatorialDistrictsQuery($query);

        return $query->limit(12)->get()->map(fn (SenatorialDistrict $district) => [
            'label' => $district->name,
            'context' => $district->state?->name,
            'url' => null,
        ])->all();
    }

    private function stateFederalDrilldowns(?User $user): array
    {
        $query = FederalConstituency::query()->with('state')->orderBy('name');
        $scopeType = $this->scopeService->getScopeType($user);

        if ($scopeType === LocationScopeService::REGION) {
            $query->whereHas('state', fn (Builder $stateQuery) => $stateQuery->where('region_id', $user?->region_id));
        } elseif ($scopeType === LocationScopeService::STATE) {
            $query->where('state_id', $user?->state_id);
        } elseif ($scopeType === LocationScopeService::SENATORIAL) {
            $query->where('senatorial_district_id', $user?->senatorial_district_id);
        } elseif ($scopeType === LocationScopeService::FEDERAL) {
            $query->where('id', $user?->federal_constituency_id);
        } elseif (!in_array($scopeType, [LocationScopeService::NATIONAL], true)) {
            return [];
        }

        $this->licensedScope()->applyToFederalConstituenciesQuery($query);

        return $query->limit(12)->get()->map(fn (FederalConstituency $constituency) => [
            'label' => $constituency->name,
            'context' => $constituency->state?->name,
            'url' => null,
        ])->all();
    }

    private function senatorialLgaDrilldowns(?User $user): array
    {
        if (!$user?->senatorial_district_id && $this->scopeService->getScopeType($user) === LocationScopeService::SENATORIAL) {
            return [];
        }

        $query = LocalGovernmentArea::query()->with('state')->orderBy('name');

        if ($this->scopeService->getScopeType($user) === LocationScopeService::SENATORIAL) {
            $query->where('senatorial_district_id', $user->senatorial_district_id);
        } else {
            $this->scopeService->applyScope($query, $user, 'local_government_areas', 'local_government_areas');
        }

        $this->licensedScope()->applyToLgasQuery($query);

        return $query->limit(12)->get()->map(fn (LocalGovernmentArea $lga) => [
            'label' => $lga->name,
            'context' => $lga->state?->name,
            'url' => null,
        ])->all();
    }

    private function federalLgaDrilldowns(?User $user): array
    {
        if (!$user?->federal_constituency_id && $this->scopeService->getScopeType($user) === LocationScopeService::FEDERAL) {
            return [];
        }

        $query = LocalGovernmentArea::query()->with('state')->orderBy('name');

        if ($this->scopeService->getScopeType($user) === LocationScopeService::FEDERAL) {
            $query->where('federal_constituency_id', $user->federal_constituency_id);
        } else {
            $this->scopeService->applyScope($query, $user, 'local_government_areas', 'local_government_areas');
        }

        $this->licensedScope()->applyToLgasQuery($query);

        return $query->limit(12)->get()->map(fn (LocalGovernmentArea $lga) => [
            'label' => $lga->name,
            'context' => $lga->state?->name,
            'url' => null,
        ])->all();
    }

    private function scopeLabel(?User $user): string
    {
        return match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => optional($user?->region)->name ?? 'Regional Scope',
            LocationScopeService::STATE => optional($user?->state)->name ?? 'State Scope',
            LocationScopeService::SENATORIAL => optional($user?->senatorialDistrict)->name ?? 'Senatorial District Scope',
            LocationScopeService::FEDERAL => optional($user?->federalConstituency)->name ?? 'Federal Constituency Scope',
            LocationScopeService::LGA => optional($user?->lga)->name ?? 'LGA',
            LocationScopeService::WARD => optional($user?->ward)->name ?? 'Ward',
            LocationScopeService::POLLING_UNIT => optional($user?->pollingUnit)->name ?? 'Polling Unit',
            default => app(CampaignPackageUiService::class)->dashboardScopeTitle(),
        };
    }

    private function dashboardTitle(?User $user): string
    {
        $label = strtoupper($this->scopeLabel($user));

        return match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::NATIONAL => app(CampaignPackageUiService::class)->statisticsTitle(),
            LocationScopeService::FEDERAL => "{$label} FEDERAL CONSTITUENCY",
            default => "{$label} STATISTICS",
        };
    }

    private function safeRoute(?string $name, mixed $parameter = null): ?string
    {
        if (!$name || !Route::has($name)) {
            return null;
        }

        if ($parameter === null) {
            return route($name);
        }

        return route($name, $parameter);
    }

    private function resultReportUrl(?User $user, array $election, array $query = []): ?string
    {
        $uuid = $election['active_election']['uuid'] ?? null;
        $routeName = $user?->access_level.'.election.votesByPu';

        if (!$uuid || !Route::has($routeName)) {
            return null;
        }

        $url = route($routeName, $uuid);

        return $query ? $url.'?'.http_build_query($query) : $url;
    }

    private function electionResultUrl(?User $user, array $election): ?string
    {
        $uuid = $election['active_election']['uuid'] ?? null;
        $routeName = $user?->access_level.'.election.results';

        if (!$uuid || !Route::has($routeName)) {
            return $this->resultReportUrl($user, $election);
        }

        return route($routeName, $uuid);
    }

    private function electionOperationsUrl(?User $user, array $query = []): ?string
    {
        $routeName = $user?->access_level.'.election.operations';

        if (!Route::has($routeName)) {
            return null;
        }

        $url = route($routeName);

        return $query ? $url.'?'.http_build_query($query) : $url;
    }

    private function peopleMetricUrl(?User $user, string $metric): ?string
    {
        $routeName = $user?->access_level.'.peopleMetric';

        if (!Route::has($routeName)) {
            return null;
        }

        return route($routeName, array_filter([
            'metric' => $metric,
            'uuid' => $this->scopeRouteParameter($user),
        ]));
    }

    private function scopeRouteParameter(?User $user): ?string
    {
        return match ($this->scopeService->getScopeType($user)) {
            LocationScopeService::REGION => $user?->region?->uuid,
            LocationScopeService::STATE => $user?->state?->uuid,
            LocationScopeService::SENATORIAL => $user?->senatorialDistrict?->uuid,
            LocationScopeService::FEDERAL => $user?->federalConstituency?->uuid,
            LocationScopeService::LGA => $user?->lga?->uuid,
            LocationScopeService::WARD => $user?->ward?->uuid,
            LocationScopeService::POLLING_UNIT => $user?->pollingUnit?->uuid,
            default => null,
        };
    }

    private function regionIdForScopedUser(?User $user): ?int
    {
        return $user?->region_id
            ?? $user?->state?->region_id
            ?? $user?->senatorialDistrict?->state?->region_id
            ?? $user?->federalConstituency?->state?->region_id
            ?? $user?->lga?->state?->region_id
            ?? $user?->ward?->localGovernmentArea?->state?->region_id
            ?? $user?->pollingUnit?->ward?->localGovernmentArea?->state?->region_id;
    }

    private function stateIdForScopedUser(?User $user): ?int
    {
        return $user?->state_id
            ?? $user?->senatorialDistrict?->state_id
            ?? $user?->federalConstituency?->state_id
            ?? $user?->lga?->state_id
            ?? $user?->ward?->localGovernmentArea?->state_id
            ?? $user?->pollingUnit?->ward?->localGovernmentArea?->state_id;
    }
}
