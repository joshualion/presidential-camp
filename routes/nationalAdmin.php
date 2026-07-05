<?php

use App\Http\Controllers\AgeGradeController;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\DashboardStatsController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\nationaladmin\NationalAdminController;
use App\Http\Controllers\nationaladmin\NationalSystemSettingController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\PoliticalPartyController;
use App\Http\Controllers\ReligionController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\VolunteerGroupController;
use Illuminate\Support\Facades\Route;

// Manage Locations
Route::get('/national/location/senatorial-districts', [LocationController::class, 'allSenatorialDistricts'])->name('nationaladmin.location.senatorial-districts')->middleware(['auth','access_level:nationaladmin']);
Route::get('/national/location/senatorial-district/add', [LocationController::class, 'addSenatorialDistrict'])->name('nationaladmin.location.senatorial-district.add')->middleware(['auth','access_level:nationaladmin']);
Route::post('/national/location/senatorial-district/store', [LocationController::class, 'storeSenatorialDistrict'])->name('nationaladmin.location.senatorial-district.store')->middleware(['auth','access_level:nationaladmin']);
Route::get('/national/location/senatorial-district/edit/{uuid}', [LocationController::class, 'editSenatorialDistrict'])->name('nationaladmin.location.senatorial-district.edit')->middleware(['auth','access_level:nationaladmin']);
Route::post('/national/location/senatorial-district/update/{uuid}', [LocationController::class, 'updateSenatorialDistrict'])->name('nationaladmin.location.senatorial-district.update')->middleware(['auth','access_level:nationaladmin']);
Route::delete('/national/location/senatorial-district/delete/{uuid}', [LocationController::class, 'deleteSenatorialDistrict'])->name('nationaladmin.location.senatorial-district.delete')->middleware(['auth','access_level:nationaladmin']);

Route::get('/national/location/federal-constituencies', [LocationController::class, 'allFederalConstituencies'])->name('nationaladmin.location.federal-constituencies')->middleware(['auth','access_level:nationaladmin']);
Route::get('/national/location/federal-constituency/add', [LocationController::class, 'addFederalConstituency'])->name('nationaladmin.location.federal-constituency.add')->middleware(['auth','access_level:nationaladmin']);
Route::post('/national/location/federal-constituency/store', [LocationController::class, 'storeFederalConstituency'])->name('nationaladmin.location.federal-constituency.store')->middleware(['auth','access_level:nationaladmin']);
Route::get('/national/location/federal-constituency/edit/{uuid}', [LocationController::class, 'editFederalConstituency'])->name('nationaladmin.location.federal-constituency.edit')->middleware(['auth','access_level:nationaladmin']);
Route::post('/national/location/federal-constituency/update/{uuid}', [LocationController::class, 'updateFederalConstituency'])->name('nationaladmin.location.federal-constituency.update')->middleware(['auth','access_level:nationaladmin']);
Route::delete('/national/location/federal-constituency/delete/{uuid}', [LocationController::class, 'deleteFederalConstituency'])->name('nationaladmin.location.federal-constituency.delete')->middleware(['auth','access_level:nationaladmin']);

Route::get('/national/location/localgovernments', [LocationController::class, 'allLocalGovernments'])->name('nationaladmin.location.lgas')->middleware(['auth','access_level:nationaladmin']);
Route::get('/national/location/localgovernment/add', [LocationController::class, 'addLocalGovernment'])->name('nationaladmin.location.lga.add')->middleware(['auth','access_level:nationaladmin']);
Route::post('/national/location/localgovernment/store', [LocationController::class, 'storeLocalGovernment'])->name('nationaladmin.location.lga.store')->middleware(['auth','access_level:nationaladmin']);
Route::get('/national/location/localgovernment/edit/{uuid}', [LocationController::class, 'editLocalGovernment'])->name('nationaladmin.location.lga.edit')->middleware(['auth','access_level:nationaladmin']);
Route::post('/national/location/localgovernment/update/{uuid}', [LocationController::class, 'updateLocalGovernment'])->name('nationaladmin.location.lga.update')->middleware(['auth','access_level:nationaladmin']);
Route::delete('/national/location/localgovernment/delete/{uuid}', [LocationController::class, 'deleteLocalGovernment'])->name('nationaladmin.location.lga.delete')->middleware(['auth','access_level:nationaladmin']);

//import LGA Form
Route::get('/national/location/importlgas', [LocationController::class, 'importLgasForm'])->name('nationaladmin.location.lga.import')->middleware(['auth','access_level:nationaladmin']);
// Export Route
Route::get('/national/location/exportlgas', [LocationController::class, 'exportLgas'])->name('nationaladmin.location.lga.exportLgas')->middleware(['auth','access_level:nationaladmin']);
// Import Route['auth',
Route::post('/national/location/importlgas', [LocationController::class, 'importLgas'])->name('nationaladmin.location.lga.importLgas')->middleware(['auth','access_level:nationaladmin']);


Route::get('/national/location/wards', [LocationController::class, 'allWards'])->name('nationaladmin.location.wards')->middleware(['auth','access_level:nationaladmin']);
Route::get('/national/location/ward/add', [LocationController::class, 'addWard'])->name('nationaladmin.location.ward.add')->middleware(['auth','access_level:nationaladmin']);
Route::post('/national/location/ward/store', [LocationController::class, 'storeWard'])->name('nationaladmin.location.ward.store')->middleware(['auth','access_level:nationaladmin']);
Route::get('/national/location/ward/edit/{uuid}', [LocationController::class, 'editWard'])->name('nationaladmin.location.ward.edit')->middleware(['auth','access_level:nationaladmin']);
Route::post('/national/location/ward/update/{uuid}', [LocationController::class, 'updateWard'])->name('nationaladmin.location.ward.update')->middleware(['auth','access_level:nationaladmin']);
Route::delete('/national/location/ward/delete/{uuid}', [LocationController::class, 'deleteWard'])->name('nationaladmin.location.ward.delete')->middleware(['auth','access_level:nationaladmin']);

//import PU Form
Route::get('/national/location/importwards', [LocationController::class, 'importWardsForm'])->name('nationaladmin.location.ward.import')->middleware(['auth','access_level:nationaladmin']);

// Export Route
Route::get('/national/location/exportwards', [LocationController::class, 'exportWards'])->name('nationaladmin.location.ward.exportWards')->middleware(['auth','access_level:nationaladmin']);

// Import Route
Route::post('/national/location/importwards', [LocationController::class, 'importWards'])->name('nationaladmin.location.ward.importWards')->middleware('access_level:nationaladmin');


Route::get('/national/location/pollingunits', [LocationController::class, 'allPollingUnits'])->name('nationaladmin.location.pus')->middleware(['auth','access_level:nationaladmin']);
Route::get('/national/location/pollingunit/add', [LocationController::class, 'addPollingUnit'])->name('nationaladmin.location.pu.add')->middleware(['auth','access_level:nationaladmin']);
Route::post('/national/location/pollingunit/store', [LocationController::class, 'storePollingUnit'])->name('nationaladmin.location.pu.store')->middleware(['auth','access_level:nationaladmin']);
Route::get('/national/location/pollingunit/edit/{uuid}', [LocationController::class, 'editPollingUnit'])->name('nationaladmin.location.pu.edit')->middleware(['auth','access_level:nationaladmin']);
Route::post('/national/location/pollingunit/update/{uuid}', [LocationController::class, 'updatePollingUnit'])->name('nationaladmin.location.pu.update')->middleware(['auth','access_level:nationaladmin']);
Route::delete('/national/location/pollingunit/delete/{uuid}', [LocationController::class, 'deletePollingUnit'])->name('nationaladmin.location.pu.delete')->middleware(['auth','access_level:nationaladmin']);

//import PU Form
Route::get('/national/import-polling-units', [LocationController::class, 'importPollingUnitsForm'])->name('nationaladmin.location.pu.import')->middleware(['auth','access_level:nationaladmin']);
// Export Route
Route::get('/national/export-polling-units', [LocationController::class, 'exportPollingUnits'])->name('nationaladmin.location.pu.exportPollingUnits')->middleware(['auth','access_level:nationaladmin']);

// Import Route
Route::post('/national/import-polling-units', [LocationController::class, 'importPollingUnits'])->name('nationaladmin.location.pu.importPollingUnits')->middleware(['auth','access_level:nationaladmin']);

Route::get('/national/logout', [NationalAdminController::class, 'nationaladminLogout'])->name('nationaladmin.logout')->middleware(['auth','access_level:nationaladmin']);

//PORTAL ROUTES
Route::middleware(['auth','check_onboarding'])->group(function () {

 // national Admin Profile & Dashboard Routes
 Route::get('/national/dashboard', [NationalAdminController::class, 'nationalAdminDashBoard'])->name('nationaladmin.dashboard')->middleware('access_level:nationaladmin');
 Route::get('/national/dashboard/stats', [DashboardStatsController::class, 'cards'])->name('nationaladmin.dashboard.stats')->middleware('access_level:nationaladmin');
 Route::get('/national/profile', [NationalAdminController::class, 'nationaladminProfile'])->name('nationaladmin.profile')->middleware('access_level:nationaladmin');
 Route::post('/national/profile/store', [NationalAdminController::class, 'nationaladminProfileStore'])->name('nationaladmin.profile.store')->middleware('access_level:nationaladmin');
 Route::get('/national/change/password', [NationalAdminController::class, 'nationaladminChangePassword'])->name('nationaladmin.change.password')->middleware('access_level:nationaladmin');
 Route::post('/national/update/password', [NationalAdminController::class, 'nationaladminUpdatePassword'])->name('nationaladmin.update.password')->middleware('access_level:nationaladmin');



  //nationaladmin system Setting
  Route::get('/national/system/settings/', [NationalSystemSettingController::class,'SystemSettings'])->name('nationaladmin.settings.system')->middleware('access_level:superadmin');
  Route::post('/national/settings/system/update', [NationalSystemSettingController::class,'updateSystemsSettings'])->name('nationaladmin.settings.system.update')->middleware('access_level:superadmin');

  // Admin Payment Setting - temporarily disabled pending finance model review.
  // Route::get('/national/settings/paymentgateway', [PaymentGatewayController::class,'getPaymentGateways'])->name('nationaladmin.settings.paymentgateway');
  // Route::post('/national/settings/paymentgateway/update', [PaymentGatewayController::class,'UpdatePaymentGateway'])->name('nationaladmin.settings.paymentgateway.update');
  
  //Admin SMTP Setting
  Route::get('/national/settings/smtp', [NationalSystemSettingController::class,'SmtpSettings'])->name('nationaladmin.settings.smtp')->middleware('access_level:superadmin');
  Route::post('/national/settings/smtp/update', [NationalSystemSettingController::class,'UpdateSmtpSettings'])->name('nationaladmin.settings.smtp.update')->middleware('access_level:superadmin');

  //Admin Privacy Setting
  Route::get('nationaladmin/settings/policy', [NationalSystemSettingController::class,'PrivacySetting'])->name('nationaladmin.settings.privacy')->middleware('access_level:superadmin');
  Route::post('/national/settings/privacy/update', [NationalSystemSettingController::class,'UpdatePrivacyPolicy'])->name('nationaladmin.settings.privacy.update')->middleware('access_level:superadmin');

  //Admin Terms of Condition Setting
  Route::get('nationaladmin/settings/terms', [NationalSystemSettingController::class,'TosSetting'])->name('nationaladmin.settings.terms')->middleware('access_level:superadmin');
  Route::post('/national/settings/terms/update', [NationalSystemSettingController::class,'UpdateTos'])->name('nationaladmin.settings.terms.update')->middleware('access_level:superadmin');




// Permission Routes
Route::get('/national/settings/permissions', [RolePermissionController::class, 'allpermission'])->name('nationaladmin.settings.permissions')->middleware('access_level:superadmin');
Route::get('/national/settings/permission/add', [RolePermissionController::class, 'addPermission'])->name('nationaladmin.settings.permission.add')->middleware('access_level:superadmin');
Route::post('/national/settings/permission/store', [RolePermissionController::class, 'storePermission'])->name('nationaladmin.settings.permission.store')->middleware('access_level:superadmin');
Route::get('/national/settings/permission/edit/{id}', [RolePermissionController::class, 'editPermission'])->name('nationaladmin.settings.permission.edit')->middleware('access_level:superadmin');
Route::post('/national/settings/permission/update/{id}', [RolePermissionController::class, 'updatePermission'])->name('nationaladmin.settings.permission.update')->middleware('access_level:superadmin');
Route::delete('/national/settings/permission/delete/{id}', [RolePermissionController::class, 'deletePermission'])->name('nationaladmin.settings.permission.delete')->middleware('access_level:superadmin');
Route::get('/national/settings/import/permissions', [RolePermissionController::class, 'importPermission'])->name('nationaladmin.settings.import.permission')->middleware('access_level:superadmin');
Route::get('/national/settings/export/permissions', [RolePermissionController::class, 'exportPermission'])->name('nationaladmin.settings.export.permission')->middleware('access_level:superadmin');
Route::post('/national/settings/import/permissions/data', [RolePermissionController::class, 'importPermissionData'])->name('nationaladmin.settings.import.permission.data')->middleware('access_level:superadmin');

//Role Routes
Route::get('/national/settings/roles', [RolePermissionController::class, 'allrole'])->name('nationaladmin.settings.roles')->middleware('access_level:superadmin');
Route::get('/national/settings/role/add', [RolePermissionController::class, 'addrole'])->name('nationaladmin.settings.role.add')->middleware('access_level:superadmin');
Route::post('/national/settings/role/store', [RolePermissionController::class, 'storerole'])->name('nationaladmin.settings.role.store')->middleware('access_level:superadmin');
Route::get('/national/settings/role/edit/{id}', [RolePermissionController::class, 'editrole'])->name('nationaladmin.settings.role.edit')->middleware('access_level:superadmin');
Route::post('/national/settings/role/update/{id}', [RolePermissionController::class, 'updaterole'])->name('nationaladmin.settings.role.update')->middleware('access_level:superadmin');
Route::delete('/national/settings/role/delete/{id}', [RolePermissionController::class, 'deleterole'])->name('nationaladmin.settings.role.delete')->middleware('access_level:superadmin');

// Roles & Permission Routes
Route::get('/national/settings/rolespermission', [RolePermissionController::class, 'allRolesPermission'])->name('nationaladmin.settings.rolespermission')->middleware('access_level:superadmin');
Route::get('/national/settings/add/roles/permission', [RolePermissionController::class, 'addRolePermission'])->name('nationaladmin.settings.addrole.permission')->middleware('access_level:superadmin');
Route::post('/national/settings/role/permission/store', [RolePermissionController::class, 'RolePermissionStore'])->name('nationaladmin.settings.role.permission.store')->middleware('access_level:superadmin');
Route::get('/national/settings/role/permission/edit/{id}', [RolePermissionController::class, 'editRolePermission'])->name('nationaladmin.settings.role.permission.edit')->middleware('access_level:superadmin');
Route::post('/national/settings/role/permission/update/{id}', [RolePermissionController::class, 'updateRolePermission'])->name('nationaladmin.settings.role.permission.update')->middleware('access_level:superadmin');
Route::delete('/national/settings/role/permission/delete/{id}', [RolePermissionController::class, 'deleteRolePermission'])->name('nationaladmin.settings.role.permission.delete')->middleware('access_level:superadmin');


/*Super Admin Ajax Routes */
//Gender Distribution
Route::get('/national/member/distribution/gender', [ChartsController::class, 'NationalGenderDistribution'])->name('nationaladmin.member.distribution.gender')->middleware('access_level:nationaladmin');

// Age Distribution
Route::get('/national/member/distribution/age', [ChartsController::class, 'NationalAgeDistribution'])->name('nationaladmin.member.distribution.age')->middleware('access_level:nationaladmin');

// Region Distribution
Route::get('/national/member/distribution/region', [ChartsController::class, 'NationalRegionDistribution'])->name('nationaladmin.member.distribution.region')->middleware('access_level:nationaladmin');
/*End Super Admin Ajax Routes */
// Religion Distribution
Route::get('/national/member/distribution/religion', [ChartsController::class, 'NationalReligionDistribution'])->name('nationaladmin.member.distribution.religion')->middleware('access_level:nationaladmin');
// Valid Voter Distribution
Route::get('/national/member/distribution/voter', [ChartsController::class, 'NationalVoterDistribution'])->name('nationaladmin.member.distribution.voter')->middleware('access_level:nationaladmin');
// State Distribution
Route::get('/national/member/distribution/state', [ChartsController::class, 'NationalStateDistribution'])->name('nationaladmin.member.distribution.state')->middleware('access_level:nationaladmin');
//Member Management
Route::get('/national/members/data/fetch/{uuid?}', [MembersController::class, 'getMembersData'])->name('nationaladmin.members.data');
Route::get('/national/members/fetch/{uuid?}', [MembersController::class, 'allMembers'])->name('nationaladmin.members');
//Fetch Excos
Route::get('/national/leaders/data/fetch/{uuid?}', [MembersController::class, 'getExcoMembersData'])->name('nationaladmin.leaders.data');
Route::get('/national/leaders/fetch/{uuid?}', [MembersController::class, 'allExcoMembers'])->name('nationaladmin.leaders');
//Fetch Regulars
Route::get('/national/regulars/data/fetch/{uuid?}', [MembersController::class, 'getRegularMembersData'])->name('nationaladmin.regulars.data');
Route::get('/national/regulars/fetch/{uuid?}', [MembersController::class, 'allRegularMembers'])->name('nationaladmin.regulars');
Route::get('/national/people/{metric}/data/fetch/{uuid?}', [MembersController::class, 'getPeopleMetricMembersData'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('nationaladmin.peopleMetric.data');
Route::get('/national/people/{metric}/fetch/{uuid?}', [MembersController::class, 'allPeopleMetricMembers'])->whereIn('metric', ['agents', 'eligible-voters', 'without-voter-card', 'new-today', 'new-this-week', 'new-this-month'])->name('nationaladmin.peopleMetric');

Route::get('/national/member/add', [MembersController::class, 'addMember'])->name('nationaladmin.member.add')->middleware('access_level:nationaladmin');
Route::get('/national/members/import', [MembersController::class, 'importMembers'])->name('nationaladmin.member.import')->middleware('access_level:nationaladmin');
Route::get('/national/members/import/template', [MembersController::class, 'downloadMemberImportTemplate'])->name('nationaladmin.member.import.template')->middleware('access_level:nationaladmin');
Route::post('/national/members/import', [MembersController::class, 'storeMemberImport'])->name('nationaladmin.member.import.store')->middleware('access_level:nationaladmin');
Route::post('/national/member/store', [MembersController::class, 'storeMember'])->name('nationaladmin.member.store')->middleware('access_level:nationaladmin');
Route::get('/national/member/edit/{uuid}', [MembersController::class, 'editMember'])->name('nationaladmin.member.edit')->middleware('access_level:nationaladmin');
Route::put('/national/member/update/', [MembersController::class, 'updateMember'])->name('nationaladmin.member.update')->middleware('access_level:nationaladmin');
Route::get('/national/member/view/{uuid}', [MembersController::class, 'viewMember'])->name('nationaladmin.member.view')->middleware('access_level:nationaladmin');
Route::get('/national/member/suspend/{uuid}', [MembersController::class, 'suspendMember'])->name('nationaladmin.member.suspend')->middleware('access_level:nationaladmin');
Route::delete('/national/member/delete/{uuid}', [MembersController::class, 'deleteMember'])->name('nationaladmin.member.delete')->middleware('access_level:nationaladmin');
 //Members By region
Route::get('/national/members/region', [MembersController::class, 'membersByRegions'])->name('nationaladmin.members.region')->middleware('access_level:nationaladmin,nationaladmin');
Route::get('/national/members/region/{uuid}', [MembersController::class, 'ViewMembersByRegion'])->name('nationaladmin.member.region.view')->middleware('access_level:nationaladmin,nationaladmin');
Route::get('/national/region/members/data/{uuid}', [MembersController::class, 'getRegionMembersData'])->name('nationaladmin.region.members.data');
// Members By State
Route::get('/national/members/bystate/{uuid?}', [MembersController::class, 'membersByStates'])->name('nationaladmin.members.byState')->middleware('access_level:nationaladmin,regionadmin,stateadmin');
Route::get('/national/members/state/{uuid}', [MembersController::class, 'ViewMembersByState'])->name('nationaladmin.member.state.view')->middleware('access_level:nationaladmin,regionadmin,regionadmin,stateadmin');
Route::get('/national/state/members/data/{uuid}', [MembersController::class, 'getStateMembersData'])->name('nationaladmin.state.members.data');
// Members By Senatorial District
Route::get('/national/members/bysenatorialdistrict/{uuid?}', [MembersController::class, 'membersBySenatorialDistricts'])->name('nationaladmin.members.bySenatorialDistrict')->middleware('access_level:nationaladmin');
// Members By Federal Constituency
Route::get('/national/members/byfederalconstituency/{uuid?}', [MembersController::class, 'membersByFederalConstituencies'])->name('nationaladmin.members.byFederalConstituency')->middleware('access_level:nationaladmin');
// Members By Local Government
Route::get('/national/members/bylga/{uuid?}', [MembersController::class, 'membersByLocalGovernments'])->name('nationaladmin.members.byLga')->middleware('access_level:nationaladmin');
Route::get('/national/members/lga/{uuid}', [MembersController::class, 'viewMembersByLocalGovernments'])->name('nationaladmin.member.lga.view')->middleware('access_level:nationaladmin');
Route::get('/national/lga/members/data/{uuid}', [MembersController::class, 'getLgaMembersData'])->name('nationaladmin.lga.members.data');
// Members By Ward
Route::get('/national/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('nationaladmin.members.byWard')->middleware('access_level:nationaladmin');
Route::get('/national/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('nationaladmin.members.byWard.ajax')->middleware('access_level:nationaladmin');
Route::get('/national/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('nationaladmin.member.ward.view')->middleware('access_level:nationaladmin');
Route::get('/national/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('nationaladmin.ward.members.data');
// Members By Ward
Route::get('/national/members/byward/{uuid?}', [MembersController::class, 'membersByWards'])->name('nationaladmin.members.byWard')->middleware('access_level:nationaladmin');
Route::get('/national/members/byward/ajax/{uuid?}', [AjaxController::class, 'membersByWardsAjax'])->name('nationaladmin.members.byWard.ajax')->middleware('access_level:nationaladmin');
Route::get('/national/members/ward/{uuid}', [MembersController::class, 'viewMembersByWard'])->name('nationaladmin.member.ward.view')->middleware('access_level:nationaladmin');
Route::get('/national/ward/members/data/{uuid}', [MembersController::class, 'getWardMembersData'])->name('nationaladmin.ward.members.data');

// Members By Pu
Route::get('/national/members/bypu/{uuid?}', [MembersController::class, 'membersByPus'])->name('nationaladmin.members.byPu')->middleware('access_level:nationaladmin');
Route::get('/national/members/bypu/ajax/{uuid?}', [AjaxController::class, 'membersByPusAjax'])->name('nationaladmin.members.byPu.ajax')->middleware('access_level:nationaladmin');
Route::get('/national/members/pu/{uuid}', [MembersController::class, 'viewMembersByPu'])->name('nationaladmin.member.pu.view')->middleware('access_level:nationaladmin');
Route::get('/national/pu/members/data/{uuid}', [MembersController::class, 'getPuMembersData'])->name('nationaladmin.pu.members.data');

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

// Manage Senatorial Districts
Route::get('/national/location/senatorial-district/dashboard/{uuid}', [NationalAdminController::class, 'senatorialDistrictDashBoard'])->name('nationaladmin.location.senatorial-district.dashboard')->middleware('access_level:nationaladmin');
Route::get('/national/senatorial-district/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionBySenatorialDistrict'])->name('nationaladmin.senatorial-district.member.distribution.gender')->middleware('access_level:nationaladmin');
Route::get('/national/senatorial-district/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionBySenatorialDistrict'])->name('nationaladmin.senatorial-district.member.distribution.age')->middleware('access_level:nationaladmin');
Route::get('/national/senatorial-district/member/distribution/lga/{uuid}', [ChartsController::class, 'lgaDistributionBySenatorialDistrict'])->name('nationaladmin.senatorial-district.member.distribution.lga')->middleware('access_level:nationaladmin');
Route::get('/national/senatorial-district/member/distribution/voter/{uuid}', [ChartsController::class, 'VoterDistributionBySenatorialDistrict'])->name('nationaladmin.senatorial-district.member.distribution.voter')->middleware('access_level:nationaladmin');

// Manage Federal Constituencies
Route::get('/national/location/federal-constituency/dashboard/{uuid}', [NationalAdminController::class, 'federalConstituencyDashBoard'])->name('nationaladmin.location.federal-constituency.dashboard')->middleware('access_level:nationaladmin');
Route::get('/national/federal-constituency/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByFederalConstituency'])->name('nationaladmin.federal-constituency.member.distribution.gender')->middleware('access_level:nationaladmin');
Route::get('/national/federal-constituency/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByFederalConstituency'])->name('nationaladmin.federal-constituency.member.distribution.age')->middleware('access_level:nationaladmin');
Route::get('/national/federal-constituency/member/distribution/lga/{uuid}', [ChartsController::class, 'lgaDistributionByFederalConstituency'])->name('nationaladmin.federal-constituency.member.distribution.lga')->middleware('access_level:nationaladmin');
Route::get('/national/federal-constituency/member/distribution/voter/{uuid}', [ChartsController::class, 'VoterDistributionByFederalConstituency'])->name('nationaladmin.federal-constituency.member.distribution.voter')->middleware('access_level:nationaladmin');

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
Route::get('/national/location/localgovernment/dashboard/{uuid}', [NationalAdminController::class, 'localGovernmentDashBoard'])->name('nationaladmin.location.lga.dashboard')->middleware('access_level:nationaladmin');
Route::get('/national/lga/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByLga'])->name('nationaladmin.lga.member.distribution.gender')->middleware('access_level:nationaladmin');
Route::get('/national/lga/member/distribution/age/{uuid}', [ChartsController::class, 'AgeDistributionByLga'])->name('nationaladmin.lga.member.distribution.age')->middleware('access_level:nationaladmin');
Route::get('/national/lga/member/distribution/ward/{uuid}', [ChartsController::class, 'wardDistributionByLga'])->name('nationaladmin.lga.member.distribution.ward')->middleware('access_level:nationaladmin');
Route::get('/national/lga/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByLga'])->name('nationaladmin.lga.member.distribution.voter')->middleware('access_level:nationaladmin');


// manage Wards
Route::get('/national/location/ward/dashboard/{uuid}', [NationalAdminController::class, 'wardDashBoard'])->name('nationaladmin.location.ward.dashboard')->middleware('access_level:nationaladmin');
Route::get('/national/ward/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByWard'])->name('nationaladmin.ward.member.distribution.gender')->middleware('access_level:nationaladmin');
Route::get('/national/ward/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByWard'])->name('nationaladmin.ward.member.distribution.voter')->middleware('access_level:nationaladmin');

//Manage Polling Units
Route::get('/national/location/pollingunit/dashboard/{uuid}', [NationalAdminController::class, 'PuDashBoard'])->name('nationaladmin.location.pu.dashboard')->middleware('access_level:nationaladmin');
Route::get('/national/pollingunit/member/distribution/gender/{uuid}', [ChartsController::class, 'GenderDistributionByPu'])->name('nationaladmin.pu.member.distribution.gender')->middleware('access_level:nationaladmin');
Route::get('/national/pollingunit/member/distribution/voter{uuid}', [ChartsController::class, 'VoterDistributionByPu'])->name('nationaladmin.pu.member.distribution.voter')->middleware('access_level:nationaladmin');



/* MANAGE VOLUNTEER GROUP - CRUD  */

Route::get('/national/setting/volunteer/group', [VolunteerGroupController::class, 'allVolunteerGroup'])->name('nationaladmin.volunteer.group')->middleware('access_level:superadmin');
Route::get('/national/setting/volunteer/group/add', [VolunteerGroupController::class, 'addVolunteerGroup'])->name('nationaladmin.volunteer.group.add')->middleware('access_level:superadmin');
Route::post('/national/setting/volunteer/group/store', [VolunteerGroupController::class, 'storeVolunteerGroup'])->name('nationaladmin.volunteer.group.store')->middleware('access_level:superadmin');
Route::get('/national/setting/volunteer/group/edit/{uuid}', [VolunteerGroupController::class, 'editVolunteerGroup'])->name('nationaladmin.volunteer.group.edit')->middleware('access_level:superadmin');
Route::post('/national/setting/volunteer/group/update/{uuid}', [VolunteerGroupController::class, 'updateVolunteerGroup'])->name('nationaladmin.volunteer.group.update')->middleware('access_level:superadmin');
Route::delete('/national/setting/volunteer/group/delete/{uuid}', [VolunteerGroupController::class, 'deleteVolunteerGroup'])->name('nationaladmin.volunteer.group.delete')->middleware('access_level:superadmin');


// RELIGION MANAGEMENT CRUD

Route::get('/national/setting/religion', [ReligionController::class, 'allReligions'])->name('nationaladmin.religion')->middleware('access_level:superadmin');
Route::get('/national/setting/religion/add', [ReligionController::class, 'addReligion'])->name('nationaladmin.religion.add')->middleware('access_level:superadmin');
Route::post('/national/setting/religion/store', [ReligionController::class, 'storeReligion'])->name('nationaladmin.religion.store')->middleware('access_level:superadmin');
Route::get('/national/setting/religion/edit/{uuid}', [ReligionController::class, 'editReligion'])->name('nationaladmin.religion.edit')->middleware('access_level:superadmin');
Route::post('/national/setting/religion/update/{uuid}', [ReligionController::class, 'updateReligion'])->name('nationaladmin.religion.update')->middleware('access_level:superadmin');
Route::delete('/national/setting/religion/delete/{uuid}', [ReligionController::class, 'deleteReligion'])->name('nationaladmin.religion.delete')->middleware('access_level:superadmin');

// AGE GRADE MANAGEMENT CRUD

Route::get('/national/setting/agegrade', [AgeGradeController::class, 'allAgeGrades'])->name('nationaladmin.agegrade')->middleware('access_level:superadmin');
Route::get('/national/setting/agegrade/add', [AgeGradeController::class, 'addAgeGrade'])->name('nationaladmin.agegrade.add')->middleware('access_level:superadmin');
Route::post('/national/setting/agegrade/store', [AgeGradeController::class, 'storeAgeGrade'])->name('nationaladmin.agegrade.store')->middleware('access_level:superadmin');
Route::get('/national/setting/agegrade/edit/{uuid}', [AgeGradeController::class, 'editAgeGrade'])->name('nationaladmin.agegrade.edit')->middleware('access_level:superadmin');
Route::post('/national/setting/agegrade/update/{uuid}', [AgeGradeController::class, 'updateAgeGrade'])->name('nationaladmin.agegrade.update')->middleware('access_level:superadmin');
Route::delete('/national/setting/agegrade/delete/{uuid}', [AgeGradeController::class, 'deleteAgeGrade'])->name('nationaladmin.agegrade.delete')->middleware('access_level:superadmin');

// Political Party Management CRUD

Route::get('/national/election/setting/politicalparty', [PoliticalPartyController::class, 'allPoliticalParties'])->name('nationaladmin.election.politicalparty')->middleware('access_level:superadmin');
Route::get('/national/election/setting/politicalparty/add', [PoliticalPartyController::class, 'addPoliticalParty'])->name('nationaladmin.election.politicalparty.add')->middleware('access_level:superadmin');
Route::post('/national/election/setting/politicalparty/store', [PoliticalPartyController::class, 'storePoliticalParty'])->name('nationaladmin.election.politicalparty.store')->middleware('access_level:superadmin');
Route::get('/national/election/setting/politicalparty/edit/{uuid}', [PoliticalPartyController::class, 'editPoliticalParty'])->name('nationaladmin.election.politicalparty.edit')->middleware('access_level:superadmin');
Route::post('/national/election/setting/politicalparty/update/{uuid}', [PoliticalPartyController::class, 'updatePoliticalParty'])->name('nationaladmin.election.politicalparty.update')->middleware('access_level:superadmin');
Route::delete('/national/election/setting/politicalparty/delete/{uuid}', [PoliticalPartyController::class, 'deletePoliticalParty'])->name('nationaladmin.election.politicalparty.delete')->middleware('access_level:superadmin');
// Election Management CRUD

Route::get('/national/elections', [ElectionController::class, 'allElections'])->name('nationaladmin.elections')->middleware('access_level:nationaladmin');
Route::get('/national/election/operations-center', [ElectionController::class, 'operationsCenter'])->name('nationaladmin.election.operations')->middleware('access_level:nationaladmin');
Route::get('/national/election/add', [ElectionController::class, 'addElection'])->name('nationaladmin.election.add')->middleware('access_level:nationaladmin');
Route::post('/national/election/store', [ElectionController::class, 'storeElection'])->name('nationaladmin.election.store')->middleware('access_level:nationaladmin');
Route::get('/national/election/edit/{uuid}', [ElectionController::class, 'editElection'])->name('nationaladmin.election.edit')->middleware('access_level:nationaladmin');
Route::post('/national/election/update/{uuid}', [ElectionController::class, 'updateElection'])->name('nationaladmin.election.update')->middleware('access_level:nationaladmin');
Route::delete('/national/election/delete/{uuid}', [ElectionController::class, 'deleteElection'])->name('nationaladmin.election.delete')->middleware('access_level:nationaladmin');

// Election Result Route
Route::get('/national/election/result/{uuid}', [ElectionController::class, 'electionResult'])->name('nationaladmin.election.results')->middleware('access_level:nationaladmin');

// Election PU Votes Report
Route::get('/national/election/votes/{uuid?}', [ElectionController::class, 'manageVotesByPu'])->name('nationaladmin.election.votesByPu')->middleware('access_level:nationaladmin');
Route::get('/national/election/votes/data/{uuid}', [ElectionController::class, 'VotesByPuData'])->name('nationaladmin.election.votesByPuData')->middleware('access_level:nationaladmin');


// Election PU Incident Report
Route::get('/national/election/incident/{uuid}/{polling_unit_id}', [ElectionController::class, 'manageIncidentByPu'])->name('nationaladmin.election.incident')->middleware('access_level:nationaladmin');
   

//Manage Votes
Route::get('/national/votes', [ElectionController::class, 'allVotes'])->name('nationaladmin.votes')->middleware('access_level:nationaladmin');
Route::get('/national/vote/add', [ElectionController::class, 'addVote'])->name('nationaladmin.vote.add')->middleware('access_level:nationaladmin');
Route::post('/national/vote/store', [ElectionController::class, 'storeVote'])->name('nationaladmin.vote.store')->middleware('access_level:nationaladmin');
Route::get('/national/vote/edit/{uuid}', [ElectionController::class, 'editVote'])->name('nationaladmin.vote.edit')->middleware('access_level:nationaladmin');
Route::post('/national/vote/update/{uuid}', [ElectionController::class, 'updateVote'])->name('nationaladmin.vote.update')->middleware('access_level:nationaladmin');
Route::delete('/national/vote/delete/{uuid}', [ElectionController::class, 'deleteVote'])->name('nationaladmin.vote.delete')->middleware('access_level:nationaladmin');

//Manage Incidents
Route::get('/national/incident/add', [ElectionController::class, 'addIncident'])->name('nationaladmin.incident.add')->middleware('access_level:nationaladmin');
Route::post('/national/incident/store', [ElectionController::class, 'storeIcident'])->name('nationaladmin.incident.store')->middleware('access_level:nationaladmin');
 

 

});
