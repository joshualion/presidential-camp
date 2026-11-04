<?php

use App\Http\Controllers\EnterpriseWorkforceReportController;
use Illuminate\Support\Facades\Route;

// Each endpoint requires both the installation entitlement and an explicit user grant.
Route::middleware(['auth', 'check_onboarding'])->prefix('enterprise/reports')->name('enterprise.reports.')->group(function () {
    Route::get('/workforce', [EnterpriseWorkforceReportController::class, 'index'])
        ->middleware('enterprise.feature:advanced_reports,reports.view')->name('workforce');
    Route::get('/workforce.csv', [EnterpriseWorkforceReportController::class, 'export'])
        ->middleware(['enterprise.feature:advanced_reports,reports.view', 'enterprise.feature:advanced_reports,reports.export', 'throttle:6,1'])
        ->name('workforce.export');
});
