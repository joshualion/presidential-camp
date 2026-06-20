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
        Route::match(['get', 'post'], '/license', [InstallerController::class, 'license'])->name('license');
        Route::match(['get', 'post'], '/admin', [InstallerController::class, 'admin'])->name('admin');
        Route::match(['get', 'post'], '/run', [InstallerController::class, 'run'])->name('run');
        Route::get('/complete', [InstallerController::class, 'complete'])->name('complete');
    });
