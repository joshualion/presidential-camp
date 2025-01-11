<?php

use App\Http\Controllers\BulksmsController;
use App\Http\Controllers\ElectionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileCompletionController;
use App\Http\Controllers\CredentialUpdateController;
use App\Http\Controllers\AjaxController;






// Ajax Routes
Route::post('/get-group-recipients', [AjaxController::class, 'getGroupRecipients'])->name('getGroupRecipients');

Route::get('/get-regions', [AjaxController::class, 'getRegions'])->name('getRegions');
Route::post('/get-states', [AjaxController::class, 'ajaxGetStates'])->name('getStates');
Route::post('/get-lgas', [AjaxController::class, 'ajaxGetLgas'])->name('getLgas');
Route::post('/get-wards', [AjaxController::class, 'ajaxGetWards'])->name('getWards');
Route::post('/get-polling-units', [AjaxController::class, 'ajaxGetPollingUnits'])->name('getPollingUnits');

Route::get('/get-roles-by-access-level/{accessLevel}', [AjaxController::class, 'getRolesByAccessLevel'])->name('ajaxGetRolesByAccessLevel');

Route::get('/lga/data', [AjaxController::class, 'getLgaData'])->name('lgas.data');

Route::get('/ward/data', [AjaxController::class, 'getWardData'])->name('wards.data');

Route::get('/pu/data', [AjaxController::class, 'getPUData'])->name('pus.data');

Route::get('/election/{uuid}/chart-data', [ElectionController::class, 'getElectionResultChartData'])
    ->name('election.chart.data');


   
    // Credential update route
    Route::get('/update-credentials', [CredentialUpdateController::class, 'showUpdateForm'])
        ->name('update.credentials')
        ->middleware(['auth']);
    
    // Process credential update
    Route::post('/update-credentials', [CredentialUpdateController::class, 'update']) ->name('update.credentials.store')
    ->middleware(['auth']);
    
    // Profile completion route
    Route::get('/complete-profile', [ProfileCompletionController::class, 'showProfileForm'])
        ->name('complete.profile')
        ->middleware(['auth']);
    
    // Process profile completion
    Route::post('/complete-profile', [ProfileCompletionController::class, 'complete'])->name('complete.profile.store')
    ->middleware(['auth']);
    



//Require Super Admin Routes file
require __DIR__.'/superAdmin.php';

//Require National Admin Routes file
require __DIR__.'/nationalAdmin.php';

//Require Regional Admin Routes file
require __DIR__.'/regionalAdmin.php';

//Require State Admin Routes file
require __DIR__.'/stateAdmin.php';

//Require LGA Admin Routes file
require __DIR__.'/lgaAdmin.php';

//Require Auth Routes file
require __DIR__.'/auth.php';

//Application Landing Page
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        $dashboardRoute = match ($user->access_level) {
            'superadmin' => route('superadmin.dashboard'),
            'nationaladmin' => route('nationaladmin.dashboard'),
            'stateadmin' => route('stateadmin.dashboard'),
            'lgaadmin' => route('lgaadmin.dashboard'),
            'wardadmin' => route('wardadmin.dashboard'),
            'puadmin' => route('puadmin.dashboard'),
            // Add more levels here as needed
            default => route('login'),
        };
        return redirect($dashboardRoute);
    }
    return redirect()->route('login');
});