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
    Route::get('/senatorial/member/add', [MembersController::class, 'addMember'])->name('senatorialadmin.member.add')->middleware('access_level:senatorialadmin');
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
    Route::get('/senatorial/lga/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByLga'])->name('senatorialadmin.lga.member.distribution.gender')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/lga/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByLga'])->name('senatorialadmin.lga.member.distribution.age')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/lga/member/distribution/ward/{uuid}', [ChartsController::class, 'wardDistributionByLga'])->name('senatorialadmin.lga.member.distribution.ward')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/lga/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByLga'])->name('senatorialadmin.lga.member.distribution.voter')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/location/ward/dashboard/{uuid}', [SenatorialAdminController::class, 'wardDashBoard'])->name('senatorialadmin.location.ward.dashboard')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/ward/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByWard'])->name('senatorialadmin.ward.member.distribution.gender')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/ward/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByWard'])->name('senatorialadmin.ward.member.distribution.voter')->middleware('access_level:senatorialadmin');
    Route::get('/senatorial/location/pollingunit/dashboard/{uuid}', [SenatorialAdminController::class, 'PuDashBoard'])->name('senatorialadmin.location.pu.dashboard')->middleware('access_level:senatorialadmin');
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
