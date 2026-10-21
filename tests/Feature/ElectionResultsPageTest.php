<?php

use App\Models\Election;
use App\Models\Country;
use App\Models\Region;
use App\Models\State;
use App\Models\SenatorialDistrict;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\Ward;
use App\Models\PollingUnit;
use App\Models\PollingUnitResult;
use App\Models\Vote;
use App\Models\PoliticalParty;
use App\Models\SupportGroup;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function electionResultsLocation(string $suffix): array
{
    $country = Country::create(['uuid' => (string) Str::uuid(), 'name' => 'Country '.$suffix]);
    $region = Region::create(['name' => 'Region '.$suffix, 'country_id' => $country->id]);
    $state = State::create(['name' => 'State '.$suffix, 'region_id' => $region->id]);
    $senatorial = SenatorialDistrict::create(['state_id' => $state->id, 'name' => 'Senate '.$suffix, 'normalized_name' => 'senate-'.$suffix]);
    $federal = FederalConstituency::create(['state_id' => $state->id, 'senatorial_district_id' => $senatorial->id, 'name' => 'Federal '.$suffix, 'normalized_name' => 'federal-'.$suffix]);
    $lga = LocalGovernmentArea::create(['state_id' => $state->id, 'senatorial_district_id' => $senatorial->id, 'federal_constituency_id' => $federal->id, 'name' => 'LGA '.$suffix]);
    $ward = Ward::create(['lga_id' => $lga->id, 'name' => 'Ward '.$suffix]);
    $pu = PollingUnit::create(['ward_id' => $ward->id, 'senatorial_district_id' => $senatorial->id, 'federal_constituency_id' => $federal->id, 'name' => 'PU '.$suffix]);

    return ['region_id' => $region->id, 'state_id' => $state->id, 'senatorial_district_id' => $senatorial->id, 'federal_constituency_id' => $federal->id, 'lga_id' => $lga->id, 'ward_id' => $ward->id, 'polling_unit_id' => $pu->id];
}

function electionResultsSubmission(Election $election, User $admin, array $location, int $quantity, string $partyName): void
{
    $result = PollingUnitResult::create(array_merge($location, ['election_id' => $election->id, 'submitted_by' => $admin->id, 'submitted_at' => now(), 'verification_status' => 'pending']));
    $party = PoliticalParty::create(['name' => $partyName, 'acronym' => Str::upper(Str::random(6))]);
    Vote::create(array_merge($location, ['election_id' => $election->id, 'party_id' => $party->id, 'agent_id' => $admin->id, 'polling_unit_result_id' => $result->id, 'quantity' => $quantity]));
}

dataset('election result administrators', ['superadmin', 'nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin']);
dataset('scoped election result administrators', ['regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin']);

beforeEach(function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Election Results Test']);
    $party = PoliticalParty::create(['name' => 'Results Test Party', 'acronym' => 'RTP']);
    $this->resultsElection = Election::create(['party_id' => $party->id, 'name' => 'New Election', 'year' => today(), 'status' => 'ongoing']);
    $this->resultsAdmin = User::factory()->create(['access_level' => 'superadmin', 'email' => 'results-admin@campaign.test', 'password' => Hash::make('results-admin-test-password')]);
    SupportGroup::create(['name' => 'Election Volunteers'])->users()->attach($this->resultsAdmin->id);
    $this->resultsLocation = electionResultsLocation('home');
    // Country remains optional for a national administrator, as in existing installations.
    $this->resultsAdmin->update($this->resultsLocation);
});

test('a new election renders an empty results page for Super Admin', function () {
    $this->withoutExceptionHandling();
    $this->actingAs($this->resultsAdmin)->get(route('superadmin.election.results', $this->resultsElection->uuid))
        ->assertOk()->assertSee('No results have been received yet.');
});

test('all administrator levels render empty results and empty chart data', function (string $level) {
    $this->resultsAdmin->update(['access_level' => $level]);
    $this->actingAs($this->resultsAdmin)->get(route($level.'.election.results', $this->resultsElection->uuid))
        ->assertOk()->assertSee('No results have been received yet.')
        ->assertSee('No party votes have been recorded yet.')
        ->assertSee('Results Summary Ranking')
        ->assertViewHas('summaryStats', fn ($stats) => $stats['total_valid_votes'] === 0 && $stats['submitted_polling_units'] === 0 && $stats['results_received_percent'] == 0);
    $this->get(route('election.chart.data', $this->resultsElection->uuid))->assertOk()->assertJsonPath('labels', [])->assertJsonPath('datasets.0.data', []);
})->with('election result administrators');

test('all administrator levels show received results normally', function (string $level) {
    $this->resultsAdmin->update(['access_level' => $level]);
    electionResultsSubmission($this->resultsElection, $this->resultsAdmin, $this->resultsLocation, 125, 'Received Results Party');
    $this->actingAs($this->resultsAdmin)->get(route($level.'.election.results', $this->resultsElection->uuid))
        ->assertOk()->assertSee('Received Results Party')->assertSee('125')
        ->assertDontSee('No results have been received yet.')
        ->assertViewHas('summaryStats', fn ($stats) => $stats['total_valid_votes'] === 125 && $stats['submitted_polling_units'] === 1 && $stats['results_received_percent'] == 100);
    $this->get(route('election.chart.data', $this->resultsElection->uuid))->assertOk()->assertJsonPath('datasets.0.data', [125]);
})->with('election result administrators');

test('a submitted zero vote result is not described as an unreceived result', function (string $level) {
    $this->resultsAdmin->update(['access_level' => $level]);
    electionResultsSubmission($this->resultsElection, $this->resultsAdmin, $this->resultsLocation, 0, 'Zero Vote Party');
    $this->actingAs($this->resultsAdmin)->get(route($level.'.election.results', $this->resultsElection->uuid))
        ->assertOk()->assertDontSee('No results have been received yet.')->assertSee('No party votes have been recorded yet.')
        ->assertViewHas('summaryStats', fn ($stats) => $stats['total_valid_votes'] === 0 && $stats['submitted_polling_units'] === 1);
})->with('election result administrators');

test('scoped administrators cannot see results or charts outside their geography', function (string $level) {
    $this->resultsAdmin->update(['access_level' => $level]);
    electionResultsSubmission($this->resultsElection, $this->resultsAdmin, electionResultsLocation('outside'), 9123, 'Outside Scope Party');
    $this->actingAs($this->resultsAdmin)->get(route($level.'.election.results', $this->resultsElection->uuid))
        ->assertOk()->assertSee('No results have been received yet.')->assertDontSee('Outside Scope Party')
        ->assertViewHas('summaryStats', fn ($stats) => $stats['total_valid_votes'] === 0 && $stats['submitted_polling_units'] === 0);
    $this->get(route('election.chart.data', $this->resultsElection->uuid))->assertOk()->assertJsonPath('datasets.0.data', []);
})->with('scoped election result administrators');

test('unknown election results return 404', function () {
    $this->actingAs($this->resultsAdmin)->get(route('superadmin.election.results', (string) Str::uuid()))->assertNotFound();
});

test('a polling unit administrator cannot open a Super Admin results route', function () {
    $this->resultsAdmin->update(['access_level' => 'puadmin']);
    $this->actingAs($this->resultsAdmin)->get(route('superadmin.election.results', $this->resultsElection->uuid))->assertForbidden();
});

test('national administrators can view an election before any polling units exist', function (string $level) {
    $this->resultsAdmin->update(['access_level' => $level, 'polling_unit_id' => null]);
    PollingUnit::query()->delete();
    $this->actingAs($this->resultsAdmin)->get(route($level.'.election.results', $this->resultsElection->uuid))
        ->assertOk()->assertSee('No results have been received yet.')
        ->assertViewHas('summaryStats', fn ($stats) => $stats['total_polling_units'] === 0 && $stats['results_received_percent'] == 0);
})->with(['superadmin', 'nationaladmin']);
