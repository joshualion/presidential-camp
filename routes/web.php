<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AjaxController;





// Ajax Routes
Route::get('/get-regions', [AjaxController::class, 'getRegions'])->name('getRegions');
Route::post('/get-states', [AjaxController::class, 'ajaxGetStates'])->name('getStates');
Route::post('/get-lgas', [AjaxController::class, 'ajaxGetLgas'])->name('getLgas');
Route::post('/get-wards', [AjaxController::class, 'ajaxGetWards'])->name('getWards');
Route::post('/get-polling-units', [AjaxController::class, 'ajaxGetPollingUnits'])->name('getPollingUnits');

Route::get('/get-roles-by-access-level/{accessLevel}', [AjaxController::class, 'getRolesByAccessLevel'])->name('ajaxGetRolesByAccessLevel');

Route::get('/lga/data', [AjaxController::class, 'getLgaData'])->name('lgas.data');

Route::get('/ward/data', [AjaxController::class, 'getWardData'])->name('wards.data');

Route::get('/pu/data', [AjaxController::class, 'getPUData'])->name('pus.data');

Route::get('/', function () {
    return redirect()->route('login');
});

//Require Super Admin Routes file
require __DIR__.'/superAdmin.php';

//Require National Admin Routes file
require __DIR__.'/nationalAdmin.php';

//Require Auth Routes file
require __DIR__.'/auth.php';
