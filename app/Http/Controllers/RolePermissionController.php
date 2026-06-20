<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Database\QueryException;
use App\Models\User;
use App\Services\CampaignPackagePermissionService;
use App\Services\CampaignPackageRoleService;
use App\Services\CampaignPackageUiService;
use App\Exports\PermissionExport;
use App\Imports\PermissionImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RolePermissionController extends Controller
{

    public function __construct()
    {

        $pageTitle = 'Roles & Permissions';
        View::share('pageTitle', $pageTitle);
    }
     // Function to get the profile data
     private function getProfileData()
     {
         $id = Auth::user()->id;
         return User::find($id);
     }

     private function packageRoles(): CampaignPackageRoleService
     {
         return app(CampaignPackageRoleService::class);
     }

     private function packagePermissions(): CampaignPackagePermissionService
     {
         return app(CampaignPackagePermissionService::class);
     }

     private function packageUi(): CampaignPackageUiService
     {
         return app(CampaignPackageUiService::class);
     }

     private function roleManagementViewData(User $profileData, array $extra = []): array
     {
         return array_merge([
             'profileData' => $profileData,
             'rbacPackageDisplay' => $this->packageUi()->packageDisplayName(),
             'rbacScopeDisplay' => $this->packageUi()->scopeDisplayName(),
             'rbacPackageMode' => $this->packagePermissions()->packageModeActive(),
             'rbacRoleGroups' => $this->packageRoles()->manageableRoleGroups($profileData),
         ], $extra);
     }

     private function manageableRoleOrFail(int|string $id, User $profileData): Role
     {
         $role = Role::findOrFail($id);

         if (!$this->packagePermissions()->canManageRole($profileData, $role)) {
             throw ValidationException::withMessages([
                 'role' => 'This role is not available for the current campaign package.',
             ]);
         }

         return $role;
     }

     private function permissionGroups()
     {
         $query = DB::table('permissions')->select('group_name')->groupBy('group_name');

         if ($this->packagePermissions()->packageModeActive()) {
             $groups = array_keys($this->packageRoles()->manageableRoleGroups($this->getProfileData()));
             $query->whereIn('group_name', $groups);
         }

         return $query->get();
     }

     public function allPermission(){

        $permissions = Permission::all();
        $profileData = $this->getProfileData();
        $pageTitle = 'Permissions';
        return view('backend.'.$profileData->access_level.'.settings.permission.permissions', compact('permissions','profileData','pageTitle'));
        }

     public function addPermission(){
        $profileData = $this->getProfileData();
        return view('backend.'.$profileData->access_level.'.settings.permission.add_permission', compact('profileData'));
     }

     public function storePermission(Request $request){
        $profileData = $this->getProfileData();
        
        try{
        $permission = Permission::create([
            'name' => $request->name,
            'group_name' => $request->group_name,
          ]);

          $notification = array(
            'message' => 'Permission Created Successfully',
            'alert-type' => 'success'
          );
        }catch (QueryException $e){
            if ($e->getCode() == 23000) {
                // Handle the integrity constraint violation error here
                // You can provide a user-friendly error message or take appropriate action
                $notification = [
                    'message' => 'Duplicate entry found. Please use a different name.',
                    'alert-type' => 'error'
                ];
            } else {
                // Handle other database-related errors if necessary
                $notification = [
                    'message' => 'An error occurred while adding the permission.',
                    'alert-type' => 'error'
                ];
            }
        }


          return redirect()->route($profileData->access_level.'.settings.permissions')->with($notification);
     }


     public function editPermission($id){
         $permission = Permission::findOrFail(decrypt($id));
         $profileData = $this->getProfileData();
         $pageTitle = 'Edit Permission';
         return view('backend.'.$profileData->access_level.'.settings.permission.edit_permission', compact('permission', 'profileData', 'pageTitle'));
     }

     public function updatePermission(Request $request){

        $profileData = $this->getProfileData();
        try {
            $perm_id = $request->id;
            Permission::findOrfail($perm_id)->update([
            'name' => $request->name,
            'group_name' => $request->group_name,
          ]);

          $notification = array(
            'message' => 'Permission Updated Successfully',
            'alert-type' => 'success'
          );

        }catch (QueryException $e){
            if ($e->getCode() == 23000) {
                // Handle the integrity constraint violation error here
                // You can provide a user-friendly error message or take appropriate action
                $notification = [
                    'message' => 'Duplicate entry found. Please choose a different name.',
                    'alert-type' => 'error'
                ];
            } else {
                // Handle other database-related errors if necessary
                $notification = [
                    'message' => 'An error occurred while updating the permission.',
                    'alert-type' => 'error'
                ];
            }
        }

          return redirect()->route($profileData->access_level.'.settings.permissions')->with($notification);
     }


     public function deletePermission($id){
        Permission::findOrFail(decrypt($id))->delete();
        $notification = array(
            'message' => 'Permission Deleted Successfully',
            'alert-type' => 'success'
          );

          return redirect()->back()->with($notification);


     }

        public function importPermission(){
            $profileData = $this->getProfileData();
            $pageTitle = 'Import Permission';
            return view('backend.'.$profileData->access_level.'.settings.permission.import_permission', compact('profileData','pageTitle'));

        }

        public function exportPermission(){
            return Excel::download(new PermissionExport, 'permissions.xlsx');
        }

        public function importPermissionData(Request $request){
            $profileData = $this->getProfileData();
            Excel::import(new PermissionImport, $request->file('import_file'));
            $notification = array(
                'message' => 'Permission Imported Successfully',
                'alert-type' => 'success'
            );
            return redirect()->route($profileData->access_level.'.settings.permissions')->with($notification);
        }



    // Role Routes

    public function allrole(){
        $profileData = $this->getProfileData();
        $roles = $this->packageRoles()->manageableRoles($profileData);
        $pageTitle = 'Roles';
        return view('backend.'.$profileData->access_level.'.settings.role.roles', $this->roleManagementViewData($profileData, compact('roles', 'pageTitle')));
     }

     public function addrole(){
        $profileData = $this->getProfileData();
        $pageTitle = 'Add Role';
        return view('backend.'.$profileData->access_level.'.settings.role.add_role', $this->roleManagementViewData($profileData, compact('pageTitle')));
     }

     public function storerole(Request $request){

        $profileData = $this->getProfileData();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group_name' => 'required|string',
        ]);

        if (!$this->packagePermissions()->canCreateRole($profileData, $validated['name'], $validated['group_name'])) {
            throw ValidationException::withMessages([
                'name' => 'This role is not available for the current campaign package.',
                'group_name' => 'This access level is not available for the current campaign package.',
            ]);
        }

        try{
        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'group_name' => $validated['group_name'],
          ]);

          $notification = array(
            'message' => 'Role Created Successfully',
            'alert-type' => 'success'
          );

        }catch (QueryException $e){
            if ($e->getCode() == 23000) {
                // Handle the integrity constraint violation error here
                // You can provide a user-friendly error message or take appropriate action
                $notification = [
                    'message' => 'Duplicate entry found. Please use a different name.',
                    'alert-type' => 'error'
                ];
            } else {
                // Handle other database-related errors if necessary
                $notification = [
                    'message' => 'An error occurred while adding the role.',
                    'alert-type' => 'error'
                ];
            }
        }

        return redirect()->route($profileData->access_level.'.settings.roles')->with($notification);
    }

        public function editrole($id){
            $profileData = $this->getProfileData();
            $role = $this->manageableRoleOrFail(decrypt($id), $profileData);
            $pageTitle = 'Edit Role';
            return view('backend.'.$profileData->access_level.'.settings.role.edit_role', $this->roleManagementViewData($profileData, compact('role', 'pageTitle')));
        }


        public function updaterole(Request $request){
            $profileData =  $this->getProfileData();
            $validated = $request->validate([
                'id' => 'required|integer',
                'name' => 'required|string|max:255',
                'group_name' => 'required|string',
            ]);

            $this->manageableRoleOrFail($validated['id'], $profileData);

            if (!$this->packagePermissions()->canCreateRole($profileData, $validated['name'], $validated['group_name'])) {
                throw ValidationException::withMessages([
                    'name' => 'This role is not available for the current campaign package.',
                    'group_name' => 'This access level is not available for the current campaign package.',
                ]);
            }
            
            try {
                $role_id = $validated['id'];
                Role::findOrfail($role_id)->update([
                'name' => $validated['name'],
                'group_name' => $validated['group_name'],
              ]);

              $notification = array(
                'message' => 'Role Updated Successfully',
                'alert-type' => 'success'
              );

            }catch (QueryException $e){
                if ($e->getCode() == 23000) {
                    // Handle the integrity constraint violation error here
                    // You can provide a user-friendly error message or take appropriate action
                    $notification = [
                        'message' => 'Duplicate entry found. Please choose a different name.',
                        'alert-type' => 'error'
                    ];
                } else {
                    // Handle other database-related errors if necessary
                    $notification = [
                        'message' => 'An error occurred while updating the role.',
                        'alert-type' => 'error'
                    ];
                }
            }

            return redirect()->route($profileData->access_level.'.settings.roles')->with($notification);
        }


        public function deleterole($id){
            $profileData = $this->getProfileData();
            $this->manageableRoleOrFail(decrypt($id), $profileData)->delete();
            $notification = array(
                'message' => 'Role Deleted Successfully',
                'alert-type' => 'success'
              );

            return redirect()->back()->with($notification);
        }


        // Role & Permission Functions
        public function addRolePermission(){
            $profileData = $this->getProfileData();
            $roles = $this->packageRoles()->manageableRoles($profileData);
            $permissions = Permission::all();
            $permission_groups = $this->permissionGroups();
            return view('backend.'.$profileData->access_level.'.settings.rolespermission.add_role_permission', $this->roleManagementViewData($profileData, compact('roles', 'permissions', 'permission_groups')));
        }


        public function RolePermissionStore(Request $request){
            $profileData = $this->getProfileData();
            $validated = $request->validate([
                'role_id' => 'required|integer',
                'permission' => 'array',
                'permission.*' => 'integer|exists:permissions,id',
            ]);
            $role = $this->manageableRoleOrFail($validated['role_id'], $profileData);
            
            $role->syncPermissions($validated['permission'] ?? []);

                $notification = array(
                    'message' => 'Role Permission Assigned Successfully',
                    'alert-type' => 'success'
                  );
                return redirect()->route($profileData->access_level.'.settings.rolespermission')->with($notification);

        }


        public function allRolesPermission(){
            $profileData = $this->getProfileData();
            $roles = $this->packageRoles()
                ->manageableRoles($profileData)
                ->each(fn (Role $role) => $role->loadMissing('permissions'));
            return view('backend.'.$profileData->access_level.'.settings.rolespermission.rolespermission', $this->roleManagementViewData($profileData, compact('roles')));
        }


        public function editRolePermission($id){
            $profileData = $this->getProfileData();
            $role = $this->manageableRoleOrFail(decrypt($id), $profileData);
            $permissions = Permission::all();
            $permission_groups = $this->permissionGroups();
            return view('backend.'.$profileData->access_level.'.settings.rolespermission.edit_role_permission', $this->roleManagementViewData($profileData, compact('role', 'permissions', 'permission_groups')));
        }

        public function updateRolePermission(Request $request, $id){
            $profileData = $this->getProfileData();
            $role = $this->manageableRoleOrFail($id, $profileData);
            $validated = $request->validate([
                'permission' => 'array',
                'permission.*' => 'integer|exists:permissions,id',
            ]);
            $role->syncPermissions($validated['permission'] ?? []);
            $notification = array(
                'message' => 'Role Permission Updated Successfully',
                'alert-type' => 'success'
            );

            return redirect()->route($profileData->access_level.'.settings.rolespermission')->with($notification);


        }

        public function deleteRolePermission($id){
            $profileData = $this->getProfileData();
            $role = $this->manageableRoleOrFail(decrypt($id), $profileData);
            DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
            $notification = array(
                'message' => 'Role Permission Deleted Successfully',
                'alert-type' => 'success'
              );

            return redirect()->back()->with($notification);
        }

}
