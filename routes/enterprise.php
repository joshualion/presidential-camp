<?php

use App\Http\Controllers\EnterpriseWorkforceReportController;
use Illuminate\Support\Facades\Route;

// Each endpoint requires both the installation entitlement and an explicit user grant.
Route::middleware(['auth', 'check_onboarding'])->prefix('enterprise/reports')->name('enterprise.reports.')->group(function () {
    Route::post('/workforce/analyze', [EnterpriseWorkforceReportController::class, 'analyze'])
        ->middleware(['enterprise.feature:advanced_reports,reports.view', 'enterprise.feature:advanced_reports,reports.ai.generate', 'throttle:3,1'])
        ->name('workforce.analyze');
    Route::get('/ai/settings', [\App\Http\Controllers\EnterpriseAiSettingsController::class, 'edit'])
        ->middleware(['access_level:superadmin', 'enterprise.feature:advanced_reports,reports.settings.manage'])->name('ai.settings');
    Route::put('/ai/settings', [\App\Http\Controllers\EnterpriseAiSettingsController::class, 'update'])
        ->middleware(['access_level:superadmin', 'enterprise.feature:advanced_reports,reports.settings.manage', 'throttle:6,1'])->name('ai.settings.update');
    Route::get('/workforce', [EnterpriseWorkforceReportController::class, 'index'])
        ->middleware('enterprise.feature:advanced_reports,reports.view')->name('workforce');
    Route::get('/workforce.pdf', [EnterpriseWorkforceReportController::class, 'pdf'])
        ->middleware(['enterprise.feature:advanced_reports,reports.view', 'enterprise.feature:advanced_reports,reports.export', 'throttle:6,1'])
        ->name('workforce.pdf');
    Route::get('/workforce.csv', [EnterpriseWorkforceReportController::class, 'export'])
        ->middleware(['enterprise.feature:advanced_reports,reports.view', 'enterprise.feature:advanced_reports,reports.export', 'throttle:6,1'])
        ->name('workforce.export');
});

