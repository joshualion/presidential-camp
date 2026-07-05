<?php


use App\Http\Controllers\AjaxController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\DashboardStatsController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\lgaadmin\LgaAdminController;
use Illuminate\Support\Facades\Route;


Route::get('/lga/logout', [LgaAdminController::class, 'lgaadminLogout'])->name('lgaadmin.logout')->middleware(['auth','access_level:lgaadmin']);


Route::middleware(['auth','check_onboarding'])->group(function () {

 // Regional Admin Profile & Dashboard Routes
 Route::get('/lga/dashboard', [LgaAdminController::class, 'lgaAdminDashBoard'])->name('lgaadmin.dashboard')->middleware('access_level:lgaadmin');
 Route::get('/lga/dashboard/stats', [DashboardStatsController::class, 'cards'])->name('lgaadmin.dashboard.stats')->middleware('access_level:lgaadmin');
 Route::get('/lga/profile', [LgaAdminController::class, 'lgaadminProfile'])->name('lgaadmin.profile')->middleware('access_level:lgaadmin');
 Route::post('/lga/profile/store', [LgaAdminController::class, 'lgaadminProfileStore'])->name('lgaadmin.profile.store')->middleware('access_level:lgaadmin');
 Route::get('/lga/change/password', [LgaAdminController::class, 'lgaadminChangePassword'])->name('lgaadmin.change.password')->middleware('access_level:lgaadmin');
 Route::post('/lga/update/password', [LgaAdminController::class, 'lgaadminUpdatePassword'])->name('lgaadmin.update.password')->middleware('access_level:lgaadmin');
 


    /*Regional Admin Ajax Routes */

//Gender Distribution
Route::get('/lga/member/distribution/gender', [LgaAdminController::class, 'lgaadminGenderDistribution'])->name('lgaadmin.member.distribution.gender')->middleware('access_level:lgaadmin');

// Age Distribution
Route::get('/lga/member/distribution/age', [LgaAdminController::class, 'lgaadminAgeDistribution'])->name('lgaadmin.member.distribution.age')->middleware('access_level:lgaadmin');

// Region Distribution
Route::get('/lga/member/distribution/region', [LgaAdminController::class, 'lgaadminRegionDistribution'])->name('lgaadmin.member.distribution.region')->middleware('access_level:lgaadmin');
/*End Super Admin Ajax Routes */
// Religion Distribution
Route::get('/lga/member/distribution/religion', [LgaAdminController::class, 'lgaadminReligionDistribution'])->name('lgaadmin.member.distribution.religion')->middleware('access_level:lgaadmin');
// Valid Voter Distribution
Route::get('/lga/member/distribution/voter', [LgaAdminController::class, 'lgaadminVoterDistribution'])->name('lgaadmin.member.distribution.voter')->middleware('access_level:lgaadmin');
// State Distribution
Route::get('/lga/member/distribution/state', [LgaAdminController::class, 'lgaadminStateDistribution'])->name('lgaadmin.member.distribution.state')->middleware('access_level:lgaadmin');


//Member Management
Route::get('/lga/members/data/fetch/{uuid?}', [MembersController::class, 'getMembersData'])->name('lgaadmin.members.data');
Route::get('/lga/members/fetch/{uuid?}', [MembersController::class, 'allMembers'])->name('lgaadmin.members');
//Fetch Excos
Route::get('/lga/leaders/data/fetch/{uuid?}', [MembersController::class, 'getExcoMembersData'])->name('lgaadmin.leaders.data');
Route::get('/lga/leaders/fetch/{uuid?}', [MembersController::class, 'allExcoMembers'])->name('lgaadmin.leaders');
//Fetch Regulars
Route::get('/lga/regulars/data/fetch/{uuid?}', [MembersController::class, 'getRegularMembersData'])->name('lgaadmin.regulars.data');
Route::get('/lga/regulars/fetch/{uuid?}', [MembersController::class, 'allRegularMembers'])->name('lgaadmin.regulars');
Route::get('/lga/people/{metric}/data/fetch/{uuid?}', [MembersController::class, 'getPeopleMetricMembersData'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('lgaadmin.peopleMetric.data');
Route::get('/lga/people/{metric}/fetch/{uuid?}', [MembersController::class, 'allPeopleMetricMembers'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('lgaadmin.peopleMetric');

Route::get('/lga/member/add', [MembersController::class, 'addMember'])->name('lgaadmin.member.add')->middleware('access_level:lgaadmin');
Route::get('/lga/members/import', [MembersController::class, 'importMembers'])->name('lgaadmin.member.import')->middleware('access_level:lgaadmin');
Route::get('/lga/members/import/template', [MembersController::class, 'downloadMemberImportTemplate'])->name('lgaadmin.member.import.template')->middleware('access_level:lgaadmin');
Route::post('/lga/members/import', [MembersController::class, 'storeMemberImport'])->name('lgaadmin.member.import.store')->middleware('access_level:lgaadmin');
Route::post('/lga/member/store', [MembersController::class, 'storeMember'])->name('lgaadmin.member.store')->middleware('access_level:lgaadmin');
Route::get('/lga/member/edit/{uuid}', [MembersController::class, 'editMember'])->name('lgaadmin.member.edit')->middleware('access_level:lgaadmin');
Route::put('/lga/member/update/', [MembersController::class, 'updateMember'])->name('lgaadmin.member.update')->middleware('access_level:lgaadmin');
Route::get('/lga/member/view/{uuid}', [MembersController::class, 'viewMember'])->name('lgaadmin.member.view')->middleware('access_level:lgaadmin');
Route::get('/lga/member/suspend/{uuid}', [MembersController::class, 'suspendMember'])->name('lgaadmin.member.suspend')->middleware('access_level:lgaadmin');
Route::delete('/lga/member/delete/{uuid}', [MembersController::class, 'deleteMember'])->name('lgaadmin.member.delete')->middleware('access_level:lgaadmin');

// Members By State
Route::get('/lga/members/bystate/{uuid?}', [MembersController::class, 'membersByStates'])->name('lgaadmin.members.byState')->middleware('access_level:lgaadmin,regionadmin,lgaadmin');
Route::get('/lga/members/lga/{uuid}', [MembersController::class, 'ViewMembersByState'])->name('lgaadmin.member.state.view')->middleware('access_level:lgaadmin,regionadmin,regionadmin,lgaadmin');
Route::get('/lga/state/members/data/{uuid}', [MembersController::class, 'getStateMembersData'])->name('lgaadmin.state.members.data');
// Members By Local Government
Route::get('/lga/members/bylga/{uuid?}', [MembersController::class, 'membersByLocalGovernments'])->name('lgaadmin.members.byLga')->middleware('access_level:lgaadmin');
Route::get('/lga/members/lga/{uuid}', [MembersController::class, 'viewMembersByLocalGovernments'])->name('lgaadmin.member.lga.view')->middleware('access_level:lgaadmin');
Route::get('/lga/lga/members/data/{uuid}', [MembersController::class, 'getLgaMembersData'])->name('lgaadmin.lga.members.data');
// Members By Ward
Route::get('/lga/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('lgaadmin.members.byWard')->middleware('access_level:lgaadmin');
Route::get('/lga/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('lgaadmin.members.byWard.ajax')->middleware('access_level:lgaadmin');
Route::get('/lga/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('lgaadmin.member.ward.view')->middleware('access_level:lgaadmin');
Route::get('/lga/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('lgaadmin.ward.members.data');
// Members By Ward
Route::get('/lga/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('lgaadmin.members.byWard')->middleware('access_level:lgaadmin');
Route::get('/lga/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('lgaadmin.members.byWard.ajax')->middleware('access_level:lgaadmin');
Route::get('/lga/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('lgaadmin.member.ward.view')->middleware('access_level:lgaadmin');
Route::get('/lga/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('lgaadmin.ward.members.data');

// Members By Pu
Route::get('/lga/members/bypu/{uuid?}', [MembersController::class, 'membersByPus'])->name('lgaadmin.members.byPu')->middleware('access_level:lgaadmin');
Route::get('/lga/members/bypu/ajax/{uuid?}', [AjaxController::class, 'membersByPusAjax'])->name('lgaadmin.members.byPu.ajax')->middleware('access_level:lgaadmin');
Route::get('/lga/members/pu/{uuid}', [MembersController::class, 'viewMembersByPu'])->name('lgaadmin.member.pu.view')->middleware('access_level:lgaadmin');
Route::get('/lga/pu/members/data/{uuid}', [MembersController::class, 'getPuMembersData'])->name('lgaadmin.pu.members.data');

//Locations Management Routes


//Manage Regions
Route::get('/lga/location/region/dashboard/{uuid}', [LgaAdminController::class, 'RegionDashBoard'])->name('lgaadmin.location.region.dashboard')->middleware('access_level:lgaadmin');
Route::get('/lga/region/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByRegion'])->name('lgaadmin.region.member.distribution.gender')->middleware('access_level:lgaadmin');
Route::get('/lga/region/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByRegion'])->name('lgaadmin.region.member.distribution.age')->middleware('access_level:lgaadmin');
Route::get('/lga/region/member/distribution/lga/{uuid}', [ChartsController::class, 'StateDistributionByRegion'])->name('lgaadmin.region.member.distribution.state')->middleware('access_level:lgaadmin');
Route::get('/lga/region/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByRegion'])->name('lgaadmin.region.member.distribution.voter')->middleware('access_level:lgaadmin');
Route::get('/lga/location/regions', [LocationController::class, 'allRegions'])->name('lgaadmin.location.regions')->middleware('access_level:lgaadmin');
Route::get('/lga/location/region/add', [LocationController::class, 'addRegion'])->name('lgaadmin.location.region.add')->middleware('access_level:lgaadmin');
Route::post('/lga/location/region/store', [LocationController::class, 'storeRegion'])->name('lgaadmin.location.region.store')->middleware('access_level:lgaadmin');
Route::get('/lga/location/region/edit/{uuid}', [LocationController::class, 'editRegion'])->name('lgaadmin.location.region.edit')->middleware('access_level:lgaadmin');
Route::post('/lga/location/region/update/{uuid}', [LocationController::class, 'updateRegion'])->name('lgaadmin.location.region.update')->middleware('access_level:lgaadmin');
Route::delete('/lga/location/region/delete/{uuid}', [LocationController::class, 'deleteRegion'])->name('lgaadmin.location.region.delete')->middleware('access_level:lgaadmin');

// Manage States
Route::get('/lga/location/lga/dashboard/{uuid}', [LgaAdminController::class, 'StateDashBoard'])->name('lgaadmin.location.state.dashboard')->middleware('access_level:lgaadmin');
Route::get('/lga/state/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByState'])->name('lgaadmin.state.member.distribution.gender')->middleware('access_level:lgaadmin');
Route::get('/lga/state/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByState'])->name('lgaadmin.state.member.distribution.age')->middleware('access_level:lgaadmin');
Route::get('/lga/state/member/distribution/lga/{uuid}', [ChartsController::class, 'LgaDistributionByState'])->name('lgaadmin.state.member.distribution.lga')->middleware('access_level:lgaadmin');
Route::get('/lga/state/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByState'])->name('lgaadmin.state.member.distribution.voter')->middleware('access_level:lgaadmin');

Route::get('/lga/location/states', [LocationController::class, 'allStates'])->name('lgaadmin.location.states')->middleware('access_level:lgaadmin');
Route::get('/lga/location/lga/add', [LocationController::class, 'addState'])->name('lgaadmin.location.state.add')->middleware('access_level:lgaadmin');
Route::post('/lga/location/lga/store', [LocationController::class, 'storeState'])->name('lgaadmin.location.state.store')->middleware('access_level:lgaadmin');
Route::get('/lga/location/lga/edit/{uuid}', [LocationController::class, 'editState'])->name('lgaadmin.location.state.edit')->middleware('access_level:lgaadmin');
Route::post('/lga/location/lga/update/{uuid}', [LocationController::class, 'updateState'])->name('lgaadmin.location.state.update')->middleware('access_level:lgaadmin');
Route::delete('/lga/location/lga/delete/{uuid}', [LocationController::class, 'deleteState'])->name('lgaadmin.location.state.delete')->middleware('access_level:lgaadmin');

// manage Local Governments
Route::get('/lga/location/localgovernment/dashboard/{uuid}', [LgaAdminController::class, 'localGovernmentDashBoard'])->name('lgaadmin.location.lga.dashboard')->middleware('access_level:lgaadmin');
Route::get('/lga/lga/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByLga'])->name('lgaadmin.lga.member.distribution.gender')->middleware('access_level:lgaadmin');
Route::get('/lga/lga/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByLga'])->name('lgaadmin.lga.member.distribution.age')->middleware('access_level:lgaadmin');
Route::get('/lga/lga/member/distribution/ward/{uuid}', [ChartsController::class, 'wardDistributionByLga'])->name('lgaadmin.lga.member.distribution.ward')->middleware('access_level:lgaadmin');
Route::get('/lga/lga/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByLga'])->name('lgaadmin.lga.member.distribution.voter')->middleware('access_level:lgaadmin');


Route::get('/lga/location/localgovernments', [LocationController::class, 'allLocalGovernments'])->name('lgaadmin.location.lgas')->middleware('access_level:lgaadmin');
Route::get('/lga/location/localgovernment/add', [LocationController::class, 'addLocalGovernment'])->name('lgaadmin.location.lga.add')->middleware('access_level:lgaadmin');
Route::post('/lga/location/localgovernment/store', [LocationController::class, 'storeLocalGovernment'])->name('lgaadmin.location.lga.store')->middleware('access_level:lgaadmin');
Route::get('/lga/location/localgovernment/edit/{uuid}', [LocationController::class, 'editLocalGovernment'])->name('lgaadmin.location.lga.edit')->middleware('access_level:lgaadmin');
Route::post('/lga/location/localgovernment/update/{uuid}', [LocationController::class, 'updateLocalGovernment'])->name('lgaadmin.location.lga.update')->middleware('access_level:lgaadmin');
Route::delete('/lga/location/localgovernment/delete/{uuid}', [LocationController::class, 'deleteLocalGovernment'])->name('lgaadmin.location.lga.delete')->middleware('access_level:lgaadmin');

//import LGA Form
Route::get('/lga/location/importlgas', [LocationController::class, 'importLgasForm'])->name('lgaadmin.location.lga.import')->middleware('access_level:lgaadmin');

// Export Route
Route::get('/lga/location/exportlgas', [LocationController::class, 'exportLgas'])->name('lgaadmin.location.lga.exportLgas')->middleware('access_level:lgaadmin');

// Import Route
Route::post('/lga/location/importlgas', [LocationController::class, 'importLgas'])->name('lgaadmin.location.lga.importLgas')->middleware('access_level:lgaadmin');


// manage Wards
Route::get('/lga/location/ward/dashboard/{uuid}', [LgaAdminController::class, 'wardDashBoard'])->name('lgaadmin.location.ward.dashboard')->middleware('access_level:lgaadmin');
Route::get('/lga/ward/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByWard'])->name('lgaadmin.ward.member.distribution.gender')->middleware('access_level:lgaadmin');
Route::get('/lga/ward/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByWard'])->name('lgaadmin.ward.member.distribution.voter')->middleware('access_level:lgaadmin');

Route::get('/lga/location/wards', [LocationController::class, 'allWards'])->name('lgaadmin.location.wards')->middleware('access_level:lgaadmin');
Route::get('/lga/location/ward/add', [LocationController::class, 'addWard'])->name('lgaadmin.location.ward.add')->middleware('access_level:lgaadmin');
Route::post('/lga/location/ward/store', [LocationController::class, 'storeWard'])->name('lgaadmin.location.ward.store')->middleware('access_level:lgaadmin');
Route::get('/lga/location/ward/edit/{uuid}', [LocationController::class, 'editWard'])->name('lgaadmin.location.ward.edit')->middleware('access_level:lgaadmin');
Route::post('/lga/location/ward/update/{uuid}', [LocationController::class, 'updateWard'])->name('lgaadmin.location.ward.update')->middleware('access_level:lgaadmin');
Route::delete('/lga/location/ward/delete/{uuid}', [LocationController::class, 'deleteWard'])->name('lgaadmin.location.ward.delete')->middleware('access_level:lgaadmin');

//import PU Form
Route::get('/lga/location/importwards', [LocationController::class, 'importWardsForm'])->name('lgaadmin.location.ward.import')->middleware('access_level:lgaadmin');

// Export Route
Route::get('/lga/location/exportwards', [LocationController::class, 'exportWards'])->name('lgaadmin.location.ward.exportWards')->middleware('access_level:lgaadmin');

// Import Route
Route::post('/lga/location/importwards', [LocationController::class, 'importWards'])->name('lgaadmin.location.ward.importWards')->middleware('access_level:lgaadmin');

//Manage Polling Units
Route::get('/lga/location/pollingunit/dashboard/{uuid}', [LgaAdminController::class, 'PuDashBoard'])->name('lgaadmin.location.pu.dashboard')->middleware('access_level:lgaadmin');
Route::get('/lga/pollingunit/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByPu'])->name('lgaadmin.pu.member.distribution.gender')->middleware('access_level:lgaadmin');
Route::get('/lga/pollingunit/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByPu'])->name('lgaadmin.pu.member.distribution.voter')->middleware('access_level:lgaadmin');



Route::get('/lga/location/pollingunits', [LocationController::class, 'allPollingUnits'])->name('lgaadmin.location.pus')->middleware('access_level:lgaadmin');
Route::get('/lga/location/pollingunit/add', [LocationController::class, 'addPollingUnit'])->name('lgaadmin.location.pu.add')->middleware('access_level:lgaadmin');
Route::post('/lga/location/pollingunit/store', [LocationController::class, 'storePollingUnit'])->name('lgaadmin.location.pu.store')->middleware('access_level:lgaadmin');
Route::get('/lga/location/pollingunit/edit/{uuid}', [LocationController::class, 'editPollingUnit'])->name('lgaadmin.location.pu.edit')->middleware('access_level:lgaadmin');
Route::post('/lga/location/pollingunit/update/{uuid}', [LocationController::class, 'updatePollingUnit'])->name('lgaadmin.location.pu.update')->middleware('access_level:lgaadmin');
Route::delete('/lga/location/pollingunit/delete/{uuid}', [LocationController::class, 'deletePollingUnit'])->name('lgaadmin.location.pu.delete')->middleware('access_level:lgaadmin');

//import PU Form
Route::get('/lga/import-polling-units', [LocationController::class, 'importPollingUnitsForm'])->name('lgaadmin.location.pu.import')->middleware('access_level:lgaadmin');
// Export Route
Route::get('/lga/export-polling-units', [LocationController::class, 'exportPollingUnits'])->name('lgaadmin.location.pu.exportPollingUnits')->middleware('access_level:lgaadmin');

// Import Route
Route::post('/lga/import-polling-units', [LocationController::class, 'importPollingUnits'])->name('lgaadmin.location.pu.importPollingUnits')->middleware('access_level:lgaadmin');


// Election Management CRUD
Route::get('/lga/elections', [ElectionController::class, 'allElections'])->name('lgaadmin.elections')->middleware('access_level:lgaadmin');
Route::get('/lga/election/operations-center', [ElectionController::class, 'operationsCenter'])->name('lgaadmin.election.operations')->middleware('access_level:lgaadmin');
Route::get('/lga/election/add', [ElectionController::class, 'addElection'])->name('lgaadmin.election.add')->middleware('access_level:lgaadmin');
Route::post('/lga/election/store', [ElectionController::class, 'storeElection'])->name('lgaadmin.election.store')->middleware('access_level:lgaadmin');
Route::get('/lga/election/edit/{uuid}', [ElectionController::class, 'editElection'])->name('lgaadmin.election.edit')->middleware('access_level:lgaadmin');
Route::post('/lga/election/update/{uuid}', [ElectionController::class, 'updateElection'])->name('lgaadmin.election.update')->middleware('access_level:lgaadmin');
Route::delete('/lga/election/delete/{uuid}', [ElectionController::class, 'deleteElection'])->name('lgaadmin.election.delete')->middleware('access_level:lgaadmin');

// Election Result Route
Route::get('/lga/election/result/{uuid}', [ElectionController::class, 'electionResult'])->name('lgaadmin.election.results')->middleware('access_level:lgaadmin');

// Election PU Votes Report
Route::get('/lga/election/votes/{uuid?}', [ElectionController::class, 'manageVotesByPu'])->name('lgaadmin.election.votesByPu')->middleware('access_level:lgaadmin');
Route::get('/lga/election/votes/data/{uuid}', [ElectionController::class, 'VotesByPuData'])->name('lgaadmin.election.votesByPuData')->middleware('access_level:lgaadmin');


// Election Incident Report
Route::get('/lga/election/incident/{uuid}/{polling_unit_id}', [ElectionController::class, 'manageIncidentByPu'])
    ->name('lgaadmin.election.incident')
    ->middleware('access_level:lgaadmin');

    Route::get('/lga/incident/add', [ElectionController::class, 'addIncident'])->name('lgaadmin.incident.add')->middleware('access_level:lgaadmin');
    Route::post('/lga/incident/store', [ElectionController::class, 'storeIcident'])->name('lgaadmin.incident.store')->middleware('access_level:lgaadmin');
    

//Manage Votes
Route::get('/lga/votes', [ElectionController::class, 'allVotes'])->name('lgaadmin.votes')->middleware('access_level:lgaadmin');
Route::get('/lga/vote/add', [ElectionController::class, 'addVote'])->name('lgaadmin.vote.add')->middleware('access_level:lgaadmin');
Route::post('/lga/vote/store', [ElectionController::class, 'storeVote'])->name('lgaadmin.vote.store')->middleware('access_level:lgaadmin');
Route::get('/lga/vote/edit/{uuid}', [ElectionController::class, 'editVote'])->name('lgaadmin.vote.edit')->middleware('access_level:lgaadmin');
Route::post('/lga/vote/update/{uuid}', [ElectionController::class, 'updateVote'])->name('lgaadmin.vote.update')->middleware('access_level:lgaadmin');
Route::delete('/lga/vote/delete/{uuid}', [ElectionController::class, 'deleteVote'])->name('lgaadmin.vote.delete')->middleware('access_level:lgaadmin');



});
