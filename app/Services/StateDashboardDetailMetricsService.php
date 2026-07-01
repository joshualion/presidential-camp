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
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\VideoEvidence;
use App\Models\Vote;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class StateDashboardDetailMetricsService
{
    public function forState(State $state, ?User $viewer): array
    {
        $coverage = $this->coverage($state);
        $people = $this->people($state);
        $activity = $this->membershipActivity($state);
        $election = $this->election($state, $coverage['polling_units_total']);

        return [
            'scope' => [
                'type' => LocationScopeService::STATE,
                'id' => $state->id,
                'label' => $state->name,
                'title' => strtoupper($state->name).' STATISTICS',
            ],
            'card_groups' => [
                'coverage' => [
                    $this->card('Senatorial Districts', $coverage['senatorial_districts_with_users'], 'bg-primary', 'fas fa-landmark', 'More info', $this->route($viewer?->access_level.'.members.bySenatorialDistrict', $state->uuid), $coverage['senatorial_districts_total'], 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                    $this->card('Federal Constituencies', $coverage['federal_constituencies_with_users'], 'bg-purple', 'fas fa-university', 'More info', $this->route($viewer?->access_level.'.members.byFederalConstituency', $state->uuid), $coverage['federal_constituencies_total'], 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                    $this->card('LGAs', $coverage['lgas_with_users'], 'bg-success', 'fas fa-layer-group', 'More info', $this->route($viewer?->access_level.'.members.byLga', $state->uuid), $coverage['lgas_total'], 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                    $this->card('Wards', $coverage['wards_with_users'], 'bg-info', 'fas fa-map-marker-alt', 'More info', $this->route($viewer?->access_level.'.members.byWard', $state->uuid), $coverage['wards_total'], 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                    $this->card('Polling Units', $coverage['polling_units_with_users'], 'bg-danger', 'fas fa-vote-yea', 'More info', $this->route($viewer?->access_level.'.members.byPu', $state->uuid), $coverage['polling_units_total'], 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                ],
                'people' => [
                    $this->card('Regular Members', $people['members'], 'bg-primary', 'fas fa-user', 'People metric', $this->route($viewer?->access_level.'.member.state.view', $state->uuid), null, 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                    $this->card('Polling Unit Agents', $people['agents'], 'bg-warning', 'fas fa-user-shield', 'People metric', $this->peopleMetricUrl($viewer, 'agents', $state->uuid), null, 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                    $this->card('Coordinators / Admins', $people['executives'], 'bg-dark', 'fas fa-id-badge', 'People metric', $this->route($viewer?->access_level.'.leaders', $state->uuid), null, 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                    $this->card('Eligible Voters', $people['eligible_voters'], 'bg-success', 'fas fa-id-card', 'Voter readiness', $this->peopleMetricUrl($viewer, 'eligible-voters', $state->uuid), $people['users'], 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                    $this->card('Without Voter Card', $people['ineligible_voters'], 'bg-danger', 'fas fa-user-times', 'Follow-up required', $this->peopleMetricUrl($viewer, 'without-voter-card', $state->uuid), $people['users'], 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                ],
                'membership_activity' => [
                    $this->card('New Today', $activity['new_today'], 'bg-danger', 'fas fa-user-plus', 'Membership activity', $this->peopleMetricUrl($viewer, 'new-today', $state->uuid), null, 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                    $this->card('New This Week', $activity['new_this_week'], 'bg-info', 'fas fa-calendar-week', 'Membership activity', $this->peopleMetricUrl($viewer, 'new-this-week', $state->uuid), null, 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                    $this->card('New This Month', $activity['new_this_month'], 'bg-success', 'fas fa-calendar-alt', 'Membership activity', $this->peopleMetricUrl($viewer, 'new-this-month', $state->uuid), null, 'col-xl-3 col-md-6 col-12 d-flex mb-3'),
                ],
                'election_operations' => [
                    $this->card('Elections', $election['elections'], 'bg-primary', 'fas fa-award', 'Election metric', $this->route($viewer?->access_level.'.elections'), null, 'col-lg-4 col-md-6 col-12 d-flex mb-3 px-2'),
                    $this->card('Total Votes', $election['votes'], 'bg-success', 'fas fa-poll', 'View vote intelligence', $this->electionResultUrl($viewer, $election), null, 'col-lg-4 col-md-6 col-12 d-flex mb-3 px-2'),
                    $this->card('Results Submitted', $election['results_submitted'], 'bg-info', 'fas fa-file-alt', 'View submitted results', $this->resultReportUrl($viewer, $election, ['state_id' => $state->id, 'verification_status' => 'submitted']), null, 'col-lg-4 col-md-6 col-12 d-flex mb-3 px-2'),
                    $this->card('Verified Results', $election['verified_results'], 'bg-success', 'fas fa-check-circle', 'View verified results', $this->resultReportUrl($viewer, $election, ['state_id' => $state->id, 'verification_status' => 'verified']), null, 'col-lg-4 col-md-6 col-12 d-flex mb-3 px-2'),
                    $this->card('Disputed Results', $election['disputed_results'], 'bg-danger', 'fas fa-flag', 'View disputed results', $this->resultReportUrl($viewer, $election, ['state_id' => $state->id, 'dispute_status' => 'disputed']), null, 'col-lg-4 col-md-6 col-12 d-flex mb-3 px-2'),
                    $this->card('Results Pending', $election['results_pending'], 'bg-warning', 'fas fa-hourglass-half', 'Open situation room', $this->electionOperationsUrl($viewer, ['focus' => 'pending-results', 'state_id' => $state->id]), null, 'col-lg-4 col-md-6 col-12 d-flex mb-3 px-2'),
                    $this->card('Incidents Reported', $election['incidents_reported'], 'bg-danger', 'fas fa-exclamation-triangle', 'Open incident reports', $this->electionOperationsUrl($viewer, ['focus' => 'incidents', 'state_id' => $state->id]), null, 'col-lg-4 col-md-6 col-12 d-flex mb-3 px-2'),
                    $this->card('Pictures Uploaded', $election['pictures_uploaded'], 'bg-secondary', 'fas fa-image', 'Open evidence reports', $this->electionOperationsUrl($viewer, ['focus' => 'pictures', 'state_id' => $state->id]), null, 'col-lg-4 col-md-6 col-12 d-flex mb-3 px-2'),
                    $this->card('Videos Uploaded', $election['videos_uploaded'], 'bg-dark', 'fas fa-video', 'Open evidence reports', $this->electionOperationsUrl($viewer, ['focus' => 'videos', 'state_id' => $state->id]), null, 'col-lg-4 col-md-6 col-12 d-flex mb-3 px-2'),
                ],
            ],
        ];
    }

    private function coverage(State $state): array
    {
        $lgaIds = LocalGovernmentArea::where('state_id', $state->id)->pluck('id');
        $wardIds = Ward::whereIn('lga_id', $lgaIds)->pluck('id');

        return [
            'senatorial_districts_total' => SenatorialDistrict::where('state_id', $state->id)->count(),
            'senatorial_districts_with_users' => User::where('state_id', $state->id)->where('access_level', '!=', 'superadmin')->whereNotNull('senatorial_district_id')->distinct('senatorial_district_id')->count('senatorial_district_id'),
            'federal_constituencies_total' => FederalConstituency::where('state_id', $state->id)->count(),
            'federal_constituencies_with_users' => User::where('state_id', $state->id)->where('access_level', '!=', 'superadmin')->whereNotNull('federal_constituency_id')->distinct('federal_constituency_id')->count('federal_constituency_id'),
            'lgas_total' => $lgaIds->count(),
            'lgas_with_users' => User::where('state_id', $state->id)->where('access_level', '!=', 'superadmin')->whereNotNull('lga_id')->distinct('lga_id')->count('lga_id'),
            'wards_total' => $wardIds->count(),
            'wards_with_users' => User::where('state_id', $state->id)->where('access_level', '!=', 'superadmin')->whereNotNull('ward_id')->distinct('ward_id')->count('ward_id'),
            'polling_units_total' => PollingUnit::whereIn('ward_id', $wardIds)->count(),
            'polling_units_with_users' => User::where('state_id', $state->id)->where('access_level', '!=', 'superadmin')->whereNotNull('polling_unit_id')->distinct('polling_unit_id')->count('polling_unit_id'),
        ];
    }

    private function people(State $state): array
    {
        $usersQuery = User::where('state_id', $state->id)->where('access_level', '!=', 'superadmin');
        $users = (clone $usersQuery)->count();
        $eligible = (clone $usersQuery)->whereIn('validVoter', ['yes', 'Yes', 'YES'])->count();
        $executives = (clone $usersQuery)
            ->where('access_level', '!=', 'puadmin')
            ->whereDoesntHave('roles', function (Builder $roleQuery) {
                $roleQuery->where('name', 'like', '%agent%');
            })
            ->where(function (Builder $query) {
                $query->where('access_level', '!=', 'user')
                    ->orWhereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', '!=', 'Member'));
            })
            ->count();

        return [
            'users' => $users,
            'members' => (clone $usersQuery)->where('access_level', 'user')->count(),
            'eligible_voters' => $eligible,
            'ineligible_voters' => max(0, $users - $eligible),
            'agents' => PollingUnitAgentAssignment::approved()
                ->whereHas('pollingUnit.ward.localGovernmentArea', fn (Builder $query) => $query->where('state_id', $state->id))
                ->count(),
            'executives' => $executives,
        ];
    }

    private function membershipActivity(State $state): array
    {
        return [
            'new_today' => $this->newMemberCount($state, Carbon::now()->subDay()),
            'new_this_week' => $this->newMemberCount($state, Carbon::now()->subWeek()),
            'new_this_month' => $this->newMemberCount($state, Carbon::now()->subMonth()),
        ];
    }

    private function election(State $state, int $pollingUnitCount): array
    {
        $activeElection = Election::where('status', 'ongoing')
            ->orWhereDate('year', now()->toDateString())
            ->latest('updated_at')
            ->first();

        $votesQuery = Vote::where('state_id', $state->id);
        $resultsQuery = PollingUnitResult::where('state_id', $state->id);
        $incidentsQuery = ElectionIncident::where('state_id', $state->id);

        if ($activeElection) {
            $votesQuery->where('election_id', $activeElection->id);
            $resultsQuery->where('election_id', $activeElection->id);
            $incidentsQuery->where('election_id', $activeElection->id);
        }

        $resultsSubmitted = (clone $resultsQuery)->distinct()->count('polling_unit_id');
        $incidentIds = (clone $incidentsQuery)->select('id');

        return [
            'active_election' => $activeElection ? ['uuid' => $activeElection->uuid] : null,
            'elections' => Election::count(),
            'votes' => (clone $votesQuery)->sum('quantity'),
            'results_submitted' => $resultsSubmitted,
            'verified_results' => (clone $resultsQuery)->where('verification_status', 'verified')->distinct()->count('polling_unit_id'),
            'disputed_results' => (clone $resultsQuery)->where('dispute_status', 'disputed')->distinct()->count('polling_unit_id'),
            'results_pending' => max(0, $pollingUnitCount - $resultsSubmitted),
            'incidents_reported' => (clone $incidentsQuery)->count(),
            'pictures_uploaded' => PictureEvidence::whereIn('election_incident_id', clone $incidentIds)->count(),
            'videos_uploaded' => VideoEvidence::whereIn('election_incident_id', clone $incidentIds)->count(),
        ];
    }

    private function newMemberCount(State $state, Carbon $since): int
    {
        return User::where('state_id', $state->id)
            ->where('access_level', '!=', 'superadmin')
            ->where('created_at', '>=', $since)
            ->count();
    }

    private function card(string $label, int|float $value, string $color, string $icon, string $footerText, ?string $footerUrl = null, ?int $total = null, ?string $columnClass = null): array
    {
        return [
            'key' => Str::slug($label, '_'),
            'label' => $label,
            'value' => (int) $value,
            'total' => $total,
            'color' => $color,
            'icon' => $icon,
            'footerText' => $footerText,
            'footerUrl' => $footerUrl,
            'columnClass' => $columnClass,
        ];
    }

    private function route(?string $name, mixed $parameter = null): ?string
    {
        if (!$name || !Route::has($name)) {
            return null;
        }

        return $parameter === null ? route($name) : route($name, $parameter);
    }

    private function peopleMetricUrl(?User $viewer, string $metric, string $uuid): ?string
    {
        $routeName = $viewer?->access_level.'.peopleMetric';

        if (!Route::has($routeName)) {
            return null;
        }

        return route($routeName, [
            'metric' => $metric,
            'uuid' => $uuid,
        ]);
    }

    private function resultReportUrl(?User $viewer, array $election, array $query): ?string
    {
        $uuid = $election['active_election']['uuid'] ?? null;
        $routeName = $viewer?->access_level.'.election.votesByPu';

        if (!$uuid || !Route::has($routeName)) {
            return null;
        }

        return route($routeName, $uuid).'?'.http_build_query($query);
    }

    private function electionResultUrl(?User $viewer, array $election): ?string
    {
        $uuid = $election['active_election']['uuid'] ?? null;
        $routeName = $viewer?->access_level.'.election.results';

        if (!$uuid || !Route::has($routeName)) {
            return null;
        }

        return route($routeName, $uuid);
    }

    private function electionOperationsUrl(?User $viewer, array $query = []): ?string
    {
        $routeName = $viewer?->access_level.'.election.operations';

        if (!Route::has($routeName)) {
            return null;
        }

        $url = route($routeName);

        return $query ? $url.'?'.http_build_query($query) : $url;
    }
}
