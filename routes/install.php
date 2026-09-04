<?php

use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['installer.block_if_installed'])
    ->prefix('install')
    ->name('install.')
    ->group(function () {
        Route::get('/', [InstallerController::class, 'welcome'])->name('welcome');
        Route::match(['get', 'post'], '/requirements', [InstallerController::class, 'requirements'])->name('requirements');
        Route::match(['get', 'post'], '/environment', [InstallerController::class, 'environment'])->name('environment');
        Route::match(['get', 'post'], '/database', [InstallerController::class, 'database'])->name('database');
        Route::match(['get', 'post'], '/mail', [InstallerController::class, 'mail'])->name('mail');
        Route::match(['get', 'post'], '/storage', [InstallerController::class, 'storage'])->name('storage');
        Route::match(['get', 'post'], '/campaign-scope', [InstallerController::class, 'campaignScope'])->name('campaign_scope');
        Route::match(['get', 'post'], '/campaign-geography', [InstallerController::class, 'campaignGeography'])->name('campaign_geography');
        Route::match(['get', 'post'], '/campaign-identity', [InstallerController::class, 'campaignIdentity'])->name('campaign_identity');
        Route::match(['get', 'post'], '/geography-setup', [InstallerController::class, 'geographySetup'])->name('geography_setup');
        Route::match(['get', 'post'], '/license', [InstallerController::class, 'license'])->name('license');
        Route::match(['get', 'post'], '/admin', [InstallerController::class, 'admin'])->name('admin');
        Route::match(['get', 'post'], '/run', [InstallerController::class, 'run'])->name('run');
        Route::match(['get', 'post'], '/provision-locations', [InstallerController::class, 'provision'])->name('provision');
        Route::post('/provision-locations/step', [InstallerController::class, 'provisionLocationsStep'])->name('provision.step');
        Route::post('/provision-locations/finish', [InstallerController::class, 'finishProvisioning'])->name('provision.finish');
        Route::get('/complete', [InstallerController::class, 'complete'])->name('complete');
    });
