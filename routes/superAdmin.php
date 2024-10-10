<?php

use App\Http\Controllers\superadmin\SuperAdminController;
use App\Http\Controllers\superadmin\SystemSettingController;
use App\Http\Controllers\superadmin\RolePermissionController;
use Illuminate\Support\Facades\Route;




Route::middleware('auth')->group(function () {
    //Superadmin system Setting
    Route::get('/superadmin/system/settings/', [SystemSettingController::class,'systemSettings'])->name('superadmin.settings.system');
    Route::post('/superadmin/settings/system/update', [SystemSettingController::class,'updateSystemsSettings'])->name('superadmin.settings.system.update');

     //Admin SMTP Setting
     Route::get('/superadmin/settings/smtp', [SystemSettingController::class,'SmtpSettings'])->name('superadmin.settings.smtp');
     Route::post('/superadmin/settings/smtp/update', [SystemSettingController::class,'UpdateSmtpSettings'])->name('superadmin.settings.smtp.update');

     //Admin Privacy Setting
     Route::get('superadmin/settings/policy', [SystemSettingController::class,'PrivacySetting'])->name('superadmin.settings.privacy');
     Route::post('/superadmin/settings/privacy/update', [SystemSettingController::class,'UpdatePrivacyPolicy'])->name('superadmin.settings.privacy.update');

      //Admin Terms of Condition Setting
      Route::get('superadmin/settings/terms', [SystemSettingController::class,'TosSetting'])->name('superadmin.settings.terms');
      Route::post('/superadmin/settings/terms/update', [SystemSettingController::class,'UpdateTos'])->name('superadmin.settings.terms.update');


  // Super Admin Profile & Dashboard Routes
  Route::get('/superadmin/dashboard', [SuperAdminController::class, 'SuperAdminDashBoard'])->name('superadmin.dashboard')->middleware('access_level:superadmin');
  Route::get('/superadmin/profile', [SuperAdminController::class, 'SuperAdminProfile'])->name('superadmin.profile')->middleware('access_level:superadmin');
  Route::post('/superadmin/profile/store', [SuperAdminController::class, 'SuperAdminProfileStore'])->name('superadmin.profile.store')->middleware('access_level:superadmin');
  Route::get('/superadmin/change/password', [SuperAdminController::class, 'SuperAdminChangePassword'])->name('superadmin.change.password')->middleware('access_level:superadmin');
  Route::post('/superadmin/update/password', [SuperAdminController::class, 'SuperAdminUpdatePassword'])->name('superadmin.update.password')->middleware('access_level:superadmin');
  Route::get('/superadmin/logout', [SuperAdminController::class, 'superAdminLogout'])->name('superadmin.logout')->middleware('access_level:superadmin');


// Permission Routes
Route::get('/superadmin/settings/permissions', [RolePermissionController::class, 'allpermission'])->name('superadmin.settings.permissions')->middleware('access_level:superadmin');
Route::get('/superadmin/settings/permission/add', [RolePermissionController::class, 'addPermission'])->name('superadmin.settings.permission.add')->middleware('access_level:superadmin');
Route::post('/superadmin/settings/permission/store', [RolePermissionController::class, 'storePermission'])->name('superadmin.settings.permission.store')->middleware('access_level:superadmin');
Route::get('/superadmin/settings/permission/edit/{id}', [RolePermissionController::class, 'editPermission'])->name('superadmin.settings.permission.edit')->middleware('access_level:superadmin');
Route::post('/superadmin/settings/permission/update/{id}', [RolePermissionController::class, 'updatePermission'])->name('superadmin.settings.permission.update')->middleware('access_level:superadmin');
Route::delete('/superadmin/settings/permission/delete/{id}', [RolePermissionController::class, 'deletePermission'])->name('superadmin.settings.permission.delete')->middleware('access_level:superadmin');
Route::get('/superadmin/settings/import/permissions', [RolePermissionController::class, 'importPermission'])->name('superadmin.settings.import.permission')->middleware('access_level:superadmin');
Route::get('/superadmin/settings/export/permissions', [RolePermissionController::class, 'exportPermission'])->name('superadmin.settings.export.permission')->middleware('access_level:superadmin');
Route::post('/superadmin/settings/import/permissions/data', [RolePermissionController::class, 'importPermissionData'])->name('superadmin.settings.import.permission.data')->middleware('access_level:superadmin');

//Role Routes
Route::get('/superadmin/settings/roles', [RolePermissionController::class, 'allrole'])->name('superadmin.settings.roles')->middleware('access_level:superadmin');
Route::get('/superadmin/settings/role/add', [RolePermissionController::class, 'addrole'])->name('superadmin.settings.role.add')->middleware('access_level:superadmin');
Route::post('/superadmin/settings/role/store', [RolePermissionController::class, 'storerole'])->name('superadmin.settings.role.store')->middleware('access_level:superadmin');
Route::get('/superadmin/settings/role/edit/{id}', [RolePermissionController::class, 'editrole'])->name('superadmin.settings.role.edit')->middleware('access_level:superadmin');
Route::post('/superadmin/settings/role/update/{id}', [RolePermissionController::class, 'updaterole'])->name('superadmin.settings.role.update')->middleware('access_level:superadmin');
Route::delete('/superadmin/settings/role/delete/{id}', [RolePermissionController::class, 'deleterole'])->name('superadmin.settings.role.delete')->middleware('access_level:superadmin');

// Roles & Permission Routes
Route::get('/superadmin/settings/rolespermission', [RolePermissionController::class, 'allRolesPermission'])->name('superadmin.settings.rolespermission')->middleware('access_level:superadmin');
Route::get('/superadmin/settings/add/roles/permission', [RolePermissionController::class, 'addRolePermission'])->name('superadmin.settings.addrole.permission')->middleware('access_level:superadmin');
Route::post('/superadmin/settings/role/permission/store', [RolePermissionController::class, 'RolePermissionStore'])->name('superadmin.settings.role.permission.store')->middleware('access_level:superadmin');
Route::get('/superadmin/settings/role/permission/edit/{id}', [RolePermissionController::class, 'editRolePermission'])->name('superadmin.settings.role.permission.edit')->middleware('access_level:superadmin');
Route::post('/superadmin/settings/role/permission/update/{id}', [RolePermissionController::class, 'updateRolePermission'])->name('superadmin.settings.role.permission.update')->middleware('access_level:superadmin');
Route::delete('/superadmin/settings/role/permission/delete/{id}', [RolePermissionController::class, 'deleteRolePermission'])->name('superadmin.settings.role.permission.delete')->middleware('access_level:superadmin');


/*Super Admin Ajax Routes */
//Gender Distribution
Route::get('/superadmin/member/distribution/gender', [SuperAdminController::class, 'SuperAdminGenderDistribution'])->name('superadmin.member.distribution.gender')->middleware('access_level:superadmin');

// Age Distribution
Route::get('/superadmin/member/distribution/age', [SuperAdminController::class, 'SuperAdminAgeDistribution'])->name('superadmin.member.distribution.age')->middleware('access_level:superadmin');

// Region Distribution
Route::get('/superadmin/member/distribution/region', [SuperAdminController::class, 'SuperAdminRegionDistribution'])->name('superadmin.member.distribution.region')->middleware('access_level:superadmin');
/*End Super Admin Ajax Routes */
// Religion Distribution
Route::get('/superadmin/member/distribution/religion', [SuperAdminController::class, 'SuperAdminReligionDistribution'])->name('superadmin.member.distribution.religion')->middleware('access_level:superadmin');
// Valid Voter Distribution
Route::get('/superadmin/member/distribution/voter', [SuperAdminController::class, 'SuperAdminVoterDistribution'])->name('superadmin.member.distribution.voter')->middleware('access_level:superadmin');
// State Distribution
Route::get('/superadmin/member/distribution/state', [SuperAdminController::class, 'SuperAdminStateDistribution'])->name('superadmin.member.distribution.state')->middleware('access_level:superadmin');
});

