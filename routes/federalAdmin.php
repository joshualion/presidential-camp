<?php

use App\Http\Controllers\AjaxController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\DashboardStatsController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\federaladmin\FederalAdminController;
use App\Http\Controllers\MembersController;
use Illuminate\Support\Facades\Route;

Route::get('/federal/logout', [FederalAdminController::class, 'federaladminLogout'])
    ->name('federaladmin.logout')
    ->middleware(['auth', 'access_level:federaladmin']);

Route::middleware(['auth', 'check_onboarding'])->group(function () {
    Route::get('/federal/dashboard', [FederalAdminController::class, 'federalAdminDashBoard'])
        ->name('federaladmin.dashboard')
        ->middleware('access_level:federaladmin');
    Route::get('/federal/dashboard/stats', [DashboardStatsController::class, 'cards'])
        ->name('federaladmin.dashboard.stats')
        ->middleware('access_level:federaladmin');

    Route::get('/federal/profile', [FederalAdminController::class, 'federaladminProfile'])
        ->name('federaladmin.profile')
        ->middleware('access_level:federaladmin');
    Route::post('/federal/profile/store', [FederalAdminController::class, 'federaladminProfileStore'])
        ->name('federaladmin.profile.store')
        ->middleware('access_level:federaladmin');
    Route::get('/federal/change/password', [FederalAdminController::class, 'federaladminChangePassword'])
        ->name('federaladmin.change.password')
        ->middleware('access_level:federaladmin');
    Route::post('/federal/update/password', [FederalAdminController::class, 'federaladminUpdatePassword'])
        ->name('federaladmin.update.password')
        ->middleware('access_level:federaladmin');

    Route::get('/federal/members/data/fetch/{uuid?}', [MembersController::class, 'getMembersData'])->name('federaladmin.members.data');
    Route::get('/federal/members/fetch/{uuid?}', [MembersController::class, 'allMembers'])->name('federaladmin.members');
    Route::get('/federal/leaders/data/fetch/{uuid?}', [MembersController::class, 'getExcoMembersData'])->name('federaladmin.leaders.data');
    Route::get('/federal/leaders/fetch/{uuid?}', [MembersController::class, 'allExcoMembers'])->name('federaladmin.leaders');
    Route::get('/federal/regulars/data/fetch/{uuid?}', [MembersController::class, 'getRegularMembersData'])->name('federaladmin.regulars.data');
    Route::get('/federal/regulars/fetch/{uuid?}', [MembersController::class, 'allRegularMembers'])->name('federaladmin.regulars');
    Route::get('/federal/people/{metric}/data/fetch/{uuid?}', [MembersController::class, 'getPeopleMetricMembersData'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('federaladmin.peopleMetric.data');
    Route::get('/federal/people/{metric}/fetch/{uuid?}', [MembersController::class, 'allPeopleMetricMembers'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('federaladmin.peopleMetric');
    Route::get('/federal/member/add', [MembersController::class, 'addMember'])->name('federaladmin.member.add')->middleware('access_level:federaladmin');
    Route::get('/federal/members/import', [MembersController::class, 'importMembers'])->name('federaladmin.member.import')->middleware('access_level:federaladmin');
    Route::get('/federal/members/import/template', [MembersController::class, 'downloadMemberImportTemplate'])->name('federaladmin.member.import.template')->middleware('access_level:federaladmin');
    Route::post('/federal/members/import', [MembersController::class, 'storeMemberImport'])->name('federaladmin.member.import.store')->middleware('access_level:federaladmin');
    Route::post('/federal/member/store', [MembersController::class, 'storeMember'])->name('federaladmin.member.store')->middleware('access_level:federaladmin');
    Route::get('/federal/member/edit/{uuid}', [MembersController::class, 'editMember'])->name('federaladmin.member.edit')->middleware('access_level:federaladmin');
    Route::put('/federal/member/update/', [MembersController::class, 'updateMember'])->name('federaladmin.member.update')->middleware('access_level:federaladmin');
    Route::get('/federal/member/view/{uuid}', [MembersController::class, 'viewMember'])->name('federaladmin.member.view')->middleware('access_level:federaladmin');
    Route::get('/federal/member/suspend/{uuid}', [MembersController::class, 'suspendMember'])->name('federaladmin.member.suspend')->middleware('access_level:federaladmin');
    Route::delete('/federal/member/delete/{uuid}', [MembersController::class, 'deleteMember'])->name('federaladmin.member.delete')->middleware('access_level:federaladmin');
    Route::get('/federal/members/bylga/{uuid?}', [MembersController::class, 'membersByLocalGovernments'])->name('federaladmin.members.byLga')->middleware('access_level:federaladmin');
    Route::get('/federal/members/lga/{uuid}', [MembersController::class, 'viewMembersByLocalGovernments'])->name('federaladmin.member.lga.view')->middleware('access_level:federaladmin');
    Route::get('/federal/lga/members/data/{uuid}', [MembersController::class, 'getLgaMembersData'])->name('federaladmin.lga.members.data');
    Route::get('/federal/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('federaladmin.members.byWard')->middleware('access_level:federaladmin');
    Route::get('/federal/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('federaladmin.members.byWard.ajax')->middleware('access_level:federaladmin');
    Route::get('/federal/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('federaladmin.member.ward.view')->middleware('access_level:federaladmin');
    Route::get('/federal/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('federaladmin.ward.members.data');
    Route::get('/federal/members/bypu/{uuid?}', [MembersController::class, 'membersByPus'])->name('federaladmin.members.byPu')->middleware('access_level:federaladmin');
    Route::get('/federal/members/bypu/ajax/{uuid?}', [AjaxController::class, 'membersByPusAjax'])->name('federaladmin.members.byPu.ajax')->middleware('access_level:federaladmin');
    Route::get('/federal/members/pu/{uuid}', [MembersController::class, 'viewMembersByPu'])->name('federaladmin.member.pu.view')->middleware('access_level:federaladmin');
    Route::get('/federal/pu/members/data/{uuid}', [MembersController::class, 'getPuMembersData'])->name('federaladmin.pu.members.data');

    Route::get('/federal/location/localgovernment/dashboard/{uuid}', [FederalAdminController::class, 'localGovernmentDashBoard'])->name('federaladmin.location.lga.dashboard')->middleware('access_level:federaladmin');
    Route::get('/federal/location/localgovernments', [\App\Http\Controllers\LocationController::class, 'allLocalGovernments'])->name('federaladmin.location.lgas')->middleware('access_level:federaladmin');
    Route::get('/federal/location/localgovernment/edit/{uuid}', [\App\Http\Controllers\LocationController::class, 'editLocalGovernment'])->name('federaladmin.location.lga.edit')->middleware('access_level:federaladmin');
    Route::post('/federal/location/localgovernment/update/{uuid}', [\App\Http\Controllers\LocationController::class, 'updateLocalGovernment'])->name('federaladmin.location.lga.update')->middleware('access_level:federaladmin');
    Route::get('/federal/lga/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByLga'])->name('federaladmin.lga.member.distribution.gender')->middleware('access_level:federaladmin');
    Route::get('/federal/lga/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByLga'])->name('federaladmin.lga.member.distribution.age')->middleware('access_level:federaladmin');
    Route::get('/federal/lga/member/distribution/ward/{uuid}', [ChartsController::class, 'wardDistributionByLga'])->name('federaladmin.lga.member.distribution.ward')->middleware('access_level:federaladmin');
    Route::get('/federal/lga/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByLga'])->name('federaladmin.lga.member.distribution.voter')->middleware('access_level:federaladmin');
    Route::get('/federal/location/ward/dashboard/{uuid}', [FederalAdminController::class, 'wardDashBoard'])->name('federaladmin.location.ward.dashboard')->middleware('access_level:federaladmin');
    Route::get('/federal/location/wards', [\App\Http\Controllers\LocationController::class, 'allWards'])->name('federaladmin.location.wards')->middleware('access_level:federaladmin');
    Route::get('/federal/location/ward/edit/{uuid}', [\App\Http\Controllers\LocationController::class, 'editWard'])->name('federaladmin.location.ward.edit')->middleware('access_level:federaladmin');
    Route::post('/federal/location/ward/update/{uuid}', [\App\Http\Controllers\LocationController::class, 'updateWard'])->name('federaladmin.location.ward.update')->middleware('access_level:federaladmin');
    Route::get('/federal/ward/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByWard'])->name('federaladmin.ward.member.distribution.gender')->middleware('access_level:federaladmin');
    Route::get('/federal/ward/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByWard'])->name('federaladmin.ward.member.distribution.voter')->middleware('access_level:federaladmin');
    Route::get('/federal/location/pollingunit/dashboard/{uuid}', [FederalAdminController::class, 'PuDashBoard'])->name('federaladmin.location.pu.dashboard')->middleware('access_level:federaladmin');
    Route::get('/federal/location/pollingunits', [\App\Http\Controllers\LocationController::class, 'allPollingUnits'])->name('federaladmin.location.pus')->middleware('access_level:federaladmin');
    Route::get('/federal/location/pollingunit/edit/{uuid}', [\App\Http\Controllers\LocationController::class, 'editPollingUnit'])->name('federaladmin.location.pu.edit')->middleware('access_level:federaladmin');
    Route::post('/federal/location/pollingunit/update/{uuid}', [\App\Http\Controllers\LocationController::class, 'updatePollingUnit'])->name('federaladmin.location.pu.update')->middleware('access_level:federaladmin');
    Route::get('/federal/pollingunit/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByPu'])->name('federaladmin.pu.member.distribution.gender')->middleware('access_level:federaladmin');
    Route::get('/federal/pollingunit/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByPu'])->name('federaladmin.pu.member.distribution.voter')->middleware('access_level:federaladmin');
    Route::get('/federal/federal-constituency/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByFederalConstituency'])->name('federaladmin.federal-constituency.member.distribution.gender')->middleware('access_level:federaladmin');
    Route::get('/federal/federal-constituency/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByFederalConstituency'])->name('federaladmin.federal-constituency.member.distribution.age')->middleware('access_level:federaladmin');
    Route::get('/federal/federal-constituency/member/distribution/lga/{uuid}', [ChartsController::class, 'lgaDistributionByFederalConstituency'])->name('federaladmin.federal-constituency.member.distribution.lga')->middleware('access_level:federaladmin');
    Route::get('/federal/federal-constituency/member/distribution/voter/{uuid}', [ChartsController::class, 'VoterDistributionByFederalConstituency'])->name('federaladmin.federal-constituency.member.distribution.voter')->middleware('access_level:federaladmin');

    Route::get('/federal/elections', [ElectionController::class, 'allElections'])
        ->name('federaladmin.elections')
        ->middleware('access_level:federaladmin');
    Route::get('/federal/election/operations-center', [ElectionController::class, 'operationsCenter'])
        ->name('federaladmin.election.operations')
        ->middleware('access_level:federaladmin');
    Route::get('/federal/election/add', [ElectionController::class, 'addElection'])
        ->name('federaladmin.election.add')
        ->middleware('access_level:federaladmin');
    Route::post('/federal/election/store', [ElectionController::class, 'storeElection'])
        ->name('federaladmin.election.store')
        ->middleware('access_level:federaladmin');
    Route::get('/federal/election/edit/{uuid}', [ElectionController::class, 'editElection'])
        ->name('federaladmin.election.edit')
        ->middleware('access_level:federaladmin');
    Route::post('/federal/election/update/{uuid}', [ElectionController::class, 'updateElection'])
        ->name('federaladmin.election.update')
        ->middleware('access_level:federaladmin');
    Route::delete('/federal/election/delete/{uuid}', [ElectionController::class, 'deleteElection'])
        ->name('federaladmin.election.delete')
        ->middleware('access_level:federaladmin');
    Route::get('/federal/election/result/{uuid}', [ElectionController::class, 'electionResult'])
        ->name('federaladmin.election.results')
        ->middleware('access_level:federaladmin');
    Route::get('/federal/election/votes/{uuid?}', [ElectionController::class, 'manageVotesByPu'])
        ->name('federaladmin.election.votesByPu')
        ->middleware('access_level:federaladmin');
    Route::get('/federal/election/votes/data/{uuid}', [ElectionController::class, 'VotesByPuData'])
        ->name('federaladmin.election.votesByPuData')
        ->middleware('access_level:federaladmin');
    Route::get('/federal/election/incident/{uuid}/{polling_unit_id}', [ElectionController::class, 'manageIncidentByPu'])
        ->name('federaladmin.election.incident')
        ->middleware('access_level:federaladmin');
    Route::get('/federal/incident/add', [ElectionController::class, 'addIncident'])
        ->name('federaladmin.incident.add')
        ->middleware('access_level:federaladmin');
    Route::post('/federal/incident/store', [ElectionController::class, 'storeIcident'])
        ->name('federaladmin.incident.store')
        ->middleware('access_level:federaladmin');
    Route::get('/federal/vote/add', [ElectionController::class, 'addVote'])
        ->name('federaladmin.vote.add')
        ->middleware('access_level:federaladmin');
    Route::post('/federal/vote/store', [ElectionController::class, 'storeVote'])
        ->name('federaladmin.vote.store')
        ->middleware('access_level:federaladmin');
});
