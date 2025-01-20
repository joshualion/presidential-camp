<?php

use App\Http\Controllers\AgeGradeController;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\BulksmsController;
use App\Http\Controllers\BulkSmsSettingController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\LiveChatController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\PoliticalPartyController;
use App\Http\Controllers\ReligionController;
use App\Http\Controllers\superadmin\SuperAdminController;
use App\Http\Controllers\superadmin\SystemSettingController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\VolunteerGroupController;
use Illuminate\Support\Facades\Route;



// Manage Locations
Route::get('/superadmin/location/localgovernments', [LocationController::class, 'allLocalGovernments'])->name('superadmin.location.lgas')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/localgovernment/add', [LocationController::class, 'addLocalGovernment'])->name('superadmin.location.lga.add')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/localgovernment/store', [LocationController::class, 'storeLocalGovernment'])->name('superadmin.location.lga.store')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/localgovernment/edit/{uuid}', [LocationController::class, 'editLocalGovernment'])->name('superadmin.location.lga.edit')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/localgovernment/update/{uuid}', [LocationController::class, 'updateLocalGovernment'])->name('superadmin.location.lga.update')->middleware(['auth','access_level:superadmin']);
Route::delete('/superadmin/location/localgovernment/delete/{uuid}', [LocationController::class, 'deleteLocalGovernment'])->name('superadmin.location.lga.delete')->middleware(['auth','access_level:superadmin']);

//import LGA Form
Route::get('/superadmin/location/importlgas', [LocationController::class, 'importLgasForm'])->name('superadmin.location.lga.import')->middleware(['auth','access_level:superadmin']);
// Export Route
Route::get('/superadmin/location/exportlgas', [LocationController::class, 'exportLgas'])->name('superadmin.location.lga.exportLgas')->middleware(['auth','access_level:superadmin']);
// Import Route['auth',
Route::post('/superadmin/location/importlgas', [LocationController::class, 'importLgas'])->name('superadmin.location.lga.importLgas')->middleware(['auth','access_level:superadmin']);


Route::get('/superadmin/location/wards', [LocationController::class, 'allWards'])->name('superadmin.location.wards')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/ward/add', [LocationController::class, 'addWard'])->name('superadmin.location.ward.add')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/ward/store', [LocationController::class, 'storeWard'])->name('superadmin.location.ward.store')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/ward/edit/{uuid}', [LocationController::class, 'editWard'])->name('superadmin.location.ward.edit')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/ward/update/{uuid}', [LocationController::class, 'updateWard'])->name('superadmin.location.ward.update')->middleware(['auth','access_level:superadmin']);
Route::delete('/superadmin/location/ward/delete/{uuid}', [LocationController::class, 'deleteWard'])->name('superadmin.location.ward.delete')->middleware(['auth','access_level:superadmin']);

//import PU Form
Route::get('/superadmin/location/importwards', [LocationController::class, 'importWardsForm'])->name('superadmin.location.ward.import')->middleware(['auth','access_level:superadmin']);

// Export Route
Route::get('/superadmin/location/exportwards', [LocationController::class, 'exportWards'])->name('superadmin.location.ward.exportWards')->middleware(['auth','access_level:superadmin']);

// Import Route
Route::post('/superadmin/location/importwards', [LocationController::class, 'importWards'])->name('superadmin.location.ward.importWards')->middleware('access_level:superadmin');


Route::get('/superadmin/location/pollingunits', [LocationController::class, 'allPollingUnits'])->name('superadmin.location.pus')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/pollingunit/add', [LocationController::class, 'addPollingUnit'])->name('superadmin.location.pu.add')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/pollingunit/store', [LocationController::class, 'storePollingUnit'])->name('superadmin.location.pu.store')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/pollingunit/edit/{uuid}', [LocationController::class, 'editPollingUnit'])->name('superadmin.location.pu.edit')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/pollingunit/update/{uuid}', [LocationController::class, 'updatePollingUnit'])->name('superadmin.location.pu.update')->middleware(['auth','access_level:superadmin']);
Route::delete('/superadmin/location/pollingunit/delete/{uuid}', [LocationController::class, 'deletePollingUnit'])->name('superadmin.location.pu.delete')->middleware(['auth','access_level:superadmin']);

//import PU Form
Route::get('/superadmin/import-polling-units', [LocationController::class, 'importPollingUnitsForm'])->name('superadmin.location.pu.import')->middleware(['auth','access_level:superadmin']);
// Export Route
Route::get('/superadmin/export-polling-units', [LocationController::class, 'exportPollingUnits'])->name('superadmin.location.pu.exportPollingUnits')->middleware(['auth','access_level:superadmin']);

// Import Route
Route::post('/superadmin/import-polling-units', [LocationController::class, 'importPollingUnits'])->name('superadmin.location.pu.importPollingUnits')->middleware(['auth','access_level:superadmin']);

Route::get('/superadmin/logout', [SuperAdminController::class, 'superAdminLogout'])->name('superadmin.logout')->middleware(['auth','access_level:superadmin']);

//PORTAL ROUTES
Route::middleware(['auth','check_onboarding'])->group(function () {

  
 // national Admin Profile & Dashboard Routes
 Route::get('/superadmin/dashboard', [SuperAdminController::class, 'superAdminDashBoard'])->name('superadmin.dashboard')->middleware('access_level:superadmin');
 Route::get('/superadmin/profile', [SuperAdminController::class, 'superadminProfile'])->name('superadmin.profile')->middleware('access_level:superadmin');
 Route::post('/superadmin/profile/store', [SuperAdminController::class, 'superadminProfileStore'])->name('superadmin.profile.store')->middleware('access_level:superadmin');
 Route::get('/superadmin/change/password', [SuperAdminController::class, 'superadminChangePassword'])->name('superadmin.change.password')->middleware('access_level:superadmin');
 Route::post('/superadmin/update/password', [SuperAdminController::class, 'superadminUpdatePassword'])->name('superadmin.update.password')->middleware('access_level:superadmin');



  //nationaladmin system Setting
  Route::get('/superadmin/system/settings/', [SystemSettingController::class,'SystemSettings'])->name('superadmin.settings.system')->middleware('access_level:superadmin');
  Route::post('/superadmin/settings/system/update', [SystemSettingController::class,'updateSystemsSettings'])->name('superadmin.settings.system.update')->middleware('access_level:superadmin');

  //Admin Payment Setting
  Route::get('/superadmin/settings/paymentgateway', [PaymentGatewayController::class,'getPaymentGateways'])->name('superadmin.settings.paymentgateway');
  Route::post('/superadmin/settings/paymentgateway/update', [PaymentGatewayController::class,'UpdatePaymentGateway'])->name('superadmin.settings.paymentgateway.update');
  
  //Admin SMTP Setting
  Route::get('/superadmin/settings/smtp', [SystemSettingController::class,'SmtpSettings'])->name('superadmin.settings.smtp')->middleware('access_level:superadmin');
  Route::post('/superadmin/settings/smtp/update', [SystemSettingController::class,'UpdateSmtpSettings'])->name('superadmin.settings.smtp.update')->middleware('access_level:superadmin');

  //Admin Privacy Setting
  Route::get('/superadmin/settings/policy', [SystemSettingController::class,'PrivacySetting'])->name('superadmin.settings.privacy')->middleware('access_level:superadmin');
  Route::post('/superadmin/settings/privacy/update', [SystemSettingController::class,'UpdatePrivacyPolicy'])->name('superadmin.settings.privacy.update')->middleware('access_level:superadmin');

  //Admin Terms of Condition Setting
  Route::get('/superadmin/settings/terms', [SystemSettingController::class,'TosSetting'])->name('superadmin.settings.terms')->middleware('access_level:superadmin');
  Route::post('/superadmin/settings/terms/update', [SystemSettingController::class,'UpdateTos'])->name('superadmin.settings.terms.update')->middleware('access_level:superadmin');




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
Route::get('/superadmin/member/distribution/gender', [SuperAdminController::class, 'SuperadminGenderDistribution'])->name('superadmin.member.distribution.gender')->middleware('access_level:superadmin');

// Age Distribution
Route::get('/superadmin/member/distribution/age', [SuperAdminController::class, 'SuperadminAgeDistribution'])->name('superadmin.member.distribution.age')->middleware('access_level:superadmin');

// Region Distribution
Route::get('/superadmin/member/distribution/region', [SuperAdminController::class, 'SuperadminRegionDistribution'])->name('superadmin.member.distribution.region')->middleware('access_level:superadmin');
/*End Super Admin Ajax Routes */
// Religion Distribution
Route::get('/superadmin/member/distribution/religion', [SuperAdminController::class, 'SuperadminReligionDistribution'])->name('superadmin.member.distribution.religion')->middleware('access_level:superadmin');
// Valid Voter Distribution
Route::get('/superadmin/member/distribution/voter', [SuperAdminController::class, 'SuperadminVoterDistribution'])->name('superadmin.member.distribution.voter')->middleware('access_level:superadmin');
// State Distribution
Route::get('/superadmin/member/distribution/state', [SuperAdminController::class, 'SuperadminStateDistribution'])->name('superadmin.member.distribution.state')->middleware('access_level:superadmin');
//Member Management
Route::get('/superadmin/members/data/fetch/{uuid?}', [MembersController::class, 'getMembersData'])->name('superadmin.members.data');
Route::get('/superadmin/members/fetch/{uuid?}', [MembersController::class, 'allMembers'])->name('superadmin.members');
//Fetch Excos
Route::get('/superadmin/leaders/data/fetch/{uuid?}', [MembersController::class, 'getExcoMembersData'])->name('superadmin.leaders.data');
Route::get('/superadmin/leaders/fetch/{uuid?}', [MembersController::class, 'allExcoMembers'])->name('superadmin.leaders');
//Fetch Regulars
Route::get('/superadmin/regulars/data/fetch/{uuid?}', [MembersController::class, 'getRegularMembersData'])->name('superadmin.regulars.data');
Route::get('/superadmin/regulars/fetch/{uuid?}', [MembersController::class, 'allRegularMembers'])->name('superadmin.regulars');

Route::get('/superadmin/member/add', [MembersController::class, 'addMember'])->name('superadmin.member.add')->middleware('access_level:superadmin');
Route::post('/superadmin/member/store', [MembersController::class, 'storeMember'])->name('superadmin.member.store')->middleware('access_level:superadmin');
Route::get('/superadmin/member/edit/{uuid}', [MembersController::class, 'editMember'])->name('superadmin.member.edit')->middleware('access_level:superadmin');
Route::put('/superadmin/member/update/', [MembersController::class, 'updateMember'])->name('superadmin.member.update')->middleware('access_level:superadmin');
Route::get('/superadmin/member/view/{uuid}', [MembersController::class, 'viewMember'])->name('superadmin.member.view')->middleware('access_level:superadmin');
Route::get('/superadmin/member/suspend/{uuid}', [MembersController::class, 'suspendMember'])->name('superadmin.member.suspend')->middleware('access_level:superadmin');
Route::delete('/superadmin/member/delete/{uuid}', [MembersController::class, 'deleteMember'])->name('superadmin.member.delete')->middleware('access_level:superadmin');
 //Members By region
Route::get('/superadmin/members/region', [MembersController::class, 'membersByRegions'])->name('superadmin.members.region')->middleware('access_level:superadmin,nationaladmin');
Route::get('/superadmin/members/region/{uuid}', [MembersController::class, 'ViewMembersByRegion'])->name('superadmin.member.region.view')->middleware('access_level:superadmin,nationaladmin');
Route::get('/superadmin/region/members/data/{uuid}', [MembersController::class, 'getRegionMembersData'])->name('superadmin.region.members.data');
// Members By State
Route::get('/superadmin/members/bystate/{uuid?}', [MembersController::class, 'membersByStates'])->name('superadmin.members.byState')->middleware('access_level:superadmin,regionadmin,stateadmin');
Route::get('/superadmin/members/state/{uuid}', [MembersController::class, 'ViewMembersByState'])->name('superadmin.member.state.view')->middleware('access_level:superadmin,regionadmin,regionadmin,stateadmin');
Route::get('/superadmin/state/members/data/{uuid}', [MembersController::class, 'getStateMembersData'])->name('superadmin.state.members.data');
// Members By Local Government
Route::get('/superadmin/members/bylga/{uuid?}', [MembersController::class, 'membersByLocalGovernments'])->name('superadmin.members.byLga')->middleware('access_level:superadmin');
Route::get('/superadmin/members/lga/{uuid}', [MembersController::class, 'viewMembersByLocalGovernments'])->name('superadmin.member.lga.view')->middleware('access_level:superadmin');
Route::get('/superadmin/lga/members/data/{uuid}', [MembersController::class, 'getLgaMembersData'])->name('superadmin.lga.members.data');
// Members By Ward
Route::get('/superadmin/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('superadmin.members.byWard')->middleware('access_level:superadmin');
Route::get('/superadmin/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('superadmin.members.byWard.ajax')->middleware('access_level:superadmin');
Route::get('/superadmin/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('superadmin.member.ward.view')->middleware('access_level:superadmin');
Route::get('/superadmin/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('superadmin.ward.members.data');
// Members By Ward
Route::get('/superadmin/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('superadmin.members.byWard')->middleware('access_level:superadmin');
Route::get('/superadmin/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('superadmin.members.byWard.ajax')->middleware('access_level:superadmin');
Route::get('/superadmin/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('superadmin.member.ward.view')->middleware('access_level:superadmin');
Route::get('/superadmin/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('superadmin.ward.members.data');

// Members By Pu
Route::get('/superadmin/members/bypu/{uuid?}', [MembersController::class, 'membersByPus'])->name('superadmin.members.byPu')->middleware('access_level:superadmin');
Route::get('/superadmin/members/bypu/ajax/{uuid?}', [AjaxController::class, 'membersByPusAjax'])->name('superadmin.members.byPu.ajax')->middleware('access_level:superadmin');
Route::get('/superadmin/members/pu/{uuid}', [MembersController::class, 'viewMembersByPu'])->name('superadmin.member.pu.view')->middleware('access_level:superadmin');
Route::get('/superadmin/pu/members/data/{uuid}', [MembersController::class, 'getPuMembersData'])->name('superadmin.pu.members.data');

//Locations Management Routes
//Manage Regions
Route::get('/superadmin/location/region/dashboard/{uuid}', [SuperAdminController::class, 'RegionDashBoard'])->name('superadmin.location.region.dashboard')->middleware('access_level:superadmin');
Route::get('/superadmin/region/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByRegion'])->name('superadmin.region.member.distribution.gender')->middleware('access_level:superadmin');
Route::get('/superadmin/region/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByRegion'])->name('superadmin.region.member.distribution.age')->middleware('access_level:superadmin');
Route::get('/superadmin/region/member/distribution/state/{uuid}', [ChartsController::class, 'StateDistributionByRegion'])->name('superadmin.region.member.distribution.state')->middleware('access_level:superadmin');
Route::get('/superadmin/region/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByRegion'])->name('superadmin.region.member.distribution.voter')->middleware('access_level:superadmin');
Route::get('/superadmin/location/regions', [LocationController::class, 'allRegions'])->name('superadmin.location.regions')->middleware('access_level:superadmin');
Route::get('/superadmin/location/region/add', [LocationController::class, 'addRegion'])->name('superadmin.location.region.add')->middleware('access_level:superadmin');
Route::post('/superadmin/location/region/store', [LocationController::class, 'storeRegion'])->name('superadmin.location.region.store')->middleware('access_level:superadmin');
Route::get('/superadmin/location/region/edit/{uuid}', [LocationController::class, 'editRegion'])->name('superadmin.location.region.edit')->middleware('access_level:superadmin');
Route::post('/superadmin/location/region/update/{uuid}', [LocationController::class, 'updateRegion'])->name('superadmin.location.region.update')->middleware('access_level:superadmin');
Route::delete('/superadmin/location/region/delete/{uuid}', [LocationController::class, 'deleteRegion'])->name('superadmin.location.region.delete')->middleware('access_level:superadmin');

// Manage States
Route::get('/superadmin/location/state/dashboard/{uuid}', [SuperAdminController::class, 'StateDashBoard'])->name('superadmin.location.state.dashboard')->middleware('access_level:superadmin');
Route::get('/superadmin/state/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByState'])->name('superadmin.state.member.distribution.gender')->middleware('access_level:superadmin');
Route::get('/superadmin/state/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByState'])->name('superadmin.state.member.distribution.age')->middleware('access_level:superadmin');
Route::get('/superadmin/state/member/distribution/lga/{uuid}', [ChartsController::class, 'LgaDistributionByState'])->name('superadmin.state.member.distribution.lga')->middleware('access_level:superadmin');
Route::get('/superadmin/state/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByState'])->name('superadmin.state.member.distribution.voter')->middleware('access_level:superadmin');

Route::get('/superadmin/location/states', [LocationController::class, 'allStates'])->name('superadmin.location.states')->middleware('access_level:superadmin');
Route::get('/superadmin/location/state/add', [LocationController::class, 'addState'])->name('superadmin.location.state.add')->middleware('access_level:superadmin');
Route::post('/superadmin/location/state/store', [LocationController::class, 'storeState'])->name('superadmin.location.state.store')->middleware('access_level:superadmin');
Route::get('/superadmin/location/state/edit/{uuid}', [LocationController::class, 'editState'])->name('superadmin.location.state.edit')->middleware('access_level:superadmin');
Route::post('/superadmin/location/state/update/{uuid}', [LocationController::class, 'updateState'])->name('superadmin.location.state.update')->middleware('access_level:superadmin');
Route::delete('/superadmin/location/state/delete/{uuid}', [LocationController::class, 'deleteState'])->name('superadmin.location.state.delete')->middleware('access_level:superadmin');

// manage Local Governments
Route::get('/superadmin/location/localgovernment/dashboard/{uuid}', [SuperAdminController::class, 'localGovernmentDashBoard'])->name('superadmin.location.lga.dashboard')->middleware('access_level:superadmin');
Route::get('/superadmin/lga/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByLga'])->name('superadmin.lga.member.distribution.gender')->middleware('access_level:superadmin');
Route::get('/superadmin/lga/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByLga'])->name('superadmin.lga.member.distribution.age')->middleware('access_level:superadmin');
Route::get('/superadmin/lga/member/distribution/ward/{uuid}', [ChartsController::class, 'wardDistributionByLga'])->name('superadmin.lga.member.distribution.ward')->middleware('access_level:superadmin');
Route::get('/superadmin/lga/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByLga'])->name('superadmin.lga.member.distribution.voter')->middleware('access_level:superadmin');


// manage Wards
Route::get('/superadmin/location/ward/dashboard/{uuid}', [SuperAdminController::class, 'wardDashBoard'])->name('superadmin.location.ward.dashboard')->middleware('access_level:superadmin');
Route::get('/superadmin/ward/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByWard'])->name('superadmin.ward.member.distribution.gender')->middleware('access_level:superadmin');
Route::get('/superadmin/ward/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByWard'])->name('superadmin.ward.member.distribution.voter')->middleware('access_level:superadmin');

//Manage Polling Units
Route::get('/superadmin/location/pollingunit/dashboard/{uuid}', [SuperAdminController::class, 'PuDashBoard'])->name('superadmin.location.pu.dashboard')->middleware('access_level:superadmin');
Route::get('/superadmin/pollingunit/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByPu'])->name('superadmin.pu.member.distribution.gender')->middleware('access_level:superadmin');
Route::get('/superadmin/pollingunit/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByPu'])->name('superadmin.pu.member.distribution.voter')->middleware('access_level:superadmin');



/* MANAGE VOLUNTEER GROUP - CRUD  */

Route::get('/superadmin/setting/volunteer/group', [VolunteerGroupController::class, 'allVolunteerGroup'])->name('superadmin.volunteer.group')->middleware('access_level:superadmin');
Route::get('/superadmin/setting/volunteer/group/add', [VolunteerGroupController::class, 'addVolunteerGroup'])->name('superadmin.volunteer.group.add')->middleware('access_level:superadmin');
Route::post('/superadmin/setting/volunteer/group/store', [VolunteerGroupController::class, 'storeVolunteerGroup'])->name('superadmin.volunteer.group.store')->middleware('access_level:superadmin');
Route::get('/superadmin/setting/volunteer/group/edit/{uuid}', [VolunteerGroupController::class, 'editVolunteerGroup'])->name('superadmin.volunteer.group.edit')->middleware('access_level:superadmin');
Route::post('/superadmin/setting/volunteer/group/update/{uuid}', [VolunteerGroupController::class, 'updateVolunteerGroup'])->name('superadmin.volunteer.group.update')->middleware('access_level:superadmin');
Route::delete('/superadmin/setting/volunteer/group/delete/{uuid}', [VolunteerGroupController::class, 'deleteVolunteerGroup'])->name('superadmin.volunteer.group.delete')->middleware('access_level:superadmin');


// RELIGION MANAGEMENT CRUD

Route::get('/superadmin/setting/religion', [ReligionController::class, 'allReligions'])->name('superadmin.religion')->middleware('access_level:superadmin');
Route::get('/superadmin/setting/religion/add', [ReligionController::class, 'addReligion'])->name('superadmin.religion.add')->middleware('access_level:superadmin');
Route::post('/superadmin/setting/religion/store', [ReligionController::class, 'storeReligion'])->name('superadmin.religion.store')->middleware('access_level:superadmin');
Route::get('/superadmin/setting/religion/edit/{uuid}', [ReligionController::class, 'editReligion'])->name('superadmin.religion.edit')->middleware('access_level:superadmin');
Route::post('/superadmin/setting/religion/update/{uuid}', [ReligionController::class, 'updateReligion'])->name('superadmin.religion.update')->middleware('access_level:superadmin');
Route::delete('/superadmin/setting/religion/delete/{uuid}', [ReligionController::class, 'deleteReligion'])->name('superadmin.religion.delete')->middleware('access_level:superadmin');

// AGE GRADE MANAGEMENT CRUD

Route::get('/superadmin/setting/agegrade', [AgeGradeController::class, 'allAgeGrades'])->name('superadmin.agegrade')->middleware('access_level:superadmin');
Route::get('/superadmin/setting/agegrade/add', [AgeGradeController::class, 'addAgeGrade'])->name('superadmin.agegrade.add')->middleware('access_level:superadmin');
Route::post('/superadmin/setting/agegrade/store', [AgeGradeController::class, 'storeAgeGrade'])->name('superadmin.agegrade.store')->middleware('access_level:superadmin');
Route::get('/superadmin/setting/agegrade/edit/{uuid}', [AgeGradeController::class, 'editAgeGrade'])->name('superadmin.agegrade.edit')->middleware('access_level:superadmin');
Route::post('/superadmin/setting/agegrade/update/{uuid}', [AgeGradeController::class, 'updateAgeGrade'])->name('superadmin.agegrade.update')->middleware('access_level:superadmin');
Route::delete('/superadmin/setting/agegrade/delete/{uuid}', [AgeGradeController::class, 'deleteAgeGrade'])->name('superadmin.agegrade.delete')->middleware('access_level:superadmin');

// Political Party Management CRUD

Route::get('/superadmin/election/setting/politicalparty', [PoliticalPartyController::class, 'allPoliticalParties'])->name('superadmin.election.politicalparty')->middleware('access_level:superadmin');
Route::get('/superadmin/election/setting/politicalparty/add', [PoliticalPartyController::class, 'addPoliticalParty'])->name('superadmin.election.politicalparty.add')->middleware('access_level:superadmin');
Route::post('/superadmin/election/setting/politicalparty/store', [PoliticalPartyController::class, 'storePoliticalParty'])->name('superadmin.election.politicalparty.store')->middleware('access_level:superadmin');
Route::get('/superadmin/election/setting/politicalparty/edit/{uuid}', [PoliticalPartyController::class, 'editPoliticalParty'])->name('superadmin.election.politicalparty.edit')->middleware('access_level:superadmin');
Route::post('/superadmin/election/setting/politicalparty/update/{uuid}', [PoliticalPartyController::class, 'updatePoliticalParty'])->name('superadmin.election.politicalparty.update')->middleware('access_level:superadmin');
Route::delete('/superadmin/election/setting/politicalparty/delete/{uuid}', [PoliticalPartyController::class, 'deletePoliticalParty'])->name('superadmin.election.politicalparty.delete')->middleware('access_level:superadmin');
// Election Management CRUD

Route::get('/superadmin/elections', [ElectionController::class, 'allElections'])->name('superadmin.elections')->middleware('access_level:superadmin');
Route::get('/superadmin/election/add', [ElectionController::class, 'addElection'])->name('superadmin.election.add')->middleware('access_level:superadmin');
Route::post('/superadmin/election/store', [ElectionController::class, 'storeElection'])->name('superadmin.election.store')->middleware('access_level:superadmin');
Route::get('/superadmin/election/edit/{uuid}', [ElectionController::class, 'editElection'])->name('superadmin.election.edit')->middleware('access_level:superadmin');
Route::post('/superadmin/election/update/{uuid}', [ElectionController::class, 'updateElection'])->name('superadmin.election.update')->middleware('access_level:superadmin');
Route::delete('/superadmin/election/delete/{uuid}', [ElectionController::class, 'deleteElection'])->name('superadmin.election.delete')->middleware('access_level:superadmin');

// Election Result Route
Route::get('/superadmin/election/result/{uuid}', [ElectionController::class, 'electionResult'])->name('superadmin.election.results')->middleware('access_level:superadmin');

// Election PU Votes Report
Route::get('/superadmin/election/votes/{uuid}', [ElectionController::class, 'manageVotesByPu'])->name('superadmin.election.votesByPu')->middleware('access_level:superadmin');
Route::get('/superadmin/election/votes/data/{uuid}', [ElectionController::class, 'VotesByPuData'])->name('superadmin.election.votesByPuData')->middleware('access_level:superadmin');


// Election PU Incident Report
Route::get('/superadmin/election/incident/{uuid}/{polling_unit_id}', [ElectionController::class, 'manageIncidentByPu'])->name('superadmin.election.incident')->middleware('access_level:superadmin');
   

//Manage Votes
Route::get('/superadmin/votes', [ElectionController::class, 'allVotes'])->name('superadmin.votes')->middleware('access_level:superadmin');
Route::get('/superadmin/vote/add', [ElectionController::class, 'addVote'])->name('superadmin.vote.add')->middleware('access_level:superadmin');
Route::post('/superadmin/vote/store', [ElectionController::class, 'storeVote'])->name('superadmin.vote.store')->middleware('access_level:superadmin');
Route::get('/superadmin/vote/edit/{uuid}', [ElectionController::class, 'editVote'])->name('superadmin.vote.edit')->middleware('access_level:superadmin');
Route::post('/superadmin/vote/update/{uuid}', [ElectionController::class, 'updateVote'])->name('superadmin.vote.update')->middleware('access_level:superadmin');
Route::delete('/superadmin/vote/delete/{uuid}', [ElectionController::class, 'deleteVote'])->name('superadmin.vote.delete')->middleware('access_level:superadmin');

//Manage Incidents
Route::get('/superadmin/incident/add', [ElectionController::class, 'addIncident'])->name('superadmin.incident.add')->middleware('access_level:superadmin');
Route::post('/superadmin/incident/store', [ElectionController::class, 'storeIcident'])->name('superadmin.incident.store')->middleware('access_level:superadmin');
 
  // Bulksms Settings Routes
  Route::get('/superadmin/bulksms/setting', [BulkSmsSettingController::class, 'index'])->name('superadmin.bulksms.setting')->middleware('access_level:superadmin');
  Route::post('/superadmin/bulksms/setting/store', [BulkSmsSettingController::class, 'store'])->name('superadmin.bulksms.setting.store')->middleware('access_level:superadmin');
  Route::post('/superadmin/bulksms/setting/update/', [BulkSmsSettingController::class, 'update'])->name('superadmin.bulksms.setting.update')->middleware('access_level:superadmin');
  // Send Bulksms Routes
  Route::get('/superadmin/bulksms/send', [BulksmsController::class, 'index'])->name('superadmin.bulksms.send')->middleware('access_level:superadmin');
  Route::post('/superadmin/bulksms/send/sms', [BulksmsController::class, 'sendsms'])->name('superadmin.bulksms.send.sms')->middleware(middleware: 'access_level:superadmin');


  // Live Chat Application
  Route::get('/superadmin/chat/messenger', [LiveChatController::class,'index'])->name('superadmin.livechat.send')->middleware('access_level:superadmin');
  Route::get('/superadmin/chat/messages', [LiveChatController::class, 'fetchMessages'])->name('superadmin.livechat.fetch.messages')->middleware('access_level:superadmin');
  Route::post('/superadmin/chat/send', [LiveChatController::class, 'sendMessage'])->name('superadmin.livechat.send.messages')->middleware('access_level:superadmin');


});

