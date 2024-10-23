<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\Religion;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\Ward;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;


class MembersController extends Controller
{
    public function __construct()
    {
        $pageTitle = 'Members';
        View::share('pageTitle', $pageTitle);
    }
     // Function to get the profile data
     private function getProfileData()
     {
         $id = Auth::user()->id;
         return User::find($id);
     }


     //Function to get the system country
     private function getCountries()
     {
          // Retrieve the value of the default_country setting
          $defaultCountry = SystemSetting::find(1);

         // Retrieve the ID of the default country
         return Country::where('name', $defaultCountry->system_country)->first();
     }

     //Function to retrieves regions from set country
     public function getRegions()
     {
         $country_id = $this->getCountries()->id;
         $country = Country::where('id',$country_id)->first();

         if($country->name == 'Nigeria'){
             $region = Region::where('country_id', $country_id)->where('name', '!=', 'No-region')->get();
         }else{
         $region = Region::where('country_id', $country_id)->get();
         }


         return($region);
     }

     public function allMembers()
     {
         $profileData = $this->getProfileData();
         $roles   = Role::all();
         return view('backend.'.$profileData->access_level.'.member.members', compact( 'profileData',  'roles'));
     }

     public function getMembersData()
     {


         $members = User::where('access_level', '!=', 'superadmin')->get();

         return datatables($members)
             ->addIndexColumn() // This will automatically add a serial number column
             ->addColumn('name', function($member) {
                 return $member->firstname . ' ' . $member->lastname;
             })

             ->addColumn('access_level', function($member) {
                 return $member->access_level;
             })
             ->addColumn('role', function($member) {
                 return $member->roles->map(function($role) {
                     return '<span class="badge badge-pill bg-secondary">' . $role->name . '</span>';
                 })->implode(' ');
             })
             ->addColumn('action', function($member) {
                 $profileData = $this->getProfileData();

                 // Check if the member is active or not for Suspend/Activate button
                 if ($member->status == 'active') {
                     $button = '
                         <a href="' . route('nationaladmin.member.suspend', $member->uuid) . '">
                             <button class="btn btn-warning btn-sm">
                                 <i class="fas fa-pause"></i> Suspend
                             </button>
                         </a>
                     ';
                 } else {
                     $button = '
                         <a href="' . route('nationaladmin.member.suspend', $member->uuid) . '">
                             <button class="btn btn-success btn-sm">
                                 <i class="fas fa-play"></i> Activate
                             </button>
                         </a>
                     ';
                 }

                 // Action buttons visible to all
                 $actionButtons = '
                     <a href="' . route('nationaladmin.member.view', $member->uuid) . '">
                         <button class="btn btn-primary btn-sm">
                             <i class="fas fa-eye"></i> Show
                         </button>
                     </a>
                     ' . $button;

                 // Conditionally show Edit and Delete buttons based on roles/access_level
                 if (
                     $profileData->hasAnyRole([
                         'National ICT/Media Director',
                         'Regional ICT/Media Director',
                         'State ICT/Media Director',
                         'LGA ICT/Media Director',
                         'Ward ICT/Media Director',
                         'PU ICT/Media Director'
                     ]) || $profileData->access_level == 'superadmin'
                 ) {
                     $actionButtons .= '
                         <a href="' . route('nationaladmin.member.edit', $member->uuid) . '">
                             <button class="btn btn-info btn-sm">
                                 <i class="fas fa-pencil-alt"></i> Edit
                             </button>
                         </a>
                         <form action="' . route('nationaladmin.member.delete', $member->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                             ' . csrf_field() . method_field('DELETE') . '
                             <button type="submit" class="btn btn-danger btn-sm delete-btn">
                                 <i class="fas fa-trash"></i> Delete
                             </button>
                         </form>
                     ';
                 }

                 return $actionButtons;
             })
             ->rawColumns(['role', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
             ->make(true);
     }



     public function addMember()
     {
         $profileData = $this->getProfileData();
         $pageTitle = 'Team Members';
         $roles = Role::all();
         $countries = Country::all();
         $regions = $this->getRegions();
         $states = State::all();
         $lgas =   LocalGovernmentArea::all();
         $wards = Ward::all();
         return view('backend.'.$profileData->access_level.'.member.add', compact( 'profileData', 'pageTitle', 'roles', 'countries', 'regions', 'states', 'lgas', 'wards'));
     }

     public function storeMember(Request $request)
     {
        $profileData = $this->getProfileData();
         $request->validate([
             'username' => 'required|string|max:255|unique:users',
             'firstname' => 'required|string',
             'lastname' => 'required|string',
             'email' => 'required|email|unique:users,email',
             'password' => 'required|confirmed|min:6',
             'access_level' => 'required',
         ]);

         if(empty($request->roles)){
             $access_level = 'user';
         }else{
             $access_level = $request->access_level;
         }

         $user = User::create([
             'uuid' => Str::uuid()->toString(),
             'username' => $request->username,
             'firstname' => $request->firstname,
             'lastname' => $request->lastname,
             'email' => $request->email,
             'password' => Hash::make($request->password),
             'access_level' => $access_level,
             'region_id' => $request->region_id,
             'state_id' => $request->state_id,
             'lga_id' => $request->lga_id,
             'ward_id' => $request->ward_id,
         ]);

         // Assign Role
         if ($request->has('roles') && !empty($request->roles)) {
             // Assuming you're passing only one role ID (you can adjust this for multiple roles if needed)
             $roleId = $request->roles; // Make sure $request->roles contains the correct role ID

         // Find the role by its ID
         $role = Role::findById($roleId);

             if ($role) {
                 // Sync the role to the member using its name
                 $user->syncRoles([$role->name]);
             } else {
                 // Handle the case where the role is not found
                 $notification = [
                     'message' => 'Invalid role selected.',
                     'alert-type' => 'error'
                 ];

                 return redirect()->back()->withErrors($notification);
             }
             } else {
                 // No role selected, assign default role 'Member' (role ID = 40)
                 $defaultRole = Role::findById(40); // Find the role by its ID (40)

                 if ($defaultRole) {
                     $user->syncRoles([$defaultRole->name]);
                 }
             }

         $notification = [
             'message' => 'Member Added Successfully',
             'alert-type' => 'success'
         ];
         return redirect()->route($profileData->access_level.'.members')->with($notification);
     }

     public function editMember($uuid)
     {
         $profileData = $this->getProfileData();
         $pageTitle = 'Edit Team Member';
         $member = User::where('uuid', $uuid)->first();
         $roles = Role::all();  // Assuming a member can have multiple roles
         $countries = Country::all();
         $regions = $this->getRegions();
         $states = State::all();
         $lgas = LocalGovernmentArea::all();
         $wards = Ward::all();
         $pollingUnits = PollingUnit::all();
         $religions = Religion::all();
         $ageGrades = AgeGrade::all();

         return view('backend.'.$profileData->access_level.'.member.member', compact(
             'profileData', 'pageTitle', 'member', 'roles', 'countries',
             'regions', 'states', 'lgas', 'wards', 'pollingUnits', 'religions', 'ageGrades'
         ));
     }


     public function updateMember(Request $request) // Add $id to match the route
     {

         $profileData = $this->getProfileData();
         // Find the member by ID and update
         $member = User::findOrFail($request->id);

         // Validation rules
         $validatedData = $request->validate([
             'firstname' => 'required|string|max:255',
             'lastname' => 'required|string|max:255',
             'email' => 'required|email|max:255',
             'phone' => 'required|string|max:15|unique:users,phone,' .$member->id,
             'validvoter' => 'required|in:yes,no',
             'vin' => 'nullable|string|max:255',
             'age_grade_id' => 'required|exists:age_grades,id',
             'gender' => 'required|in:male,female',
             'region_id' => 'required|exists:regions,id',
             'state_id' => 'required|exists:states,id',
             'lga_id' => 'required|exists:local_government_areas,id',
             'ward_id' => 'required|exists:wards,id',
             'polling_unit_id' => 'required|exists:polling_units,id',
             'access_level' => 'required',
             'religion_id' => 'required|exists:religions,id',
             'address' => 'nullable|string|max:255',
             'occupation' => 'nullable|string|max:255',
             'qualification' => 'nullable|string|max:255',
             'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
         ]);




         $member->update([
             'firstname' => $request->firstname,
             'lastname' => $request->lastname,
             'email' => $request->email,
             'phone' => $request->phone,
             'validVoter' => $request->validvoter,
             'vin' => $request->vin,
             'age_grade_id' => $request->age_grade_id,
             'gender' => $request->gender,
             'region_id' => $request->region_id,
             'state_id' => $request->state_id,
             'lga_id' => $request->lga_id,
             'ward_id' => $request->ward_id,
             'polling_unit_id' => $request->polling_unit_id,
             'access_level' =>  $request->access_level,
             'religion_id' => $request->religion_id,
             'address' => $request->address,
             'occupation' => $request->occupation,
             'qualification' => $request->qualification,
         ]);

         if ($request->hasFile('photo')) {
             $photo = $request->file('photo');
             $filename = time() . '.' . $photo->getClientOriginalExtension();
             $photo->move(public_path('uploads/member_images'), $filename);
             $member->update(['photo' => $filename]);
         }

         // Assign Role
         if ($request->has('roles') && !empty($request->roles)) {
             // Assuming you're passing only one role ID (you can adjust this for multiple roles if needed)
             $roleId = $request->roles; // Make sure $request->roles contains the correct role ID

         // Find the role by its ID
         $role = Role::findById($roleId);

             if ($role) {
                 // Sync the role to the member using its name
                 $member->syncRoles([$role->name]);
             } else {
                 // Handle the case where the role is not found
                 $notification = [
                     'message' => 'Invalid role selected.',
                     'alert-type' => 'error'
                 ];

                 return redirect()->back()->withErrors($notification);
             }
             } else {
                 // No role selected, assign default role 'Member' (role ID = 40)
                 $defaultRole = Role::findById(40); // Find the role by its ID (40)

                 if ($defaultRole) {
                     $member->syncRoles([$defaultRole->name]);
                 }
             }



         $notification = [
             'message' => 'Member updated successfully.',
             'alert-type' => 'success'
         ];

         return redirect()->route($profileData->access_level.'.members')->with($notification);
     }

     public function viewMember($uuid){
         $profileData = $this->getProfileData();
         $pageTitle = 'Team Member';
         $member = User::where('uuid', $uuid)->first();
         $roles = Role::all();  // Assuming a member can have multiple roles
         $countries = Country::all();
         $regions = $this->getRegions();
         $states = State::all();
         $lgas = LocalGovernmentArea::all();
         $wards = Ward::all();
         $pollingUnits = PollingUnit::all();
         $religions = Religion::all();
         $ageGrades = AgeGrade::all();

         return view('backend.'.$profileData->access_level.'.member.show_member', compact(
             'profileData', 'pageTitle', 'member', 'roles', 'countries',
             'regions', 'states', 'lgas', 'wards', 'pollingUnits', 'religions', 'ageGrades'
         ));

     }

     public function suspendMember($uuid){
         $member = User::where('uuid', $uuid)->first();

         //check status and suspend or activated accordingly
         if($member->status == 'inactive'){
             $member->status = 'active';
             $member->save();
             $notification = [
                 'message' => 'Member activated successfully.',
                 'alert-type' => 'success'
             ];

         }else{

             $member->status = 'inactive';
             $member->save();
         $notification = [
             'message' => 'Member suspended successfully.',
             'alert-type' => 'success'
         ];

         }

         return redirect()->back()->with($notification);
     }



     public function deleteMember(Request $request, $uuid)
     {
         // Find the member by uuid
         $member = User::where('uuid', $uuid)->first();

         // Check if the member exists
         if (!$member) {
             // Prepare error notification
             $notification = [
                 'message' => 'Member not found!',
                 'alert-type' => 'error'
             ];

             // Redirect back with error notification
             return redirect()->back()->with($notification);
         }

         // Attempt to delete the member
         try {
             $member->delete();

             // Prepare success notification
             $notification = [
                 'message' => 'Member deleted successfully!',
                 'alert-type' => 'success'
             ];

             // Redirect back with success notification
             return redirect()->back()->with($notification);
         } catch (\Exception $e) {
             // Prepare error notification in case of failure
             $notification = [
                 'message' => 'Failed to delete member!',
                 'alert-type' => 'error'
             ];

             // Redirect back with error notification
             return redirect()->back()->with($notification);
         }
     }


     public function membersByRegions()
     {
         $profileData = $this->getProfileData();
         $regions = $this->getRegions();
         return view('backend.'.$profileData->access_level.'.member.membersByRegion', compact('regions', 'profileData'));
     }


    public function ViewMembersByRegion($uuid)
        {
            $profileData = $this->getProfileData();

            // Find region by UUID or return a 404 error if not found
            $region = Region::where('uuid', $uuid)->firstOrFail();

            // Retrieve members belonging to the region
            $members = User::where('region_id', $region->id)->get();

            return view('backend.' . $profileData->access_level . '.member.viewMembersByRegion', compact('region', 'members', 'profileData'));
    }



    public function getRegionMembersData($uuid)
        {

            $region = Region::where('uuid', $uuid)->first();

            $members = User::where('access_level', '!=', 'superadmin')
                            ->where('region_id', $region->id)
                            ->get();

            return datatables($members)
                ->addIndexColumn() // This will automatically add a serial number column
                ->addColumn('name', function($member) {
                    return $member->firstname . ' ' . $member->lastname;
                })

                ->addColumn('access_level', function($member) {
                    return $member->access_level;
                })
                ->addColumn('role', function($member) {
                    return $member->roles->map(function($role) {
                        return '<span class="badge badge-pill bg-secondary">' . $role->name . '</span>';
                    })->implode(' ');
                })
                ->addColumn('action', function($member) {
                    $profileData = $this->getProfileData();

                    // Check if the member is active or not for Suspend/Activate button
                    if ($member->status == 'active') {
                        $button = '
                            <a href="' . route('nationaladmin.member.suspend', $member->uuid) . '">
                                <button class="btn btn-warning btn-sm">
                                    <i class="fas fa-pause"></i> Suspend
                                </button>
                            </a>
                        ';
                    } else {
                        $button = '
                            <a href="' . route('nationaladmin.member.suspend', $member->uuid) . '">
                                <button class="btn btn-success btn-sm">
                                    <i class="fas fa-play"></i> Activate
                                </button>
                            </a>
                        ';
                    }

                    // Action buttons visible to all
                    $actionButtons = '
                        <a href="' . route('nationaladmin.member.view', $member->uuid) . '">
                            <button class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Show
                            </button>
                        </a>
                        ' . $button;

                    // Conditionally show Edit and Delete buttons based on roles/access_level
                    if (
                        $profileData->hasAnyRole([
                            'National ICT/Media Director',
                            'Regional ICT/Media Director',
                            'State ICT/Media Director',
                            'LGA ICT/Media Director',
                            'Ward ICT/Media Director',
                            'PU ICT/Media Director'
                        ]) || $profileData->access_level == 'superadmin'
                    ) {
                        $actionButtons .= '
                            <a href="' . route('nationaladmin.member.edit', $member->uuid) . '">
                                <button class="btn btn-info btn-sm">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                            </a>
                            <form action="' . route('nationaladmin.member.delete', $member->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                                ' . csrf_field() . method_field('DELETE') . '
                                <button type="submit" class="btn btn-danger btn-sm delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        ';
                    }

                    return $actionButtons;
                })
                ->rawColumns(['role', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
                ->make(true);
    }

    
    // *** MEMBERS BY STATES ***
    public function membersByStates($uuid = null)
    {

        $profileData = $this->getProfileData();
       
    if($uuid) {
            
        
                $country = Country::where('uuid', $uuid)->first();
                $region = Region::where('uuid', $uuid)->first();

                // Check if the $uuid belongs to a region
                if($region) {
                    // Fetch states by region ID
                    $states = State::where('region_id', $region->id)
                        ->orderBy('name', 'asc')
                        ->get();
                } 
                // If the $uuid belongs to a country, fetch all states under regions within that country
                elseif($country) {
                    // Fetch regions that belong to the country
                    $regions = Region::where('country_id', $country->id)->pluck('id');

                    // Fetch states that belong to these regions
                    $states = State::whereIn('region_id', $regions)
                        ->orderBy('name', 'asc')
                        ->get();
                        }

        }else{
            
                // If no UUID is provided, fetch all states
              $states = State::orderBy('name', 'asc')->get();
        }

        
        
        return view('backend.'.$profileData->access_level.'.member.membersByState', compact('states', 'profileData'));
    }

    public function ViewMembersByState($uuid)
    {
        $profileData = $this->getProfileData();

        // Find state by UUID or return a 404 error if not found
        $state = State::where('uuid', $uuid)->firstOrFail();

        // Retrieve members belonging to the region
        $members = User::where('state_id', $state->id)->get();

        return view('backend.' . $profileData->access_level . '.member.viewMembersByState', compact('state', 'members', 'profileData'));
    }

    public function getStateMembersData($uuid)
    {

        $state = State::where('uuid', $uuid)->first();

        $members = User::where('access_level', '!=', 'superadmin')
                        ->where('state_id', $state->id)
                        ->get();

        return datatables($members)
            ->addIndexColumn() // This will automatically add a serial number column
            ->addColumn('name', function($member) {
                return $member->firstname . ' ' . $member->lastname;
            })

            ->addColumn('access_level', function($member) {
                return $member->access_level;
            })
            ->addColumn('role', function($member) {
                return $member->roles->map(function($role) {
                    return '<span class="badge badge-pill bg-secondary">' . $role->name . '</span>';
                })->implode(' ');
            })
            ->addColumn('action', function($member) {
                $profileData = $this->getProfileData();

                // Check if the member is active or not for Suspend/Activate button
                if ($member->status == 'active') {
                    $button = '
                        <a href="' . route($profileData->access_level.'.member.suspend', $member->uuid) . '">
                            <button class="btn btn-warning btn-sm">
                                <i class="fas fa-pause"></i> Suspend
                            </button>
                        </a>
                    ';
                } else {
                    $button = '
                        <a href="' . route($profileData->access_level.'.member.suspend', $member->uuid) . '">
                            <button class="btn btn-success btn-sm">
                                <i class="fas fa-play"></i> Activate
                            </button>
                        </a>
                    ';
                }

                // Action buttons visible to all
                $actionButtons = '
                    <a href="' . route($profileData->access_level.'.member.view', $member->uuid) . '">
                        <button class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i> Show
                        </button>
                    </a>
                    ' . $button;

                // Conditionally show Edit and Delete buttons based on roles/access_level
                if (
                    $profileData->hasAnyRole([
                        'National ICT/Media Director',
                        'Regional ICT/Media Director',
                        'State ICT/Media Director',
                        'LGA ICT/Media Director',
                        'Ward ICT/Media Director',
                        'PU ICT/Media Director'
                    ]) || $profileData->access_level == 'superadmin'
                ) {
                    $actionButtons .= '
                        <a href="' . route($profileData->access_level.'.member.edit', $member->uuid) . '">
                            <button class="btn btn-info btn-sm">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </a>
                        <form action="' . route($profileData->access_level.'.member.delete', $member->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="submit" class="btn btn-danger btn-sm delete-btn">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    ';
                }

                return $actionButtons;
            })
            ->rawColumns(['role', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
            ->make(true);
    }


}
