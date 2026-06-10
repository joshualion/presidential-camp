<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/logout', [UserController::class, 'memberLogout'])->name('user.logout')->middleware(['auth']);

Route::middleware(['auth'])->group(function () {
    // Member Profile & Dashboard Routes
    Route::get('dashboard', [UserController::class, 'memberDashBoard'])->name('user.dashboard');
    Route::get('profile', [UserController::class, 'memberProfile'])->name('user.profile');
    Route::post('profile/store', [UserController::class, 'memberProfileStore'])->name('user.profile.store');
    Route::get('change/password', [UserController::class, 'memberChangePassword'])->name('user.change.password');
    Route::post('update/password', [UserController::class, 'memberUpdatePassword'])->name('user.update.password');

    // ✅ Referrals page
    Route::get('referrals', [UserController::class, 'memberReferrals'])->name('user.referrals');
    Route::get('pu/details', [UserController::class, 'memberPuDetails'])->name('user.pu.details');
        // ✅ new voting block route
    Route::get('block', [UserController::class, 'memberBlock'])->name('user.block');
});


