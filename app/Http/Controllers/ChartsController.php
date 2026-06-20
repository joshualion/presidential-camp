<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\Election;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PoliticalParty;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\Religion;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\Vote;
use App\Models\Ward;
use App\Services\CampaignDashboardChartService;
use App\Services\LicensedScopeQueryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ChartsController extends Controller
{


    public function __construct()
    {

        $pageTitle = 'Admin Dashboard';
        View::share('pageTitle', $pageTitle);
    }

    // Function to get the current User profile data
    private function getProfileData()
    {
        $id = Auth::user()->id;
        return User::find($id);
    }

    private function licensedScope(): LicensedScopeQueryService
    {
        return app(LicensedScopeQueryService::class);
    }

    private function chartService(): CampaignDashboardChartService
    {
        return app(CampaignDashboardChartService::class);
    }

    private function userQuery()
    {
        $query = User::where('access_level', '!=', 'superadmin');
        $this->licensedScope()->applyToUsersQuery($query);

        return $query;
    }

    private function regionsQuery()
    {
        $query = Region::query();
        $this->licensedScope()->applyToRegionsQuery($query);

        return $query;
    }

    private function statesQuery()
    {
        $query = State::query();
        $this->licensedScope()->applyToStatesQuery($query);

        return $query;
    }

    //Function to get the system country
    private function getCountries()
    {
         // Retrieve the value of the default_country setting
         $defaultCountry = SystemSetting::find(1);

        // Retrieve the ID of the default country
        return Country::where('name', $defaultCountry->system_country)->first();
    }

    //Function to retrieves regions from set country
    public function getRegions()
    {
        $country_id = $this->getCountries()->id;
        $country = Country::where('id',$country_id)->first();

        if($country->name == 'Nigeria'){
            $region = Region::where('country_id', $country_id)->where('name', '!=', 'No-region')->get();
        }else{
        $region = Region::where('country_id', $country_id)->get();
        }


        return($region);
    }

    public function NationalGenderDistribution()
    {
        return response()->json($this->chartService()->gender($this->getProfileData()));
    }

    public function NationalAgeDistribution()
    {
        return response()->json($this->chartService()->age($this->getProfileData()));
    }

    public function NationalRegionDistribution()
    {
        return response()->json($this->chartService()->regions($this->getProfileData()));
    }

    public function NationalReligionDistribution()
    {
        return response()->json($this->chartService()->religion($this->getProfileData()));
    }

    public function NationalVoterDistribution()
    {
        return response()->json($this->chartService()->voter($this->getProfileData()));
    }

    public function NationalStateDistribution()
    {
        return response()->json($this->chartService()->packageGeography($this->getProfileData()));
    }

    public function GenderDistributionByRegion($uuid)
    {
        $region = Region::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfRegionNotAllowed($region->id);

        return response()->json($this->chartService()->gender($this->getProfileData(), ['type' => 'region', 'id' => $region->id]));
    }

    public function GenderDistributionByState($uuid)
    {
        $state = State::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfStateNotAllowed($state->id);

        return response()->json($this->chartService()->gender($this->getProfileData(), ['type' => 'state', 'id' => $state->id]));
    }

    public function GenderDistributionByLga($uuid)
    {
        $lga = LocalGovernmentArea::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfLgaNotAllowed($lga->id);

        return response()->json($this->chartService()->gender($this->getProfileData(), ['type' => 'lga', 'id' => $lga->id]));
    }

    public function GenderDistributionBySenatorialDistrict($uuid)
    {
        $district = SenatorialDistrict::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfSenatorialDistrictNotAllowed($district->id);

        return response()->json($this->chartService()->gender($this->getProfileData(), ['type' => 'senatorial', 'id' => $district->id]));
    }

    public function GenderDistributionByFederalConstituency($uuid)
    {
        $constituency = FederalConstituency::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfFederalConstituencyNotAllowed($constituency->id);

        return response()->json($this->chartService()->gender($this->getProfileData(), ['type' => 'federal', 'id' => $constituency->id]));
    }

    public function GenderDistributionByWard($uuid)
    {
        $ward = Ward::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfWardNotAllowed($ward->id);

        return response()->json($this->chartService()->gender($this->getProfileData(), ['type' => 'ward', 'id' => $ward->id]));
    }

    public function GenderDistributionByPu($uuid)
    {
        $pu = PollingUnit::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfPollingUnitNotAllowed($pu->id);

        return response()->json($this->chartService()->gender($this->getProfileData(), ['type' => 'polling_unit', 'id' => $pu->id]));
    }



    // function member Distribution By Age
    public function AgeDistributionByRegion($uuid)
    {
        $region = Region::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfRegionNotAllowed($region->id);

        return response()->json($this->chartService()->age($this->getProfileData(), ['type' => 'region', 'id' => $region->id]));
    }

    public function AgeDistributionByState($uuid)
    {
        $state = State::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfStateNotAllowed($state->id);

        return response()->json($this->chartService()->age($this->getProfileData(), ['type' => 'state', 'id' => $state->id]));
    }

    public function AgeDistributionByLga($uuid)
    {
        $lga = LocalGovernmentArea::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfLgaNotAllowed($lga->id);

        return response()->json($this->chartService()->age($this->getProfileData(), ['type' => 'lga', 'id' => $lga->id]));
    }

    public function AgeDistributionBySenatorialDistrict($uuid)
    {
        $district = SenatorialDistrict::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfSenatorialDistrictNotAllowed($district->id);

        return response()->json($this->chartService()->age($this->getProfileData(), ['type' => 'senatorial', 'id' => $district->id]));
    }

    public function AgeDistributionByFederalConstituency($uuid)
    {
        $constituency = FederalConstituency::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfFederalConstituencyNotAllowed($constituency->id);

        return response()->json($this->chartService()->age($this->getProfileData(), ['type' => 'federal', 'id' => $constituency->id]));
    }

       //function member Distribution By Eligible Voters
    public function VoterDistributionByRegion($uuid)
    {
           $region = Region::where('uuid', $uuid)->firstOrFail();
           $this->licensedScope()->abortIfRegionNotAllowed($region->id);

           return response()->json($this->chartService()->voter($this->getProfileData(), ['type' => 'region', 'id' => $region->id]));
    }

    public function VoterDistributionByState($uuid)
    {
        $state = State::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfStateNotAllowed($state->id);

        return response()->json($this->chartService()->voter($this->getProfileData(), ['type' => 'state', 'id' => $state->id]));
    }

    public function VoterDistributionByLga($uuid)
    {
        $lga = LocalGovernmentArea::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfLgaNotAllowed($lga->id);

        return response()->json($this->chartService()->voter($this->getProfileData(), ['type' => 'lga', 'id' => $lga->id]));
    }

    public function VoterDistributionBySenatorialDistrict($uuid)
    {
        $district = SenatorialDistrict::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfSenatorialDistrictNotAllowed($district->id);

        return response()->json($this->chartService()->voter($this->getProfileData(), ['type' => 'senatorial', 'id' => $district->id]));
    }

    public function VoterDistributionByFederalConstituency($uuid)
    {
        $constituency = FederalConstituency::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfFederalConstituencyNotAllowed($constituency->id);

        return response()->json($this->chartService()->voter($this->getProfileData(), ['type' => 'federal', 'id' => $constituency->id]));
    }

    public function VoterDistributionByWard($uuid)
    {
        $ward = Ward::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfWardNotAllowed($ward->id);

        return response()->json($this->chartService()->voter($this->getProfileData(), ['type' => 'ward', 'id' => $ward->id]));
    }

    public function VoterDistributionByPu($uuid)
    {
        $pu = PollingUnit::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfPollingUnitNotAllowed($pu->id);

        return response()->json($this->chartService()->voter($this->getProfileData(), ['type' => 'polling_unit', 'id' => $pu->id]));
    }

      //function member Distribution By State
    public function StateDistributionByRegion($uuid)
    {
          $region = Region::where('uuid', $uuid)->firstOrFail();
          $this->licensedScope()->abortIfRegionNotAllowed($region->id);

          return response()->json($this->chartService()->states($this->getProfileData(), ['type' => 'region', 'id' => $region->id]));
    }

    public function lgaDistributionByState($uuid)
    {
        $state = State::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfStateNotAllowed($state->id);

        return response()->json($this->chartService()->lgas($this->getProfileData(), ['type' => 'state', 'id' => $state->id]));
    }

    public function lgaDistributionBySenatorialDistrict($uuid)
    {
        $district = SenatorialDistrict::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfSenatorialDistrictNotAllowed($district->id);

        return response()->json($this->chartService()->lgas($this->getProfileData(), ['type' => 'senatorial_district', 'id' => $district->id]));
    }

    public function lgaDistributionByFederalConstituency($uuid)
    {
        $constituency = FederalConstituency::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfFederalConstituencyNotAllowed($constituency->id);

        return response()->json($this->chartService()->lgas($this->getProfileData(), ['type' => 'federal_constituency', 'id' => $constituency->id]));
    }

    public function wardDistributionByLga($uuid)
    {
        $lga = LocalGovernmentArea::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfLgaNotAllowed($lga->id);

        return response()->json($this->chartService()->wards($this->getProfileData(), ['type' => 'lga', 'id' => $lga->id]));
    }


    //Election Results Charts

    public function getElectionResultChartData($uuid)
    {
        // Fetch the election based on the UUID
        $election = Election::where('uuid', $uuid)->firstOrFail();
        $profileData = $this->getProfileData();

        // Query the votes grouped by party
        $voteQuery = Vote::query()
            ->select('party_id', DB::raw('SUM(quantity) as total_votes'))
            ->where('election_id', $election->id)
            ->groupBy('party_id');
        $this->licensedScope()->applyToVotesQuery($voteQuery);

        // Apply regional constraints based on user's access level
        switch ($profileData->access_level) {
            case 'regionaladmin':
                $voteQuery->where('region_id', $profileData->region_id);
                break;
            case 'stateadmin':
                $voteQuery->where('state_id', $profileData->state_id);
                break;
            case 'lgaadmin':
                $voteQuery->where('lga_id', $profileData->lga_id);
                break;
            case 'wardadmin':
                $voteQuery->where('ward_id', $profileData->ward_id);
                break;
            case 'pollingunitadmin':
                $voteQuery->where('polling_unit_id', $profileData->polling_unit_id);
                break;
            default:
                // Superadmin and Nationaladmin have no additional constraints
                break;
        }

        $results = $voteQuery->get();

        // Transform the data for Chart.js
        $chartData = $results->map(function ($result) {
            $party = PoliticalParty::find($result->party_id);

            return [
                'party' => $party ? "{$party->name} ({$party->acronym})" : 'Unknown Party',
                'total_votes' => $result->total_votes,
            ];
        });

        return response()->json($chartData);
    }
}
