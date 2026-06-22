<?php

use App\Http\Controllers\AjaxController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\DashboardStatsController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\senatorialadmin\SenatorialAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/senatorial/logout', [SenatorialAdminController::class, 'senatorialadminLogout'])
    ->name('senatorialadmin.logout')
    ->middleware(['auth', 'access_level:senatorialadmin']);

Route::middleware(['auth', 'check_onboarding'])->group(function () {
    Route::get('/senatorial/dashboard', [SenatorialAdminController::class, 'senatorialAdminDashBoard'])
        ->name('senatorialadmin.dashboard')
        ->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/dashboard/stats', [DashboardStatsController::class, 'cards'])
        ->name('senatorialadmin.dashboard.stats')
        ->middleware('access_level:senatorialadmin');

    Route::get('/senatorial/profile', [SenatorialAdminController::class, 'senatorialadminProfile'])
        ->name('senatorialadmin.profile')
        ->middleware('access_level:senatorialadmin');
    Route::post('/senatorial/profile/store', [SenatorialAdminController::class, 'senatorialadminProfileStore'])
        ->name('senatorialadmin.profile.store')
        ->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/change/password', [SenatorialAdminController::class, 'senatorialadminChangePassword'])
        ->name('senatorialadmin.change.password')
        ->middleware('access_level:senatorialadmin');
    Route::post('/senatorial/update/password', [SenatorialAdminController::class, 'senatorialadminUpdatePassword'])
        ->name('senatorialadmin.update.password')
        ->middleware('access_level:senatorialadmin');

    Route::get('/senatorial/members/data/fetch/{uuid?}', [MembersController::class, 'getMembersData'])->name('senatorialadmin.members.data');
    Route::get('/senatorial/members/fetch/{uuid?}', [MembersController::class, 'allMembers'])->name('senatorialadmin.members');
    Route::get('/senatorial/leaders/data/fetch/{uuid?}', [MembersController::class, 'getExcoMembersData'])->name('senatorialadmin.leaders.data');
    Route::get('/senatorial/leaders/fetch/{uuid?}', [MembersController::class, 'allExcoMembers'])->name('senatorialadmin.leaders');
    Route::get('/senatorial/regulars/data/fetch/{uuid?}', [MembersController::class, 'getRegularMembersData'])->name('senatorialadmin.regulars.data');
    Route::get('/senatorial/regulars/fetch/{uuid?}', [MembersController::class, 'allRegularMembers'])->name('senatorialadmin.regulars');
    Route::get('/senatorial/people/{metric}/data/fetch/{uuid?}', [MembersController::class, 'getPeopleMetricMembersData'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('senatorialadmin.peopleMetric.data');
    Route::get('/senatorial/people/{metric}/fetch/{uuid?}', [MembersController::class, 'allPeopleMetricMembers'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('senatorialadmin.peopleMetric');
    Route::get('/senatorial/member/add', [MembersController::class, 'addMember'])->name('senatorialadmin.member.add')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/members/import', [MembersController::class, 'importMembers'])->name('senatorialadmin.member.import')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/members/import/template', [MembersController::class, 'downloadMemberImportTemplate'])->name('senatorialadmin.member.import.template')->middleware('access_level:senatorialadmin');
    Route::post('/senatorial/members/import', [MembersController::class, 'storeMemberImport'])->name('senatorialadmin.member.import.store')->middleware('access_level:senatorialadmin');
    Route::post('/senatorial/member/store', [MembersController::class, 'storeMember'])->name('senatorialadmin.member.store')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/member/edit/{uuid}', [MembersController::class, 'editMember'])->name('senatorialadmin.member.edit')->middleware('access_level:senatorialadmin');
    Route::put('/senatorial/member/update/', [MembersController::class, 'updateMember'])->name('senatorialadmin.member.update')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/member/view/{uuid}', [MembersController::class, 'viewMember'])->name('senatorialadmin.member.view')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/member/suspend/{uuid}', [MembersController::class, 'suspendMember'])->name('senatorialadmin.member.suspend')->middleware('access_level:senatorialadmin');
    Route::delete('/senatorial/member/delete/{uuid}', [MembersController::class, 'deleteMember'])->name('senatorialadmin.member.delete')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/members/bylga/{uuid?}', [MembersController::class, 'membersByLocalGovernments'])->name('senatorialadmin.members.byLga')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/members/lga/{uuid}', [MembersController::class, 'viewMembersByLocalGovernments'])->name('senatorialadmin.member.lga.view')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/lga/members/data/{uuid}', [MembersController::class, 'getLgaMembersData'])->name('senatorialadmin.lga.members.data');
    Route::get('/senatorial/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('senatorialadmin.members.byWard')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('senatorialadmin.members.byWard.ajax')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('senatorialadmin.member.ward.view')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('senatorialadmin.ward.members.data');
    Route::get('/senatorial/members/bypu/{uuid?}', [MembersController::class, 'membersByPus'])->name('senatorialadmin.members.byPu')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/members/bypu/ajax/{uuid?}', [AjaxController::class, 'membersByPusAjax'])->name('senatorialadmin.members.byPu.ajax')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/members/pu/{uuid}', [MembersController::class, 'viewMembersByPu'])->name('senatorialadmin.member.pu.view')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/pu/members/data/{uuid}', [MembersController::class, 'getPuMembersData'])->name('senatorialadmin.pu.members.data');

    Route::get('/senatorial/location/localgovernment/dashboard/{uuid}', [SenatorialAdminController::class, 'localGovernmentDashBoard'])->name('senatorialadmin.location.lga.dashboard')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/location/federal-constituencies', [\App\Http\Controllers\LocationController::class, 'allFederalConstituencies'])->name('senatorialadmin.location.federal-constituencies')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/location/federal-constituency/edit/{uuid}', [\App\Http\Controllers\LocationController::class, 'editFederalConstituency'])->name('senatorialadmin.location.federal-constituency.edit')->middleware('access_level:senatorialadmin');
    Route::post('/senatorial/location/federal-constituency/update/{uuid}', [\App\Http\Controllers\LocationController::class, 'updateFederalConstituency'])->name('senatorialadmin.location.federal-constituency.update')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/location/localgovernments', [\App\Http\Controllers\LocationController::class, 'allLocalGovernments'])->name('senatorialadmin.location.lgas')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/location/localgovernment/edit/{uuid}', [\App\Http\Controllers\LocationController::class, 'editLocalGovernment'])->name('senatorialadmin.location.lga.edit')->middleware('access_level:senatorialadmin');
    Route::post('/senatorial/location/localgovernment/update/{uuid}', [\App\Http\Controllers\LocationController::class, 'updateLocalGovernment'])->name('senatorialadmin.location.lga.update')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/lga/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByLga'])->name('senatorialadmin.lga.member.distribution.gender')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/lga/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByLga'])->name('senatorialadmin.lga.member.distribution.age')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/lga/member/distribution/ward/{uuid}', [ChartsController::class, 'wardDistributionByLga'])->name('senatorialadmin.lga.member.distribution.ward')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/lga/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByLga'])->name('senatorialadmin.lga.member.distribution.voter')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/location/ward/dashboard/{uuid}', [SenatorialAdminController::class, 'wardDashBoard'])->name('senatorialadmin.location.ward.dashboard')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/location/wards', [\App\Http\Controllers\LocationController::class, 'allWards'])->name('senatorialadmin.location.wards')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/location/ward/edit/{uuid}', [\App\Http\Controllers\LocationController::class, 'editWard'])->name('senatorialadmin.location.ward.edit')->middleware('access_level:senatorialadmin');
    Route::post('/senatorial/location/ward/update/{uuid}', [\App\Http\Controllers\LocationController::class, 'updateWard'])->name('senatorialadmin.location.ward.update')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/ward/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByWard'])->name('senatorialadmin.ward.member.distribution.gender')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/ward/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByWard'])->name('senatorialadmin.ward.member.distribution.voter')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/location/pollingunit/dashboard/{uuid}', [SenatorialAdminController::class, 'PuDashBoard'])->name('senatorialadmin.location.pu.dashboard')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/location/pollingunits', [\App\Http\Controllers\LocationController::class, 'allPollingUnits'])->name('senatorialadmin.location.pus')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/location/pollingunit/edit/{uuid}', [\App\Http\Controllers\LocationController::class, 'editPollingUnit'])->name('senatorialadmin.location.pu.edit')->middleware('access_level:senatorialadmin');
    Route::post('/senatorial/location/pollingunit/update/{uuid}', [\App\Http\Controllers\LocationController::class, 'updatePollingUnit'])->name('senatorialadmin.location.pu.update')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/pollingunit/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByPu'])->name('senatorialadmin.pu.member.distribution.gender')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/pollingunit/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByPu'])->name('senatorialadmin.pu.member.distribution.voter')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/senatorial-district/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionBySenatorialDistrict'])->name('senatorialadmin.senatorial-district.member.distribution.gender')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/senatorial-district/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionBySenatorialDistrict'])->name('senatorialadmin.senatorial-district.member.distribution.age')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/senatorial-district/member/distribution/lga/{uuid}', [ChartsController::class, 'lgaDistributionBySenatorialDistrict'])->name('senatorialadmin.senatorial-district.member.distribution.lga')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/senatorial-district/member/distribution/voter/{uuid}', [ChartsController::class, 'VoterDistributionBySenatorialDistrict'])->name('senatorialadmin.senatorial-district.member.distribution.voter')->middleware('access_level:senatorialadmin');

    Route::get('/senatorial/elections', [ElectionController::class, 'allElections'])
        ->name('senatorialadmin.elections')
        ->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/election/operations-center', [ElectionController::class, 'operationsCenter'])
        ->name('senatorialadmin.election.operations')
        ->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/election/add', [ElectionController::class, 'addElection'])
        ->name('senatorialadmin.election.add')
        ->middleware('access_level:senatorialadmin');
    Route::post('/senatorial/election/store', [ElectionController::class, 'storeElection'])
        ->name('senatorialadmin.election.store')
        ->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/election/edit/{uuid}', [ElectionController::class, 'editElection'])
        ->name('senatorialadmin.election.edit')
        ->middleware('access_level:senatorialadmin');
    Route::post('/senatorial/election/update/{uuid}', [ElectionController::class, 'updateElection'])
        ->name('senatorialadmin.election.update')
        ->middleware('access_level:senatorialadmin');
    Route::delete('/senatorial/election/delete/{uuid}', [ElectionController::class, 'deleteElection'])
        ->name('senatorialadmin.election.delete')
        ->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/election/result/{uuid}', [ElectionController::class, 'electionResult'])
        ->name('senatorialadmin.election.results')
        ->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/election/votes/{uuid?}', [ElectionController::class, 'manageVotesByPu'])
        ->name('senatorialadmin.election.votesByPu')
        ->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/election/votes/data/{uuid}', [ElectionController::class, 'VotesByPuData'])
        ->name('senatorialadmin.election.votesByPuData')
        ->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/election/incident/{uuid}/{polling_unit_id}', [ElectionController::class, 'manageIncidentByPu'])
        ->name('senatorialadmin.election.incident')
        ->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/incident/add', [ElectionController::class, 'addIncident'])
        ->name('senatorialadmin.incident.add')
        ->middleware('access_level:senatorialadmin');
    Route::post('/senatorial/incident/store', [ElectionController::class, 'storeIcident'])
        ->name('senatorialadmin.incident.store')
        ->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/vote/add', [ElectionController::class, 'addVote'])
        ->name('senatorialadmin.vote.add')
        ->middleware('access_level:senatorialadmin');
    Route::post('/senatorial/vote/store', [ElectionController::class, 'storeVote'])
        ->name('senatorialadmin.vote.store')
        ->middleware('access_level:senatorialadmin');
});
