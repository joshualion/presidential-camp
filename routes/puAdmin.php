<?php


use App\Http\Controllers\AjaxController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\DashboardStatsController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\puadmin\PollingUnitAdminController;
use Illuminate\Support\Facades\Route;


Route::get('/pu/logout', [PollingUnitAdminController::class, 'puAdminLogout'])->name('puadmin.logout')->middleware(['auth','access_level:puadmin']);


Route::middleware(['auth','check_onboarding'])->group(function () {

 // Regional Admin Profile & Dashboard Routes
 Route::get('/pu/dashboard', [PollingUnitAdminController::class, 'puAdminDashBoard'])->name('puadmin.dashboard')->middleware('access_level:puadmin');
 Route::get('/pu/dashboard/stats', [DashboardStatsController::class, 'cards'])->name('puadmin.dashboard.stats')->middleware('access_level:puadmin');
 Route::get('/pu/profile', [PollingUnitAdminController::class, 'puAdminProfile'])->name('puadmin.profile')->middleware('access_level:puadmin');
 Route::post('/pu/profile/store', [PollingUnitAdminController::class, 'puAdminProfileStore'])->name('puadmin.profile.store')->middleware('access_level:puadmin');
 Route::get('/pu/change/password', [PollingUnitAdminController::class, 'puAdminChangePassword'])->name('puadmin.change.password')->middleware('access_level:puadmin');
 Route::post('/pu/update/password', [PollingUnitAdminController::class, 'puAdminUpdatePassword'])->name('puadmin.update.password')->middleware('access_level:puadmin');
 


    /*Regional Admin Ajax Routes */

//Gender Distribution
Route::get('/pu/member/distribution/gender', [PollingUnitAdminController::class, 'puadminGenderDistribution'])->name('puadmin.member.distribution.gender')->middleware('access_level:puadmin');

// Age Distribution
Route::get('/pu/member/distribution/age', [PollingUnitAdminController::class, 'puadminAgeDistribution'])->name('puadmin.member.distribution.age')->middleware('access_level:puadmin');

// Region Distribution
Route::get('/pu/member/distribution/region', [PollingUnitAdminController::class, 'puadminRegionDistribution'])->name('puadmin.member.distribution.region')->middleware('access_level:puadmin');
/*End Super Admin Ajax Routes */
// Religion Distribution
Route::get('/pu/member/distribution/religion', [PollingUnitAdminController::class, 'puadminReligionDistribution'])->name('puadmin.member.distribution.religion')->middleware('access_level:puadmin');
// Valid Voter Distribution
Route::get('/pu/member/distribution/voter', [PollingUnitAdminController::class, 'puadminVoterDistribution'])->name('puadmin.member.distribution.voter')->middleware('access_level:puadmin');
// State Distribution
Route::get('/pu/member/distribution/state', [PollingUnitAdminController::class, 'puadminStateDistribution'])->name('puadmin.member.distribution.state')->middleware('access_level:puadmin');


//Member Management
Route::get('/pu/members/data/fetch/{uuid?}', [MembersController::class, 'getMembersData'])->name('puadmin.members.data');
Route::get('/pu/members/fetch/{uuid?}', [MembersController::class, 'allMembers'])->name('puadmin.members');
//Fetch Excos
Route::get('/pu/leaders/data/fetch/{uuid?}', [MembersController::class, 'getExcoMembersData'])->name('puadmin.leaders.data');
Route::get('/pu/leaders/fetch/{uuid?}', [MembersController::class, 'allExcoMembers'])->name('puadmin.leaders');
//Fetch Regulars
Route::get('/pu/regulars/data/fetch/{uuid?}', [MembersController::class, 'getRegularMembersData'])->name('puadmin.regulars.data');
Route::get('/pu/regulars/fetch/{uuid?}', [MembersController::class, 'allRegularMembers'])->name('puadmin.regulars');
Route::get('/pu/people/{metric}/data/fetch/{uuid?}', [MembersController::class, 'getPeopleMetricMembersData'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('puadmin.peopleMetric.data');
Route::get('/pu/people/{metric}/fetch/{uuid?}', [MembersController::class, 'allPeopleMetricMembers'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('puadmin.peopleMetric');

Route::get('/pu/member/add', [MembersController::class, 'addMember'])->name('puadmin.member.add')->middleware('access_level:puadmin');
Route::get('/pu/members/import', [MembersController::class, 'importMembers'])->name('puadmin.member.import')->middleware('access_level:puadmin');
Route::get('/pu/members/import/template', [MembersController::class, 'downloadMemberImportTemplate'])->name('puadmin.member.import.template')->middleware('access_level:puadmin');
Route::post('/pu/members/import', [MembersController::class, 'storeMemberImport'])->name('puadmin.member.import.store')->middleware('access_level:puadmin');
Route::post('/pu/member/store', [MembersController::class, 'storeMember'])->name('puadmin.member.store')->middleware('access_level:puadmin');
Route::get('/pu/member/edit/{uuid}', [MembersController::class, 'editMember'])->name('puadmin.member.edit')->middleware('access_level:puadmin');
Route::put('/pu/member/update/', [MembersController::class, 'updateMember'])->name('puadmin.member.update')->middleware('access_level:puadmin');
Route::get('/pu/member/view/{uuid}', [MembersController::class, 'viewMember'])->name('puadmin.member.view')->middleware('access_level:puadmin');
Route::get('/pu/member/suspend/{uuid}', [MembersController::class, 'suspendMember'])->name('puadmin.member.suspend')->middleware('access_level:puadmin');
Route::delete('/pu/member/delete/{uuid}', [MembersController::class, 'deleteMember'])->name('puadmin.member.delete')->middleware('access_level:puadmin');

// Members By State
Route::get('/pu/members/bystate/{uuid?}', [MembersController::class, 'membersByStates'])->name('puadmin.members.byState')->middleware('access_level:puadmin,regionadmin,lgaadmin');
Route::get('/pu/members/pu/{uuid}', [MembersController::class, 'ViewMembersByState'])->name('puadmin.member.state.view')->middleware('access_level:puadmin,regionadmin,regionadmin,lgaadmin');
Route::get('/pu/state/members/data/{uuid}', [MembersController::class, 'getStateMembersData'])->name('puadmin.state.members.data');
// Members By Local Government
Route::get('/pu/members/bylga/{uuid?}', [MembersController::class, 'membersByLocalGovernments'])->name('puadmin.members.byLga')->middleware('access_level:puadmin');
Route::get('/pu/members/pu/{uuid}', [MembersController::class, 'viewMembersByLocalGovernments'])->name('puadmin.member.lga.view')->middleware('access_level:puadmin');
Route::get('/pu/lga/members/data/{uuid}', [MembersController::class, 'getLgaMembersData'])->name('puadmin.lga.members.data');
// Members By Ward
Route::get('/pu/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('puadmin.members.byWard')->middleware('access_level:puadmin');
Route::get('/pu/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('puadmin.members.byWard.ajax')->middleware('access_level:puadmin');
Route::get('/pu/members/pu/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('puadmin.member.ward.view')->middleware('access_level:puadmin');
Route::get('/pu/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('puadmin.ward.members.data');
// Members By Ward
Route::get('/pu/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('puadmin.members.byWard')->middleware('access_level:puadmin');
Route::get('/pu/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('puadmin.members.byWard.ajax')->middleware('access_level:puadmin');
Route::get('/pu/members/pu/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('puadmin.member.ward.view')->middleware('access_level:puadmin');
Route::get('/pu/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('puadmin.ward.members.data');

// Members By Pu
Route::get('/pu/members/bypu/{uuid?}', [MembersController::class, 'membersByPus'])->name('puadmin.members.byPu')->middleware('access_level:puadmin');
Route::get('/pu/members/bypu/ajax/{uuid?}', [AjaxController::class, 'membersByPusAjax'])->name('puadmin.members.byPu.ajax')->middleware('access_level:puadmin');
Route::get('/pu/members/pu/{uuid}', [MembersController::class, 'viewMembersByPu'])->name('puadmin.member.pu.view')->middleware('access_level:puadmin');
Route::get('/pu/pu/members/data/{uuid}', [MembersController::class, 'getPuMembersData'])->name('puadmin.pu.members.data');

//Locations Management Routes


//Manage Regions
Route::get('/pu/location/region/dashboard/{uuid}', [PollingUnitAdminController::class, 'RegionDashBoard'])->name('puadmin.location.region.dashboard')->middleware('access_level:puadmin');
Route::get('/pu/region/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByRegion'])->name('puadmin.region.member.distribution.gender')->middleware('access_level:puadmin');
Route::get('/pu/region/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByRegion'])->name('puadmin.region.member.distribution.age')->middleware('access_level:puadmin');
Route::get('/pu/region/member/distribution/pu/{uuid}', [ChartsController::class, 'StateDistributionByRegion'])->name('puadmin.region.member.distribution.state')->middleware('access_level:puadmin');
Route::get('/pu/region/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByRegion'])->name('puadmin.region.member.distribution.voter')->middleware('access_level:puadmin');
Route::get('/pu/location/regions', [LocationController::class, 'allRegions'])->name('puadmin.location.regions')->middleware('access_level:puadmin');
Route::get('/pu/location/region/add', [LocationController::class, 'addRegion'])->name('puadmin.location.region.add')->middleware('access_level:puadmin');
Route::post('/pu/location/region/store', [LocationController::class, 'storeRegion'])->name('puadmin.location.region.store')->middleware('access_level:puadmin');
Route::get('/pu/location/region/edit/{uuid}', [LocationController::class, 'editRegion'])->name('puadmin.location.region.edit')->middleware('access_level:puadmin');
Route::post('/pu/location/region/update/{uuid}', [LocationController::class, 'updateRegion'])->name('puadmin.location.region.update')->middleware('access_level:puadmin');
Route::delete('/pu/location/region/delete/{uuid}', [LocationController::class, 'deleteRegion'])->name('puadmin.location.region.delete')->middleware('access_level:puadmin');

// Manage States
Route::get('/pu/location/pu/dashboard/{uuid}', [PollingUnitAdminController::class, 'StateDashBoard'])->name('puadmin.location.state.dashboard')->middleware('access_level:puadmin');
Route::get('/pu/state/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByState'])->name('puadmin.state.member.distribution.gender')->middleware('access_level:puadmin');
Route::get('/pu/state/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByState'])->name('puadmin.state.member.distribution.age')->middleware('access_level:puadmin');
Route::get('/pu/state/member/distribution/pu/{uuid}', [ChartsController::class, 'LgaDistributionByState'])->name('puadmin.state.member.distribution.lga')->middleware('access_level:puadmin');
Route::get('/pu/state/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByState'])->name('puadmin.state.member.distribution.voter')->middleware('access_level:puadmin');

Route::get('/pu/location/states', [LocationController::class, 'allStates'])->name('puadmin.location.states')->middleware('access_level:puadmin');
Route::get('/pu/location/pu/add', [LocationController::class, 'addState'])->name('puadmin.location.state.add')->middleware('access_level:puadmin');
Route::post('/pu/location/pu/store', [LocationController::class, 'storeState'])->name('puadmin.location.state.store')->middleware('access_level:puadmin');
Route::get('/pu/location/pu/edit/{uuid}', [LocationController::class, 'editState'])->name('puadmin.location.state.edit')->middleware('access_level:puadmin');
Route::post('/pu/location/pu/update/{uuid}', [LocationController::class, 'updateState'])->name('puadmin.location.state.update')->middleware('access_level:puadmin');
Route::delete('/pu/location/pu/delete/{uuid}', [LocationController::class, 'deleteState'])->name('puadmin.location.state.delete')->middleware('access_level:puadmin');

// manage Local Governments
Route::get('/pu/location/localgovernment/dashboard/{uuid}', [PollingUnitAdminController::class, 'localGovernmentDashBoard'])->name('puadmin.location.lga.dashboard')->middleware('access_level:puadmin');
Route::get('/pu/lga/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByLga'])->name('puadmin.lga.member.distribution.gender')->middleware('access_level:puadmin');
Route::get('/pu/lga/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByLga'])->name('puadmin.lga.member.distribution.age')->middleware('access_level:puadmin');
Route::get('/pu/lga/member/distribution/pu/{uuid}', [ChartsController::class, 'wardDistributionByLga'])->name('puadmin.lga.member.distribution.ward')->middleware('access_level:puadmin');
Route::get('/pu/lga/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByLga'])->name('puadmin.lga.member.distribution.voter')->middleware('access_level:puadmin');


Route::get('/pu/location/localgovernments', [LocationController::class, 'allLocalGovernments'])->name('puadmin.location.lgas')->middleware('access_level:puadmin');
Route::get('/pu/location/localgovernment/add', [LocationController::class, 'addLocalGovernment'])->name('puadmin.location.lga.add')->middleware('access_level:puadmin');
Route::post('/pu/location/localgovernment/store', [LocationController::class, 'storeLocalGovernment'])->name('puadmin.location.lga.store')->middleware('access_level:puadmin');
Route::get('/pu/location/localgovernment/edit/{uuid}', [LocationController::class, 'editLocalGovernment'])->name('puadmin.location.lga.edit')->middleware('access_level:puadmin');
Route::post('/pu/location/localgovernment/update/{uuid}', [LocationController::class, 'updateLocalGovernment'])->name('puadmin.location.lga.update')->middleware('access_level:puadmin');
Route::delete('/pu/location/localgovernment/delete/{uuid}', [LocationController::class, 'deleteLocalGovernment'])->name('puadmin.location.lga.delete')->middleware('access_level:puadmin');

//import LGA Form
Route::get('/pu/location/importlgas', [LocationController::class, 'importLgasForm'])->name('puadmin.location.lga.import')->middleware('access_level:puadmin');

// Export Route
Route::get('/pu/location/exportlgas', [LocationController::class, 'exportLgas'])->name('puadmin.location.lga.exportLgas')->middleware('access_level:puadmin');

// Import Route
Route::post('/pu/location/importlgas', [LocationController::class, 'importLgas'])->name('puadmin.location.lga.importLgas')->middleware('access_level:puadmin');


// manage Wards
Route::get('/pu/location/pu/dashboard/{uuid}', [PollingUnitAdminController::class, 'wardDashBoard'])->name('puadmin.location.ward.dashboard')->middleware('access_level:puadmin');
Route::get('/pu/ward/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByWard'])->name('puadmin.ward.member.distribution.gender')->middleware('access_level:puadmin');
Route::get('/pu/ward/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByWard'])->name('puadmin.ward.member.distribution.voter')->middleware('access_level:puadmin');

Route::get('/pu/location/wards', [LocationController::class, 'allWards'])->name('puadmin.location.wards')->middleware('access_level:puadmin');
Route::get('/pu/location/pu/add', [LocationController::class, 'addWard'])->name('puadmin.location.ward.add')->middleware('access_level:puadmin');
Route::post('/pu/location/pu/store', [LocationController::class, 'storeWard'])->name('puadmin.location.ward.store')->middleware('access_level:puadmin');
Route::get('/pu/location/pu/edit/{uuid}', [LocationController::class, 'editWard'])->name('puadmin.location.ward.edit')->middleware('access_level:puadmin');
Route::post('/pu/location/pu/update/{uuid}', [LocationController::class, 'updateWard'])->name('puadmin.location.ward.update')->middleware('access_level:puadmin');
Route::delete('/pu/location/pu/delete/{uuid}', [LocationController::class, 'deleteWard'])->name('puadmin.location.ward.delete')->middleware('access_level:puadmin');

//import PU Form
Route::get('/pu/location/importwards', [LocationController::class, 'importWardsForm'])->name('puadmin.location.ward.import')->middleware('access_level:puadmin');

// Export Route
Route::get('/pu/location/exportwards', [LocationController::class, 'exportWards'])->name('puadmin.location.ward.exportWards')->middleware('access_level:puadmin');

// Import Route
Route::post('/pu/location/importwards', [LocationController::class, 'importWards'])->name('puadmin.location.ward.importWards')->middleware('access_level:puadmin');

//Manage Polling Units
Route::get('/pu/location/pollingunit/dashboard/{uuid}', [PollingUnitAdminController::class, 'PuDashBoard'])->name('puadmin.location.pu.dashboard')->middleware('access_level:puadmin');
Route::get('/pu/pollingunit/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByPu'])->name('puadmin.pu.member.distribution.gender')->middleware('access_level:puadmin');
Route::get('/pu/pollingunit/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByPu'])->name('puadmin.pu.member.distribution.voter')->middleware('access_level:puadmin');



//import PU Form
Route::get('/pu/import-polling-units', [LocationController::class, 'importPollingUnitsForm'])->name('puadmin.location.pu.import')->middleware('access_level:puadmin');
// Export Route
Route::get('/pu/export-polling-units', [LocationController::class, 'exportPollingUnits'])->name('puadmin.location.pu.exportPollingUnits')->middleware('access_level:puadmin');

// Import Route
Route::post('/pu/import-polling-units', [LocationController::class, 'importPollingUnits'])->name('puadmin.location.pu.importPollingUnits')->middleware('access_level:puadmin');


// Election Management CRUD
Route::get('/pu/elections', [ElectionController::class, 'allElections'])->name('puadmin.elections')->middleware('access_level:puadmin');
Route::get('/pu/election/operations-center', [ElectionController::class, 'pollingUnitSituationRoom'])->name('puadmin.election.operations')->middleware('access_level:puadmin');
Route::get('/pu/election/add', [ElectionController::class, 'addElection'])->name('puadmin.election.add')->middleware('access_level:puadmin');
Route::post('/pu/election/store', [ElectionController::class, 'storeElection'])->name('puadmin.election.store')->middleware('access_level:puadmin');
Route::get('/pu/election/edit/{uuid}', [ElectionController::class, 'editElection'])->name('puadmin.election.edit')->middleware('access_level:puadmin');
Route::post('/pu/election/update/{uuid}', [ElectionController::class, 'updateElection'])->name('puadmin.election.update')->middleware('access_level:puadmin');
Route::delete('/pu/election/delete/{uuid}', [ElectionController::class, 'deleteElection'])->name('puadmin.election.delete')->middleware('access_level:puadmin');

// Election Result Route
Route::get('/pu/election/result/{uuid}', [ElectionController::class, 'electionResult'])->name('puadmin.election.results')->middleware('access_level:puadmin');

// Election PU Votes Report
Route::get('/pu/election/votes/{uuid?}', [ElectionController::class, 'manageVotesByPu'])->name('puadmin.election.votesByPu')->middleware('access_level:puadmin');
Route::get('/pu/election/votes/data/{uuid}', [ElectionController::class, 'VotesByPuData'])->name('puadmin.election.votesByPuData')->middleware('access_level:puadmin');


// Election Incident Report
Route::get('/pu/election/incident/{uuid}/{polling_unit_id}', [ElectionController::class, 'manageIncidentByPu'])
    ->name('puadmin.election.incident')
    ->middleware('access_level:puadmin');

    Route::get('/pu/incident/add', [ElectionController::class, 'addIncident'])->name('puadmin.incident.add')->middleware('access_level:puadmin');
    Route::post('/pu/incident/store', [ElectionController::class, 'storeIcident'])->name('puadmin.incident.store')->middleware('access_level:puadmin');
    

//Manage Votes
Route::get('/pu/votes', [ElectionController::class, 'allVotes'])->name('puadmin.votes')->middleware('access_level:puadmin');
Route::get('/pu/vote/add', [ElectionController::class, 'addVote'])->name('puadmin.vote.add')->middleware('access_level:puadmin');
Route::post('/pu/vote/store', [ElectionController::class, 'storeVote'])->name('puadmin.vote.store')->middleware('access_level:puadmin');
Route::get('/pu/vote/edit/{uuid}', [ElectionController::class, 'editVote'])->name('puadmin.vote.edit')->middleware('access_level:puadmin');
Route::post('/pu/vote/update/{uuid}', [ElectionController::class, 'updateVote'])->name('puadmin.vote.update')->middleware('access_level:puadmin');
Route::delete('/pu/vote/delete/{uuid}', [ElectionController::class, 'deleteVote'])->name('puadmin.vote.delete')->middleware('access_level:puadmin');



});
