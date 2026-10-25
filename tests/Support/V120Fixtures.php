<?php

use App\Models\Country;
use App\Models\Region;
use App\Models\State;
use App\Models\SenatorialDistrict;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\Ward;
use App\Models\PollingUnit;
use App\Models\PollingUnitAgentAssignment;
use App\Models\User;
use App\Models\PoliticalParty;
use App\Models\Election;
use Illuminate\Support\Str;

function v120Location(string $suffix): array
{
    $country = Country::create(['uuid' => (string) Str::uuid(), 'name' => 'Country '.$suffix]);
    $region = Region::create(['name' => 'Region '.$suffix, 'country_id' => $country->id]);
    $state = State::create(['name' => 'State '.$suffix, 'region_id' => $region->id]);
    $senatorial = SenatorialDistrict::create(['name' => 'Senate '.$suffix, 'normalized_name' => 'senate-'.$suffix, 'state_id' => $state->id]);
    $federal = FederalConstituency::create(['name' => 'Federal '.$suffix, 'normalized_name' => 'federal-'.$suffix, 'state_id' => $state->id, 'senatorial_district_id' => $senatorial->id]);
    $lga = LocalGovernmentArea::create(['name' => 'LGA '.$suffix, 'state_id' => $state->id, 'senatorial_district_id' => $senatorial->id, 'federal_constituency_id' => $federal->id]);
    $ward = Ward::create(['name' => 'Ward '.$suffix, 'lga_id' => $lga->id]);
    $pu = PollingUnit::create(['name' => 'PU '.$suffix, 'ward_id' => $ward->id, 'senatorial_district_id' => $senatorial->id, 'federal_constituency_id' => $federal->id]);

    return ['country_id' => $country->id, 'region_id' => $region->id, 'state_id' => $state->id,
        'senatorial_district_id' => $senatorial->id, 'federal_constituency_id' => $federal->id,
        'lga_id' => $lga->id, 'ward_id' => $ward->id, 'polling_unit_id' => $pu->id];
}

function v120Assignment(User $user, int $pu): PollingUnitAgentAssignment
{
    return PollingUnitAgentAssignment::create(['user_id' => $user->id, 'polling_unit_id' => $pu, 'registered_polling_unit_id' => $pu,
        'status' => PollingUnitAgentAssignment::STATUS_APPROVED, 'source' => PollingUnitAgentAssignment::SOURCE_ADMIN_APPOINTMENT,
        'assignment_type' => PollingUnitAgentAssignment::ASSIGNMENT_REGISTERED_POLLING_UNIT,
        'workflow_stage' => PollingUnitAgentAssignment::STAGE_APPROVED,
        'identity_verification_status' => PollingUnitAgentAssignment::IDENTITY_VERIFIED, 'approved_at' => now()]);
}

function v120Election(): Election
{
    $party = PoliticalParty::create(['name' => 'Test Party', 'acronym' => 'TP']);
    return Election::create(['party_id' => $party->id, 'name' => 'Test Election', 'year' => today(), 'status' => 'ongoing']);
}
