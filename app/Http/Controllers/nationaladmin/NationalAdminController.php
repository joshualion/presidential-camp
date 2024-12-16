<?php

namespace App\Http\Controllers\nationaladmin;

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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class NationalAdminController extends Controller
{

    public function __construct()
    {

        $pageTitle = 'National Members/Agents Dashboard';
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


   public function nationalAdminDashBoard(){
        $uuid = $this->getCountries()->uuid;
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

        return view('backend.nationaladmin.dashboard',
        compact(
        'profileData',
        'uuid',
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
    public function nationalAdminProfile()
    {
            $profileData = $this->getProfileData();
            $pageTitle = 'Profile';
            $roles = Role::all();  // Assuming a member can have multiple roles
            $countries = Country::all();
            $regions = $this->getRegions();
            $states = State::all();
            $lgas = LocalGovernmentArea::all();
            $wards = Ward::all();
            $pollingUnits = PollingUnit::all();
            $religions = Religion::all();
            $ageGrades = AgeGrade::all();

            return view('backend.nationaladmin.profile', compact(
                'profileData', 'pageTitle', 'roles', 'countries',
                'regions', 'states', 'lgas', 'wards', 'pollingUnits', 'religions', 'ageGrades'
            ));
    }

      //Function for admin profile update
    public function nationalAdminProfileStore(Request $request)
    {
        $profileData = $this->getProfileData();
          
        // Validation rules
       $validatedData = $request->validate([
          'firstname' => 'required|string|max:255',
          'lastname' => 'required|string|max:255',
          'email' => 'required|email|max:255',
          'phone' => 'required|string|max:15|unique:users,phone,' .$profileData->id,
          'validvoter' => 'required|in:yes,no',
          'vin' => 'nullable|string|max:255',
          'age_grade_id' => 'required|exists:age_grades,id',
          'gender' => 'required|in:male,female',
          'region_id' => 'required|exists:regions,id',
          'state_id' => 'required|exists:states,id',
          'lga_id' => 'required|exists:local_government_areas,id',
          'ward_id' => 'required|exists:wards,id',
          'polling_unit_id' => 'required|exists:polling_units,id',
          'religion_id' => 'required|exists:religions,id',
          'address' => 'nullable|string|max:255',
          'occupation' => 'nullable|string|max:255',
          'qualification' => 'nullable|string|max:255',
          'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
      ]);

      $profileData->update([
          'firstname' => $validatedData['firstname'],
          'lastname' => $validatedData['lastname'],
          'email' => $validatedData['email'],
          'phone' => $validatedData['phone'],
          'validVoter' => $validatedData['validvoter'],
          'vin' => $validatedData['vin'],
          'age_grade_id' => $validatedData['age_grade_id'],
          'gender' => $validatedData['gender'],
          'region_id' => $validatedData['region_id'],
          'state_id' => $validatedData['state_id'],
          'lga_id' => $validatedData['lga_id'],
          'ward_id' => $validatedData['ward_id'],
          'polling_unit_id' => $validatedData['polling_unit_id'],
          'religion_id' => $validatedData['religion_id'],
          'address' => $validatedData['address'],
          'occupation' => $validatedData['occupation'],
          'qualification' => $validatedData['qualification'],
      ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = date('YmdHi') . $file->getClientOriginalName();
            $file->move(public_path('uploads/member_images/'), $filename);
            @unlink(public_path('uploads/member_images/') . $profileData->photo);
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
    public function nationalAdminChangePassword()
    {
        $pageTitle = "Change Password";
        $profileData = $this->getProfileData();
        return view('backend.nationaladmin.change_password', compact('pageTitle','profileData'));
    }

    //Function for admin update password
    public function nationalAdminUpdatePassword(Request $request)
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
     public function nationalAdminLogout(Request $request)
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
        public function nationalAdminGenderDistribution()
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
        public function nationalAdminAgeDistribution()
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
         public function nationalAdminRegionDistribution()
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
        public function nationalAdminReligionDistribution()
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
        public function nationalAdminVoterDistribution()
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
         public function nationalAdminStateDistribution()
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
         //Other Dashboards
        public function RegionDashBoard($uuid)
        {
                        $region = Region::where('uuid', $uuid)->first();
                        $pageTitle = 'Regional Dashboard';
                        $profileData  = $this->getProfileData();

                        $stateCount = State::where('region_id', $region->id)->count();
                        $statesWithUsersCount = User::whereIn('state_id', function($query) use ($region) {
                            $query->select('id')
                                ->from('states')
                                ->where('region_id', $region->id);
                        })->distinct('state_id')->count('state_id');



                    // Get the count of LGAs by first finding all the states in the region and then finding LGAs in those states
                    $stateIds = State::where('region_id', $region->id)->pluck('id'); // Get all state IDs in the region
                    $lgaCount = LocalGovernmentArea::whereIn('state_id', $stateIds)->count();

                    // Get LGAs that belong to the states in the region and have users
                    $lgaWithUsersCount = LocalGovernmentArea::whereIn('state_id', $stateIds)
                    ->whereHas('users') // Ensures only LGAs with users are counted
                    ->count();

                    // Get all the LGA IDs in those states
                    $lgas = LocalGovernmentArea::whereIn('state_id', $stateIds)->pluck('id');

                    // Get the total count of wards in the LGAs (wardCount)
                    $wardCount = Ward::whereIn('lga_id', $lgas)->count();

                    // Get the count of wards that have associated users (wardWithUsersCount)
                    $wardWithUsersCount = Ward::whereIn('lga_id', $lgas)
                        ->whereHas('users') // Ensures only wards with users are counted
                        ->count();

                    // Get all the ward IDs in those LGAs
                    $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');

                    // Get the total count of polling units in the wards (puCount)
                    $puCount = PollingUnit::whereIn('ward_id', $wards)->count();

                    // Get the count of polling units that have associated users (puWithUsersCount)
                    $puWithUsersCount = PollingUnit::whereIn('ward_id', $wards)
                        ->whereHas('users') // Ensures only polling units with users are counted
                        ->count();

                        $excoCount = User::where('region_id', $region->id)
                        ->where('access_level', '!=', 'user')
                        ->where('access_level', '!=', 'superadmin')->count();

                        $memberCount = User ::where('region_id', $region->id)
                        ->where('access_level', 'user')->count();

                        $userCount = User ::where('region_id', $region->id)
                        ->where('access_level', '!=', 'superadmin')->count();

                        $newMembers = User ::where('region_id', $region->id)
                        ->where('access_level', '!=', 'superadmin')
                        ->where('created_at', '>=', Carbon::now()->subDays(1))
                        ->count();

                        return view('backend.'.$profileData->access_level.'.region.dashboard',
                        compact(
                'profileData',
                'pageTitle',
                            'region',
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
                        'userCount',
                        'newMembers'
                        ));


        }

        public function StateDashBoard($uuid)
        {
            $pageTitle = 'State Dashboard';
            $profileData  = $this->getProfileData();
            $state = State::where('uuid', $uuid)->first();

            if(!$state){
                return redirect()->back()->with('error', 'State not found');
            }

            $lgaCount = LocalGovernmentArea::where('state_id', $state->id)->count();

            // Get LGAs that belong to the state  and have atleast one user
            $lgasWithUsersCount = User::whereIn('lga_id', function($query) use ($state) {
                $query->select('id')
                    ->from('local_government_areas')
                    ->where('state_id', $state->id);
            })->distinct('lga_id')->count('lga_id');


             // Get all the LGA IDs in those states
             $lgas = LocalGovernmentArea::where('state_id', $state->id)->pluck('id');

             // Get the total count of wards in the LGAs (wardCount)
             $wardCount = Ward::whereIn('lga_id', $lgas)->count();

             // Get the count of wards that have associated users (wardWithUsersCount)
             $wardWithUsersCount = Ward::whereIn('lga_id', $lgas)
                 ->whereHas('users') // Ensures only wards with users are counted
                 ->count();

             // Get all the ward IDs in those LGAs
             $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');

             // Get the total count of polling units in the wards (puCount)
             $puCount = PollingUnit::whereIn('ward_id', $wards)->count();

             // Get the count of polling units that have associated users (puWithUsersCount)
             $puWithUsersCount = PollingUnit::whereIn('ward_id', $wards)
                 ->whereHas('users') // Ensures only polling units with users are counted
                 ->count();
                 $excoCount = User::where('state_id', $state->id)
                 ->where('access_level', '!=', 'user')
                 ->where('access_level', '!=', 'superadmin')->count();

                 $memberCount = User ::where('state_id', $state->id)
                 ->where('access_level', '!=', 'superadmin')
                 ->where('access_level', 'user')->count();

                 $userCount = User ::where('state_id', $state->id)
                 ->where('access_level', '!=', 'superadmin')->count();

                 $newMembers = User ::where('state_id', $state->id)
                 ->where('access_level', '!=', 'superadmin')
                 ->where('created_at', '>=', Carbon::now()->subDays(1))
                 ->count();



                return view('backend.'.$profileData->access_level.'.state.dashboard',
                        compact(
                            'profileData',
                            'pageTitle',
                            'state',
                            'lgaCount',
                            'lgasWithUsersCount',
                            'wardCount',
                            'wardWithUsersCount',
                            'puCount',
                            'puWithUsersCount',
                            'excoCount',
                            'memberCount',
                            'userCount',
                            'newMembers'
                        ));


        }


        public function localGovernmentDashBoard($uuid)
        {
            $pageTitle = 'Local Government Dashboard';
            $profileData  = $this->getProfileData();
            $lga = LocalGovernmentArea::where('uuid', $uuid)->first();

            if(!$lga){
                return redirect()->back()->with('error', 'LGA not found');
            }



             // Get the total count of wards in the LGAs (wardCount)
             $wardCount = Ward::where('lga_id', $lga->id)->count();

             // Get the count of wards that have associated users (wardWithUsersCount)
             $wardWithUsersCount = Ward::where('lga_id', $lga->id)
                 ->whereHas('users') // Ensures only wards with users are counted
                 ->count();

             // Get all the ward IDs in those LGAs
             $wards = Ward::where('lga_id', $lga->id)->pluck('id');

             // Get the total count of polling units in the wards (puCount)
             $puCount = PollingUnit::whereIn('ward_id', $wards)->count();

             // Get the count of polling units that have associated users (puWithUsersCount)
             $puWithUsersCount = PollingUnit::whereIn('ward_id', $wards)
                 ->whereHas('users') // Ensures only polling units with users are counted
                 ->count();
                 $excoCount = User::where('lga_id', $lga->id)
                 ->where('access_level', '!=', 'user')
                 ->where('access_level', '!=', 'superadmin')->count();

                 $memberCount = User ::where('lga_id', $lga->id)
                 ->where('access_level', '!=', 'superadmin')
                 ->where('access_level', 'user')->count();

                 $userCount = User ::where('lga_id', $lga->id)
                 ->where('access_level', '!=', 'superadmin')->count();

                 $newMembers = User ::where('lga_id', $lga->id)
                 ->where('access_level', '!=', 'superadmin')
                 ->where('created_at', '>=', Carbon::now()->subDays(1))
                 ->count();



                return view('backend.'.$profileData->access_level.'.lga.dashboard',
                        compact(
                            'profileData',
                            'pageTitle',
                            'lga',
                            'wardCount',
                            'wardWithUsersCount',
                            'puCount',
                            'puWithUsersCount',
                            'excoCount',
                            'memberCount',
                            'userCount',
                            'newMembers'
                        ));


        }


        public function wardDashBoard($uuid)

        {
            $pageTitle = 'Ward Dashboard';
            $profileData  = $this->getProfileData();
            $ward = Ward::where('uuid', $uuid)->first();

            if(!$ward){
                $notification = [
                    'message' => 'Ward not found',
                    'alert-type' => 'error'
                ];

                return redirect()->back()->with($notification);
            }



             // Get the total count of wards in the LGAs (wardCount)
             $puCount = PollingUnit::where('ward_id', $ward->id)->count();

             // Get the count of wards that have associated users (wardWithUsersCount)
             $puWithUsersCount =PollingUnit::where('ward_id', $ward->id)
                 ->whereHas('users') // Ensures only wards with users are counted
                 ->count();


                 $excoCount = User::where('ward_id', $ward->id)
                 ->where('access_level', '!=', 'user')
                 ->where('access_level', '!=', 'superadmin')->count();

                 $memberCount = User ::where('ward_id', $ward->id)
                 ->where('access_level', '!=', 'superadmin')
                 ->where('access_level', 'user')->count();

                 $eligibleVotersCount = User ::where('ward_id', $ward->id)
                 ->where('access_level', '!=', 'superadmin')
                 ->where('validvoter', 'yes')->count();

                 $inEligibleVotersCount = User ::where('ward_id', $ward->id)
                 ->where('access_level', '!=', 'superadmin')
                 ->where('validvoter', 'No')->count();

                 $userCount = User ::where('ward_id', $ward->id)
                 ->where('access_level', '!=', 'superadmin')->count();

                 $newMembers = User ::where('ward_id', $ward->id)
                 ->where('access_level', '!=', 'superadmin')
                 ->where('created_at', '>=', Carbon::now()->subDays(1))
                 ->count();



                return view('backend.'.$profileData->access_level.'.ward.dashboard',
                        compact(
                            'profileData',
                            'pageTitle',
                            'ward',
                            'puCount',
                            'puWithUsersCount',
                            'excoCount',
                            'memberCount',
                            'eligibleVotersCount',
                            'inEligibleVotersCount',
                            'userCount',
                            'newMembers'
                        ));


        }

        public function puDashBoard($uuid)

        {
            $pageTitle = 'Polling Unit Dashboard';
            $profileData  = $this->getProfileData();
            $pu = PollingUnit::where('uuid', $uuid)->first();

                    if(!$pu){
                        $notification = [
                            'message' => 'Polling Unit not found',
                            'alert-type' => 'error'
                        ];

                        return redirect()->back()->with($notification);
                    }

                 $excoCount = User::where('polling_unit_id', $pu->id)
                 ->where('access_level', '!=', 'user')
                 ->where('access_level', '!=', 'superadmin')->count();

                 $memberCount = User ::where('polling_unit_id', $pu->id)
                 ->where('access_level', '!=', 'superadmin')
                 ->where('access_level', 'user')->count();
                 

                 $eligibleVotersCount = User ::where('polling_unit_id', $pu->id)
                 ->where('access_level', '!=', 'superadmin')
                 ->where('validvoter', 'yes')->count();

                 $inEligibleVotersCount = User ::where('polling_unit_id', $pu->id)
                 ->where('access_level', '!=', 'superadmin')
                 ->where('validvoter', 'No')->count();

                 $userCount = User ::where('polling_unit_id', $pu->id)
                 ->where('access_level', '!=', 'superadmin')->count();

                 $newMembers = User ::where('polling_unit_id', $pu->id)
                 ->where('access_level', '!=', 'superadmin')
                 ->where('created_at', '>=', Carbon::now()->subDays(1))
                 ->count();



                return view('backend.'.$profileData->access_level.'.pu.dashboard',
                        compact(
                            'profileData',
                            'pageTitle',
                            'pu',
                            'excoCount',
                            'memberCount',
                            'eligibleVotersCount',
                            'inEligibleVotersCount',
                            'userCount',
                            'newMembers'
                        ));


        }









}
