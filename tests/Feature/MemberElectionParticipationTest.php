<?php

use App\Models\Country;
use App\Models\Election;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\LocalLicense;
use App\Models\PoliticalParty;
use App\Models\PollingUnit;
use App\Models\PollingUnitAgentAssignment;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use App\Services\MemberCapabilityService;
use App\Services\MemberElectionParticipationService;
use App\Services\PollingUnitResultPermissionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

function participationTree(string $suffix = 'main'): array
{
    $country = Country::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Country '.$suffix]);
    $region = Region::query()->create(['name' => 'Region '.$suffix, 'country_id' => $country->id]);
    $state = State::query()->create(['name' => 'State '.$suffix, 'region_id' => $region->id]);
    $senatorial = SenatorialDistrict::query()->create(['state_id' => $state->id, 'name' => 'Senate '.$suffix, 'normalized_name' => 'senate-'.$suffix]);
    $federal = FederalConstituency::query()->create([
        'state_id' => $state->id,
        'senatorial_district_id' => $senatorial->id,
        'name' => 'Federal '.$suffix,
        'normalized_name' => 'federal-'.$suffix,
    ]);
    $lga = LocalGovernmentArea::query()->create([
        'name' => 'LGA '.$suffix,
        'state_id' => $state->id,
        'senatorial_district_id' => $senatorial->id,
        'federal_constituency_id' => $federal->id,
    ]);
    $ward = Ward::query()->create(['name' => 'Ward '.$suffix, 'lga_id' => $lga->id]);
    $pollingUnit = PollingUnit::query()->create([
        'name' => 'PU '.$suffix,
        'ward_id' => $ward->id,
        'senatorial_district_id' => $senatorial->id,
        'federal_constituency_id' => $federal->id,
    ]);

    return compact('country', 'region', 'state', 'senatorial', 'federal', 'lga', 'ward', 'pollingUnit');
}

function participationMember(array $attributes = []): User
{
    return User::query()->create(array_merge([
        'uuid' => (string) Str::uuid(),
        'username' => 'agent-'.Str::lower(Str::random(8)),
        'email' => Str::lower(Str::random(8)).'@example.test',
        'phone' => '+234'.random_int(4000000000, 4999999999),
        'password' => Hash::make('Password1'),
        'access_level' => 'user',
        'status' => 'active',
    ], $attributes));
}

function approvedParticipationAssignment(User $member, PollingUnit $pollingUnit): PollingUnitAgentAssignment
{
    return PollingUnitAgentAssignment::query()->create([
        'user_id' => $member->id,
        'polling_unit_id' => $pollingUnit->id,
        'registered_polling_unit_id' => $pollingUnit->id,
        'status' => PollingUnitAgentAssignment::STATUS_APPROVED,
        'source' => PollingUnitAgentAssignment::SOURCE_ADMIN_APPOINTMENT,
        'assignment_type' => PollingUnitAgentAssignment::ASSIGNMENT_REGISTERED_POLLING_UNIT,
        'workflow_stage' => PollingUnitAgentAssignment::STAGE_APPROVED,
        'identity_verification_status' => PollingUnitAgentAssignment::IDENTITY_VERIFIED,
        'approved_at' => now(),
    ]);
}

function participationElection(): array
{
    $partyA = PoliticalParty::query()->create(['name' => 'Party A', 'acronym' => 'PA']);
    $partyB = PoliticalParty::query()->create(['name' => 'Party B', 'acronym' => 'PB']);
    $election = Election::query()->create([
        'party_id' => $partyA->id,
        'name' => 'Test Election',
        'year' => today(),
        'status' => 'ongoing',
    ]);

    return compact('partyA', 'partyB', 'election');
}

test('only an approved assignment enables member election submission', function () {
    $tree = participationTree('capability');
    $member = participationMember(['polling_unit_id' => $tree['pollingUnit']->id]);
    $license = LocalLicense::query()->create([
        'license_key' => 'CM-PARTICIPATION',
        'deployment_mode' => 'self_hosted',
        'package_type' => 'governorship',
        'status' => 'active',
    ]);
    $license->modules()->create(['module_key' => 'agents', 'enabled' => true]);
    $license->modules()->create(['module_key' => 'elections', 'enabled' => true]);
    $capabilities = app(MemberCapabilityService::class);

    expect($capabilities->allows($member, MemberCapabilityService::ELECTION_REPORTS))->toBeTrue()
        ->and($capabilities->allows($member, MemberCapabilityService::ELECTION_SUBMISSION))->toBeFalse();

    $assignment = approvedParticipationAssignment($member, $tree['pollingUnit']);
    expect($capabilities->allows($member, MemberCapabilityService::ELECTION_SUBMISSION))->toBeTrue();

    $assignment->update(['status' => PollingUnitAgentAssignment::STATUS_SUSPENDED]);
    expect($capabilities->allows($member, MemberCapabilityService::ELECTION_SUBMISSION))->toBeFalse();
});

test('approved agent submits and amends a pending result using canonical assignment location', function () {
    $tree = participationTree('result');
    $member = participationMember(['polling_unit_id' => $tree['pollingUnit']->id]);
    $assignment = approvedParticipationAssignment($member, $tree['pollingUnit']);
    ['partyA' => $partyA, 'partyB' => $partyB, 'election' => $election] = participationElection();
    $service = app(MemberElectionParticipationService::class);

    $result = $service->submitResult($member, $election, $assignment, [
        $partyA->id => 120,
        $partyB->id => 80,
    ]);

    expect($result->submitted_by)->toBe($member->id)
        ->and($result->polling_unit_id)->toBe($tree['pollingUnit']->id)
        ->and($result->state_id)->toBe($tree['state']->id)
        ->and($result->senatorial_district_id)->toBe($tree['senatorial']->id)
        ->and($result->federal_constituency_id)->toBe($tree['federal']->id)
        ->and($result->votes->pluck('quantity', 'party_id')->all())->toBe([$partyA->id => 120, $partyB->id => 80]);

    $amended = $service->submitResult($member, $election, $assignment, [
        $partyA->id => 125,
        $partyB->id => 75,
    ]);

    expect($amended->id)->toBe($result->id)
        ->and($amended->votes->pluck('quantity', 'party_id')->all())->toBe([$partyA->id => 125, $partyB->id => 75]);
});

test('verified result cannot be amended by its submitting agent', function () {
    $tree = participationTree('locked');
    $member = participationMember(['polling_unit_id' => $tree['pollingUnit']->id]);
    $assignment = approvedParticipationAssignment($member, $tree['pollingUnit']);
    ['partyA' => $party, 'election' => $election] = participationElection();
    $service = app(MemberElectionParticipationService::class);
    $result = $service->submitResult($member, $election, $assignment, [$party->id => 10]);
    $result->update(['verification_status' => 'verified', 'result_status' => 'verified']);

    expect(app(PollingUnitResultPermissionService::class)->canEditPollingUnitResult($member, $result->fresh()))->toBeFalse()
        ->and(fn () => $service->submitResult($member, $election, $assignment, [$party->id => 11]))
        ->toThrow(HttpException::class);
});

test('approved agent incident is locked to assigned polling unit location', function () {
    $tree = participationTree('incident');
    $member = participationMember(['polling_unit_id' => $tree['pollingUnit']->id]);
    $assignment = approvedParticipationAssignment($member, $tree['pollingUnit']);
    ['election' => $election] = participationElection();

    $incident = app(MemberElectionParticipationService::class)->reportIncident($member, $election, $assignment, [
        'incident_type' => 'intimidation',
        'severity' => 'high',
        'remarks' => 'Voters were prevented from entering.',
    ]);

    expect($incident->agent_id)->toBe($member->id)
        ->and($incident->polling_unit_id)->toBe($tree['pollingUnit']->id)
        ->and($incident->state_id)->toBe($tree['state']->id)
        ->and($incident->lga_id)->toBe($tree['lga']->id)
        ->and($incident->ward_id)->toBe($tree['ward']->id);
});

test('member election routes distinguish reports from approved agent submissions', function () {
    expect(Route::getRoutes()->getByName('user.elections')->gatherMiddleware())
        ->toContain('module.enabled:elections', 'member.capability:elections.reports')
        ->and(Route::getRoutes()->getByName('user.elections.workspace')->gatherMiddleware())
        ->toContain('module.enabled:elections', 'member.capability:elections.submit')
        ->and(Route::getRoutes()->getByName('user.elections.result.store')->gatherMiddleware())
        ->toContain('member.capability:elections.submit')
        ->and(Route::getRoutes()->getByName('election.report.print')->gatherMiddleware())
        ->toContain('election.report.access')
        ->and(Route::getRoutes()->getByName('election.result.verify')->gatherMiddleware())
        ->toContain('access_level:superadmin,nationaladmin,regionaladmin,stateadmin,senatorialadmin,federaladmin,lgaadmin,wardadmin');
});
