<?php

use App\Http\Controllers\ChartsController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\nationaladmin\NationalAdminController;
use App\Http\Controllers\nationaladmin\NationalSystemSettingController;
use App\Http\Controllers\RolePermissionController;
use Illuminate\Support\Facades\Route;




Route::middleware('auth')->group(function () {
    //nationaladmin system Setting
    Route::get('/national/system/settings/', [NationalSystemSettingController::class,'SystemSettings'])->name('nationaladmin.settings.system')->middleware('access_level:nationaladmin');
    Route::post('/national/settings/system/update', [NationalSystemSettingController::class,'updateSystemsSettings'])->name('nationaladmin.settings.system.update')->middleware('access_level:nationaladmin');

  //Admin SMTP Setting
  Route::get('/national/settings/smtp', [NationalSystemSettingController::class,'SmtpSettings'])->name('nationaladmin.settings.smtp')->middleware('access_level:nationaladmin');
  Route::post('/national/settings/smtp/update', [NationalSystemSettingController::class,'UpdateSmtpSettings'])->name('nationaladmin.settings.smtp.update')->middleware('access_level:nationaladmin');

  //Admin Privacy Setting
  Route::get('nationaladmin/settings/policy', [NationalSystemSettingController::class,'PrivacySetting'])->name('nationaladmin.settings.privacy')->middleware('access_level:nationaladmin');
  Route::post('/national/settings/privacy/update', [NationalSystemSettingController::class,'UpdatePrivacyPolicy'])->name('nationaladmin.settings.privacy.update')->middleware('access_level:nationaladmin');

  //Admin Terms of Condition Setting
  Route::get('nationaladmin/settings/terms', [NationalSystemSettingController::class,'TosSetting'])->name('nationaladmin.settings.terms')->middleware('access_level:nationaladmin');
  Route::post('/national/settings/terms/update', [NationalSystemSettingController::class,'UpdateTos'])->name('nationaladmin.settings.terms.update')->middleware('access_level:nationaladmin');


  // Super Admin Profile & Dashboard Routes
  Route::get('/national/dashboard', [NationalAdminController::class, 'nationaladminDashBoard'])->name('nationaladmin.dashboard')->middleware('access_level:nationaladmin');
  Route::get('/national/profile', [NationalAdminController::class, 'nationaladminProfile'])->name('nationaladmin.profile')->middleware('access_level:nationaladmin');
  Route::post('/national/profile/store', [NationalAdminController::class, 'nationaladminProfileStore'])->name('nationaladmin.profile.store')->middleware('access_level:nationaladmin');
  Route::get('/national/change/password', [NationalAdminController::class, 'nationaladminChangePassword'])->name('nationaladmin.change.password')->middleware('access_level:nationaladmin');
  Route::post('/national/update/password', [NationalAdminController::class, 'nationaladminUpdatePassword'])->name('nationaladmin.update.password')->middleware('access_level:nationaladmin');
  Route::get('/national/logout', [NationalAdminController::class, 'nationaladminLogout'])->name('nationaladmin.logout')->middleware('access_level:nationaladmin');


// Permission Routes
Route::get('/national/settings/permissions', [RolePermissionController::class, 'allpermission'])->name('nationaladmin.settings.permissions')->middleware('access_level:nationaladmin');
Route::get('/national/settings/permission/add', [RolePermissionController::class, 'addPermission'])->name('nationaladmin.settings.permission.add')->middleware('access_level:nationaladmin');
Route::post('/national/settings/permission/store', [RolePermissionController::class, 'storePermission'])->name('nationaladmin.settings.permission.store')->middleware('access_level:nationaladmin');
Route::get('/national/settings/permission/edit/{id}', [RolePermissionController::class, 'editPermission'])->name('nationaladmin.settings.permission.edit')->middleware('access_level:nationaladmin');
Route::post('/national/settings/permission/update/{id}', [RolePermissionController::class, 'updatePermission'])->name('nationaladmin.settings.permission.update')->middleware('access_level:nationaladmin');
Route::delete('/national/settings/permission/delete/{id}', [RolePermissionController::class, 'deletePermission'])->name('nationaladmin.settings.permission.delete')->middleware('access_level:nationaladmin');
Route::get('/national/settings/import/permissions', [RolePermissionController::class, 'importPermission'])->name('nationaladmin.settings.import.permission')->middleware('access_level:nationaladmin');
Route::get('/national/settings/export/permissions', [RolePermissionController::class, 'exportPermission'])->name('nationaladmin.settings.export.permission')->middleware('access_level:nationaladmin');
Route::post('/national/settings/import/permissions/data', [RolePermissionController::class, 'importPermissionData'])->name('nationaladmin.settings.import.permission.data')->middleware('access_level:nationaladmin');

//Role Routes
Route::get('/national/settings/roles', [RolePermissionController::class, 'allrole'])->name('nationaladmin.settings.roles')->middleware('access_level:nationaladmin');
Route::get('/national/settings/role/add', [RolePermissionController::class, 'addrole'])->name('nationaladmin.settings.role.add')->middleware('access_level:nationaladmin');
Route::post('/national/settings/role/store', [RolePermissionController::class, 'storerole'])->name('nationaladmin.settings.role.store')->middleware('access_level:nationaladmin');
Route::get('/national/settings/role/edit/{id}', [RolePermissionController::class, 'editrole'])->name('nationaladmin.settings.role.edit')->middleware('access_level:nationaladmin');
Route::post('/national/settings/role/update/{id}', [RolePermissionController::class, 'updaterole'])->name('nationaladmin.settings.role.update')->middleware('access_level:nationaladmin');
Route::delete('/national/settings/role/delete/{id}', [RolePermissionController::class, 'deleterole'])->name('nationaladmin.settings.role.delete')->middleware('access_level:nationaladmin');

// Roles & Permission Routes
Route::get('/national/settings/rolespermission', [RolePermissionController::class, 'allRolesPermission'])->name('nationaladmin.settings.rolespermission')->middleware('access_level:nationaladmin');
Route::get('/national/settings/add/roles/permission', [RolePermissionController::class, 'addRolePermission'])->name('nationaladmin.settings.addrole.permission')->middleware('access_level:nationaladmin');
Route::post('/national/settings/role/permission/store', [RolePermissionController::class, 'RolePermissionStore'])->name('nationaladmin.settings.role.permission.store')->middleware('access_level:nationaladmin');
Route::get('/national/settings/role/permission/edit/{id}', [RolePermissionController::class, 'editRolePermission'])->name('nationaladmin.settings.role.permission.edit')->middleware('access_level:nationaladmin');
Route::post('/national/settings/role/permission/update/{id}', [RolePermissionController::class, 'updateRolePermission'])->name('nationaladmin.settings.role.permission.update')->middleware('access_level:nationaladmin');
Route::delete('/national/settings/role/permission/delete/{id}', [RolePermissionController::class, 'deleteRolePermission'])->name('nationaladmin.settings.role.permission.delete')->middleware('access_level:nationaladmin');


/*Super Admin Ajax Routes */
//Gender Distribution
Route::get('/national/member/distribution/gender', [NationalAdminController::class, 'nationaladminGenderDistribution'])->name('nationaladmin.member.distribution.gender')->middleware('access_level:nationaladmin');

// Age Distribution
Route::get('/national/member/distribution/age', [NationalAdminController::class, 'nationaladminAgeDistribution'])->name('nationaladmin.member.distribution.age')->middleware('access_level:nationaladmin');

// Region Distribution
Route::get('/national/member/distribution/region', [NationalAdminController::class, 'nationaladminRegionDistribution'])->name('nationaladmin.member.distribution.region')->middleware('access_level:nationaladmin');
/*End Super Admin Ajax Routes */
// Religion Distribution
Route::get('/national/member/distribution/religion', [NationalAdminController::class, 'nationaladminReligionDistribution'])->name('nationaladmin.member.distribution.religion')->middleware('access_level:nationaladmin');
// Valid Voter Distribution
Route::get('/national/member/distribution/voter', [NationalAdminController::class, 'nationaladminVoterDistribution'])->name('nationaladmin.member.distribution.voter')->middleware('access_level:nationaladmin');
// State Distribution
Route::get('/national/member/distribution/state', [NationalAdminController::class, 'nationaladminStateDistribution'])->name('nationaladmin.member.distribution.state')->middleware('access_level:nationaladmin');
//Member Management
Route::get('/national/members/data', [MembersController::class, 'getMembersData'])->name('nationaladmin.members.data');

Route::get('/national/members', [MembersController::class, 'allMembers'])->name('nationaladmin.members')->middleware('access_level:nationaladmin');
Route::get('/national/member/add', [MembersController::class, 'addMember'])->name('nationaladmin.member.add')->middleware('access_level:nationaladmin');
Route::post('/national/member/store', [MembersController::class, 'storeMember'])->name('nationaladmin.member.store')->middleware('access_level:nationaladmin');
Route::get('/national/member/edit/{uuid}', [MembersController::class, 'editMember'])->name('nationaladmin.member.edit')->middleware('access_level:nationaladmin');
Route::put('/national/member/update/', [MembersController::class, 'updateMember'])->name('nationaladmin.member.update')->middleware('access_level:nationaladmin');
Route::get('/national/member/view/{uuid}', [MembersController::class, 'viewMember'])->name('nationaladmin.member.view')->middleware('access_level:nationaladmin');
Route::get('/national/member/suspend/{uuid}', [MembersController::class, 'suspendMember'])->name('nationaladmin.member.suspend')->middleware('access_level:nationaladmin');
Route::delete('/national/member/delete/{uuid}', [MembersController::class, 'deleteMember'])->name('nationaladmin.member.delete')->middleware('access_level:nationaladmin');
 //Members By region
Route::get('/national/members/region', [MembersController::class, 'membersByRegions'])->name('nationaladmin.members.region')->middleware('access_level:nationaladmin');
Route::get('/national/members/region/{uuid}', [MembersController::class, 'ViewMembersByRegion'])->name('nationaladmin.member.region.view')->middleware('access_level:nationaladmin');
Route::get('/national/region/members/data/{uuid}', [MembersController::class, 'getRegionMembersData'])->name('nationaladmin.region.members.data');
// Members By State
Route::get('/national/members/bystate/{uuid?}', [MembersController::class, 'membersByStates'])->name('nationaladmin.members.byState')->middleware('access_level:nationaladmin');    
Route::get('/national/members/state/{uuid}', [MembersController::class, 'ViewMembersByState'])->name('nationaladmin.member.state.view')->middleware('access_level:nationaladmin');
Route::get('/national/state/members/data/{uuid}', [MembersController::class, 'getStateMembersData'])->name('nationaladmin.state.members.data');

//Locations Management Routes
//Manage Regions
Route::get('/national/location/region/dashboard/{uuid}', [NationalAdminController::class, 'RegionDashBoard'])->name('nationaladmin.location.region.dashboard')->middleware('access_level:nationaladmin');
Route::get('/national/region/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByRegion'])->name('nationaladmin.region.member.distribution.gender')->middleware('access_level:nationaladmin');
Route::get('/national/region/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByRegion'])->name('nationaladmin.region.member.distribution.age')->middleware('access_level:nationaladmin');
Route::get('/national/region/member/distribution/state/{uuid}', [ChartsController::class, 'StateDistributionByRegion'])->name('nationaladmin.region.member.distribution.state')->middleware('access_level:nationaladmin');
Route::get('/national/region/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByRegion'])->name('nationaladmin.region.member.distribution.voter')->middleware('access_level:nationaladmin');
Route::get('/national/location/regions', [LocationController::class, 'allRegions'])->name('nationaladmin.location.regions')->middleware('access_level:nationaladmin');
Route::get('/national/location/region/add', [LocationController::class, 'addRegion'])->name('nationaladmin.location.region.add')->middleware('access_level:nationaladmin');
Route::post('/national/location/region/store', [LocationController::class, 'storeRegion'])->name('nationaladmin.location.region.store')->middleware('access_level:nationaladmin');
Route::get('/national/location/region/edit/{uuid}', [LocationController::class, 'editRegion'])->name('nationaladmin.location.region.edit')->middleware('access_level:nationaladmin');
Route::post('/national/location/region/update/{uuid}', [LocationController::class, 'updateRegion'])->name('nationaladmin.location.region.update')->middleware('access_level:nationaladmin');
Route::delete('/national/location/region/delete/{uuid}', [LocationController::class, 'deleteRegion'])->name('nationaladmin.location.region.delete')->middleware('access_level:nationaladmin');

// Manage States
Route::get('/national/location/state/dashboard/{uuid}', [NationalAdminController::class, 'StateDashBoard'])->name('nationaladmin.location.state.dashboard')->middleware('access_level:nationaladmin');
Route::get('/national/state/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByState'])->name('nationaladmin.state.member.distribution.gender')->middleware('access_level:nationaladmin');
Route::get('/national/state/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByState'])->name('nationaladmin.state.member.distribution.age')->middleware('access_level:nationaladmin');
Route::get('/national/state/member/distribution/lga/{uuid}', [ChartsController::class, 'LgaDistributionByState'])->name('nationaladmin.state.member.distribution.lga')->middleware('access_level:nationaladmin');
Route::get('/national/state/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByState'])->name('nationaladmin.state.member.distribution.voter')->middleware('access_level:nationaladmin');

Route::get('/national/location/states', [LocationController::class, 'allStates'])->name('nationaladmin.location.states')->middleware('access_level:nationaladmin');
Route::get('/national/location/state/add', [LocationController::class, 'addState'])->name('nationaladmin.location.state.add')->middleware('access_level:nationaladmin');
Route::post('/national/location/state/store', [LocationController::class, 'storeState'])->name('nationaladmin.location.state.store')->middleware('access_level:nationaladmin');
Route::get('/national/location/state/edit/{uuid}', [LocationController::class, 'editState'])->name('nationaladmin.location.state.edit')->middleware('access_level:nationaladmin');
Route::post('/national/location/state/update/{uuid}', [LocationController::class, 'updateState'])->name('nationaladmin.location.state.update')->middleware('access_level:nationaladmin');
Route::delete('/national/location/state/delete/{uuid}', [LocationController::class, 'deleteState'])->name('nationaladmin.location.state.delete')->middleware('access_level:nationaladmin');

// manage Local Governments
Route::get('/national/location/localgovernments', [LocationController::class, 'allLocalGovernments'])->name('nationaladmin.location.lgas')->middleware('access_level:nationaladmin');
Route::get('/national/location/localgovernment/add', [LocationController::class, 'addLocalGovernment'])->name('nationaladmin.location.lga.add')->middleware('access_level:nationaladmin');
Route::post('/national/location/localgovernment/store', [LocationController::class, 'storeLocalGovernment'])->name('nationaladmin.location.lga.store')->middleware('access_level:nationaladmin');
Route::get('/national/location/localgovernment/edit/{uuid}', [LocationController::class, 'editLocalGovernment'])->name('nationaladmin.location.lga.edit')->middleware('access_level:nationaladmin');
Route::post('/national/location/localgovernment/update/{uuid}', [LocationController::class, 'updateLocalGovernment'])->name('nationaladmin.location.lga.update')->middleware('access_level:nationaladmin');
Route::delete('/national/location/localgovernment/delete/{uuid}', [LocationController::class, 'deleteLocalGovernment'])->name('nationaladmin.location.lga.delete')->middleware('access_level:nationaladmin');

// manage Wards
Route::get('/national/location/wards', [LocationController::class, 'allWards'])->name('nationaladmin.location.wards')->middleware('access_level:nationaladmin');
Route::get('/national/location/ward/add', [LocationController::class, 'addWard'])->name('nationaladmin.location.ward.add')->middleware('access_level:nationaladmin');
Route::post('/national/location/ward/store', [LocationController::class, 'storeWard'])->name('nationaladmin.location.ward.store')->middleware('access_level:nationaladmin');
Route::get('/national/location/ward/edit/{uuid}', [LocationController::class, 'editWard'])->name('nationaladmin.location.ward.edit')->middleware('access_level:nationaladmin');
Route::post('/national/location/ward/update/{uuid}', [LocationController::class, 'updateWard'])->name('nationaladmin.location.ward.update')->middleware('access_level:nationaladmin');
Route::delete('/national/location/ward/delete/{uuid}', [LocationController::class, 'deleteWard'])->name('nationaladmin.location.ward.delete')->middleware('access_level:nationaladmin');
//import PU Form
Route::get('/national/location/importwards', [LocationController::class, 'importWardsForm'])->name('nationaladmin.location.ward.import')->middleware('access_level:nationaladmin');
// Export Route
Route::get('/national/location/exportwards', [LocationController::class, 'exportWards'])->name('nationaladmin.location.ward.exportWards')->middleware('access_level:nationaladmin');

// Import Route
Route::post('/national/location/importwards', [LocationController::class, 'importWards'])->name('nationaladmin.location.ward.importWards')->middleware('access_level:nationaladmin');

//Manage Polling Units
Route::get('/national/location/pollingunits', [LocationController::class, 'allPollingUnits'])->name('nationaladmin.location.pus')->middleware('access_level:nationaladmin');
Route::get('/national/location/pollingunit/add', [LocationController::class, 'addPollingUnit'])->name('nationaladmin.location.pu.add')->middleware('access_level:nationaladmin');
Route::post('/national/location/pollingunit/store', [LocationController::class, 'storePollingUnit'])->name('nationaladmin.location.pu.store')->middleware('access_level:nationaladmin');
Route::get('/national/location/pollingunit/edit/{uuid}', [LocationController::class, 'editPollingUnit'])->name('nationaladmin.location.pu.edit')->middleware('access_level:nationaladmin');
Route::post('/national/location/pollingunit/update/{uuid}', [LocationController::class, 'updatePollingUnit'])->name('nationaladmin.location.pu.update')->middleware('access_level:nationaladmin');
Route::delete('/national/location/pollingunit/delete/{uuid}', [LocationController::class, 'deletePollingUnit'])->name('nationaladmin.location.pu.delete')->middleware('access_level:nationaladmin');
//import PU Form
Route::get('/national/import-polling-units', [LocationController::class, 'importPollingUnitsForm'])->name('nationaladmin.location.pu.import')->middleware('access_level:nationaladmin');
// Export Route
Route::get('/national/export-polling-units', [LocationController::class, 'exportPollingUnits'])->name('nationaladmin.location.pu.exportPollingUnits')->middleware('access_level:nationaladmin');

// Import Route
Route::post('/national/import-polling-units', [LocationController::class, 'importPollingUnits'])->name('nationaladmin.location.pu.importPollingUnits')->middleware('access_level:nationaladmin');

});

