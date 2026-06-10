<?php

namespace App\Http\Controllers\nationaladmin;

use App\Http\Controllers\Controller;
use App\Services\LocationDashboardMetricsService;
use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\Religion;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;

class NationalAdminController extends Controller
{
    public function __construct()
    {
        View::share('pageTitle', 'National Admin');
    }

    private function getProfileData()
    {
        return User::find(Auth::user()->id);
    }

    private function getCountries()
    {
        $defaultCountry = SystemSetting::find(1);

        return Country::where('name', $defaultCountry->system_country)->first();
    }

    public function getRegions()
    {
        $country = $this->getCountries();

        if (!$country) {
            return collect();
        }

        if($country->name == 'Nigeria'){
            return Region::where('country_id', $country->id)->where('name', '!=', 'No-region')->get();
        }

        return Region::where('country_id', $country->id)->get();
    }

    public function nationaladminLogout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function nationalAdminDashBoard()
    {
        $profileData = $this->getProfileData();
        $dashboardMetrics = app(LocationDashboardMetricsService::class)->forUser($profileData);
        $geography = $dashboardMetrics['geography'];
        $coverage = $dashboardMetrics['coverage'];
        $people = $dashboardMetrics['people'];
        $election = $dashboardMetrics['election'];
        $uuid = null;

        $regionsCount = $geography['regions'];
        $regionsWithUsersCount = $coverage['regions_with_users'];
        $stateCount = $geography['states'];
        $statesWithUsersCount = $coverage['states_with_users'];
        $senatorialDistrictCount = $geography['senatorial_districts'];
        $senatorialDistrictsWithUsersCount = $coverage['senatorial_districts_with_users'];
        $federalConstituencyCount = $geography['federal_constituencies'];
        $federalConstituenciesWithUsersCount = $coverage['federal_constituencies_with_users'];
        $lgaCount = $geography['lgas'];
        $lgaWithUsersCount = $coverage['lgas_with_users'];
        $wardCount = $geography['wards'];
        $wardWithUsersCount = $coverage['wards_with_users'];
        $puCount = $geography['polling_units'];
        $puWithUsersCount = $coverage['polling_units_with_users'];
        $excoCount = $people['executives'];
        $coordinatorCount = $people['coordinators'];
        $memberCount = $people['members'];
        $userCount = $people['users'];
        $totalVotes = $election['votes'];
        $resultsSubmittedCount = $election['polling_unit_results'];
        $incidentsReportedCount = $election['election_incidents'];

        return view('backend.nationaladmin.dashboard', compact(
            'profileData',
            'dashboardMetrics',
            'uuid',
            'regionsCount',
            'regionsWithUsersCount',
            'stateCount',
            'statesWithUsersCount',
            'senatorialDistrictCount',
            'senatorialDistrictsWithUsersCount',
            'federalConstituencyCount',
            'federalConstituenciesWithUsersCount',
            'lgaCount',
            'lgaWithUsersCount',
            'wardCount',
            'wardWithUsersCount',
            'puCount',
            'puWithUsersCount',
            'excoCount',
            'coordinatorCount',
            'memberCount',
            'userCount',
            'totalVotes',
            'resultsSubmittedCount',
            'incidentsReportedCount'
        ));
    }

    public function nationaladminProfile()
    {
        $profileData = $this->getProfileData();
        $pageTitle = 'Profile';
        $roles = Role::all();
        $countries = Country::all();
        $regions = $this->getRegions();
        $states = State::all();
        $lgas = LocalGovernmentArea::all();
        $wards = Ward::all();
        $pollingUnits = PollingUnit::all();
        $religions = Religion::all();
        $ageGrades = AgeGrade::all();
        $userSupportGroups = $profileData->supportGroups()->pluck('name')->toArray();

        return view('backend.nationaladmin.profile', compact(
            'profileData',
            'userSupportGroups',
            'pageTitle',
            'roles',
            'countries',
            'regions',
            'states',
            'lgas',
            'wards',
            'pollingUnits',
            'religions',
            'ageGrades'
        ));
    }

    public function nationaladminProfileStore(Request $request)
    {
        $profileData = $this->getProfileData();
        $profileData->update($request->except(['_token', '_method']));

        return redirect()->route('nationaladmin.profile')->with([
            'message' => 'Profile updated successfully',
            'alert-type' => 'success',
        ]);
    }

    public function nationaladminChangePassword()
    {
        $profileData = $this->getProfileData();

        return view('backend.nationaladmin.change_password', compact('profileData'));
    }

    public function nationaladminUpdatePassword()
    {
        return redirect()->route('nationaladmin.change.password')->with([
            'message' => 'Password update is not implemented in this package.',
            'alert-type' => 'warning',
        ]);
    }

    public function nationaladminGenderDistribution()
    {
        return abort(404);
    }

    public function nationaladminAgeDistribution()
    {
        return abort(404);
    }

    public function nationaladminRegionDistribution()
    {
        return abort(404);
    }

    public function nationaladminReligionDistribution()
    {
        return abort(404);
    }

    public function nationaladminVoterDistribution()
    {
        return abort(404);
    }

    public function nationaladminStateDistribution()
    {
        return abort(404);
    }

    public function RegionDashBoard($uuid)
    {
        $pageTitle = 'Region Dashboard';
        $profileData = $this->getProfileData();
        $region = Region::where('uuid', $uuid)->first();

        if(!$region){
            return redirect()->back()->with('error', 'Region not found');
        }

        $stateCount = State::where('region_id', $region->id)->count();
        $statesWithUsersCount = User::where('region_id', $region->id)->whereNotNull('state_id')->distinct('state_id')->count('state_id');
        $states = State::where('region_id', $region->id)->pluck('id');
        $lgaCount = LocalGovernmentArea::whereIn('state_id', $states)->count();
        $lgaWithUsersCount = User::where('region_id', $region->id)->whereNotNull('lga_id')->distinct('lga_id')->count('lga_id');
        $lgas = LocalGovernmentArea::whereIn('state_id', $states)->pluck('id');
        $wardCount = Ward::whereIn('lga_id', $lgas)->count();
        $wardWithUsersCount = User::where('region_id', $region->id)->whereNotNull('ward_id')->distinct('ward_id')->count('ward_id');
        $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');
        $puCount = PollingUnit::whereIn('ward_id', $wards)->count();
        $puWithUsersCount = User::where('region_id', $region->id)->whereNotNull('polling_unit_id')->distinct('polling_unit_id')->count('polling_unit_id');
        $excoCount = User::where('region_id', $region->id)->where('access_level', '!=', 'user')->where('access_level', '!=', 'superadmin')->count();
        $memberCount = User::where('region_id', $region->id)->where('access_level', '!=', 'superadmin')->where('access_level', 'user')->count();
        $userCount = User::where('region_id', $region->id)->where('access_level', '!=', 'superadmin')->count();
        $newMembers = User::where('region_id', $region->id)->where('access_level', '!=', 'superadmin')->where('created_at', '>=', Carbon::now()->subDays(1))->count();

        return view('backend.'.$profileData->access_level.'.region.dashboard',
            compact('profileData', 'pageTitle', 'region', 'stateCount', 'statesWithUsersCount', 'lgaCount', 'lgaWithUsersCount', 'wardCount', 'wardWithUsersCount', 'puCount', 'puWithUsersCount', 'excoCount', 'memberCount', 'userCount', 'newMembers'));
    }

    public function StateDashBoard($uuid)
    {
        $pageTitle = 'State Dashboard';
        $profileData = $this->getProfileData();
        $state = State::where('uuid', $uuid)->first();

        if(!$state){
            return redirect()->back()->with('error', 'State not found');
        }

        $senatorialDistrictCount = SenatorialDistrict::where('state_id', $state->id)->count();
        $senatorialDistrictsWithUsersCount = SenatorialDistrict::where('state_id', $state->id)
            ->whereHas('users', fn ($query) => $query->where('access_level', '!=', 'superadmin'))
            ->count();
        $federalConstituencyCount = FederalConstituency::where('state_id', $state->id)->count();
        $federalConstituenciesWithUsersCount = FederalConstituency::where('state_id', $state->id)
            ->whereHas('users', fn ($query) => $query->where('access_level', '!=', 'superadmin'))
            ->count();
        $lgaCount = LocalGovernmentArea::where('state_id', $state->id)->count();
        $lgasWithUsersCount = User::whereIn('lga_id', function($query) use ($state) {
            $query->select('id')->from('local_government_areas')->where('state_id', $state->id);
        })->distinct('lga_id')->count('lga_id');
        $lgas = LocalGovernmentArea::where('state_id', $state->id)->pluck('id');
        $wardCount = Ward::whereIn('lga_id', $lgas)->count();
        $wardWithUsersCount = Ward::whereIn('lga_id', $lgas)->whereHas('users')->count();
        $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');
        $puCount = PollingUnit::whereIn('ward_id', $wards)->count();
        $puWithUsersCount = PollingUnit::whereIn('ward_id', $wards)->whereHas('users')->count();
        $excoCount = User::where('state_id', $state->id)->where('access_level', '!=', 'user')->where('access_level', '!=', 'superadmin')->count();
        $memberCount = User::where('state_id', $state->id)->where('access_level', '!=', 'superadmin')->where('access_level', 'user')->count();
        $userCount = User::where('state_id', $state->id)->where('access_level', '!=', 'superadmin')->count();
        $newMembers = User::where('state_id', $state->id)->where('access_level', '!=', 'superadmin')->where('created_at', '>=', Carbon::now()->subDays(1))->count();
        $EligibleVotersCount = User::where('state_id', $state->id)->where('access_level', '!=', 'superadmin')->where('validvoter', 'yes')->count();

        return view('backend.'.$profileData->access_level.'.state.dashboard',
            compact('profileData', 'pageTitle', 'state', 'senatorialDistrictCount', 'senatorialDistrictsWithUsersCount', 'federalConstituencyCount', 'federalConstituenciesWithUsersCount', 'lgaCount', 'lgasWithUsersCount', 'wardCount', 'wardWithUsersCount', 'puCount', 'puWithUsersCount', 'excoCount', 'memberCount', 'userCount', 'newMembers', 'EligibleVotersCount'));
    }

    public function senatorialDistrictDashBoard($uuid)
    {
        $pageTitle = 'Senatorial District Dashboard';
        $profileData = $this->getProfileData();
        $district = SenatorialDistrict::with('state')->where('uuid', $uuid)->first();

        if(!$district){
            return redirect()->back()->with('error', 'Senatorial District not found');
        }

        $lgaCount = LocalGovernmentArea::where('senatorial_district_id', $district->id)->count();
        $lgaWithUsersCount = LocalGovernmentArea::where('senatorial_district_id', $district->id)->whereHas('users')->count();
        $lgas = LocalGovernmentArea::where('senatorial_district_id', $district->id)->pluck('id');
        $wardCount = Ward::whereIn('lga_id', $lgas)->count();
        $wardWithUsersCount = Ward::whereIn('lga_id', $lgas)->whereHas('users')->count();
        $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');

        $puCount = PollingUnit::where('senatorial_district_id', $district->id)->count();
        if ($puCount === 0) {
            $puCount = PollingUnit::whereIn('ward_id', $wards)->count();
        }

        $puWithUsersCount = PollingUnit::where('senatorial_district_id', $district->id)->whereHas('users')->count();
        if ($puWithUsersCount === 0) {
            $puWithUsersCount = PollingUnit::whereIn('ward_id', $wards)->whereHas('users')->count();
        }

        $excoCount = User::where('senatorial_district_id', $district->id)
            ->where('access_level', '!=', 'user')
            ->where('access_level', '!=', 'superadmin')->count();

        $memberCount = User::where('senatorial_district_id', $district->id)
            ->where('access_level', '!=', 'superadmin')
            ->where('access_level', 'user')->count();

        $userCount = User::where('senatorial_district_id', $district->id)
            ->where('access_level', '!=', 'superadmin')->count();

        $newMembers = User::where('senatorial_district_id', $district->id)
            ->where('access_level', '!=', 'superadmin')
            ->where('created_at', '>=', Carbon::now()->subDays(1))
            ->count();

        return view('backend.shared.senatorial-district.dashboard',
            compact(
                'profileData',
                'pageTitle',
                'district',
                'lgaCount',
                'lgaWithUsersCount',
                'wardCount',
                'wardWithUsersCount',
                'puCount',
                'puWithUsersCount',
                'excoCount',
                'memberCount',
                'userCount',
                'newMembers'
            ));
    }

    public function federalConstituencyDashBoard($uuid)
    {
        $pageTitle = 'Federal Constituency Dashboard';
        $profileData = $this->getProfileData();
        $constituency = FederalConstituency::with('state')->where('uuid', $uuid)->first();

        if(!$constituency){
            return redirect()->back()->with('error', 'Federal Constituency not found');
        }

        $lgaCount = LocalGovernmentArea::where('federal_constituency_id', $constituency->id)->count();
        $lgaWithUsersCount = LocalGovernmentArea::where('federal_constituency_id', $constituency->id)->whereHas('users')->count();
        $lgas = LocalGovernmentArea::where('federal_constituency_id', $constituency->id)->pluck('id');
        $wardCount = Ward::whereIn('lga_id', $lgas)->count();
        $wardWithUsersCount = Ward::whereIn('lga_id', $lgas)->whereHas('users')->count();
        $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');

        $puCount = PollingUnit::where('federal_constituency_id', $constituency->id)->count();
        if ($puCount === 0) {
            $puCount = PollingUnit::whereIn('ward_id', $wards)->count();
        }

        $puWithUsersCount = PollingUnit::where('federal_constituency_id', $constituency->id)->whereHas('users')->count();
        if ($puWithUsersCount === 0) {
            $puWithUsersCount = PollingUnit::whereIn('ward_id', $wards)->whereHas('users')->count();
        }

        $excoCount = User::where('federal_constituency_id', $constituency->id)
            ->where('access_level', '!=', 'user')
            ->where('access_level', '!=', 'superadmin')->count();

        $memberCount = User::where('federal_constituency_id', $constituency->id)
            ->where('access_level', '!=', 'superadmin')
            ->where('access_level', 'user')->count();

        $userCount = User::where('federal_constituency_id', $constituency->id)
            ->where('access_level', '!=', 'superadmin')->count();

        $newMembers = User::where('federal_constituency_id', $constituency->id)
            ->where('access_level', '!=', 'superadmin')
            ->where('created_at', '>=', Carbon::now()->subDays(1))
            ->count();

        return view('backend.shared.federal-constituency.dashboard',
            compact(
                'profileData',
                'pageTitle',
                'constituency',
                'lgaCount',
                'lgaWithUsersCount',
                'wardCount',
                'wardWithUsersCount',
                'puCount',
                'puWithUsersCount',
                'excoCount',
                'memberCount',
                'userCount',
                'newMembers'
            ));
    }

    public function localGovernmentDashBoard($uuid)
    {
        $pageTitle = 'Local Government Dashboard';
        $profileData = $this->getProfileData();
        $lga = LocalGovernmentArea::where('uuid', $uuid)->first();

        if(!$lga){
            return redirect()->back()->with('error', 'LGA not found');
        }

        $wardCount = Ward::where('lga_id', $lga->id)->count();
        $wardWithUsersCount = Ward::where('lga_id', $lga->id)->whereHas('users')->count();
        $wards = Ward::where('lga_id', $lga->id)->pluck('id');
        $puCount = PollingUnit::whereIn('ward_id', $wards)->count();
        $puWithUsersCount = PollingUnit::whereIn('ward_id', $wards)->whereHas('users')->count();
        $excoCount = User::where('lga_id', $lga->id)->where('access_level', '!=', 'user')->where('access_level', '!=', 'superadmin')->count();
        $memberCount = User::where('lga_id', $lga->id)->where('access_level', '!=', 'superadmin')->where('access_level', 'user')->count();
        $userCount = User::where('lga_id', $lga->id)->where('access_level', '!=', 'superadmin')->count();
        $newMembers = User::where('lga_id', $lga->id)->where('access_level', '!=', 'superadmin')->where('created_at', '>=', Carbon::now()->subDays(1))->count();

        return view('backend.'.$profileData->access_level.'.lga.dashboard',
            compact('profileData', 'pageTitle', 'lga', 'wardCount', 'wardWithUsersCount', 'puCount', 'puWithUsersCount', 'excoCount', 'memberCount', 'userCount', 'newMembers'));
    }

    public function wardDashBoard($uuid)
    {
        $pageTitle = 'Ward Dashboard';
        $profileData = $this->getProfileData();
        $ward = Ward::where('uuid', $uuid)->first();

        if(!$ward){
            return redirect()->back()->with(['message' => 'Ward not found', 'alert-type' => 'error']);
        }

        $puCount = PollingUnit::where('ward_id', $ward->id)->count();
        $puWithUsersCount = PollingUnit::where('ward_id', $ward->id)->whereHas('users')->count();
        $excoCount = User::where('ward_id', $ward->id)->where('access_level', '!=', 'user')->where('access_level', '!=', 'superadmin')->count();
        $memberCount = User::where('ward_id', $ward->id)->where('access_level', '!=', 'superadmin')->where('access_level', 'user')->count();
        $eligibleVotersCount = User::where('ward_id', $ward->id)->where('access_level', '!=', 'superadmin')->where('validvoter', 'yes')->count();
        $inEligibleVotersCount = User::where('ward_id', $ward->id)->where('access_level', '!=', 'superadmin')->where('validvoter', 'No')->count();
        $userCount = User::where('ward_id', $ward->id)->where('access_level', '!=', 'superadmin')->count();
        $newMembers = User::where('ward_id', $ward->id)->where('access_level', '!=', 'superadmin')->where('created_at', '>=', Carbon::now()->subDays(1))->count();

        return view('backend.'.$profileData->access_level.'.ward.dashboard',
            compact('profileData', 'pageTitle', 'ward', 'puCount', 'puWithUsersCount', 'excoCount', 'memberCount', 'eligibleVotersCount', 'inEligibleVotersCount', 'userCount', 'newMembers'));
    }

    public function PuDashBoard($uuid)
    {
        $pageTitle = 'Polling Unit Dashboard';
        $profileData = $this->getProfileData();
        $pu = PollingUnit::where('uuid', $uuid)->first();

        if(!$pu){
            return redirect()->back()->with(['message' => 'Polling Unit not found', 'alert-type' => 'error']);
        }

        $excoCount = User::where('polling_unit_id', $pu->id)->where('access_level', '!=', 'user')->where('access_level', '!=', 'superadmin')->count();
        $memberCount = User::where('polling_unit_id', $pu->id)->where('access_level', '!=', 'superadmin')->where('access_level', 'user')->count();
        $eligibleVotersCount = User::where('polling_unit_id', $pu->id)->where('access_level', '!=', 'superadmin')->where('validvoter', 'yes')->count();
        $inEligibleVotersCount = User::where('polling_unit_id', $pu->id)->where('access_level', '!=', 'superadmin')->where('validvoter', 'No')->count();
        $userCount = User::where('polling_unit_id', $pu->id)->where('access_level', '!=', 'superadmin')->count();
        $newMembers = User::where('polling_unit_id', $pu->id)->where('access_level', '!=', 'superadmin')->where('created_at', '>=', Carbon::now()->subDays(1))->count();

        return view('backend.'.$profileData->access_level.'.pu.dashboard',
            compact('profileData', 'pageTitle', 'pu', 'excoCount', 'memberCount', 'eligibleVotersCount', 'inEligibleVotersCount', 'userCount', 'newMembers'));
    }
}
