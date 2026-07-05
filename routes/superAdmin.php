<?php

use App\Http\Controllers\AgeGradeController;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\DashboardStatsController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\LiveChatController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\PoliticalPartyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReligionController;
use App\Http\Controllers\superadmin\SuperAdminController;
use App\Http\Controllers\superadmin\SystemSettingController;
use App\Http\Controllers\superadmin\EmailNotificationPermissionController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\VolunteerGroupController;
use App\Livewire\SuperAdminDashboard;
use Illuminate\Support\Facades\Route;


// Manage Locations
Route::get('/superadmin/location/senatorial-districts', [LocationController::class, 'allSenatorialDistricts'])->name('superadmin.location.senatorial-districts')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/senatorial-district/add', [LocationController::class, 'addSenatorialDistrict'])->name('superadmin.location.senatorial-district.add')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/senatorial-district/store', [LocationController::class, 'storeSenatorialDistrict'])->name('superadmin.location.senatorial-district.store')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/senatorial-district/edit/{uuid}', [LocationController::class, 'editSenatorialDistrict'])->name('superadmin.location.senatorial-district.edit')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/senatorial-district/update/{uuid}', [LocationController::class, 'updateSenatorialDistrict'])->name('superadmin.location.senatorial-district.update')->middleware(['auth','access_level:superadmin']);
Route::delete('/superadmin/location/senatorial-district/delete/{uuid}', [LocationController::class, 'deleteSenatorialDistrict'])->name('superadmin.location.senatorial-district.delete')->middleware(['auth','access_level:superadmin']);

Route::get('/superadmin/location/federal-constituencies', [LocationController::class, 'allFederalConstituencies'])->name('superadmin.location.federal-constituencies')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/federal-constituency/add', [LocationController::class, 'addFederalConstituency'])->name('superadmin.location.federal-constituency.add')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/federal-constituency/store', [LocationController::class, 'storeFederalConstituency'])->name('superadmin.location.federal-constituency.store')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/federal-constituency/edit/{uuid}', [LocationController::class, 'editFederalConstituency'])->name('superadmin.location.federal-constituency.edit')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/federal-constituency/update/{uuid}', [LocationController::class, 'updateFederalConstituency'])->name('superadmin.location.federal-constituency.update')->middleware(['auth','access_level:superadmin']);
Route::delete('/superadmin/location/federal-constituency/delete/{uuid}', [LocationController::class, 'deleteFederalConstituency'])->name('superadmin.location.federal-constituency.delete')->middleware(['auth','access_level:superadmin']);

Route::get('/superadmin/location/localgovernments', [LocationController::class, 'allLocalGovernments'])->name('superadmin.location.lgas')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/localgovernment/add', [LocationController::class, 'addLocalGovernment'])->name('superadmin.location.lga.add')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/localgovernment/store', [LocationController::class, 'storeLocalGovernment'])->name('superadmin.location.lga.store')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/localgovernment/edit/{uuid}', [LocationController::class, 'editLocalGovernment'])->name('superadmin.location.lga.edit')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/localgovernment/update/{uuid}', [LocationController::class, 'updateLocalGovernment'])->name('superadmin.location.lga.update')->middleware(['auth','access_level:superadmin']);
Route::delete('/superadmin/location/localgovernment/delete/{uuid}', [LocationController::class, 'deleteLocalGovernment'])->name('superadmin.location.lga.delete')->middleware(['auth','access_level:superadmin']);

// Import LGA Form
Route::get('/superadmin/location/importlgas', [LocationController::class, 'importLgasForm'])->name('superadmin.location.lga.import')->middleware(['auth','access_level:superadmin']);
// Export Route
Route::get('/superadmin/location/exportlgas', [LocationController::class, 'exportLgas'])->name('superadmin.location.lga.exportLgas')->middleware(['auth','access_level:superadmin']);
// Import Route
Route::post('/superadmin/location/importlgas', [LocationController::class, 'importLgas'])->name('superadmin.location.lga.importLgas')->middleware(['auth','access_level:superadmin']);

Route::get('/superadmin/location/wards', [LocationController::class, 'allWards'])->name('superadmin.location.wards')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/ward/add', [LocationController::class, 'addWard'])->name('superadmin.location.ward.add')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/ward/store', [LocationController::class, 'storeWard'])->name('superadmin.location.ward.store')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/ward/edit/{uuid}', [LocationController::class, 'editWard'])->name('superadmin.location.ward.edit')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/ward/update/{uuid}', [LocationController::class, 'updateWard'])->name('superadmin.location.ward.update')->middleware(['auth','access_level:superadmin']);
Route::delete('/superadmin/location/ward/delete/{uuid}', [LocationController::class, 'deleteWard'])->name('superadmin.location.ward.delete')->middleware(['auth','access_level:superadmin']);

// Import Ward Form
Route::get('/superadmin/location/importwards', [LocationController::class, 'importWardsForm'])->name('superadmin.location.ward.import')->middleware(['auth','access_level:superadmin']);

// Export Route
Route::get('/superadmin/location/exportwards', [LocationController::class, 'exportWards'])->name('superadmin.location.ward.exportWards')->middleware(['auth','access_level:superadmin']);

// Import Route
Route::post('/superadmin/location/importwards', [LocationController::class, 'importWards'])->name('superadmin.location.ward.importWards')->middleware(['auth','access_level:superadmin']);

Route::get('/superadmin/location/pollingunits', [LocationController::class, 'allPollingUnits'])->name('superadmin.location.pus')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/pollingunit/add', [LocationController::class, 'addPollingUnit'])->name('superadmin.location.pu.add')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/pollingunit/store', [LocationController::class, 'storePollingUnit'])->name('superadmin.location.pu.store')->middleware(['auth','access_level:superadmin']);
Route::get('/superadmin/location/pollingunit/edit/{uuid}', [LocationController::class, 'editPollingUnit'])->name('superadmin.location.pu.edit')->middleware(['auth','access_level:superadmin']);
Route::post('/superadmin/location/pollingunit/update/{uuid}', [LocationController::class, 'updatePollingUnit'])->name('superadmin.location.pu.update')->middleware(['auth','access_level:superadmin']);
Route::delete('/superadmin/location/pollingunit/delete/{uuid}', [LocationController::class, 'deletePollingUnit'])->name('superadmin.location.pu.delete')->middleware(['auth','access_level:superadmin']);

// Import PU Form
Route::get('/superadmin/import-polling-units', [LocationController::class, 'importPollingUnitsForm'])->name('superadmin.location.pu.import')->middleware(['auth','access_level:superadmin']);
// Export Route
Route::get('/superadmin/export-polling-units', [LocationController::class, 'exportPollingUnits'])->name('superadmin.location.pu.exportPollingUnits')->middleware(['auth','access_level:superadmin']);

// Import Route
Route::post('/superadmin/import-polling-units', [LocationController::class, 'importPollingUnits'])->name('superadmin.location.pu.importPollingUnits')->middleware(['auth','access_level:superadmin']);


Route::get('/superadmin/logout', [SuperAdminController::class, 'superAdminLogout'])->name('superadmin.logout')->middleware(['auth','access_level:superadmin']);

//PORTAL ROUTES
Route::middleware(['auth','check_onboarding'])->group(function () {


 // Super Admin Profile & Dashboard Routes
 Route::get('/superadmin/dashboard', [SuperAdminController::class, 'superAdminDashBoard'])->name('superadmin.dashboard')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
 Route::get('/superadmin/dashboard/stats', [DashboardStatsController::class, 'cards'])->name('superadmin.dashboard.stats')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
 Route::get('/superadmin/profile', [SuperAdminController::class, 'superadminProfile'])->name('superadmin.profile')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
 Route::post('/superadmin/profile/store', [SuperAdminController::class, 'superadminProfileStore'])->name('superadmin.profile.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
 Route::get('/superadmin/change/password', [SuperAdminController::class, 'superadminChangePassword'])->name('superadmin.change.password')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
 Route::post('/superadmin/update/password', [SuperAdminController::class, 'superadminUpdatePassword'])->name('superadmin.update.password')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);



  //superadmin system Setting
  Route::get('/superadmin/system/settings/', [SystemSettingController::class,'SystemSettings'])->name('superadmin.settings.system')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
  Route::post('/superadmin/settings/system/update', [SystemSettingController::class,'updateSystemsSettings'])->name('superadmin.settings.system.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
  Route::post('/superadmin/settings/sidebar-theme', [SystemSettingController::class,'updateSidebarTheme'])->name('superadmin.settings.sidebar-theme')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

  //Admin Payment Setting
  Route::get('/superadmin/settings/paymentgateway', [PaymentGatewayController::class,'getPaymentGateways'])
      ->name('superadmin.settings.paymentgateway')
      ->middleware(['auth','access_level:superadmin']);
  Route::post('/superadmin/settings/paymentgateway/update', [PaymentGatewayController::class,'UpdatePaymentGateway'])
      ->name('superadmin.settings.paymentgateway.update')
      ->middleware(['auth','access_level:superadmin']);

  //Admin SMTP Setting
  Route::get('/superadmin/settings/smtp', [SystemSettingController::class,'SmtpSettings'])->name('superadmin.settings.smtp')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
  Route::post('/superadmin/settings/smtp/update', [SystemSettingController::class,'UpdateSmtpSettings'])->name('superadmin.settings.smtp.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

  Route::get('/superadmin/settings/email-notifications', [EmailNotificationPermissionController::class, 'edit'])
      ->name('superadmin.settings.email-notifications.edit')
      ->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
  Route::put('/superadmin/settings/email-notifications', [EmailNotificationPermissionController::class, 'update'])
      ->name('superadmin.settings.email-notifications.update')
      ->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

  //Admin Privacy Setting
  Route::get('/superadmin/settings/policy', [SystemSettingController::class,'PrivacySetting'])->name('superadmin.settings.privacy')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
  Route::post('/superadmin/settings/privacy/update', [SystemSettingController::class,'UpdatePrivacyPolicy'])->name('superadmin.settings.privacy.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

  //Admin Terms of Condition Setting
  Route::get('/superadmin/settings/terms', [SystemSettingController::class,'TosSetting'])->name('superadmin.settings.terms')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
  Route::post('/superadmin/settings/terms/update', [SystemSettingController::class,'UpdateTos'])->name('superadmin.settings.terms.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);




// Permission Routes
Route::get('/superadmin/settings/permissions', [RolePermissionController::class, 'allpermission'])->name('superadmin.settings.permissions')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/settings/permission/add', [RolePermissionController::class, 'addPermission'])->name('superadmin.settings.permission.add')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/settings/permission/store', [RolePermissionController::class, 'storePermission'])->name('superadmin.settings.permission.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/settings/permission/edit/{id}', [RolePermissionController::class, 'editPermission'])->name('superadmin.settings.permission.edit')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/settings/permission/update/{id}', [RolePermissionController::class, 'updatePermission'])->name('superadmin.settings.permission.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::delete('/superadmin/settings/permission/delete/{id}', [RolePermissionController::class, 'deletePermission'])->name('superadmin.settings.permission.delete')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/settings/import/permissions', [RolePermissionController::class, 'importPermission'])->name('superadmin.settings.import.permission')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/settings/export/permissions', [RolePermissionController::class, 'exportPermission'])->name('superadmin.settings.export.permission')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/settings/import/permissions/data', [RolePermissionController::class, 'importPermissionData'])->name('superadmin.settings.import.permission.data')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

//Role Routes
Route::get('/superadmin/settings/roles', [RolePermissionController::class, 'allrole'])->name('superadmin.settings.roles')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/settings/role/add', [RolePermissionController::class, 'addrole'])->name('superadmin.settings.role.add')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/settings/role/store', [RolePermissionController::class, 'storerole'])->name('superadmin.settings.role.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/settings/role/edit/{id}', [RolePermissionController::class, 'editrole'])->name('superadmin.settings.role.edit')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/settings/role/update/{id}', [RolePermissionController::class, 'updaterole'])->name('superadmin.settings.role.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::delete('/superadmin/settings/role/delete/{id}', [RolePermissionController::class, 'deleterole'])->name('superadmin.settings.role.delete')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

// Roles & Permission Routes
Route::get('/superadmin/settings/rolespermission', [RolePermissionController::class, 'allRolesPermission'])->name('superadmin.settings.rolespermission')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/settings/add/roles/permission', [RolePermissionController::class, 'addRolePermission'])->name('superadmin.settings.addrole.permission')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/settings/role/permission/store', [RolePermissionController::class, 'RolePermissionStore'])->name('superadmin.settings.role.permission.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/settings/role/permission/edit/{id}', [RolePermissionController::class, 'editRolePermission'])->name('superadmin.settings.role.permission.edit')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/settings/role/permission/update/{id}', [RolePermissionController::class, 'updateRolePermission'])->name('superadmin.settings.role.permission.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::delete('/superadmin/settings/role/permission/delete/{id}', [RolePermissionController::class, 'deleteRolePermission'])->name('superadmin.settings.role.permission.delete')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);


/*Super Admin Ajax Routes */
//Gender Distribution
Route::get('/superadmin/member/distribution/gender', [SuperAdminController::class, 'SuperadminGenderDistribution'])->name('superadmin.member.distribution.gender')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

// Age Distribution
Route::get('/superadmin/member/distribution/age', [SuperAdminController::class, 'SuperadminAgeDistribution'])->name('superadmin.member.distribution.age')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

// Region Distribution
Route::get('/superadmin/member/distribution/region', [SuperAdminController::class, 'SuperadminRegionDistribution'])->name('superadmin.member.distribution.region')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
/*End Super Admin Ajax Routes */
// Religion Distribution
Route::get('/superadmin/member/distribution/religion', [SuperAdminController::class, 'SuperadminReligionDistribution'])->name('superadmin.member.distribution.religion')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
// Valid Voter Distribution
Route::get('/superadmin/member/distribution/voter', [SuperAdminController::class, 'SuperadminVoterDistribution'])->name('superadmin.member.distribution.voter')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
// State Distribution
Route::get('/superadmin/member/distribution/state', [SuperAdminController::class, 'SuperadminStateDistribution'])->name('superadmin.member.distribution.state')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
//Member Management
Route::get('/superadmin/members/data/fetch/{uuid?}', [MembersController::class, 'getMembersData'])->name('superadmin.members.data')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/members/fetch/{uuid?}', [MembersController::class, 'allMembers'])->name('superadmin.members')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
//Fetch Excos
Route::get('/superadmin/leaders/data/fetch/{uuid?}', [MembersController::class, 'getExcoMembersData'])->name('superadmin.leaders.data')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/leaders/fetch/{uuid?}', [MembersController::class, 'allExcoMembers'])->name('superadmin.leaders')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
//Fetch Regulars
Route::get('/superadmin/regulars/data/fetch/{uuid?}', [MembersController::class, 'getRegularMembersData'])->name('superadmin.regulars.data')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/regulars/fetch/{uuid?}', [MembersController::class, 'allRegularMembers'])->name('superadmin.regulars')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/people/{metric}/data/fetch/{uuid?}', [MembersController::class, 'getPeopleMetricMembersData'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('superadmin.peopleMetric.data')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/people/{metric}/fetch/{uuid?}', [MembersController::class, 'allPeopleMetricMembers'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('superadmin.peopleMetric')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

Route::get('/superadmin/member/add', [MembersController::class, 'addMember'])->name('superadmin.member.add')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/members/import', [MembersController::class, 'importMembers'])->name('superadmin.member.import')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/members/import/template', [MembersController::class, 'downloadMemberImportTemplate'])->name('superadmin.member.import.template')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/members/import', [MembersController::class, 'storeMemberImport'])->name('superadmin.member.import.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/member/store', [MembersController::class, 'storeMember'])->name('superadmin.member.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/member/edit/{uuid}', [MembersController::class, 'editMember'])->name('superadmin.member.edit')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::put('/superadmin/member/update/', [MembersController::class, 'updateMember'])->name('superadmin.member.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/member/view/{uuid}', [MembersController::class, 'viewMember'])->name('superadmin.member.view')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/member/suspend/{uuid}', [MembersController::class, 'suspendMember'])->name('superadmin.member.suspend')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::delete('/superadmin/member/delete/{uuid}', [MembersController::class, 'deleteMember'])->name('superadmin.member.delete')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
 //Members By region
Route::get('/superadmin/members/region', [MembersController::class, 'membersByRegions'])->name('superadmin.members.region')->middleware('access_level:superadmin,superadmin');
Route::get('/superadmin/members/region/{uuid}', [MembersController::class, 'ViewMembersByRegion'])->name('superadmin.member.region.view')->middleware('access_level:superadmin,superadmin');
Route::get('/superadmin/region/members/data/{uuid}', [MembersController::class, 'getRegionMembersData'])->name('superadmin.region.members.data');
// Members By State
Route::get('/superadmin/members/bystate/{uuid?}', [MembersController::class, 'membersByStates'])->name('superadmin.members.byState')->middleware('access_level:superadmin,regionadmin,stateadmin');
Route::get('/superadmin/members/state/{uuid}', [MembersController::class, 'ViewMembersByState'])->name('superadmin.member.state.view')->middleware('access_level:superadmin,regionadmin,regionadmin,stateadmin');
Route::get('/superadmin/state/members/data/{uuid}', [MembersController::class, 'getStateMembersData'])->name('superadmin.state.members.data');
// Members By Senatorial District
Route::get('/superadmin/members/bysenatorialdistrict/{uuid?}', [MembersController::class, 'membersBySenatorialDistricts'])->name('superadmin.members.bySenatorialDistrict')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
// Members By Federal Constituency
Route::get('/superadmin/members/byfederalconstituency/{uuid?}', [MembersController::class, 'membersByFederalConstituencies'])->name('superadmin.members.byFederalConstituency')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
// Members By Local Government
Route::get('/superadmin/members/bylga/{uuid?}', [MembersController::class, 'membersByLocalGovernments'])->name('superadmin.members.byLga')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/members/lga/{uuid}', [MembersController::class, 'viewMembersByLocalGovernments'])->name('superadmin.member.lga.view')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/lga/members/data/{uuid}', [MembersController::class, 'getLgaMembersData'])->name('superadmin.lga.members.data');
// Members By Ward
Route::get('/superadmin/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('superadmin.members.byWard')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('superadmin.members.byWard.ajax')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('superadmin.member.ward.view')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('superadmin.ward.members.data');
// Members By Ward
Route::get('/superadmin/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('superadmin.members.byWard')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('superadmin.members.byWard.ajax')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('superadmin.member.ward.view')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('superadmin.ward.members.data');

// Members By Pu
Route::get('/superadmin/members/bypu/{uuid?}', [MembersController::class, 'membersByPus'])->name('superadmin.members.byPu')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/members/bypu/ajax/{uuid?}', [AjaxController::class, 'membersByPusAjax'])->name('superadmin.members.byPu.ajax')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/members/pu/{uuid}', [MembersController::class, 'viewMembersByPu'])->name('superadmin.member.pu.view')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/pu/members/data/{uuid}', [MembersController::class, 'getPuMembersData'])->name('superadmin.pu.members.data');
// Eligible Members By Pu
Route::get('/superadmin/eligible/members/bypu/{uuid?}', [MembersController::class, 'puMembersByVoteEligibility'])->name('superadmin.eligible.members.byPu')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/eligible/members/pu/{uuid}', [MembersController::class, 'viewPuMembersByVoteEligibility'])->name('superadmin.eligible.member.pu.view')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
// Eligible Members By Pu (DataTables endpoint)
Route::get('/superadmin/eligible/members/bypu/ajax/{uuid?}', [AjaxController::class, 'eligibleMembersByPusAjax'])->name('superadmin.eligible.members.byPu.ajax')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

// License Activation
Route::get('/superadmin/activate', [SystemSettingController::class, 'showActivationForm'])->name('activation.form')->middleware(['auth','access_level:superadmin','EnsureSystemNotActivated']);
Route::post('/superadmin/activate', [SystemSettingController::class, 'validateActivationCode'])->name('validate.licence')->middleware(['auth','access_level:superadmin', 'throttle:5,1']);

//Locations Management Routes
//Manage Regions
Route::get('/superadmin/location/region/dashboard/{uuid}', [SuperAdminController::class, 'RegionDashBoard'])->name('superadmin.location.region.dashboard')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/region/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByRegion'])->name('superadmin.region.member.distribution.gender')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/region/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByRegion'])->name('superadmin.region.member.distribution.age')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/region/member/distribution/state/{uuid}', [ChartsController::class, 'StateDistributionByRegion'])->name('superadmin.region.member.distribution.state')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/region/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByRegion'])->name('superadmin.region.member.distribution.voter')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/location/regions', [LocationController::class, 'allRegions'])->name('superadmin.location.regions')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/location/region/add', [LocationController::class, 'addRegion'])->name('superadmin.location.region.add')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/location/region/store', [LocationController::class, 'storeRegion'])->name('superadmin.location.region.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/location/region/edit/{uuid}', [LocationController::class, 'editRegion'])->name('superadmin.location.region.edit')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/location/region/update/{uuid}', [LocationController::class, 'updateRegion'])->name('superadmin.location.region.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::delete('/superadmin/location/region/delete/{uuid}', [LocationController::class, 'deleteRegion'])->name('superadmin.location.region.delete')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

// Manage Senatorial Districts
Route::get('/superadmin/location/senatorial-district/dashboard/{uuid}', [SuperAdminController::class, 'senatorialDistrictDashBoard'])->name('superadmin.location.senatorial-district.dashboard')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/senatorial-district/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionBySenatorialDistrict'])->name('superadmin.senatorial-district.member.distribution.gender')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/senatorial-district/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionBySenatorialDistrict'])->name('superadmin.senatorial-district.member.distribution.age')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/senatorial-district/member/distribution/lga/{uuid}', [ChartsController::class, 'lgaDistributionBySenatorialDistrict'])->name('superadmin.senatorial-district.member.distribution.lga')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/senatorial-district/member/distribution/voter/{uuid}', [ChartsController::class, 'VoterDistributionBySenatorialDistrict'])->name('superadmin.senatorial-district.member.distribution.voter')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

// Manage Federal Constituencies
Route::get('/superadmin/location/federal-constituency/dashboard/{uuid}', [SuperAdminController::class, 'federalConstituencyDashBoard'])->name('superadmin.location.federal-constituency.dashboard')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/federal-constituency/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByFederalConstituency'])->name('superadmin.federal-constituency.member.distribution.gender')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/federal-constituency/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByFederalConstituency'])->name('superadmin.federal-constituency.member.distribution.age')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/federal-constituency/member/distribution/lga/{uuid}', [ChartsController::class, 'lgaDistributionByFederalConstituency'])->name('superadmin.federal-constituency.member.distribution.lga')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/federal-constituency/member/distribution/voter/{uuid}', [ChartsController::class, 'VoterDistributionByFederalConstituency'])->name('superadmin.federal-constituency.member.distribution.voter')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

// Manage States
Route::get('/superadmin/location/state/dashboard/{uuid}', [SuperAdminController::class, 'StateDashBoard'])->name('superadmin.location.state.dashboard')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/state/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByState'])->name('superadmin.state.member.distribution.gender')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/state/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByState'])->name('superadmin.state.member.distribution.age')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/state/member/distribution/lga/{uuid}', [ChartsController::class, 'LgaDistributionByState'])->name('superadmin.state.member.distribution.lga')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/state/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByState'])->name('superadmin.state.member.distribution.voter')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

Route::get('/superadmin/location/states', [LocationController::class, 'allStates'])->name('superadmin.location.states')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/location/state/add', [LocationController::class, 'addState'])->name('superadmin.location.state.add')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/location/state/store', [LocationController::class, 'storeState'])->name('superadmin.location.state.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/location/state/edit/{uuid}', [LocationController::class, 'editState'])->name('superadmin.location.state.edit')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/location/state/update/{uuid}', [LocationController::class, 'updateState'])->name('superadmin.location.state.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::delete('/superadmin/location/state/delete/{uuid}', [LocationController::class, 'deleteState'])->name('superadmin.location.state.delete')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

// manage Local Governments
Route::get('/superadmin/location/localgovernment/dashboard/{uuid}', [SuperAdminController::class, 'localGovernmentDashBoard'])->name('superadmin.location.lga.dashboard')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/lga/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByLga'])->name('superadmin.lga.member.distribution.gender')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/lga/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByLga'])->name('superadmin.lga.member.distribution.age')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/lga/member/distribution/ward/{uuid}', [ChartsController::class, 'wardDistributionByLga'])->name('superadmin.lga.member.distribution.ward')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/lga/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByLga'])->name('superadmin.lga.member.distribution.voter')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);


// manage Wards
Route::get('/superadmin/location/ward/dashboard/{uuid}', [SuperAdminController::class, 'wardDashBoard'])->name('superadmin.location.ward.dashboard')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/ward/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByWard'])->name('superadmin.ward.member.distribution.gender')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/ward/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByWard'])->name('superadmin.ward.member.distribution.voter')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

//Manage Polling Units
Route::get('/superadmin/location/pollingunit/dashboard/{uuid}', [SuperAdminController::class, 'PuDashBoard'])->name('superadmin.location.pu.dashboard')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/pollingunit/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByPu'])->name('superadmin.pu.member.distribution.gender')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/pollingunit/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByPu'])->name('superadmin.pu.member.distribution.voter')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);



/* MANAGE VOLUNTEER GROUP - CRUD  */

Route::get('/superadmin/setting/volunteer/group', [VolunteerGroupController::class, 'allVolunteerGroup'])->name('superadmin.volunteer.group')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/setting/volunteer/group/add', [VolunteerGroupController::class, 'addVolunteerGroup'])->name('superadmin.volunteer.group.add')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/setting/volunteer/group/store', [VolunteerGroupController::class, 'storeVolunteerGroup'])->name('superadmin.volunteer.group.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/setting/volunteer/group/edit/{uuid}', [VolunteerGroupController::class, 'editVolunteerGroup'])->name('superadmin.volunteer.group.edit')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/setting/volunteer/group/update/{uuid}', [VolunteerGroupController::class, 'updateVolunteerGroup'])->name('superadmin.volunteer.group.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::delete('/superadmin/setting/volunteer/group/delete/{uuid}', [VolunteerGroupController::class, 'deleteVolunteerGroup'])->name('superadmin.volunteer.group.delete')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);


// RELIGION MANAGEMENT CRUD

Route::get('/superadmin/setting/religion', [ReligionController::class, 'allReligions'])->name('superadmin.religion')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/setting/religion/add', [ReligionController::class, 'addReligion'])->name('superadmin.religion.add')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/setting/religion/store', [ReligionController::class, 'storeReligion'])->name('superadmin.religion.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/setting/religion/edit/{uuid}', [ReligionController::class, 'editReligion'])->name('superadmin.religion.edit')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/setting/religion/update/{uuid}', [ReligionController::class, 'updateReligion'])->name('superadmin.religion.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::delete('/superadmin/setting/religion/delete/{uuid}', [ReligionController::class, 'deleteReligion'])->name('superadmin.religion.delete')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

// AGE GRADE MANAGEMENT CRUD

Route::get('/superadmin/setting/agegrade', [AgeGradeController::class, 'allAgeGrades'])->name('superadmin.agegrade')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/setting/agegrade/add', [AgeGradeController::class, 'addAgeGrade'])->name('superadmin.agegrade.add')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/setting/agegrade/store', [AgeGradeController::class, 'storeAgeGrade'])->name('superadmin.agegrade.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/setting/agegrade/edit/{uuid}', [AgeGradeController::class, 'editAgeGrade'])->name('superadmin.agegrade.edit')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/setting/agegrade/update/{uuid}', [AgeGradeController::class, 'updateAgeGrade'])->name('superadmin.agegrade.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::delete('/superadmin/setting/agegrade/delete/{uuid}', [AgeGradeController::class, 'deleteAgeGrade'])->name('superadmin.agegrade.delete')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

// Political Party Management CRUD

Route::get('/superadmin/election/setting/politicalparty', [PoliticalPartyController::class, 'allPoliticalParties'])->name('superadmin.election.politicalparty')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/election/setting/politicalparty/add', [PoliticalPartyController::class, 'addPoliticalParty'])->name('superadmin.election.politicalparty.add')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/election/setting/politicalparty/store', [PoliticalPartyController::class, 'storePoliticalParty'])->name('superadmin.election.politicalparty.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/election/setting/politicalparty/edit/{uuid}', [PoliticalPartyController::class, 'editPoliticalParty'])->name('superadmin.election.politicalparty.edit')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/election/setting/politicalparty/update/{uuid}', [PoliticalPartyController::class, 'updatePoliticalParty'])->name('superadmin.election.politicalparty.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::delete('/superadmin/election/setting/politicalparty/delete/{uuid}', [PoliticalPartyController::class, 'deletePoliticalParty'])->name('superadmin.election.politicalparty.delete')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
// Election Management CRUD

Route::get('/superadmin/elections', [ElectionController::class, 'allElections'])->name('superadmin.elections')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/election/operations-center', [ElectionController::class, 'operationsCenter'])->name('superadmin.election.operations')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/election/add', [ElectionController::class, 'addElection'])->name('superadmin.election.add')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/election/store', [ElectionController::class, 'storeElection'])->name('superadmin.election.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/election/edit/{uuid}', [ElectionController::class, 'editElection'])->name('superadmin.election.edit')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/election/update/{uuid}', [ElectionController::class, 'updateElection'])->name('superadmin.election.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::delete('/superadmin/election/delete/{uuid}', [ElectionController::class, 'deleteElection'])->name('superadmin.election.delete')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

// Election Result Route
Route::get('/superadmin/election/result/{uuid}', [ElectionController::class, 'electionResult'])->name('superadmin.election.results')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

// Election PU Votes Report
Route::get('/superadmin/election/votes/{uuid?}', [ElectionController::class, 'manageVotesByPu'])->name('superadmin.election.votesByPu')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/election/votes/data/{uuid}', [ElectionController::class, 'VotesByPuData'])->name('superadmin.election.votesByPuData')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);


// Election PU Incident Report
Route::get('/superadmin/election/incident/{uuid}/{polling_unit_id}', [ElectionController::class, 'manageIncidentByPu'])->name('superadmin.election.incident')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);


//Manage Votes
Route::get('/superadmin/votes', [ElectionController::class, 'allVotes'])->name('superadmin.votes')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/vote/add', [ElectionController::class, 'addVote'])->name('superadmin.vote.add')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/vote/store', [ElectionController::class, 'storeVote'])->name('superadmin.vote.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::get('/superadmin/vote/edit/{uuid}', [ElectionController::class, 'editVote'])->name('superadmin.vote.edit')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/vote/update/{uuid}', [ElectionController::class, 'updateVote'])->name('superadmin.vote.update')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::delete('/superadmin/vote/delete/{uuid}', [ElectionController::class, 'deleteVote'])->name('superadmin.vote.delete')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

//Manage Incidents
Route::get('/superadmin/incident/add', [ElectionController::class, 'addIncident'])->name('superadmin.incident.add')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
Route::post('/superadmin/incident/store', [ElectionController::class, 'storeIcident'])->name('superadmin.incident.store')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);



  // Live Chat Application
  Route::get('/superadmin/chat/messenger', [LiveChatController::class,'index'])->name('superadmin.livechat.send')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
  Route::get('/superadmin/chat/messages', [LiveChatController::class, 'fetchMessages'])->name('superadmin.livechat.fetch.messages')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);
  Route::post('/superadmin/chat/send', [LiveChatController::class, 'sendMessage'])->name('superadmin.livechat.send.messages')->middleware(['access_level:superadmin', 'kuyak_eyen_ino']);

  /* Finance Management Routes*/

  // Products(income Sources) Management
  //  Product management Routes
  Route::get('/superadmin/products', [ProductController::class, 'allProducts'])->name('superadmin.products')->middleware('access_level:superadmin');
  Route::get('/superadmin/product/add', [ProductController::class, 'addProduct'])->name('superadmin.product.add')->middleware('access_level:superadmin');
  Route::post('/superadmin/product/store', [ProductController::class, 'storeProduct'])->name('superadmin.product.store')->middleware('access_level:superadmin');
  Route::get('/superadmin/product/edit/{id}', [ProductController::class, 'editProduct'])->name('superadmin.product.edit')->middleware('access_level:superadmin');
  Route::post('/superadmin/product/update/{id}', [ProductController::class, 'updateProduct'])->name('superadmin.product.update')->middleware('access_level:superadmin');
  Route::delete('/superadmin/product/delete/{id}', [ProductController::class, 'deleteProduct'])->name('superadmin.product.delete')->middleware('access_level:superadmin');

  // Category management Routes
  Route::get('/superadmin/product/categories', [ProductController::class, 'allCategories'])->name('superadmin.product.categories')->middleware('access_level:superadmin');
  Route::get('/superadmin/product/category/add', [ProductController::class, 'addCategory'])->name('superadmin.product.category.add')->middleware('access_level:superadmin');
  Route::post('/superadmin/product/category/store', [ProductController::class, 'storeCategory'])->name('superadmin.product.category.store')->middleware('access_level:superadmin');
  Route::get('/superadmin/product/category/edit/{id}', [ProductController::class, 'editCategory'])->name('superadmin.product.category.edit')->middleware('access_level:superadmin');
  Route::post('/superadmin/product/category/update/{id}', [ProductController::class, 'updateCategory'])->name('superadmin.product.category.update')->middleware('access_level:superadmin');
  Route::delete('/superadmin/product/category/delete/{id}', [ProductController::class, 'deleteCategory'])->name('superadmin.product.category.delete')->middleware('access_level:superadmin');


});
