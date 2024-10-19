<?php

namespace App\Http\Controllers\nationaladmin;


use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Database\QueryException;
use App\Models\User;
use App\Exports\PermissionExport;
use App\Imports\PermissionImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class NationalRolePermissionController extends Controller
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

     public function allPermission(){
        $permissions = Permission::all();
        $profileData = $this->getProfileData();
        $pageTitle = 'Permissions';
        return view('backend.superadmin.settings.permission.permissions', compact('permissions','profileData','pageTitle'));
        }

     public function addPermission(){
        $profileData = $this->getProfileData();
        return view('backend.superadmin.settings.permission.add_permission', compact('profileData'));
     }

     public function storePermission(Request $request){
        try{
        $permission = Permission::create([
            'name' => $request->name,
            'group_name' => $request->group_name,
          ]);

          $notification = array(
            'message' => $permission.' '.'Permission Created Successfully',
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


          return redirect()->route('superadmin.settings.permissions')->with($notification);
     }


     public function editPermission($id){
         $permission = Permission::findOrFail(decrypt($id));
         $profileData = $this->getProfileData();
         $pageTitle = 'Edit Permission';
         return view('backend.superadmin.settings.permission.edit_permission', compact('permission', 'profileData', 'pageTitle'));
     }

     public function updatePermission(Request $request){

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

          return redirect()->route('superadmin.settings.permissions')->with($notification);
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
            return view('backend.superadmin.settings.permission.import_permission', compact('profileData','pageTitle'));

        }

        public function exportPermission(){
            return Excel::download(new PermissionExport, 'permissions.xlsx');
        }

        public function importPermissionData(Request $request){
            Excel::import(new PermissionImport, $request->file('import_file'));
            $notification = array(
                'message' => 'Permission Imported Successfully',
                'alert-type' => 'success'
            );
            return redirect()->route('superadmin.settings.permissions')->with($notification);
        }



    // Role Routes

    public function allrole(){
        $roles = Role::all();
        $profileData = $this->getProfileData();
        $pageTitle = 'Roles';
        return view('backend.superadmin.settings.role.roles', compact('roles','profileData', 'pageTitle'));
     }

     public function addrole(){
        $profileData = $this->getProfileData();
        $pageTitle = 'Add Role';
        return view('backend.superadmin.settings.role.add_role', compact('profileData', 'pageTitle'));
     }

     public function storerole(Request $request){

        try{
        $role = Role::create([
            'name' => $request->name,
            'group_name' => $request->group_name,
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

        return redirect()->route('superadmin.settings.roles')->with($notification);
    }

        public function editrole($id){
            $role = Role::findOrFail(decrypt($id));
            $profileData = $this->getProfileData();
            $pageTitle = 'Edit Role';
            return view('backend.superadmin.settings.role.edit_role', compact('role', 'profileData', 'pageTitle'));
        }


        public function updaterole(Request $request){
            try {
                $role_id = $request->id;
                Role::findOrfail($role_id)->update([
                'name' => $request->name,
                'group_name' => $request->group_name,
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

            return redirect()->route('superadmin.settings.roles')->with($notification);
        }


        public function deleterole($id){
            Role::findOrFail(decrypt($id))->delete();
            $notification = array(
                'message' => 'Role Deleted Successfully',
                'alert-type' => 'success'
              );
        }


        // Role & Permission Functions
        public function addRolePermission(){
            $roles = Role::all();
            $profileData = $this->getProfileData();
            $permissions = Permission::all();
            $permission_groups = User::getPermissionGroups();
            return view('backend.superadmin.settings.rolespermission.add_role_permission', compact('roles', 'permissions','profileData','permission_groups'));
        }


        public function RolePermissionStore(Request $request){
            $permissions = $request->input('permission');

            if (!empty($permissions)) {
                $data = [];

            foreach ($permissions as  $permissionId){
                        $data['role_id'] = $request->role_id;
                        $data['permission_id'] = $permissionId;
                        DB::table('role_has_permissions')->insert($data);
                }//end foreach

            }//end if

                $notification = array(
                    'message' => 'Role Permission Assigned Successfully',
                    'alert-type' => 'success'
                  );
                return redirect()->route('superadmin.settings.rolespermission')->with($notification);

        }


        public function allRolesPermission(){
            $roles = Role::all();
            $profileData = $this->getProfileData();
            return view('backend.superadmin.settings.rolespermission.rolespermission', compact('roles', 'profileData'));
        }


        public function editRolePermission($id){
            $role = Role::findOrFail(decrypt($id));
            $profileData = $this->getProfileData();
            $permissions = Permission::all();
            $permission_groups = User::getPermissionGroups();
            return view('backend.superadmin.settings.rolespermission.edit_role_permission', compact('role', 'permissions','profileData','permission_groups'));
        }

        public function updateRolePermission(Request $request, $id){
            $role = Role::findOrFail($id);
            $permissions = $request->input('permission');
            if(!empty($permissions)){
                $role->syncPermissions($permissions);
            }
            $notification = array(
                'message' => 'Role Permission Updated Successfully',
                'alert-type' => 'success'
            );

            return redirect()->route('superadmin.settings.rolespermission')->with($notification);


        }

        public function deleteRolePermission($id){
            DB::table('role_has_permissions')->where('role_id', decrypt($id))->delete();
            $notification = array(
                'message' => 'Role Permission Deleted Successfully',
                'alert-type' => 'success'
              );

            return redirect()->back()->with($notification);
        }

}
