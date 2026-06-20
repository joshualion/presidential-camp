<?php

namespace App\Http\Controllers\federaladmin;

use App\Http\Controllers\Concerns\ManagesAdminProfile;
use App\Http\Controllers\Controller;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\User;
use App\Models\Ward;
use App\Services\LocationDashboardMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class FederalAdminController extends Controller
{
    use ManagesAdminProfile;

    public function __construct()
    {
        View::share('pageTitle', 'Federal Constituency Dashboard');
    }

    public function federalAdminDashBoard()
    {
        $profileData = $this->getProfileData();
        $dashboardMetrics = app(LocationDashboardMetricsService::class)->forUser($profileData);
        $federalConstituency = $profileData->federalConstituency;

        if ($federalConstituency) {
            $pageTitle = 'Federal Constituency Dashboard';
            $constituency = $federalConstituency;
            $geography = $dashboardMetrics['geography'];
            $coverage = $dashboardMetrics['coverage'];
            $people = $dashboardMetrics['people'];
            $lgaCount = $geography['lgas'];
            $lgaWithUsersCount = $coverage['lgas_with_users'];
            $wardCount = $geography['wards'];
            $wardWithUsersCount = $coverage['wards_with_users'];
            $puCount = $geography['polling_units'];
            $puWithUsersCount = $coverage['polling_units_with_users'];
            $excoCount = $people['executives'];
            $memberCount = $people['members'];
            $userCount = $people['users'];
            $newMembers = $people['new_members'];

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
                    'newMembers',
                    'dashboardMetrics'
                ));
        }

        return view('backend.federaladmin.dashboard', compact(
            'profileData',
            'dashboardMetrics',
            'federalConstituency'
        ));
    }

    public function federaladminLogout(Request $request)
    {
        return $this->logoutAdmin($request);
    }

    public function federaladminProfile()
    {
        return $this->showAdminProfile();
    }

    public function federaladminProfileStore(Request $request)
    {
        return $this->storeAdminProfile($request);
    }

    public function federaladminChangePassword()
    {
        return $this->showAdminChangePassword();
    }

    public function federaladminUpdatePassword(Request $request)
    {
        return $this->updateAdminPassword($request);
    }

    public function localGovernmentDashBoard($uuid)
    {
        $pageTitle = 'Local Government Dashboard';
        $profileData = $this->getProfileData();
        $lga = app(\App\Services\DashboardDrilldownService::class)->lga($uuid);

        if(!$lga){
            return redirect()->back()->with('error', 'LGA not found');
        }
        app(\App\Services\LicensedScopeQueryService::class)->abortIfLgaNotAllowed($lga->id);

        $wardCount = Ward::where('lga_id', $lga->id)->count();
        $wardWithUsersCount = Ward::where('lga_id', $lga->id)->whereHas('users')->count();
        $wards = Ward::where('lga_id', $lga->id)->pluck('id');
        $puCount = PollingUnit::whereIn('ward_id', $wards)->count();
        $puWithUsersCount = PollingUnit::whereIn('ward_id', $wards)->whereHas('users')->count();
        $excoCount = User::where('lga_id', $lga->id)->where('access_level', '!=', 'user')->where('access_level', '!=', 'superadmin')->where('access_level', '!=', 'puadmin')->count();
        $memberCount = User::where('lga_id', $lga->id)->where('access_level', '!=', 'superadmin')->where('access_level', 'user')->count();
        $userCount = User::where('lga_id', $lga->id)->where('access_level', '!=', 'superadmin')->count();
        $newMembers = User::where('lga_id', $lga->id)->where('access_level', '!=', 'superadmin')->where('created_at', '>=', Carbon::now()->subDays(1))->count();

        $dashboardMetrics = app(LocationDashboardMetricsService::class)->forLocalGovernmentArea($lga, $profileData);

                    return view('backend.'.$profileData->access_level.'.lga.dashboard',
            compact('profileData', 'pageTitle', 'lga', 'dashboardMetrics', 'wardCount', 'wardWithUsersCount', 'puCount', 'puWithUsersCount', 'excoCount', 'memberCount', 'userCount', 'newMembers'));
    }

    public function wardDashBoard($uuid)
    {
        $pageTitle = 'Ward Dashboard';
        $profileData = $this->getProfileData();
        $ward = app(\App\Services\DashboardDrilldownService::class)->ward($uuid);

        if(!$ward){
            return redirect()->back()->with(['message' => 'Ward not found', 'alert-type' => 'error']);
        }
        app(\App\Services\LicensedScopeQueryService::class)->abortIfWardNotAllowed($ward->id);

        $puCount = PollingUnit::where('ward_id', $ward->id)->count();
        $puWithUsersCount = PollingUnit::where('ward_id', $ward->id)->whereHas('users')->count();
        $excoCount = User::where('ward_id', $ward->id)->where('access_level', '!=', 'user')->where('access_level', '!=', 'superadmin')->where('access_level', '!=', 'puadmin')->count();
        $memberCount = User::where('ward_id', $ward->id)->where('access_level', '!=', 'superadmin')->where('access_level', 'user')->count();
        $eligibleVotersCount = User::where('ward_id', $ward->id)->where('access_level', '!=', 'superadmin')->where('validvoter', 'yes')->count();
        $inEligibleVotersCount = User::where('ward_id', $ward->id)->where('access_level', '!=', 'superadmin')->where('validvoter', 'No')->count();
        $userCount = User::where('ward_id', $ward->id)->where('access_level', '!=', 'superadmin')->count();
        $newMembers = User::where('ward_id', $ward->id)->where('access_level', '!=', 'superadmin')->where('created_at', '>=', Carbon::now()->subDays(1))->count();

        $dashboardMetrics = app(LocationDashboardMetricsService::class)->forWard($ward, $profileData);

                    return view('backend.'.$profileData->access_level.'.ward.dashboard',
            compact('profileData', 'pageTitle', 'ward', 'dashboardMetrics', 'puCount', 'puWithUsersCount', 'excoCount', 'memberCount', 'eligibleVotersCount', 'inEligibleVotersCount', 'userCount', 'newMembers'));
    }

    public function puDashBoard($uuid)
    {
        $pageTitle = 'Polling Unit Dashboard';
        $profileData = $this->getProfileData();
        $pu = app(\App\Services\DashboardDrilldownService::class)->pollingUnit($uuid);

        if(!$pu){
            return redirect()->back()->with(['message' => 'Polling Unit not found', 'alert-type' => 'error']);
        }
        app(\App\Services\LicensedScopeQueryService::class)->abortIfPollingUnitNotAllowed($pu->id);

        $excoCount = User::where('polling_unit_id', $pu->id)->where('access_level', '!=', 'user')->where('access_level', '!=', 'superadmin')->where('access_level', '!=', 'puadmin')->count();
        $memberCount = User::where('polling_unit_id', $pu->id)->where('access_level', '!=', 'superadmin')->where('access_level', 'user')->count();
        $eligibleVotersCount = User::where('polling_unit_id', $pu->id)->where('access_level', '!=', 'superadmin')->where('validvoter', 'yes')->count();
        $inEligibleVotersCount = User::where('polling_unit_id', $pu->id)->where('access_level', '!=', 'superadmin')->where('validvoter', 'No')->count();
        $userCount = User::where('polling_unit_id', $pu->id)->where('access_level', '!=', 'superadmin')->count();
        $newMembers = User::where('polling_unit_id', $pu->id)->where('access_level', '!=', 'superadmin')->where('created_at', '>=', Carbon::now()->subDays(1))->count();

        $dashboardMetrics = app(LocationDashboardMetricsService::class)->forPollingUnit($pu, $profileData);

                    return view('backend.'.$profileData->access_level.'.pu.dashboard',
            compact('profileData', 'pageTitle', 'pu', 'dashboardMetrics', 'excoCount', 'memberCount', 'eligibleVotersCount', 'inEligibleVotersCount', 'userCount', 'newMembers'));
    }

    private function getProfileData()
    {
        return User::find(Auth::user()->id);
    }
}
