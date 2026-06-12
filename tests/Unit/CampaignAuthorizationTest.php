<?php

use App\Http\Controllers\ElectionController;
use App\Http\Controllers\MembersController;
use App\Models\FederalConstituency;
use App\Models\PollingUnitResult;
use App\Models\SenatorialDistrict;
use App\Models\User;
use App\Models\Vote;
use App\Services\LocationScopeService;
use App\Services\PollingUnitResultPermissionService;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class);

class TestableMembersController extends MembersController
{
    public bool $scopeAllowsMember = true;

    public function allowsUserDeletion(User $user): bool
    {
        return $this->canDeleteUsers($user);
    }

    public function rankFor(?string $accessLevel): ?int
    {
        return $this->accessLevelRank($accessLevel);
    }

    public function allowsMemberAccess(User $actor, User $member): bool
    {
        return $this->canAccessMember($actor, $member);
    }

    public function allowsMemberEdit(User $actor, User $member): bool
    {
        return $this->canEditMember($actor, $member);
    }

    protected function memberIsWithinScope(User $profileData, User $member): bool
    {
        return $this->scopeAllowsMember;
    }

    public function memberListQueryFor(User $actor): Builder
    {
        return $this->scopedMembersQuery($actor);
    }
}

class TestableElectionController extends ElectionController
{
    public bool $scopeAllowsPollingUnit = false;

    public function allowsElectionCreation(User $user): bool
    {
        return $this->canCreateElections($user);
    }

    public function allowsResultUpload(User $user, ?int $pollingUnitId = null): bool
    {
        return $this->canUploadPollingUnitResults($user, $pollingUnitId);
    }

    protected function pollingUnitIsWithinScope(User $profileData, ?int $pollingUnitId): bool
    {
        return $this->scopeAllowsPollingUnit;
    }
}

function campaignAuthUser(string $accessLevel, array $roles = [], ?int $pollingUnitId = null): User
{
    $user = new User([
        'access_level' => $accessLevel,
        'polling_unit_id' => $pollingUnitId,
    ]);

    $user->setRelation('roles', collect(array_map(function (string $roleName) {
        $role = new Role();
        $role->name = $roleName;

        return $role;
    }, $roles)));

    return $user;
}

function bindCampaignUploadAssignments(array $allowedAssignments): void
{
    app()->instance(PollingUnitResultPermissionService::class, new class($allowedAssignments) extends PollingUnitResultPermissionService {
        public function __construct(private array $allowedAssignments)
        {
            parent::__construct(new LocationScopeService());
        }

        public function userHasApprovedAssignmentForPollingUnit(?User $user, ?int $pollingUnitId): bool
        {
            return in_array($user?->access_level.':'.$pollingUnitId, $this->allowedAssignments, true);
        }

        public function firstApprovedPollingUnitId(?User $user): ?int
        {
            foreach ($this->allowedAssignments as $assignment) {
                [$accessLevel, $pollingUnitId] = explode(':', $assignment);

                if ($user?->access_level === $accessLevel) {
                    return (int) $pollingUnitId;
                }
            }

            return null;
        }
    });
}

test('only super admin can delete users', function () {
    $controller = new TestableMembersController();

    expect($controller->allowsUserDeletion(campaignAuthUser('superadmin')))->toBeTrue()
        ->and($controller->allowsUserDeletion(campaignAuthUser('nationaladmin', ['National ICT Director'])))->toBeFalse()
        ->and($controller->allowsUserDeletion(campaignAuthUser('stateadmin', ['State ICT Director'])))->toBeFalse()
        ->and($controller->allowsUserDeletion(campaignAuthUser('wardadmin')))->toBeFalse();
});

test('member access hierarchy includes senatorial and federal admins', function () {
    $controller = new TestableMembersController();

    expect($controller->rankFor('senatorialadmin'))->toBe(5)
        ->and($controller->rankFor('federaladmin'))->toBe(5);
});

test('super admin can view and edit senatorial and federal admins', function () {
    $controller = new TestableMembersController();
    $superAdmin = campaignAuthUser('superadmin');

    expect($controller->allowsMemberAccess($superAdmin, campaignAuthUser('senatorialadmin')))->toBeTrue()
        ->and($controller->allowsMemberEdit($superAdmin, campaignAuthUser('senatorialadmin')))->toBeTrue()
        ->and($controller->allowsMemberAccess($superAdmin, campaignAuthUser('federaladmin')))->toBeTrue()
        ->and($controller->allowsMemberEdit($superAdmin, campaignAuthUser('federaladmin')))->toBeTrue();
});

test('national ict director can view and edit lower access members', function () {
    $controller = new TestableMembersController();
    $nationalIct = campaignAuthUser('nationaladmin', ['National ICT Director']);

    expect($controller->allowsMemberAccess($nationalIct, campaignAuthUser('senatorialadmin')))->toBeTrue()
        ->and($controller->allowsMemberEdit($nationalIct, campaignAuthUser('federaladmin')))->toBeTrue();
});

test('non ict admin can view but not edit lower access members', function () {
    $controller = new TestableMembersController();
    $stateAdmin = campaignAuthUser('stateadmin');

    expect($controller->allowsMemberAccess($stateAdmin, campaignAuthUser('lgaadmin')))->toBeTrue()
        ->and($controller->allowsMemberEdit($stateAdmin, campaignAuthUser('lgaadmin')))->toBeFalse();
});

test('senatorial and federal member list queries enforce parent state consistency', function () {
    $controller = new TestableMembersController();

    $district = new SenatorialDistrict(['id' => 19, 'state_id' => 4]);
    $senatorialAdmin = campaignAuthUser('senatorialadmin');
    $senatorialAdmin->senatorial_district_id = 19;
    $senatorialAdmin->setRelation('senatorialDistrict', $district);

    $senatorialBindings = $controller->memberListQueryFor($senatorialAdmin)->getQuery()->getBindings();

    expect($senatorialBindings)->toContain(19)
        ->and($senatorialBindings)->toContain(4);

    $constituency = new FederalConstituency(['id' => 33, 'state_id' => 7, 'senatorial_district_id' => 21]);
    $federalAdmin = campaignAuthUser('federaladmin');
    $federalAdmin->federal_constituency_id = 33;
    $federalAdmin->setRelation('federalConstituency', $constituency);

    $federalBindings = $controller->memberListQueryFor($federalAdmin)->getQuery()->getBindings();

    expect($federalBindings)->toContain(33)
        ->and($federalBindings)->toContain(7)
        ->and($federalBindings)->toContain(21);
});

test('only super admin can create elections', function () {
    $controller = new TestableElectionController();

    expect($controller->allowsElectionCreation(campaignAuthUser('superadmin')))->toBeTrue()
        ->and($controller->allowsElectionCreation(campaignAuthUser('nationaladmin')))->toBeFalse()
        ->and($controller->allowsElectionCreation(campaignAuthUser('nationaladmin', ['National ICT Director'])))->toBeFalse()
        ->and($controller->allowsElectionCreation(campaignAuthUser('stateadmin', ['State ICT Director'])))->toBeFalse();
});

test('polling unit agents can upload only for their assigned polling unit', function () {
    bindCampaignUploadAssignments(['user:10']);
    $controller = new TestableElectionController();
    $agent = campaignAuthUser('user', ['Pu Agent'], 10);

    expect($controller->allowsResultUpload($agent, 10))->toBeTrue()
        ->and($controller->allowsResultUpload($agent, 11))->toBeFalse();
});

test('polling unit admins can upload only for their assigned polling unit', function () {
    bindCampaignUploadAssignments(['puadmin:25']);
    $controller = new TestableElectionController();
    $pollingUnitAdmin = campaignAuthUser('puadmin', [], 25);

    expect($controller->allowsResultUpload($pollingUnitAdmin, 25))->toBeTrue()
        ->and($controller->allowsResultUpload($pollingUnitAdmin, 26))->toBeFalse();
});

test('super admin cannot upload polling unit results without approved agent assignment', function () {
    bindCampaignUploadAssignments([]);
    $controller = new TestableElectionController();

    expect($controller->allowsResultUpload(campaignAuthUser('superadmin'), 25))->toBeFalse();
});

test('ict directors cannot upload by title without an approved polling unit agent assignment', function () {
    bindCampaignUploadAssignments([]);
    $controller = new TestableElectionController();
    $ictDirector = campaignAuthUser('stateadmin', ['State ICT Director']);

    $controller->scopeAllowsPollingUnit = true;
    expect($controller->allowsResultUpload($ictDirector, 30))->toBeFalse();
});

test('senatorial and federal ict directors need approved polling unit agent assignments to upload', function () {
    bindCampaignUploadAssignments(['senatorialadmin:40', 'federaladmin:41']);
    $controller = new TestableElectionController();

    $controller->scopeAllowsPollingUnit = true;
    expect($controller->allowsResultUpload(campaignAuthUser('senatorialadmin', ['Senatorial ICT Director']), 40))->toBeTrue()
        ->and($controller->allowsResultUpload(campaignAuthUser('federaladmin', ['Federal Constituency ICT Director']), 41))->toBeTrue();

    $controller->scopeAllowsPollingUnit = false;
    expect($controller->allowsResultUpload(campaignAuthUser('senatorialadmin', ['Senatorial ICT Director']), 42))->toBeFalse()
        ->and($controller->allowsResultUpload(campaignAuthUser('federaladmin', ['Federal Constituency ICT Director']), 43))->toBeFalse();
});

test('non ict non agent users cannot upload polling unit results', function () {
    bindCampaignUploadAssignments([]);
    $controller = new TestableElectionController();

    expect($controller->allowsResultUpload(campaignAuthUser('user', [], 10), 10))->toBeFalse()
        ->and($controller->allowsResultUpload(campaignAuthUser('stateadmin', [], 10), 10))->toBeFalse();
});

test('election location scope filters votes and polling unit results by admin boundary', function () {
    $scopeService = new LocationScopeService();

    $senatorialAdmin = campaignAuthUser('senatorialadmin');
    $senatorialAdmin->senatorial_district_id = 19;
    $senatorialVoteQuery = Vote::query();
    $senatorialResultQuery = PollingUnitResult::query();
    $scopeService->applyScope($senatorialVoteQuery, $senatorialAdmin, 'votes', 'votes');
    $scopeService->applyScope($senatorialResultQuery, $senatorialAdmin, 'polling_unit_results', 'polling_unit_results');

    expect($senatorialVoteQuery->getQuery()->getBindings())->toContain(19)
        ->and($senatorialResultQuery->getQuery()->getBindings())->toContain(19);

    $federalAdmin = campaignAuthUser('federaladmin');
    $federalAdmin->federal_constituency_id = 33;
    $federalVoteQuery = Vote::query();
    $federalResultQuery = PollingUnitResult::query();
    $scopeService->applyScope($federalVoteQuery, $federalAdmin, 'votes', 'votes');
    $scopeService->applyScope($federalResultQuery, $federalAdmin, 'polling_unit_results', 'polling_unit_results');

    expect($federalVoteQuery->getQuery()->getBindings())->toContain(33)
        ->and($federalResultQuery->getQuery()->getBindings())->toContain(33);
});

test('election statistics section renders when election cards are available', function () {
    $html = view('backend.shared.dashboard-election-stat-section', [
        'dashboardMetrics' => [
            'card_groups' => [
                'election_operations' => [
                    [
                        'key' => 'results_submitted',
                        'label' => 'Results Submitted',
                        'value' => 2,
                        'total' => null,
                        'color' => 'bg-info',
                        'icon' => 'fas fa-file-alt',
                        'footerText' => 'View submitted results',
                        'footerUrl' => null,
                    ],
                ],
            ],
        ],
    ])->render();

    expect($html)->toContain('Election Statistics')
        ->and($html)->toContain('Results Submitted')
        ->and($html)->toContain('data-dashboard-stat-group="election_operations"');
});

test('election statistics section renders navigation links for linked metric cards', function () {
    $html = view('backend.shared.dashboard-election-stat-section', [
        'dashboardMetrics' => [
            'card_groups' => [
                'election_operations' => [
                    [
                        'key' => 'total_votes',
                        'label' => 'Total Votes',
                        'value' => 2500,
                        'total' => null,
                        'color' => 'bg-success',
                        'icon' => 'fas fa-poll',
                        'footerText' => 'View vote intelligence',
                        'footerUrl' => '/scope/election/result/active-election',
                    ],
                    [
                        'key' => 'results_pending',
                        'label' => 'Results Pending',
                        'value' => 12,
                        'total' => null,
                        'color' => 'bg-warning',
                        'icon' => 'fas fa-hourglass-half',
                        'footerText' => 'Open situation room',
                        'footerUrl' => '/scope/election/operations-center?focus=pending-results',
                    ],
                    [
                        'key' => 'incidents_reported',
                        'label' => 'Incidents Reported',
                        'value' => 4,
                        'total' => null,
                        'color' => 'bg-danger',
                        'icon' => 'fas fa-exclamation-triangle',
                        'footerText' => 'Open incident reports',
                        'footerUrl' => '/scope/election/operations-center?focus=incidents',
                    ],
                ],
            ],
        ],
    ])->render();

    expect($html)->toContain('href="/scope/election/result/active-election"')
        ->and($html)->toContain('href="/scope/election/operations-center?focus=pending-results"')
        ->and($html)->toContain('href="/scope/election/operations-center?focus=incidents"');
});
