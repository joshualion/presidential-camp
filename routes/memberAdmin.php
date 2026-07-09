<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\MemberElectionController;
use App\Services\MemberCapabilityService;
use Illuminate\Support\Facades\Route;



Route::prefix('member')
    ->middleware(['auth', 'access_level:user', 'check_onboarding'])
    ->group(function () {
        // Member Profile & Dashboard Routes
        Route::get('dashboard', [UserController::class, 'memberDashBoard'])
            ->name('user.dashboard')
            ->middleware('member.capability:' . MemberCapabilityService::DASHBOARD);

        Route::get('profile', [UserController::class, 'memberProfile'])
            ->name('user.profile')
            ->middleware('member.capability:' . MemberCapabilityService::PROFILE);

        Route::post('profile/store', [UserController::class, 'memberProfileStore'])
            ->name('user.profile.store')
            ->middleware('member.capability:' . MemberCapabilityService::PROFILE);

        Route::get('change/password', [UserController::class, 'memberChangePassword'])
            ->name('user.change.password')
            ->middleware('member.capability:' . MemberCapabilityService::ACCOUNT_SECURITY);

        Route::post('update/password', [UserController::class, 'memberUpdatePassword'])
            ->name('user.update.password')
            ->middleware('member.capability:' . MemberCapabilityService::ACCOUNT_SECURITY);

        // Referrals page
        Route::get('referrals', [UserController::class, 'memberReferrals'])
            ->name('user.referrals')
            ->middleware('member.capability:' . MemberCapabilityService::REFERRALS);

        Route::get('pu/details', [UserController::class, 'memberPuDetails'])
            ->name('user.pu.details')
            ->middleware('member.capability:' . MemberCapabilityService::POLLING_UNIT_DETAILS);

        // Voting block route
        Route::get('bloc', [UserController::class, 'memberBlock'])
            ->name('user.block')
            ->middleware('member.capability:' . MemberCapabilityService::VOTING_BLOCK);

        Route::get('notices', [AnnouncementController::class, 'noticeIndex'])
            ->name('user.notices')
            ->middleware('member.capability:' . MemberCapabilityService::NOTICE_BOARD);

        Route::get('notices/{announcement}', [AnnouncementController::class, 'noticeShow'])
            ->name('user.notices.show')
            ->middleware('member.capability:' . MemberCapabilityService::NOTICE_BOARD);

        Route::middleware([
            'module.enabled:elections',
            'member.capability:' . MemberCapabilityService::ELECTION_REPORTS,
        ])->group(function () {
            Route::get('elections', [MemberElectionController::class, 'index'])
                ->name('user.elections');

            Route::get('elections/{election}/results', [MemberElectionController::class, 'results'])
                ->name('user.elections.results');
        });

        Route::middleware([
            'module.enabled:elections',
            'member.capability:' . MemberCapabilityService::ELECTION_SUBMISSION,
        ])->group(function () {
            Route::get('election-workspace', [MemberElectionController::class, 'workspace'])
                ->name('user.elections.workspace');

            Route::get('election-workspace/{election}/{assignment}/result', [MemberElectionController::class, 'resultForm'])
                ->name('user.elections.result.form');

            Route::post('election-workspace/{election}/{assignment}/result', [MemberElectionController::class, 'submitResult'])
                ->name('user.elections.result.store');

            Route::get('election-workspace/{election}/{assignment}/incident', [MemberElectionController::class, 'incidentForm'])
                ->name('user.elections.incident.form');

            Route::post('election-workspace/{election}/{assignment}/incident', [MemberElectionController::class, 'submitIncident'])
                ->name('user.elections.incident.store');
        });
    });