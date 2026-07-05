<?php
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\DashboardStatsController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\regionaladmin\RegionalAdminController;
use Illuminate\Support\Facades\Route;


Route::get('/regional/logout', [RegionalAdminController::class, 'regionaladminLogout'])->name('regionaladmin.logout')->middleware(['auth','access_level:regionaladmin']);


Route::middleware(['auth','check_onboarding'])->group(function () {

 // Regional Admin Profile & Dashboard Routes
 Route::get('/regional/dashboard', [RegionalAdminController::class, 'regionalAdminDashBoard'])->name('regionaladmin.dashboard')->middleware('access_level:regionaladmin');
 Route::get('/regional/dashboard/stats', [DashboardStatsController::class, 'cards'])->name('regionaladmin.dashboard.stats')->middleware('access_level:regionaladmin');
 Route::get('/regional/profile', [RegionalAdminController::class, 'regionaladminProfile'])->name('regionaladmin.profile')->middleware('access_level:regionaladmin');
 Route::post('/regional/profile/store', [RegionalAdminController::class, 'regionaladminProfileStore'])->name('regionaladmin.profile.store')->middleware('access_level:regionaladmin');
 Route::get('/regional/change/password', [RegionalAdminController::class, 'regionaladminChangePassword'])->name('regionaladmin.change.password')->middleware('access_level:regionaladmin');
 Route::post('/regional/update/password', [RegionalAdminController::class, 'regionaladminUpdatePassword'])->name('regionaladmin.update.password')->middleware('access_level:regionaladmin');



    /*Regional Admin Ajax Routes */

//Gender Distribution
Route::get('/regional/member/distribution/gender', [RegionalAdminController::class, 'regionaladminGenderDistribution'])->name('regionaladmin.member.distribution.gender')->middleware('access_level:regionaladmin');

// Age Distribution
Route::get('/regional/member/distribution/age', [RegionalAdminController::class, 'regionaladminAgeDistribution'])->name('regionaladmin.member.distribution.age')->middleware('access_level:regionaladmin');

// Region Distribution
Route::get('/regional/member/distribution/region', [RegionalAdminController::class, 'regionaladminRegionDistribution'])->name('regionaladmin.member.distribution.region')->middleware('access_level:regionaladmin');
/*End Super Admin Ajax Routes */
// Religion Distribution
Route::get('/regional/member/distribution/religion', [RegionalAdminController::class, 'regionaladminReligionDistribution'])->name('regionaladmin.member.distribution.religion')->middleware('access_level:regionaladmin');
// Valid Voter Distribution
Route::get('/regional/member/distribution/voter', [RegionalAdminController::class, 'regionaladminVoterDistribution'])->name('regionaladmin.member.distribution.voter')->middleware('access_level:regionaladmin');
// State Distribution
Route::get('/regional/member/distribution/state', [RegionalAdminController::class, 'regionaladminStateDistribution'])->name('regionaladmin.member.distribution.state')->middleware('access_level:regionaladmin');


//Member Management
Route::get('/regional/members/data/fetch/{uuid?}', [MembersController::class, 'getMembersData'])->name('regionaladmin.members.data');
Route::get('/regional/members/fetch/{uuid?}', [MembersController::class, 'allMembers'])->name('regionaladmin.members');
//Fetch Excos
Route::get('/regional/leaders/data/fetch/{uuid?}', [MembersController::class, 'getExcoMembersData'])->name('regionaladmin.leaders.data');
Route::get('/regional/leaders/fetch/{uuid?}', [MembersController::class, 'allExcoMembers'])->name('regionaladmin.leaders');
//Fetch Regulars
Route::get('/regional/regulars/data/fetch/{uuid?}', [MembersController::class, 'getRegularMembersData'])->name('regionaladmin.regulars.data');
Route::get('/regional/regulars/fetch/{uuid?}', [MembersController::class, 'allRegularMembers'])->name('regionaladmin.regulars');
Route::get('/regional/people/{metric}/data/fetch/{uuid?}', [MembersController::class, 'getPeopleMetricMembersData'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('regionaladmin.peopleMetric.data');
Route::get('/regional/people/{metric}/fetch/{uuid?}', [MembersController::class, 'allPeopleMetricMembers'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('regionaladmin.peopleMetric');

Route::get('/regional/member/add', [MembersController::class, 'addMember'])->name('regionaladmin.member.add')->middleware('access_level:regionaladmin');
Route::get('/regional/members/import', [MembersController::class, 'importMembers'])->name('regionaladmin.member.import')->middleware('access_level:regionaladmin');
Route::get('/regional/members/import/template', [MembersController::class, 'downloadMemberImportTemplate'])->name('regionaladmin.member.import.template')->middleware('access_level:regionaladmin');
Route::post('/regional/members/import', [MembersController::class, 'storeMemberImport'])->name('regionaladmin.member.import.store')->middleware('access_level:regionaladmin');
Route::post('/regional/member/store', [MembersController::class, 'storeMember'])->name('regionaladmin.member.store')->middleware('access_level:regionaladmin');
Route::get('/regional/member/edit/{uuid}', [MembersController::class, 'editMember'])->name('regionaladmin.member.edit')->middleware('access_level:regionaladmin');
Route::put('/regional/member/update/', [MembersController::class, 'updateMember'])->name('regionaladmin.member.update')->middleware('access_level:regionaladmin');
Route::get('/regional/member/view/{uuid}', [MembersController::class, 'viewMember'])->name('regionaladmin.member.view')->middleware('access_level:regionaladmin');
Route::get('/regional/member/suspend/{uuid}', [MembersController::class, 'suspendMember'])->name('regionaladmin.member.suspend')->middleware('access_level:regionaladmin');
Route::delete('/regional/member/delete/{uuid}', [MembersController::class, 'deleteMember'])->name('regionaladmin.member.delete')->middleware('access_level:regionaladmin');
 //Members By region
Route::get('/regional/members/region', [MembersController::class, 'membersByRegions'])->name('regionaladmin.members.region')->middleware('access_level:regionaladmin,regionaladmin');
Route::get('/regional/members/region/{uuid}', [MembersController::class, 'ViewMembersByRegion'])->name('regionaladmin.member.region.view')->middleware('access_level:regionaladmin,regionaladmin');
Route::get('/regional/region/members/data/{uuid}', [MembersController::class, 'getRegionMembersData'])->name('regionaladmin.region.members.data');
// Members By State
Route::get('/regional/members/bystate/{uuid?}', [MembersController::class, 'membersByStates'])->name('regionaladmin.members.byState')->middleware('access_level:regionaladmin,regionadmin,stateadmin');
Route::get('/regional/members/state/{uuid}', [MembersController::class, 'ViewMembersByState'])->name('regionaladmin.member.state.view')->middleware('access_level:regionaladmin,regionadmin,regionadmin,stateadmin');
Route::get('/regional/state/members/data/{uuid}', [MembersController::class, 'getStateMembersData'])->name('regionaladmin.state.members.data');
// Members By Senatorial District
Route::get('/regional/members/bysenatorialdistrict/{uuid?}', [MembersController::class, 'membersBySenatorialDistricts'])->name('regionaladmin.members.bySenatorialDistrict')->middleware('access_level:regionaladmin');
// Members By Federal Constituency
Route::get('/regional/members/byfederalconstituency/{uuid?}', [MembersController::class, 'membersByFederalConstituencies'])->name('regionaladmin.members.byFederalConstituency')->middleware('access_level:regionaladmin');
// Members By Local Government
Route::get('/regional/members/bylga/{uuid?}', [MembersController::class, 'membersByLocalGovernments'])->name('regionaladmin.members.byLga')->middleware('access_level:regionaladmin');
Route::get('/regional/members/lga/{uuid}', [MembersController::class, 'viewMembersByLocalGovernments'])->name('regionaladmin.member.lga.view')->middleware('access_level:regionaladmin');
Route::get('/regional/lga/members/data/{uuid}', [MembersController::class, 'getLgaMembersData'])->name('regionaladmin.lga.members.data');
// Members By Ward
Route::get('/regional/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('regionaladmin.members.byWard')->middleware('access_level:regionaladmin');
Route::get('/regional/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('regionaladmin.members.byWard.ajax')->middleware('access_level:regionaladmin');
Route::get('/regional/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('regionaladmin.member.ward.view')->middleware('access_level:regionaladmin');
Route::get('/regional/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('regionaladmin.ward.members.data');
// Members By Ward
Route::get('/regional/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('regionaladmin.members.byWard')->middleware('access_level:regionaladmin');
Route::get('/regional/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('regionaladmin.members.byWard.ajax')->middleware('access_level:regionaladmin');
Route::get('/regional/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('regionaladmin.member.ward.view')->middleware('access_level:regionaladmin');
Route::get('/regional/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('regionaladmin.ward.members.data');

// Members By Pu
Route::get('/regional/members/bypu/{uuid?}', [MembersController::class, 'membersByPus'])->name('regionaladmin.members.byPu')->middleware('access_level:regionaladmin');
Route::get('/regional/members/bypu/ajax/{uuid?}', [AjaxController::class, 'membersByPusAjax'])->name('regionaladmin.members.byPu.ajax')->middleware('access_level:regionaladmin');
Route::get('/regional/members/pu/{uuid}', [MembersController::class, 'viewMembersByPu'])->name('regionaladmin.member.pu.view')->middleware('access_level:regionaladmin');
Route::get('/regional/pu/members/data/{uuid}', [MembersController::class, 'getPuMembersData'])->name('regionaladmin.pu.members.data');

//Locations Management Routes


//Manage Regions
Route::get('/regional/location/region/dashboard/{uuid}', [RegionalAdminController::class, 'RegionDashBoard'])->name('regionaladmin.location.region.dashboard')->middleware('access_level:regionaladmin');
Route::get('/regional/region/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByRegion'])->name('regionaladmin.region.member.distribution.gender')->middleware('access_level:regionaladmin');
Route::get('/regional/region/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByRegion'])->name('regionaladmin.region.member.distribution.age')->middleware('access_level:regionaladmin');
Route::get('/regional/region/member/distribution/state/{uuid}', [ChartsController::class, 'StateDistributionByRegion'])->name('regionaladmin.region.member.distribution.state')->middleware('access_level:regionaladmin');
Route::get('/regional/region/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByRegion'])->name('regionaladmin.region.member.distribution.voter')->middleware('access_level:regionaladmin');
Route::get('/regional/location/regions', [LocationController::class, 'allRegions'])->name('regionaladmin.location.regions')->middleware('access_level:regionaladmin');
Route::get('/regional/location/region/add', [LocationController::class, 'addRegion'])->name('regionaladmin.location.region.add')->middleware('access_level:regionaladmin');
Route::post('/regional/location/region/store', [LocationController::class, 'storeRegion'])->name('regionaladmin.location.region.store')->middleware('access_level:regionaladmin');
Route::get('/regional/location/region/edit/{uuid}', [LocationController::class, 'editRegion'])->name('regionaladmin.location.region.edit')->middleware('access_level:regionaladmin');
Route::post('/regional/location/region/update/{uuid}', [LocationController::class, 'updateRegion'])->name('regionaladmin.location.region.update')->middleware('access_level:regionaladmin');
Route::delete('/regional/location/region/delete/{uuid}', [LocationController::class, 'deleteRegion'])->name('regionaladmin.location.region.delete')->middleware('access_level:regionaladmin');

// Manage States
Route::get('/regional/location/state/dashboard/{uuid}', [RegionalAdminController::class, 'StateDashBoard'])->name('regionaladmin.location.state.dashboard')->middleware('access_level:regionaladmin');
Route::get('/regional/state/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByState'])->name('regionaladmin.state.member.distribution.gender')->middleware('access_level:regionaladmin');
Route::get('/regional/state/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByState'])->name('regionaladmin.state.member.distribution.age')->middleware('access_level:regionaladmin');
Route::get('/regional/state/member/distribution/lga/{uuid}', [ChartsController::class, 'LgaDistributionByState'])->name('regionaladmin.state.member.distribution.lga')->middleware('access_level:regionaladmin');
Route::get('/regional/state/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByState'])->name('regionaladmin.state.member.distribution.voter')->middleware('access_level:regionaladmin');

Route::get('/regional/location/states', [LocationController::class, 'allStates'])->name('regionaladmin.location.states')->middleware('access_level:regionaladmin');
Route::get('/regional/location/state/add', [LocationController::class, 'addState'])->name('regionaladmin.location.state.add')->middleware('access_level:regionaladmin');
Route::post('/regional/location/state/store', [LocationController::class, 'storeState'])->name('regionaladmin.location.state.store')->middleware('access_level:regionaladmin');
Route::get('/regional/location/state/edit/{uuid}', [LocationController::class, 'editState'])->name('regionaladmin.location.state.edit')->middleware('access_level:regionaladmin');
Route::post('/regional/location/state/update/{uuid}', [LocationController::class, 'updateState'])->name('regionaladmin.location.state.update')->middleware('access_level:regionaladmin');
Route::delete('/regional/location/state/delete/{uuid}', [LocationController::class, 'deleteState'])->name('regionaladmin.location.state.delete')->middleware('access_level:regionaladmin');

// Manage Senatorial Districts
Route::get('/regional/location/senatorial-district/dashboard/{uuid}', [RegionalAdminController::class, 'senatorialDistrictDashBoard'])->name('regionaladmin.location.senatorial-district.dashboard')->middleware('access_level:regionaladmin');
Route::get('/regional/senatorial-district/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionBySenatorialDistrict'])->name('regionaladmin.senatorial-district.member.distribution.gender')->middleware('access_level:regionaladmin');
Route::get('/regional/senatorial-district/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionBySenatorialDistrict'])->name('regionaladmin.senatorial-district.member.distribution.age')->middleware('access_level:regionaladmin');
Route::get('/regional/senatorial-district/member/distribution/lga/{uuid}', [ChartsController::class, 'lgaDistributionBySenatorialDistrict'])->name('regionaladmin.senatorial-district.member.distribution.lga')->middleware('access_level:regionaladmin');
Route::get('/regional/senatorial-district/member/distribution/voter/{uuid}', [ChartsController::class, 'VoterDistributionBySenatorialDistrict'])->name('regionaladmin.senatorial-district.member.distribution.voter')->middleware('access_level:regionaladmin');

// Manage Federal Constituencies
Route::get('/regional/location/federal-constituency/dashboard/{uuid}', [RegionalAdminController::class, 'federalConstituencyDashBoard'])->name('regionaladmin.location.federal-constituency.dashboard')->middleware('access_level:regionaladmin');
Route::get('/regional/location/senatorial-districts', [LocationController::class, 'allSenatorialDistricts'])->name('regionaladmin.location.senatorial-districts')->middleware('access_level:regionaladmin');
Route::get('/regional/location/senatorial-district/edit/{uuid}', [LocationController::class, 'editSenatorialDistrict'])->name('regionaladmin.location.senatorial-district.edit')->middleware('access_level:regionaladmin');
Route::post('/regional/location/senatorial-district/update/{uuid}', [LocationController::class, 'updateSenatorialDistrict'])->name('regionaladmin.location.senatorial-district.update')->middleware('access_level:regionaladmin');
Route::get('/regional/location/federal-constituencies', [LocationController::class, 'allFederalConstituencies'])->name('regionaladmin.location.federal-constituencies')->middleware('access_level:regionaladmin');
Route::get('/regional/location/federal-constituency/edit/{uuid}', [LocationController::class, 'editFederalConstituency'])->name('regionaladmin.location.federal-constituency.edit')->middleware('access_level:regionaladmin');
Route::post('/regional/location/federal-constituency/update/{uuid}', [LocationController::class, 'updateFederalConstituency'])->name('regionaladmin.location.federal-constituency.update')->middleware('access_level:regionaladmin');
Route::get('/regional/federal-constituency/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByFederalConstituency'])->name('regionaladmin.federal-constituency.member.distribution.gender')->middleware('access_level:regionaladmin');
Route::get('/regional/federal-constituency/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByFederalConstituency'])->name('regionaladmin.federal-constituency.member.distribution.age')->middleware('access_level:regionaladmin');
Route::get('/regional/federal-constituency/member/distribution/lga/{uuid}', [ChartsController::class, 'lgaDistributionByFederalConstituency'])->name('regionaladmin.federal-constituency.member.distribution.lga')->middleware('access_level:regionaladmin');
Route::get('/regional/federal-constituency/member/distribution/voter/{uuid}', [ChartsController::class, 'VoterDistributionByFederalConstituency'])->name('regionaladmin.federal-constituency.member.distribution.voter')->middleware('access_level:regionaladmin');

// manage Local Governments
Route::get('/regional/location/localgovernment/dashboard/{uuid}', [RegionalAdminController::class, 'localGovernmentDashBoard'])->name('regionaladmin.location.lga.dashboard')->middleware('access_level:regionaladmin');
Route::get('/regional/lga/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByLga'])->name('regionaladmin.lga.member.distribution.gender')->middleware('access_level:regionaladmin');
Route::get('/regional/lga/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByLga'])->name('regionaladmin.lga.member.distribution.age')->middleware('access_level:regionaladmin');
Route::get('/regional/lga/member/distribution/ward/{uuid}', [ChartsController::class, 'wardDistributionByLga'])->name('regionaladmin.lga.member.distribution.ward')->middleware('access_level:regionaladmin');
Route::get('/regional/lga/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByLga'])->name('regionaladmin.lga.member.distribution.voter')->middleware('access_level:regionaladmin');


Route::get('/regional/location/localgovernments', [LocationController::class, 'allLocalGovernments'])->name('regionaladmin.location.lgas')->middleware('access_level:regionaladmin');
Route::get('/regional/location/localgovernment/add', [LocationController::class, 'addLocalGovernment'])->name('regionaladmin.location.lga.add')->middleware('access_level:regionaladmin');
Route::post('/regional/location/localgovernment/store', [LocationController::class, 'storeLocalGovernment'])->name('regionaladmin.location.lga.store')->middleware('access_level:regionaladmin');
Route::get('/regional/location/localgovernment/edit/{uuid}', [LocationController::class, 'editLocalGovernment'])->name('regionaladmin.location.lga.edit')->middleware('access_level:regionaladmin');
Route::post('/regional/location/localgovernment/update/{uuid}', [LocationController::class, 'updateLocalGovernment'])->name('regionaladmin.location.lga.update')->middleware('access_level:regionaladmin');
Route::delete('/regional/location/localgovernment/delete/{uuid}', [LocationController::class, 'deleteLocalGovernment'])->name('regionaladmin.location.lga.delete')->middleware('access_level:regionaladmin');

//import LGA Form
Route::get('/regional/location/importlgas', [LocationController::class, 'importLgasForm'])->name('regionaladmin.location.lga.import')->middleware('access_level:regionaladmin');

// Export Route
Route::get('/regional/location/exportlgas', [LocationController::class, 'exportLgas'])->name('regionaladmin.location.lga.exportLgas')->middleware('access_level:regionaladmin');

// Import Route
Route::post('/regional/location/importlgas', [LocationController::class, 'importLgas'])->name('regionaladmin.location.lga.importLgas')->middleware('access_level:regionaladmin');


// manage Wards
Route::get('/regional/location/ward/dashboard/{uuid}', [RegionalAdminController::class, 'wardDashBoard'])->name('regionaladmin.location.ward.dashboard')->middleware('access_level:regionaladmin');
Route::get('/regional/ward/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByWard'])->name('regionaladmin.ward.member.distribution.gender')->middleware('access_level:regionaladmin');
Route::get('/regional/ward/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByWard'])->name('regionaladmin.ward.member.distribution.voter')->middleware('access_level:regionaladmin');

Route::get('/regional/location/wards', [LocationController::class, 'allWards'])->name('regionaladmin.location.wards')->middleware('access_level:regionaladmin');
Route::get('/regional/location/ward/add', [LocationController::class, 'addWard'])->name('regionaladmin.location.ward.add')->middleware('access_level:regionaladmin');
Route::post('/regional/location/ward/store', [LocationController::class, 'storeWard'])->name('regionaladmin.location.ward.store')->middleware('access_level:regionaladmin');
Route::get('/regional/location/ward/edit/{uuid}', [LocationController::class, 'editWard'])->name('regionaladmin.location.ward.edit')->middleware('access_level:regionaladmin');
Route::post('/regional/location/ward/update/{uuid}', [LocationController::class, 'updateWard'])->name('regionaladmin.location.ward.update')->middleware('access_level:regionaladmin');
Route::delete('/regional/location/ward/delete/{uuid}', [LocationController::class, 'deleteWard'])->name('regionaladmin.location.ward.delete')->middleware('access_level:regionaladmin');

//import PU Form
Route::get('/regional/location/importwards', [LocationController::class, 'importWardsForm'])->name('regionaladmin.location.ward.import')->middleware('access_level:regionaladmin');

// Export Route
Route::get('/regional/location/exportwards', [LocationController::class, 'exportWards'])->name('regionaladmin.location.ward.exportWards')->middleware('access_level:regionaladmin');

// Import Route
Route::post('/regional/location/importwards', [LocationController::class, 'importWards'])->name('regionaladmin.location.ward.importWards')->middleware('access_level:regionaladmin');

//Manage Polling Units
Route::get('/regional/location/pollingunit/dashboard/{uuid}', [RegionalAdminController::class, 'PuDashBoard'])->name('regionaladmin.location.pu.dashboard')->middleware('access_level:regionaladmin');
Route::get('/regional/pollingunit/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByPu'])->name('regionaladmin.pu.member.distribution.gender')->middleware('access_level:regionaladmin');
Route::get('/regional/pollingunit/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByPu'])->name('regionaladmin.pu.member.distribution.voter')->middleware('access_level:regionaladmin');



Route::get('/regional/location/pollingunits', [LocationController::class, 'allPollingUnits'])->name('regionaladmin.location.pus')->middleware('access_level:regionaladmin');
Route::get('/regional/location/pollingunit/add', [LocationController::class, 'addPollingUnit'])->name('regionaladmin.location.pu.add')->middleware('access_level:regionaladmin');
Route::post('/regional/location/pollingunit/store', [LocationController::class, 'storePollingUnit'])->name('regionaladmin.location.pu.store')->middleware('access_level:regionaladmin');
Route::get('/regional/location/pollingunit/edit/{uuid}', [LocationController::class, 'editPollingUnit'])->name('regionaladmin.location.pu.edit')->middleware('access_level:regionaladmin');
Route::post('/regional/location/pollingunit/update/{uuid}', [LocationController::class, 'updatePollingUnit'])->name('regionaladmin.location.pu.update')->middleware('access_level:regionaladmin');
Route::delete('/regional/location/pollingunit/delete/{uuid}', [LocationController::class, 'deletePollingUnit'])->name('regionaladmin.location.pu.delete')->middleware('access_level:regionaladmin');

//import PU Form
Route::get('/regional/import-polling-units', [LocationController::class, 'importPollingUnitsForm'])->name('regionaladmin.location.pu.import')->middleware('access_level:regionaladmin');
// Export Route
Route::get('/regional/export-polling-units', [LocationController::class, 'exportPollingUnits'])->name('regionaladmin.location.pu.exportPollingUnits')->middleware('access_level:regionaladmin');

// Import Route
Route::post('/regional/import-polling-units', [LocationController::class, 'importPollingUnits'])->name('regionaladmin.location.pu.importPollingUnits')->middleware('access_level:regionaladmin');


// Election Management CRUD

Route::get('/regional/elections', [ElectionController::class, 'allElections'])->name('regionaladmin.elections')->middleware('access_level:regionaladmin');
Route::get('/regional/election/operations-center', [ElectionController::class, 'operationsCenter'])->name('regionaladmin.election.operations')->middleware('access_level:regionaladmin');
Route::get('/regional/election/add', [ElectionController::class, 'addElection'])->name('regionaladmin.election.add')->middleware('access_level:regionaladmin');
Route::post('/regional/election/store', [ElectionController::class, 'storeElection'])->name('regionaladmin.election.store')->middleware('access_level:regionaladmin');
Route::get('/regional/election/edit/{uuid}', [ElectionController::class, 'editElection'])->name('regionaladmin.election.edit')->middleware('access_level:regionaladmin');
Route::post('/regional/election/update/{uuid}', [ElectionController::class, 'updateElection'])->name('regionaladmin.election.update')->middleware('access_level:regionaladmin');
Route::delete('/regional/election/delete/{uuid}', [ElectionController::class, 'deleteElection'])->name('regionaladmin.election.delete')->middleware('access_level:regionaladmin');

// Election Result Route
Route::get('/regional/election/result/{uuid}', [ElectionController::class, 'electionResult'])->name('regionaladmin.election.results')->middleware('access_level:regionaladmin');

// Election PU Votes Report
Route::get('/regional/election/votes/{uuid?}', [ElectionController::class, 'manageVotesByPu'])->name('regionaladmin.election.votesByPu')->middleware('access_level:regionaladmin');
Route::get('/regional/election/votes/data/{uuid}', [ElectionController::class, 'VotesByPuData'])->name('regionaladmin.election.votesByPuData')->middleware('access_level:regionaladmin');


// Election Incident Report
Route::get('/regional/election/incident/{uuid}/{polling_unit_id}', [ElectionController::class, 'manageIncidentByPu'])
    ->name('regionaladmin.election.incident')
    ->middleware('access_level:regionaladmin');

    Route::get('/regional/incident/add', [ElectionController::class, 'addIncident'])->name('regionaladmin.incident.add')->middleware('access_level:regionaladmin');
    Route::post('/regional/incident/store', [ElectionController::class, 'storeIcident'])->name('regionaladmin.incident.store')->middleware('access_level:regionaladmin');
    

//Manage Votes
Route::get('/regional/votes', [ElectionController::class, 'allVotes'])->name('regionaladmin.votes')->middleware('access_level:regionaladmin');
Route::get('/regional/vote/add', [ElectionController::class, 'addVote'])->name('regionaladmin.vote.add')->middleware('access_level:regionaladmin');
Route::post('/regional/vote/store', [ElectionController::class, 'storeVote'])->name('regionaladmin.vote.store')->middleware('access_level:regionaladmin');
Route::get('/regional/vote/edit/{uuid}', [ElectionController::class, 'editVote'])->name('regionaladmin.vote.edit')->middleware('access_level:regionaladmin');
Route::post('/regional/vote/update/{uuid}', [ElectionController::class, 'updateVote'])->name('regionaladmin.vote.update')->middleware('access_level:regionaladmin');
Route::delete('/regional/vote/delete/{uuid}', [ElectionController::class, 'deleteVote'])->name('regionaladmin.vote.delete')->middleware('access_level:regionaladmin');



});
