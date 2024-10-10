<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AjaxController;

// Ajax Routes
Route::get('/get-regions', [AjaxController::class, 'getRegions'])->name('getRegions');
Route::post('/get-states', [AjaxController::class, 'ajaxGetStates'])->name('getStates');
Route::post('/get-lgas', [AjaxController::class, 'ajaxGetLgas'])->name('getLgas');
Route::post('/get-wards', [AjaxController::class, 'ajaxGetWards'])->name('getWards');
Route::post('/get-polling-units', [AjaxController::class, 'ajaxGetPollingUnits'])->name('getPollingUnits');

Route::get('/', function () {
    return view('welcome');
});

//Require Super Admin Routes file
require __DIR__.'/superAdmin.php';

//Require Auth Routes file
require __DIR__.'/auth.php';
