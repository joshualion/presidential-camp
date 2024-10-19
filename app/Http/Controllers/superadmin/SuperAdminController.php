<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\Religion;
use App\Models\State;
use App\Models\SupportGroup;
use App\Models\SystemSetting;
use App\Models\Ward;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SuperAdminController extends Controller
{

    public function __construct()
    {

        $pageTitle = 'Admin Dashboard';
        View::share('pageTitle', $pageTitle);
    }

    // Function to get the current User profile data
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


   public function SuperAdminDashBoard(){
        $profileData  = $this->getProfileData();
        $regionsCount = $this->getRegions()->count();
        $regionsWithUsersCount = User::where('access_level', '!=', 'superadmin')->whereNotNull('region_id')->where('access_level', '!=', 'superadmin')->distinct('region_id')->count();
        $stateCount = State::count();
        $statesWithUsersCount = User::where('access_level', '!=', 'superadmin')->whereNotNull('state_id')->where('access_level', '!=', 'superadmin')->distinct('state_id')->count('state_id');
        $lgaCount = LocalGovernmentArea::count();
        $lgaWithUsersCount = User::where('access_level', '!=', 'superadmin')->whereNotNull('lga_id')->where('access_level', '!=', 'superadmin')->distinct('lga_id')->count('lga_id');
        $wardCount = Ward::count();
        $wardWithUsersCount = User::where('access_level', '!=', 'superadmin')->whereNotNull('ward_id')->where('access_level', '!=', 'superadmin')->distinct('ward_id')->count('ward_id');
        $puCount = PollingUnit::count();
        $puWithUsersCount = User::where('access_level', '!=', 'superadmin')->whereNotNull('polling_unit_id')->where('access_level', '!=', 'superadmin')->distinct('polling_unit_id')->count('polling_unit_id');
        $excoCount = User::where('access_level', '!=', 'superadmin')->where('access_level', '!=', 'user')->count();
        $memberCount = User::where('access_level', '!=', 'superadmin')->where('access_level', '=', 'user')->count();
        $userCount = User::where('access_level', '!=', 'superadmin')->count();

        return view('backend.superadmin.dashboard',
        compact(
        'profileData',
        'regionsCount',
        'regionsWithUsersCount',
        'stateCount',
        'statesWithUsersCount',
        'lgaCount',
        'lgaWithUsersCount',
        'wardCount',
        'wardWithUsersCount',
        'puCount',
        'puWithUsersCount',
        'excoCount',
        'memberCount',
        'userCount'));


   }


    //Function for admin profile
    public function SuperAdminProfile()
    {
        $pageTitle = 'Admin Profile';
        $profileData = $this->getProfileData();
        $supportGroups =SupportGroup::all();
        $countries = $this->getCountries();
        $regions = $this->getRegions();
        $states = State::all();
        $lgas = LocalGovernmentArea::all();
        $wards = Ward::all();
        $pus = PollingUnit::all();

        return view('backend.superadmin.profile',  compact('pageTitle',
            'profileData',
            'supportGroups',
            'countries',
            'regions',
            'states',
            'lgas',
            'wards',
            'pus',

        ));
    }

      //Function for admin profile update
      public function SuperAdminProfileStore(Request $request)
      {
          $profileData = $this->getProfileData();
          $profileData->update($request->only([
          'username', 'vin','firstname', 'lastname', 'email',
          'phone', 'address', 'qualification','occupation',
          'age_grade', 'gender', 'validvoter', 'religion',
          'supportgroup_id','country_id','region_id','state_id',
          'lga_id','ward_id','pu_id', 'officer_id'
          ]));

          if ($request->hasFile('photo')) {
              $file = $request->file('photo');
              $filename = date('YmdHi') . $file->getClientOriginalName();
              $file->move(public_path('uploads/profile_images/'), $filename);
              @unlink(public_path('uploads/profile_images/') . $profileData->photo);
              $profileData->photo = $filename;
          }

          $profileData->save();
          $notification = [
              'message' => 'Profile Updated Successfully',
              'alert-type' => 'success'
          ];
          return redirect()->back()->with($notification);
      }



          //Function for admin change password
    public function SuperAdminChangePassword()
    {
        $pageTitle = "Change Password";
        $profileData = $this->getProfileData();
        return view('backend.superadmin.change_password', compact('pageTitle','profileData'));
    }

    //Function for admin update password
    public function SuperAdminUpdatePassword(Request $request)
    {
        $validateData = $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed'
        ]);

        $user = $this->getProfileData();
        $hashedPassword = $user->password;

        if (!Hash::check($request->old_password, $hashedPassword)) {
            $notification = [
                'message' => 'Old Password is Invalid',
                'alert-type' => 'error'
            ];
            return back()->with($notification);
        } else {
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            $notification = [
                'message' => 'Password Changed Successfully',
                'alert-type' => 'success'
            ];
            return back()->with($notification);
        }
    }

     //Function for admin logout
     public function superAdminLogout(Request $request)
     {
         Auth::guard('web')->logout();
         $request->session()->invalidate();
         $request->session()->regenerateToken();

         $notification = [
             'message' => 'You have been logged out',
             'alert-type' => 'success'
         ];

         return redirect()->route('login')->with($notification);
     }



        //function member Distribution By Gender
        public function SuperAdminGenderDistribution()
        {
            $maleCount = User::where('access_level', '!=', 'superadmin')->where('gender', 'Male')->count();
            $femaleCount = User::where('access_level', '!=', 'superadmin')->where('gender', 'Female')->count();

            $genderData = [
                'labels' => ['Male', 'Female'],
                'datasets' => [
                    [
                        'data' => [$maleCount, $femaleCount],
                        'backgroundColor' => ['#28a745', '#e74c3c']
                    ]
                ]
            ];

            return response()->json($genderData);
        }


        // function member Distribution By Age
        public function SuperAdminAgeDistribution()
        {
            // Fetch all age grades
            $ageGrades = AgeGrade::all(); // Get both id and name

            // Initialize the counts array
            $ageCounts = [];

            // Loop through each age grade and count the users
            foreach ($ageGrades as $ageGrade) {
                // Count users by age_grade_id
                $count = User::where('access_level', '!=', 'superadmin')->where('age_grade_id', $ageGrade->id)->count();
                // Append the count to the ageCounts array
                $ageCounts[] = $count;
            }

            // Define an array of colors for the chart
            $colors = [
                '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9'
            ];

            // Use the array of colors for background colors
            $backgroundColors = array_slice($colors, 0, count($ageGrades));

            // Prepare the data structure for Chart.js
            $ageData = [
                'labels' => $ageGrades->pluck('name'), // Pluck age grade names
                'datasets' => [
                    [
                        'label' => 'Age Distribution',
                        'data' => $ageCounts, // Data for the counts
                        'backgroundColor' => $backgroundColors
                    ]
                ]
            ];

            // Return the data as JSON for Chart.js
            return response()->json($ageData);
        }


         //function member Distribution By Region
         public function SuperAdminRegionDistribution()
         {
             $regions = Region::all(); // Assuming you have a Region model and table

             $regionData = [
                 'labels' => [],
                 'datasets' => [
                     [
                         'data' => [],
                         'backgroundColor' => [
                             '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9'
                         ]
                     ]
                 ]
             ];

             foreach ($regions as $region) {
                 $count = User::where('access_level', '!=', 'superadmin')->where('region_id', $region->id)->where('access_level', '!=', 'superadmin')->count();
                 $regionData['labels'][] = $region->name; // Replace 'name' with the actual column name in the regions table
                 $regionData['datasets'][0]['data'][] = $count;
             }

             return response()->json($regionData);
         }


            //function member Distribution By Religion
        public function SuperAdminReligionDistribution()
            {
                // Fetch religions from the Religion model instead of hardcoding them
                $religions = Religion::pluck('name', 'id')->toArray(); // Get an array of [id => name] pairs

                $religionCounts = [];

                // Define an array of colors
                $colors = [
                    '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9'
                ];

                // Use the array of colors for background colors
                $backgroundColors = array_slice($colors, 0, count($religions));

                // Loop through religions and count users based on religion_id
                foreach ($religions as $religionId => $religionName) {
                    $count = User::where('access_level', '!=', 'superadmin')->where('religion_id', $religionId)->where('access_level', '!=', 'superadmin')->count();
                    $religionCounts[] = $count;
                }

                // Prepare data for the chart
                $religionData = [
                    'labels' => array_values($religions), // Labels (religion names)
                    'datasets' => [
                        [
                            'label' => 'Religion Distribution',
                            'data' => $religionCounts, // Count of users in each religion
                            'backgroundColor' => $backgroundColors
                        ]
                    ]
                ];

                return response()->json($religionData);
        }

          //function member Distribution By Eligible Voters
        public function SuperAdminVoterDistribution()
          {
              $validVoterCounts = [
                  'Yes' => User::where('access_level', '!=', 'superadmin')->where('validvoter', 'Yes')->where('access_level', '!=', 'superadmin')->count(),
                  'No' => User::where('access_level', '!=', 'superadmin')->where('validvoter', 'No')->where('access_level', '!=', 'superadmin')->count(),
              ];

              $validVoterData = [
                  'labels' => ['Eligible Voters', 'Ineligible Voters'],
                  'datasets' => [
                      [
                          'data' => array_values($validVoterCounts),
                          'backgroundColor' => ['#3498db', '#e74c3c']
                      ]
                  ]
              ];

              return response()->json($validVoterData);
        }

         //function member Distribution By State
         public function SuperAdminStateDistribution()
         {
             $states = State::all(); // Assuming you have a State model and table

             $stateCounts = [];
             // Define an array of colors
             $colors = [
                 '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9',
                 '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
                 '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
                 '#e74c3c', '#f39c12', '#1abc9c',
             ];

             // Use the array of colors for background colors
             $backgroundColors = array_slice($colors, 0, count($states));

             // Iterate over states to get the user count per state
             foreach ($states as $state) {
                 $count = User::where('state_id', $state->id)->where('access_level', '!=', 'superadmin')->count();
                 $stateCounts[] = $count;
             }

             // Get an array of state names
             $stateLabels = $states->pluck('name')->toArray();

             // Filter out states with zero user counts
             $filteredStateCounts = array_filter($stateCounts, function ($count) {
                 return $count > 0;
             });

             // Filter corresponding state names and background colors
             $filteredLabels = array_values(array_intersect_key($stateLabels, $filteredStateCounts));
             $filteredBackgroundColors = array_slice($backgroundColors, 0, count($filteredLabels));

             // Prepare the state distribution data for the donut chart
             $stateData = [
                 'labels' => $filteredLabels,
                 'datasets' => [
                     [
                         'label' => 'State Distribution',
                         'data' => array_values($filteredStateCounts),
                         'backgroundColor' => $filteredBackgroundColors
                     ]
                 ]
             ];

             return response()->json($stateData);
         }


        /* USER MANAGEMENT */

        public function allMembers()
        {
            $profileData = $this->getProfileData();
            $pageTitle = 'Team Members';
            //$members = User::where('access_level', '!=', 'superadmin')->get();
            $roles   = Role::all();
            return view('backend.superadmin.member.members', compact( 'profileData', 'pageTitle',  'roles'));
        }

        public function getMembersData()
        {
            $members = User::where('access_level', '!=', 'superadmin')->get();

            return datatables($members)
                ->addIndexColumn() // This will automatically add a serial number column
                ->addColumn('name', function($member) {
                    return $member->firstname . ' ' . $member->lastname;
                })

                ->addColumn('state', function($member) {
                    return optional($member->state)->name;
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
                    // Check if the member is active or not
                    if ($member->status == 'active') {
                        // If active, show Suspend button
                        $button = '
                            <a href="' . route('superadmin.member.suspend', $member->uuid) . '">
                                <button class="btn btn-warning btn-sm">
                                    <i class="fas fa-pause"></i> Suspend
                                </button>
                            </a>
                        ';
                    } else {
                        // If not active, show Activate button
                        $button = '
                            <a href="' . route('superadmin.member.suspend', $member->uuid) . '">
                                <button class="btn btn-success btn-sm">
                                    <i class="fas fa-play"></i> Activate
                                </button>
                            </a>
                        ';
                    }

                    // Add other action buttons (View, Edit, Delete)
                    return '
                        <a href="' . route('superadmin.member.view', $member->uuid) . '">
                            <button class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Show
                            </button>
                        </a>
                        <a href="' . route('superadmin.member.edit', $member->uuid) . '">
                            <button class="btn btn-info btn-sm">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </a>
                        ' . $button . '
                        <form action="' . route('superadmin.member.delete', $member->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="submit" class="btn btn-danger btn-sm delete-btn">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    ';
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
            return view('backend.superadmin.member.add', compact( 'profileData', 'pageTitle', 'roles', 'countries', 'regions', 'states', 'lgas', 'wards'));
        }

        public function storeMember(Request $request)
        {
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
            return redirect()->route('superadmin.members')->with($notification);
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

            return view('backend.superadmin.member.member', compact(
                'profileData', 'pageTitle', 'member', 'roles', 'countries',
                'regions', 'states', 'lgas', 'wards', 'pollingUnits', 'religions', 'ageGrades'
            ));
        }


        public function updateMember(Request $request) // Add $id to match the route
        {
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
                'access_level' => 'required|in:nationaladmin,regionaladmin,stateadmin,lgaadmin,wardadmin,puadmin,user,superadmin',
                'roles' => 'nullable|exists:roles,id',
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
                'access_level' => $request->access_level,
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

            return redirect()->route('superadmin.members')->with($notification);
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

            return view('backend.superadmin.member.show_member', compact(
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






}
