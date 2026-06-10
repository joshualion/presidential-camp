<?php

namespace App\Http\Controllers\regionaladmin;

use App\Http\Controllers\Controller;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Services\LocationDashboardMetricsService;
use App\Models\Ward;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class RegionalAdminController extends Controller
{
    public function __construct()
    {
        View::share('pageTitle', 'Regional Admin');
    }

    private function getProfileData()
    {
        return User::find(Auth::user()->id);
    }

    public function regionaladminLogout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function regionalAdminDashBoard()
    {
        $profileData = $this->getProfileData();
        $region = Region::find($profileData->region_id);
        $dashboardMetrics = app(LocationDashboardMetricsService::class)->forUser($profileData);
        $geography = $dashboardMetrics['geography'];
        $coverage = $dashboardMetrics['coverage'];
        $people = $dashboardMetrics['people'];
        $election = $dashboardMetrics['election'];

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
        $newMembers = $people['new_members'];
        $totalVotes = $election['votes'];
        $resultsSubmittedCount = $election['polling_unit_results'];
        $incidentsReportedCount = $election['election_incidents'];

        return view('backend.regionaladmin.dashboard', compact(
            'profileData',
            'region',
            'dashboardMetrics',
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
            'newMembers',
            'totalVotes',
            'resultsSubmittedCount',
            'incidentsReportedCount'
        ));
    }

    public function regionaladminProfile()
    {
        $profileData = $this->getProfileData();

        return view('backend.regionaladmin.profile', compact('profileData'));
    }

    public function regionaladminProfileStore(Request $request)
    {
        $profileData = $this->getProfileData();
        $profileData->update($request->except(['_token', '_method']));

        return redirect()->route('regionaladmin.profile')->with([
            'message' => 'Profile updated successfully',
            'alert-type' => 'success',
        ]);
    }

    public function regionaladminChangePassword()
    {
        $profileData = $this->getProfileData();

        return view('backend.regionaladmin.change_password', compact('profileData'));
    }

    public function regionaladminUpdatePassword()
    {
        return redirect()->route('regionaladmin.change.password')->with([
            'message' => 'Password update is not implemented in this package.',
            'alert-type' => 'warning',
        ]);
    }

    public function regionaladminGenderDistribution()
    {
        return abort(404);
    }

    public function regionaladminAgeDistribution()
    {
        return abort(404);
    }

    public function regionaladminRegionDistribution()
    {
        return abort(404);
    }

    public function regionaladminReligionDistribution()
    {
        return abort(404);
    }

    public function regionaladminVoterDistribution()
    {
        return abort(404);
    }

    public function regionaladminStateDistribution()
    {
        return abort(404);
    }

    public function RegionDashBoard()
    {
        return abort(404);
    }

    public function StateDashBoard($uuid)
    {
        $pageTitle = 'State Dashboard';
        $profileData = $this->getProfileData();
        $state = State::where('uuid', $uuid)->first();

        if(!$state){
            return redirect()->back()->with('error', 'State not found');
        }

        if ($profileData->region_id && (int) $state->region_id !== (int) $profileData->region_id) {
            abort(403);
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

        return view('backend.'.$profileData->access_level.'.state.dashboard',
            compact('profileData', 'pageTitle', 'state', 'senatorialDistrictCount', 'senatorialDistrictsWithUsersCount', 'federalConstituencyCount', 'federalConstituenciesWithUsersCount', 'lgaCount', 'lgasWithUsersCount', 'wardCount', 'wardWithUsersCount', 'puCount', 'puWithUsersCount', 'excoCount', 'memberCount', 'userCount', 'newMembers'));
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

    public function localGovernmentDashBoard()
    {
        return abort(404);
    }

    public function wardDashBoard()
    {
        return abort(404);
    }

    public function PuDashBoard()
    {
        return abort(404);
    }
}
