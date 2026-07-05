<?php


use App\Http\Controllers\AjaxController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\DashboardStatsController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\stateadmin\StateAdminController;
use Illuminate\Support\Facades\Route;


Route::get('/state/logout', [StateAdminController::class, 'stateadminLogout'])->name('stateadmin.logout')->middleware(['auth','access_level:stateadmin']);


Route::middleware(['auth','check_onboarding'])->group(function () {

 // Regional Admin Profile & Dashboard Routes
 Route::get('/state/dashboard', [StateAdminController::class, 'stateAdminDashBoard'])->name('stateadmin.dashboard')->middleware('access_level:stateadmin');
 Route::get('/state/dashboard/stats', [DashboardStatsController::class, 'cards'])->name('stateadmin.dashboard.stats')->middleware('access_level:stateadmin');
 Route::get('/state/profile', [StateAdminController::class, 'stateadminProfile'])->name('stateadmin.profile')->middleware('access_level:stateadmin');
 Route::post('/state/profile/store', [StateAdminController::class, 'stateadminProfileStore'])->name('stateadmin.profile.store')->middleware('access_level:stateadmin');
 Route::get('/state/change/password', [StateAdminController::class, 'stateadminChangePassword'])->name('stateadmin.change.password')->middleware('access_level:stateadmin');
 Route::post('/state/update/password', [StateAdminController::class, 'stateadminUpdatePassword'])->name('stateadmin.update.password')->middleware('access_level:stateadmin');



    /*Regional Admin Ajax Routes */

//Gender Distribution
Route::get('/state/member/distribution/gender', [StateAdminController::class, 'stateadminGenderDistribution'])->name('stateadmin.member.distribution.gender')->middleware('access_level:stateadmin');

// Age Distribution
Route::get('/state/member/distribution/age', [StateAdminController::class, 'stateadminAgeDistribution'])->name('stateadmin.member.distribution.age')->middleware('access_level:stateadmin');

// Region Distribution
Route::get('/state/member/distribution/region', [StateAdminController::class, 'stateadminRegionDistribution'])->name('stateadmin.member.distribution.region')->middleware('access_level:stateadmin');
/*End Super Admin Ajax Routes */
// Religion Distribution
Route::get('/state/member/distribution/religion', [StateAdminController::class, 'stateadminReligionDistribution'])->name('stateadmin.member.distribution.religion')->middleware('access_level:stateadmin');
// Valid Voter Distribution
Route::get('/state/member/distribution/voter', [StateAdminController::class, 'stateadminVoterDistribution'])->name('stateadmin.member.distribution.voter')->middleware('access_level:stateadmin');
// State Distribution
Route::get('/state/member/distribution/state', [StateAdminController::class, 'stateadminStateDistribution'])->name('stateadmin.member.distribution.state')->middleware('access_level:stateadmin');


//Member Management
Route::get('/state/members/data/fetch/{uuid?}', [MembersController::class, 'getMembersData'])->name('stateadmin.members.data');
Route::get('/state/members/fetch/{uuid?}', [MembersController::class, 'allMembers'])->name('stateadmin.members');
//Fetch Excos
Route::get('/state/leaders/data/fetch/{uuid?}', [MembersController::class, 'getExcoMembersData'])->name('stateadmin.leaders.data');
Route::get('/state/leaders/fetch/{uuid?}', [MembersController::class, 'allExcoMembers'])->name('stateadmin.leaders');
//Fetch Regulars
Route::get('/state/regulars/data/fetch/{uuid?}', [MembersController::class, 'getRegularMembersData'])->name('stateadmin.regulars.data');
Route::get('/state/regulars/fetch/{uuid?}', [MembersController::class, 'allRegularMembers'])->name('stateadmin.regulars');
Route::get('/state/people/{metric}/data/fetch/{uuid?}', [MembersController::class, 'getPeopleMetricMembersData'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('stateadmin.peopleMetric.data');
Route::get('/state/people/{metric}/fetch/{uuid?}', [MembersController::class, 'allPeopleMetricMembers'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('stateadmin.peopleMetric');

Route::get('/state/member/add', [MembersController::class, 'addMember'])->name('stateadmin.member.add')->middleware('access_level:stateadmin');
Route::get('/state/members/import', [MembersController::class, 'importMembers'])->name('stateadmin.member.import')->middleware('access_level:stateadmin');
Route::get('/state/members/import/template', [MembersController::class, 'downloadMemberImportTemplate'])->name('stateadmin.member.import.template')->middleware('access_level:stateadmin');
Route::post('/state/members/import', [MembersController::class, 'storeMemberImport'])->name('stateadmin.member.import.store')->middleware('access_level:stateadmin');
Route::post('/state/member/store', [MembersController::class, 'storeMember'])->name('stateadmin.member.store')->middleware('access_level:stateadmin');
Route::get('/state/member/edit/{uuid}', [MembersController::class, 'editMember'])->name('stateadmin.member.edit')->middleware('access_level:stateadmin');
Route::put('/state/member/update/', [MembersController::class, 'updateMember'])->name('stateadmin.member.update')->middleware('access_level:stateadmin');
Route::get('/state/member/view/{uuid}', [MembersController::class, 'viewMember'])->name('stateadmin.member.view')->middleware('access_level:stateadmin');
Route::get('/state/member/suspend/{uuid}', [MembersController::class, 'suspendMember'])->name('stateadmin.member.suspend')->middleware('access_level:stateadmin');
Route::delete('/state/member/delete/{uuid}', [MembersController::class, 'deleteMember'])->name('stateadmin.member.delete')->middleware('access_level:stateadmin');

// Members By State
Route::get('/state/members/bystate/{uuid?}', [MembersController::class, 'membersByStates'])->name('stateadmin.members.byState')->middleware('access_level:stateadmin,regionadmin,stateadmin');
Route::get('/state/members/state/{uuid}', [MembersController::class, 'ViewMembersByState'])->name('stateadmin.member.state.view')->middleware('access_level:stateadmin,regionadmin,regionadmin,stateadmin');
Route::get('/state/state/members/data/{uuid}', [MembersController::class, 'getStateMembersData'])->name('stateadmin.state.members.data');
// Members By Senatorial District
Route::get('/state/members/bysenatorialdistrict/{uuid?}', [MembersController::class, 'membersBySenatorialDistricts'])->name('stateadmin.members.bySenatorialDistrict')->middleware('access_level:stateadmin');
// Members By Federal Constituency
Route::get('/state/members/byfederalconstituency/{uuid?}', [MembersController::class, 'membersByFederalConstituencies'])->name('stateadmin.members.byFederalConstituency')->middleware('access_level:stateadmin');
// Members By Local Government
Route::get('/state/members/bylga/{uuid?}', [MembersController::class, 'membersByLocalGovernments'])->name('stateadmin.members.byLga')->middleware('access_level:stateadmin');
Route::get('/state/members/lga/{uuid}', [MembersController::class, 'viewMembersByLocalGovernments'])->name('stateadmin.member.lga.view')->middleware('access_level:stateadmin');
Route::get('/state/lga/members/data/{uuid}', [MembersController::class, 'getLgaMembersData'])->name('stateadmin.lga.members.data');
// Members By Ward
Route::get('/state/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('stateadmin.members.byWard')->middleware('access_level:stateadmin');
Route::get('/state/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('stateadmin.members.byWard.ajax')->middleware('access_level:stateadmin');
Route::get('/state/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('stateadmin.member.ward.view')->middleware('access_level:stateadmin');
Route::get('/state/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('stateadmin.ward.members.data');
// Members By Ward
Route::get('/state/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('stateadmin.members.byWard')->middleware('access_level:stateadmin');
Route::get('/state/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('stateadmin.members.byWard.ajax')->middleware('access_level:stateadmin');
Route::get('/state/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('stateadmin.member.ward.view')->middleware('access_level:stateadmin');
Route::get('/state/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('stateadmin.ward.members.data');

// Members By Pu
Route::get('/state/members/bypu/{uuid?}', [MembersController::class, 'membersByPus'])->name('stateadmin.members.byPu')->middleware('access_level:stateadmin');
Route::get('/state/members/bypu/ajax/{uuid?}', [AjaxController::class, 'membersByPusAjax'])->name('stateadmin.members.byPu.ajax')->middleware('access_level:stateadmin');
Route::get('/state/members/pu/{uuid}', [MembersController::class, 'viewMembersByPu'])->name('stateadmin.member.pu.view')->middleware('access_level:stateadmin');
Route::get('/state/pu/members/data/{uuid}', [MembersController::class, 'getPuMembersData'])->name('stateadmin.pu.members.data');

//Locations Management Routes


//Manage Regions
Route::get('/state/location/region/dashboard/{uuid}', [StateAdminController::class, 'RegionDashBoard'])->name('stateadmin.location.region.dashboard')->middleware('access_level:stateadmin');
Route::get('/state/region/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByRegion'])->name('stateadmin.region.member.distribution.gender')->middleware('access_level:stateadmin');
Route::get('/state/region/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByRegion'])->name('stateadmin.region.member.distribution.age')->middleware('access_level:stateadmin');
Route::get('/state/region/member/distribution/state/{uuid}', [ChartsController::class, 'StateDistributionByRegion'])->name('stateadmin.region.member.distribution.state')->middleware('access_level:stateadmin');
Route::get('/state/region/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByRegion'])->name('stateadmin.region.member.distribution.voter')->middleware('access_level:stateadmin');
Route::get('/state/location/regions', [LocationController::class, 'allRegions'])->name('stateadmin.location.regions')->middleware('access_level:stateadmin');
Route::get('/state/location/region/add', [LocationController::class, 'addRegion'])->name('stateadmin.location.region.add')->middleware('access_level:stateadmin');
Route::post('/state/location/region/store', [LocationController::class, 'storeRegion'])->name('stateadmin.location.region.store')->middleware('access_level:stateadmin');
Route::get('/state/location/region/edit/{uuid}', [LocationController::class, 'editRegion'])->name('stateadmin.location.region.edit')->middleware('access_level:stateadmin');
Route::post('/state/location/region/update/{uuid}', [LocationController::class, 'updateRegion'])->name('stateadmin.location.region.update')->middleware('access_level:stateadmin');
Route::delete('/state/location/region/delete/{uuid}', [LocationController::class, 'deleteRegion'])->name('stateadmin.location.region.delete')->middleware('access_level:stateadmin');

// Manage States
Route::get('/state/location/state/dashboard/{uuid}', [StateAdminController::class, 'StateDashBoard'])->name('stateadmin.location.state.dashboard')->middleware('access_level:stateadmin');
Route::get('/state/state/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByState'])->name('stateadmin.state.member.distribution.gender')->middleware('access_level:stateadmin');
Route::get('/state/state/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByState'])->name('stateadmin.state.member.distribution.age')->middleware('access_level:stateadmin');
Route::get('/state/state/member/distribution/lga/{uuid}', [ChartsController::class, 'LgaDistributionByState'])->name('stateadmin.state.member.distribution.lga')->middleware('access_level:stateadmin');
Route::get('/state/state/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByState'])->name('stateadmin.state.member.distribution.voter')->middleware('access_level:stateadmin');

Route::get('/state/location/states', [LocationController::class, 'allStates'])->name('stateadmin.location.states')->middleware('access_level:stateadmin');
Route::get('/state/location/state/add', [LocationController::class, 'addState'])->name('stateadmin.location.state.add')->middleware('access_level:stateadmin');
Route::post('/state/location/state/store', [LocationController::class, 'storeState'])->name('stateadmin.location.state.store')->middleware('access_level:stateadmin');
Route::get('/state/location/state/edit/{uuid}', [LocationController::class, 'editState'])->name('stateadmin.location.state.edit')->middleware('access_level:stateadmin');
Route::post('/state/location/state/update/{uuid}', [LocationController::class, 'updateState'])->name('stateadmin.location.state.update')->middleware('access_level:stateadmin');
Route::delete('/state/location/state/delete/{uuid}', [LocationController::class, 'deleteState'])->name('stateadmin.location.state.delete')->middleware('access_level:stateadmin');

// Manage Senatorial Districts
Route::get('/state/location/senatorial-district/dashboard/{uuid}', [StateAdminController::class, 'senatorialDistrictDashBoard'])->name('stateadmin.location.senatorial-district.dashboard')->middleware('access_level:stateadmin');
Route::get('/state/senatorial-district/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionBySenatorialDistrict'])->name('stateadmin.senatorial-district.member.distribution.gender')->middleware('access_level:stateadmin');
Route::get('/state/senatorial-district/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionBySenatorialDistrict'])->name('stateadmin.senatorial-district.member.distribution.age')->middleware('access_level:stateadmin');
Route::get('/state/senatorial-district/member/distribution/lga/{uuid}', [ChartsController::class, 'lgaDistributionBySenatorialDistrict'])->name('stateadmin.senatorial-district.member.distribution.lga')->middleware('access_level:stateadmin');
Route::get('/state/senatorial-district/member/distribution/voter/{uuid}', [ChartsController::class, 'VoterDistributionBySenatorialDistrict'])->name('stateadmin.senatorial-district.member.distribution.voter')->middleware('access_level:stateadmin');
Route::get('/state/location/senatorial-districts', [LocationController::class, 'allSenatorialDistricts'])->name('stateadmin.location.senatorial-districts')->middleware('access_level:stateadmin');
Route::get('/state/location/senatorial-district/add', [LocationController::class, 'addSenatorialDistrict'])->name('stateadmin.location.senatorial-district.add')->middleware('access_level:stateadmin');
Route::post('/state/location/senatorial-district/store', [LocationController::class, 'storeSenatorialDistrict'])->name('stateadmin.location.senatorial-district.store')->middleware('access_level:stateadmin');
Route::get('/state/location/senatorial-district/edit/{uuid}', [LocationController::class, 'editSenatorialDistrict'])->name('stateadmin.location.senatorial-district.edit')->middleware('access_level:stateadmin');
Route::post('/state/location/senatorial-district/update/{uuid}', [LocationController::class, 'updateSenatorialDistrict'])->name('stateadmin.location.senatorial-district.update')->middleware('access_level:stateadmin');
Route::delete('/state/location/senatorial-district/delete/{uuid}', [LocationController::class, 'deleteSenatorialDistrict'])->name('stateadmin.location.senatorial-district.delete')->middleware('access_level:stateadmin');

// Manage Federal Constituencies
Route::get('/state/location/federal-constituency/dashboard/{uuid}', [StateAdminController::class, 'federalConstituencyDashBoard'])->name('stateadmin.location.federal-constituency.dashboard')->middleware('access_level:stateadmin');
Route::get('/state/federal-constituency/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByFederalConstituency'])->name('stateadmin.federal-constituency.member.distribution.gender')->middleware('access_level:stateadmin');
Route::get('/state/federal-constituency/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByFederalConstituency'])->name('stateadmin.federal-constituency.member.distribution.age')->middleware('access_level:stateadmin');
Route::get('/state/federal-constituency/member/distribution/lga/{uuid}', [ChartsController::class, 'lgaDistributionByFederalConstituency'])->name('stateadmin.federal-constituency.member.distribution.lga')->middleware('access_level:stateadmin');
Route::get('/state/federal-constituency/member/distribution/voter/{uuid}', [ChartsController::class, 'VoterDistributionByFederalConstituency'])->name('stateadmin.federal-constituency.member.distribution.voter')->middleware('access_level:stateadmin');
Route::get('/state/location/federal-constituencies', [LocationController::class, 'allFederalConstituencies'])->name('stateadmin.location.federal-constituencies')->middleware('access_level:stateadmin');
Route::get('/state/location/federal-constituency/add', [LocationController::class, 'addFederalConstituency'])->name('stateadmin.location.federal-constituency.add')->middleware('access_level:stateadmin');
Route::post('/state/location/federal-constituency/store', [LocationController::class, 'storeFederalConstituency'])->name('stateadmin.location.federal-constituency.store')->middleware('access_level:stateadmin');
Route::get('/state/location/federal-constituency/edit/{uuid}', [LocationController::class, 'editFederalConstituency'])->name('stateadmin.location.federal-constituency.edit')->middleware('access_level:stateadmin');
Route::post('/state/location/federal-constituency/update/{uuid}', [LocationController::class, 'updateFederalConstituency'])->name('stateadmin.location.federal-constituency.update')->middleware('access_level:stateadmin');
Route::delete('/state/location/federal-constituency/delete/{uuid}', [LocationController::class, 'deleteFederalConstituency'])->name('stateadmin.location.federal-constituency.delete')->middleware('access_level:stateadmin');

// manage Local Governments
Route::get('/state/location/localgovernment/dashboard/{uuid}', [StateAdminController::class, 'localGovernmentDashBoard'])->name('stateadmin.location.lga.dashboard')->middleware('access_level:stateadmin');
Route::get('/state/lga/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByLga'])->name('stateadmin.lga.member.distribution.gender')->middleware('access_level:stateadmin');
Route::get('/state/lga/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByLga'])->name('stateadmin.lga.member.distribution.age')->middleware('access_level:stateadmin');
Route::get('/state/lga/member/distribution/ward/{uuid}', [ChartsController::class, 'wardDistributionByLga'])->name('stateadmin.lga.member.distribution.ward')->middleware('access_level:stateadmin');
Route::get('/state/lga/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByLga'])->name('stateadmin.lga.member.distribution.voter')->middleware('access_level:stateadmin');


Route::get('/state/location/localgovernments', [LocationController::class, 'allLocalGovernments'])->name('stateadmin.location.lgas')->middleware('access_level:stateadmin');
Route::get('/state/location/localgovernment/add', [LocationController::class, 'addLocalGovernment'])->name('stateadmin.location.lga.add')->middleware('access_level:stateadmin');
Route::post('/state/location/localgovernment/store', [LocationController::class, 'storeLocalGovernment'])->name('stateadmin.location.lga.store')->middleware('access_level:stateadmin');
Route::get('/state/location/localgovernment/edit/{uuid}', [LocationController::class, 'editLocalGovernment'])->name('stateadmin.location.lga.edit')->middleware('access_level:stateadmin');
Route::post('/state/location/localgovernment/update/{uuid}', [LocationController::class, 'updateLocalGovernment'])->name('stateadmin.location.lga.update')->middleware('access_level:stateadmin');
Route::delete('/state/location/localgovernment/delete/{uuid}', [LocationController::class, 'deleteLocalGovernment'])->name('stateadmin.location.lga.delete')->middleware('access_level:stateadmin');

//import LGA Form
Route::get('/state/location/importlgas', [LocationController::class, 'importLgasForm'])->name('stateadmin.location.lga.import')->middleware('access_level:stateadmin');

// Export Route
Route::get('/state/location/exportlgas', [LocationController::class, 'exportLgas'])->name('stateadmin.location.lga.exportLgas')->middleware('access_level:stateadmin');

// Import Route
Route::post('/state/location/importlgas', [LocationController::class, 'importLgas'])->name('stateadmin.location.lga.importLgas')->middleware('access_level:stateadmin');


// manage Wards
Route::get('/state/location/ward/dashboard/{uuid}', [StateAdminController::class, 'wardDashBoard'])->name('stateadmin.location.ward.dashboard')->middleware('access_level:stateadmin');
Route::get('/state/ward/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByWard'])->name('stateadmin.ward.member.distribution.gender')->middleware('access_level:stateadmin');
Route::get('/state/ward/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByWard'])->name('stateadmin.ward.member.distribution.voter')->middleware('access_level:stateadmin');

Route::get('/state/location/wards', [LocationController::class, 'allWards'])->name('stateadmin.location.wards')->middleware('access_level:stateadmin');
Route::get('/state/location/ward/add', [LocationController::class, 'addWard'])->name('stateadmin.location.ward.add')->middleware('access_level:stateadmin');
Route::post('/state/location/ward/store', [LocationController::class, 'storeWard'])->name('stateadmin.location.ward.store')->middleware('access_level:stateadmin');
Route::get('/state/location/ward/edit/{uuid}', [LocationController::class, 'editWard'])->name('stateadmin.location.ward.edit')->middleware('access_level:stateadmin');
Route::post('/state/location/ward/update/{uuid}', [LocationController::class, 'updateWard'])->name('stateadmin.location.ward.update')->middleware('access_level:stateadmin');
Route::delete('/state/location/ward/delete/{uuid}', [LocationController::class, 'deleteWard'])->name('stateadmin.location.ward.delete')->middleware('access_level:stateadmin');

//import PU Form
Route::get('/state/location/importwards', [LocationController::class, 'importWardsForm'])->name('stateadmin.location.ward.import')->middleware('access_level:stateadmin');

// Export Route
Route::get('/state/location/exportwards', [LocationController::class, 'exportWards'])->name('stateadmin.location.ward.exportWards')->middleware('access_level:stateadmin');

// Import Route
Route::post('/state/location/importwards', [LocationController::class, 'importWards'])->name('stateadmin.location.ward.importWards')->middleware('access_level:stateadmin');

//Manage Polling Units
Route::get('/state/location/pollingunit/dashboard/{uuid}', [StateAdminController::class, 'PuDashBoard'])->name('stateadmin.location.pu.dashboard')->middleware('access_level:stateadmin');
Route::get('/state/pollingunit/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByPu'])->name('stateadmin.pu.member.distribution.gender')->middleware('access_level:stateadmin');
Route::get('/state/pollingunit/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByPu'])->name('stateadmin.pu.member.distribution.voter')->middleware('access_level:stateadmin');



Route::get('/state/location/pollingunits', [LocationController::class, 'allPollingUnits'])->name('stateadmin.location.pus')->middleware('access_level:stateadmin');
Route::get('/state/location/pollingunit/add', [LocationController::class, 'addPollingUnit'])->name('stateadmin.location.pu.add')->middleware('access_level:stateadmin');
Route::post('/state/location/pollingunit/store', [LocationController::class, 'storePollingUnit'])->name('stateadmin.location.pu.store')->middleware('access_level:stateadmin');
Route::get('/state/location/pollingunit/edit/{uuid}', [LocationController::class, 'editPollingUnit'])->name('stateadmin.location.pu.edit')->middleware('access_level:stateadmin');
Route::post('/state/location/pollingunit/update/{uuid}', [LocationController::class, 'updatePollingUnit'])->name('stateadmin.location.pu.update')->middleware('access_level:stateadmin');
Route::delete('/state/location/pollingunit/delete/{uuid}', [LocationController::class, 'deletePollingUnit'])->name('stateadmin.location.pu.delete')->middleware('access_level:stateadmin');

//import PU Form
Route::get('/state/import-polling-units', [LocationController::class, 'importPollingUnitsForm'])->name('stateadmin.location.pu.import')->middleware('access_level:stateadmin');
// Export Route
Route::get('/state/export-polling-units', [LocationController::class, 'exportPollingUnits'])->name('stateadmin.location.pu.exportPollingUnits')->middleware('access_level:stateadmin');

// Import Route
Route::post('/state/import-polling-units', [LocationController::class, 'importPollingUnits'])->name('stateadmin.location.pu.importPollingUnits')->middleware('access_level:stateadmin');


// Election Management CRUD
Route::get('/state/elections', [ElectionController::class, 'allElections'])->name('stateadmin.elections')->middleware('access_level:stateadmin');
Route::get('/state/election/operations-center', [ElectionController::class, 'operationsCenter'])->name('stateadmin.election.operations')->middleware('access_level:stateadmin');
Route::get('/state/election/add', [ElectionController::class, 'addElection'])->name('stateadmin.election.add')->middleware('access_level:stateadmin');
Route::post('/state/election/store', [ElectionController::class, 'storeElection'])->name('stateadmin.election.store')->middleware('access_level:stateadmin');
Route::get('/state/election/edit/{uuid}', [ElectionController::class, 'editElection'])->name('stateadmin.election.edit')->middleware('access_level:stateadmin');
Route::post('/state/election/update/{uuid}', [ElectionController::class, 'updateElection'])->name('stateadmin.election.update')->middleware('access_level:stateadmin');
Route::delete('/state/election/delete/{uuid}', [ElectionController::class, 'deleteElection'])->name('stateadmin.election.delete')->middleware('access_level:stateadmin');

// Election Result Route
Route::get('/state/election/result/{uuid}', [ElectionController::class, 'electionResult'])->name('stateadmin.election.results')->middleware('access_level:stateadmin');

// Election PU Votes Report
Route::get('/state/election/votes/{uuid?}', [ElectionController::class, 'manageVotesByPu'])->name('stateadmin.election.votesByPu')->middleware('access_level:stateadmin');
Route::get('/state/election/votes/data/{uuid}', [ElectionController::class, 'VotesByPuData'])->name('stateadmin.election.votesByPuData')->middleware('access_level:stateadmin');


// Election Incident Report
Route::get('/state/election/incident/{uuid}/{polling_unit_id}', [ElectionController::class, 'manageIncidentByPu'])
    ->name('stateadmin.election.incident')
    ->middleware('access_level:stateadmin');

    Route::get('/state/incident/add', [ElectionController::class, 'addIncident'])->name('stateadmin.incident.add')->middleware('access_level:stateadmin');
    Route::post('/state/incident/store', [ElectionController::class, 'storeIcident'])->name('stateadmin.incident.store')->middleware('access_level:stateadmin');


//Manage Votes
Route::get('/state/votes', [ElectionController::class, 'allVotes'])->name('stateadmin.votes')->middleware('access_level:stateadmin');
Route::get('/state/vote/add', [ElectionController::class, 'addVote'])->name('stateadmin.vote.add')->middleware('access_level:stateadmin');
Route::post('/state/vote/store', [ElectionController::class, 'storeVote'])->name('stateadmin.vote.store')->middleware('access_level:stateadmin');
Route::get('/state/vote/edit/{uuid}', [ElectionController::class, 'editVote'])->name('stateadmin.vote.edit')->middleware('access_level:stateadmin');
Route::post('/state/vote/update/{uuid}', [ElectionController::class, 'updateVote'])->name('stateadmin.vote.update')->middleware('access_level:stateadmin');
Route::delete('/state/vote/delete/{uuid}', [ElectionController::class, 'deleteVote'])->name('stateadmin.vote.delete')->middleware('access_level:stateadmin');



});
