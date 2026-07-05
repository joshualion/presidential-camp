<?php


use App\Http\Controllers\AjaxController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\DashboardStatsController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\wardadmin\WardAdminController;
use Illuminate\Support\Facades\Route;


Route::get('/ward/logout', [WardAdminController::class, 'wardadminLogout'])->name('wardadmin.logout')->middleware(['auth','access_level:wardadmin']);

Route::middleware(['auth','check_onboarding'])->group(function () {

 // Regional Admin Profile & Dashboard Routes
 Route::get('/ward/dashboard', [WardAdminController::class, 'wardAdminDashBoard'])->name('wardadmin.dashboard')->middleware('access_level:wardadmin');
 Route::get('/ward/dashboard/stats', [DashboardStatsController::class, 'cards'])->name('wardadmin.dashboard.stats')->middleware('access_level:wardadmin');
 Route::get('/ward/profile', [WardAdminController::class, 'wardadminProfile'])->name('wardadmin.profile')->middleware('access_level:wardadmin');
 Route::post('/ward/profile/store', [WardAdminController::class, 'wardadminProfileStore'])->name('wardadmin.profile.store')->middleware('access_level:wardadmin');
 Route::get('/ward/change/password', [WardAdminController::class, 'wardadminChangePassword'])->name('wardadmin.change.password')->middleware('access_level:wardadmin');
 Route::post('/ward/update/password', [WardAdminController::class, 'wardadminUpdatePassword'])->name('wardadmin.update.password')->middleware('access_level:wardadmin');


    /*Regional Admin Ajax Routes */

//Gender Distribution
Route::get('/ward/member/distribution/gender', [WardAdminController::class, 'wardadminGenderDistribution'])->name('wardadmin.member.distribution.gender')->middleware('access_level:wardadmin');

// Age Distribution
Route::get('/ward/member/distribution/age', [WardAdminController::class, 'wardadminAgeDistribution'])->name('wardadmin.member.distribution.age')->middleware('access_level:wardadmin');

// Region Distribution
Route::get('/ward/member/distribution/region', [WardAdminController::class, 'wardadminRegionDistribution'])->name('wardadmin.member.distribution.region')->middleware('access_level:wardadmin');
/*End Super Admin Ajax Routes */
// Religion Distribution
Route::get('/ward/member/distribution/religion', [WardAdminController::class, 'wardadminReligionDistribution'])->name('wardadmin.member.distribution.religion')->middleware('access_level:wardadmin');
// Valid Voter Distribution
Route::get('/ward/member/distribution/voter', [WardAdminController::class, 'wardadminVoterDistribution'])->name('wardadmin.member.distribution.voter')->middleware('access_level:wardadmin');
// State Distribution
Route::get('/ward/member/distribution/state', [WardAdminController::class, 'wardadminStateDistribution'])->name('wardadmin.member.distribution.state')->middleware('access_level:wardadmin');


//Member Management
Route::get('/ward/members/data/fetch/{uuid?}', [MembersController::class, 'getMembersData'])->name('wardadmin.members.data');
Route::get('/ward/members/fetch/{uuid?}', [MembersController::class, 'allMembers'])->name('wardadmin.members');
//Fetch Excos
Route::get('/ward/leaders/data/fetch/{uuid?}', [MembersController::class, 'getExcoMembersData'])->name('wardadmin.leaders.data');
Route::get('/ward/leaders/fetch/{uuid?}', [MembersController::class, 'allExcoMembers'])->name('wardadmin.leaders');
//Fetch Regulars
Route::get('/ward/regulars/data/fetch/{uuid?}', [MembersController::class, 'getRegularMembersData'])->name('wardadmin.regulars.data');
Route::get('/ward/regulars/fetch/{uuid?}', [MembersController::class, 'allRegularMembers'])->name('wardadmin.regulars');
Route::get('/ward/people/{metric}/data/fetch/{uuid?}', [MembersController::class, 'getPeopleMetricMembersData'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('wardadmin.peopleMetric.data');
Route::get('/ward/people/{metric}/fetch/{uuid?}', [MembersController::class, 'allPeopleMetricMembers'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('wardadmin.peopleMetric');

Route::get('/ward/member/add', [MembersController::class, 'addMember'])->name('wardadmin.member.add')->middleware('access_level:wardadmin');
Route::get('/ward/members/import', [MembersController::class, 'importMembers'])->name('wardadmin.member.import')->middleware('access_level:wardadmin');
Route::get('/ward/members/import/template', [MembersController::class, 'downloadMemberImportTemplate'])->name('wardadmin.member.import.template')->middleware('access_level:wardadmin');
Route::post('/ward/members/import', [MembersController::class, 'storeMemberImport'])->name('wardadmin.member.import.store')->middleware('access_level:wardadmin');
Route::post('/ward/member/store', [MembersController::class, 'storeMember'])->name('wardadmin.member.store')->middleware('access_level:wardadmin');
Route::get('/ward/member/edit/{uuid}', [MembersController::class, 'editMember'])->name('wardadmin.member.edit')->middleware('access_level:wardadmin');
Route::put('/ward/member/update/', [MembersController::class, 'updateMember'])->name('wardadmin.member.update')->middleware('access_level:wardadmin');
Route::get('/ward/member/view/{uuid}', [MembersController::class, 'viewMember'])->name('wardadmin.member.view')->middleware('access_level:wardadmin');
Route::get('/ward/member/suspend/{uuid}', [MembersController::class, 'suspendMember'])->name('wardadmin.member.suspend')->middleware('access_level:wardadmin');
Route::delete('/ward/member/delete/{uuid}', [MembersController::class, 'deleteMember'])->name('wardadmin.member.delete')->middleware('access_level:wardadmin');

// Members By State
Route::get('/ward/members/bystate/{uuid?}', [MembersController::class, 'membersByStates'])->name('wardadmin.members.byState')->middleware('access_level:wardadmin,regionadmin,lgaadmin');
Route::get('/ward/members/ward/{uuid}', [MembersController::class, 'ViewMembersByState'])->name('wardadmin.member.state.view')->middleware('access_level:wardadmin,regionadmin,regionadmin,lgaadmin');
Route::get('/ward/state/members/data/{uuid}', [MembersController::class, 'getStateMembersData'])->name('wardadmin.state.members.data');
// Members By Local Government
Route::get('/ward/members/bylga/{uuid?}', [MembersController::class, 'membersByLocalGovernments'])->name('wardadmin.members.byLga')->middleware('access_level:wardadmin');
Route::get('/ward/members/ward/{uuid}', [MembersController::class, 'viewMembersByLocalGovernments'])->name('wardadmin.member.lga.view')->middleware('access_level:wardadmin');
Route::get('/ward/lga/members/data/{uuid}', [MembersController::class, 'getLgaMembersData'])->name('wardadmin.lga.members.data');
// Members By Ward
Route::get('/ward/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('wardadmin.members.byWard')->middleware('access_level:wardadmin');
Route::get('/ward/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('wardadmin.members.byWard.ajax')->middleware('access_level:wardadmin');
Route::get('/ward/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('wardadmin.member.ward.view')->middleware('access_level:wardadmin');
Route::get('/ward/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('wardadmin.ward.members.data');
// Members By Ward
Route::get('/ward/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('wardadmin.members.byWard')->middleware('access_level:wardadmin');
Route::get('/ward/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('wardadmin.members.byWard.ajax')->middleware('access_level:wardadmin');
Route::get('/ward/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('wardadmin.member.ward.view')->middleware('access_level:wardadmin');
Route::get('/ward/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('wardadmin.ward.members.data');

// Members By Pu
Route::get('/ward/members/bypu/{uuid?}', [MembersController::class, 'membersByPus'])->name('wardadmin.members.byPu')->middleware('access_level:wardadmin');
Route::get('/ward/members/bypu/ajax/{uuid?}', [AjaxController::class, 'membersByPusAjax'])->name('wardadmin.members.byPu.ajax')->middleware('access_level:wardadmin');
Route::get('/ward/members/pu/{uuid}', [MembersController::class, 'viewMembersByPu'])->name('wardadmin.member.pu.view')->middleware('access_level:wardadmin');
Route::get('/ward/pu/members/data/{uuid}', [MembersController::class, 'getPuMembersData'])->name('wardadmin.pu.members.data');

//Locations Management Routes


//Manage Regions
Route::get('/ward/location/region/dashboard/{uuid}', [WardAdminController::class, 'RegionDashBoard'])->name('wardadmin.location.region.dashboard')->middleware('access_level:wardadmin');
Route::get('/ward/region/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByRegion'])->name('wardadmin.region.member.distribution.gender')->middleware('access_level:wardadmin');
Route::get('/ward/region/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByRegion'])->name('wardadmin.region.member.distribution.age')->middleware('access_level:wardadmin');
Route::get('/ward/region/member/distribution/ward/{uuid}', [ChartsController::class, 'StateDistributionByRegion'])->name('wardadmin.region.member.distribution.state')->middleware('access_level:wardadmin');
Route::get('/ward/region/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByRegion'])->name('wardadmin.region.member.distribution.voter')->middleware('access_level:wardadmin');
Route::get('/ward/location/regions', [LocationController::class, 'allRegions'])->name('wardadmin.location.regions')->middleware('access_level:wardadmin');
Route::get('/ward/location/region/add', [LocationController::class, 'addRegion'])->name('wardadmin.location.region.add')->middleware('access_level:wardadmin');
Route::post('/ward/location/region/store', [LocationController::class, 'storeRegion'])->name('wardadmin.location.region.store')->middleware('access_level:wardadmin');
Route::get('/ward/location/region/edit/{uuid}', [LocationController::class, 'editRegion'])->name('wardadmin.location.region.edit')->middleware('access_level:wardadmin');
Route::post('/ward/location/region/update/{uuid}', [LocationController::class, 'updateRegion'])->name('wardadmin.location.region.update')->middleware('access_level:wardadmin');
Route::delete('/ward/location/region/delete/{uuid}', [LocationController::class, 'deleteRegion'])->name('wardadmin.location.region.delete')->middleware('access_level:wardadmin');

// Manage States
Route::get('/ward/location/ward/dashboard/{uuid}', [WardAdminController::class, 'StateDashBoard'])->name('wardadmin.location.state.dashboard')->middleware('access_level:wardadmin');
Route::get('/ward/state/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByState'])->name('wardadmin.state.member.distribution.gender')->middleware('access_level:wardadmin');
Route::get('/ward/state/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByState'])->name('wardadmin.state.member.distribution.age')->middleware('access_level:wardadmin');
Route::get('/ward/state/member/distribution/ward/{uuid}', [ChartsController::class, 'LgaDistributionByState'])->name('wardadmin.state.member.distribution.lga')->middleware('access_level:wardadmin');
Route::get('/ward/state/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByState'])->name('wardadmin.state.member.distribution.voter')->middleware('access_level:wardadmin');

Route::get('/ward/location/states', [LocationController::class, 'allStates'])->name('wardadmin.location.states')->middleware('access_level:wardadmin');
Route::get('/ward/location/ward/add', [LocationController::class, 'addState'])->name('wardadmin.location.state.add')->middleware('access_level:wardadmin');
Route::post('/ward/location/ward/store', [LocationController::class, 'storeState'])->name('wardadmin.location.state.store')->middleware('access_level:wardadmin');
Route::get('/ward/location/ward/edit/{uuid}', [LocationController::class, 'editState'])->name('wardadmin.location.state.edit')->middleware('access_level:wardadmin');
Route::post('/ward/location/ward/update/{uuid}', [LocationController::class, 'updateState'])->name('wardadmin.location.state.update')->middleware('access_level:wardadmin');
Route::delete('/ward/location/ward/delete/{uuid}', [LocationController::class, 'deleteState'])->name('wardadmin.location.state.delete')->middleware('access_level:wardadmin');

// manage Local Governments
Route::get('/ward/location/localgovernment/dashboard/{uuid}', [WardAdminController::class, 'localGovernmentDashBoard'])->name('wardadmin.location.lga.dashboard')->middleware('access_level:wardadmin');
Route::get('/ward/lga/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByLga'])->name('wardadmin.lga.member.distribution.gender')->middleware('access_level:wardadmin');
Route::get('/ward/lga/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByLga'])->name('wardadmin.lga.member.distribution.age')->middleware('access_level:wardadmin');
Route::get('/ward/lga/member/distribution/ward/{uuid}', [ChartsController::class, 'wardDistributionByLga'])->name('wardadmin.lga.member.distribution.ward')->middleware('access_level:wardadmin');
Route::get('/ward/lga/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByLga'])->name('wardadmin.lga.member.distribution.voter')->middleware('access_level:wardadmin');


Route::get('/ward/location/localgovernments', [LocationController::class, 'allLocalGovernments'])->name('wardadmin.location.lgas')->middleware('access_level:wardadmin');
Route::get('/ward/location/localgovernment/add', [LocationController::class, 'addLocalGovernment'])->name('wardadmin.location.lga.add')->middleware('access_level:wardadmin');
Route::post('/ward/location/localgovernment/store', [LocationController::class, 'storeLocalGovernment'])->name('wardadmin.location.lga.store')->middleware('access_level:wardadmin');
Route::get('/ward/location/localgovernment/edit/{uuid}', [LocationController::class, 'editLocalGovernment'])->name('wardadmin.location.lga.edit')->middleware('access_level:wardadmin');
Route::post('/ward/location/localgovernment/update/{uuid}', [LocationController::class, 'updateLocalGovernment'])->name('wardadmin.location.lga.update')->middleware('access_level:wardadmin');
Route::delete('/ward/location/localgovernment/delete/{uuid}', [LocationController::class, 'deleteLocalGovernment'])->name('wardadmin.location.lga.delete')->middleware('access_level:wardadmin');

//import LGA Form
Route::get('/ward/location/importlgas', [LocationController::class, 'importLgasForm'])->name('wardadmin.location.lga.import')->middleware('access_level:wardadmin');

// Export Route
Route::get('/ward/location/exportlgas', [LocationController::class, 'exportLgas'])->name('wardadmin.location.lga.exportLgas')->middleware('access_level:wardadmin');

// Import Route
Route::post('/ward/location/importlgas', [LocationController::class, 'importLgas'])->name('wardadmin.location.lga.importLgas')->middleware('access_level:wardadmin');


// manage Wards
Route::get('/ward/location/ward/dashboard/{uuid}', [WardAdminController::class, 'wardDashBoard'])->name('wardadmin.location.ward.dashboard')->middleware('access_level:wardadmin');
Route::get('/ward/ward/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByWard'])->name('wardadmin.ward.member.distribution.gender')->middleware('access_level:wardadmin');
Route::get('/ward/ward/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByWard'])->name('wardadmin.ward.member.distribution.voter')->middleware('access_level:wardadmin');

Route::get('/ward/location/wards', [LocationController::class, 'allWards'])->name('wardadmin.location.wards')->middleware('access_level:wardadmin');
Route::get('/ward/location/ward/add', [LocationController::class, 'addWard'])->name('wardadmin.location.ward.add')->middleware('access_level:wardadmin');
Route::post('/ward/location/ward/store', [LocationController::class, 'storeWard'])->name('wardadmin.location.ward.store')->middleware('access_level:wardadmin');
Route::get('/ward/location/ward/edit/{uuid}', [LocationController::class, 'editWard'])->name('wardadmin.location.ward.edit')->middleware('access_level:wardadmin');
Route::post('/ward/location/ward/update/{uuid}', [LocationController::class, 'updateWard'])->name('wardadmin.location.ward.update')->middleware('access_level:wardadmin');
Route::delete('/ward/location/ward/delete/{uuid}', [LocationController::class, 'deleteWard'])->name('wardadmin.location.ward.delete')->middleware('access_level:wardadmin');

//import PU Form
Route::get('/ward/location/importwards', [LocationController::class, 'importWardsForm'])->name('wardadmin.location.ward.import')->middleware('access_level:wardadmin');

// Export Route
Route::get('/ward/location/exportwards', [LocationController::class, 'exportWards'])->name('wardadmin.location.ward.exportWards')->middleware('access_level:wardadmin');

// Import Route
Route::post('/ward/location/importwards', [LocationController::class, 'importWards'])->name('wardadmin.location.ward.importWards')->middleware('access_level:wardadmin');

//Manage Polling Units
Route::get('/ward/location/pollingunit/dashboard/{uuid}', [WardAdminController::class, 'PuDashBoard'])->name('wardadmin.location.pu.dashboard')->middleware('access_level:wardadmin');
Route::get('/ward/pollingunit/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByPu'])->name('wardadmin.pu.member.distribution.gender')->middleware('access_level:wardadmin');
Route::get('/ward/pollingunit/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByPu'])->name('wardadmin.pu.member.distribution.voter')->middleware('access_level:wardadmin');



Route::get('/ward/location/pollingunits', [LocationController::class, 'allPollingUnits'])->name('wardadmin.location.pus')->middleware('access_level:wardadmin');
Route::get('/ward/location/pollingunit/add', [LocationController::class, 'addPollingUnit'])->name('wardadmin.location.pu.add')->middleware('access_level:wardadmin');
Route::post('/ward/location/pollingunit/store', [LocationController::class, 'storePollingUnit'])->name('wardadmin.location.pu.store')->middleware('access_level:wardadmin');
Route::get('/ward/location/pollingunit/edit/{uuid}', [LocationController::class, 'editPollingUnit'])->name('wardadmin.location.pu.edit')->middleware('access_level:wardadmin');
Route::post('/ward/location/pollingunit/update/{uuid}', [LocationController::class, 'updatePollingUnit'])->name('wardadmin.location.pu.update')->middleware('access_level:wardadmin');
Route::delete('/ward/location/pollingunit/delete/{uuid}', [LocationController::class, 'deletePollingUnit'])->name('wardadmin.location.pu.delete')->middleware('access_level:wardadmin');

//import PU Form
Route::get('/ward/import-polling-units', [LocationController::class, 'importPollingUnitsForm'])->name('wardadmin.location.pu.import')->middleware('access_level:wardadmin');
// Export Route
Route::get('/ward/export-polling-units', [LocationController::class, 'exportPollingUnits'])->name('wardadmin.location.pu.exportPollingUnits')->middleware('access_level:wardadmin');

// Import Route
Route::post('/ward/import-polling-units', [LocationController::class, 'importPollingUnits'])->name('wardadmin.location.pu.importPollingUnits')->middleware('access_level:wardadmin');


// Election Management CRUD
Route::get('/ward/elections', [ElectionController::class, 'allElections'])->name('wardadmin.elections')->middleware('access_level:wardadmin');
Route::get('/ward/election/operations-center', [ElectionController::class, 'operationsCenter'])->name('wardadmin.election.operations')->middleware('access_level:wardadmin');
Route::get('/ward/election/add', [ElectionController::class, 'addElection'])->name('wardadmin.election.add')->middleware('access_level:wardadmin');
Route::post('/ward/election/store', [ElectionController::class, 'storeElection'])->name('wardadmin.election.store')->middleware('access_level:wardadmin');
Route::get('/ward/election/edit/{uuid}', [ElectionController::class, 'editElection'])->name('wardadmin.election.edit')->middleware('access_level:wardadmin');
Route::post('/ward/election/update/{uuid}', [ElectionController::class, 'updateElection'])->name('wardadmin.election.update')->middleware('access_level:wardadmin');
Route::delete('/ward/election/delete/{uuid}', [ElectionController::class, 'deleteElection'])->name('wardadmin.election.delete')->middleware('access_level:wardadmin');

// Election Result Route
Route::get('/ward/election/result/{uuid}', [ElectionController::class, 'electionResult'])->name('wardadmin.election.results')->middleware('access_level:wardadmin');

// Election PU Votes Report
Route::get('/ward/election/votes/{uuid?}', [ElectionController::class, 'manageVotesByPu'])->name('wardadmin.election.votesByPu')->middleware('access_level:wardadmin');
Route::get('/ward/election/votes/data/{uuid}', [ElectionController::class, 'VotesByPuData'])->name('wardadmin.election.votesByPuData')->middleware('access_level:wardadmin');


// Election Incident Report
Route::get('/ward/election/incident/{uuid}/{polling_unit_id}', [ElectionController::class, 'manageIncidentByPu'])
    ->name('wardadmin.election.incident')
    ->middleware('access_level:wardadmin');

    Route::get('/ward/incident/add', [ElectionController::class, 'addIncident'])->name('wardadmin.incident.add')->middleware('access_level:wardadmin');
    Route::post('/ward/incident/store', [ElectionController::class, 'storeIcident'])->name('wardadmin.incident.store')->middleware('access_level:wardadmin');


//Manage Votes
Route::get('/ward/votes', [ElectionController::class, 'allVotes'])->name('wardadmin.votes')->middleware('access_level:wardadmin');
Route::get('/ward/vote/add', [ElectionController::class, 'addVote'])->name('wardadmin.vote.add')->middleware('access_level:wardadmin');
Route::post('/ward/vote/store', [ElectionController::class, 'storeVote'])->name('wardadmin.vote.store')->middleware('access_level:wardadmin');
Route::get('/ward/vote/edit/{uuid}', [ElectionController::class, 'editVote'])->name('wardadmin.vote.edit')->middleware('access_level:wardadmin');
Route::post('/ward/vote/update/{uuid}', [ElectionController::class, 'updateVote'])->name('wardadmin.vote.update')->middleware('access_level:wardadmin');
Route::delete('/ward/vote/delete/{uuid}', [ElectionController::class, 'deleteVote'])->name('wardadmin.vote.delete')->middleware('access_level:wardadmin');



});
