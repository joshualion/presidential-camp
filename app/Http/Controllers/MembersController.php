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



    private function filterMemberByAccessLevel()
    {
        $profileData = $this->getProfileData();
    
        // Define access level hierarchy
        $accessLevels = [
            'superadmin',
            'nationaladmin',
            'regionaladmin',
            'stateadmin',
            'lgaadmin',
            'wardadmin',
            'puadmin',
            'user',
        ];
    
        // Determine the current access level index
        $currentLevelIndex = array_search($profileData->access_level, $accessLevels);
        if ($currentLevelIndex === false) {
            throw new \Exception('Invalid access level: ' . $profileData->access_level);
        }
    
        // Include all access levels below the current one
        $includedLevels = array_slice($accessLevels, $currentLevelIndex);
    
        // Start building the query
        $membersQuery = User::query()->whereIn('access_level', $includedLevels);
    
        // Apply geographical constraints based on the current user's access level
        if ($profileData->access_level === 'regionaladmin') {
            $membersQuery->where('region_id', $profileData->region_id);
        } elseif ($profileData->access_level === 'stateadmin') {
            $membersQuery->where('state_id', $profileData->state_id);
        } elseif ($profileData->access_level === 'lgaadmin') {
            $membersQuery->where('lga_id', $profileData->lga_id);
        } elseif ($profileData->access_level === 'wardadmin') {
            $membersQuery->where('ward_id', $profileData->ward_id);
        } elseif ($profileData->access_level === 'pollingunitadmin') {
            $membersQuery->where('polling_unit_id', $profileData->polling_unit_id);
        }
    
        return $membersQuery;
    }
    
    

     public function allMembers($uuid = null)
     {

        if($uuid) {
            $PuLgaStateRegionCountryUUID = $uuid;
           }else{
            //get system Country
            $Setting= SystemSetting::find(1);
            $countryName = $Setting->system_country;
            $country = Country::where('name',  $countryName)->first();
            $PuLgaStateRegionCountryUUID = $country->uuid;
           }


         $profileData = $this->getProfileData();
         $roles   = Role::all();
         return view('backend.'.$profileData->access_level.'.member.members', compact( 'profileData',  'roles', 'PuLgaStateRegionCountryUUID'));
     }

    //  public function getMembersData($uuid = null)
    //  {
    //     $members= $this->filterMemberByAccessLevel();
    //     if ($uuid) {
    //         if ($pu = PollingUnit::where('uuid', $uuid)->first()) {
    //             $members->where('polling_unit_id', $pu->id);
    //         } elseif ($ward = Ward::where('uuid', $uuid)->first()) {
    //             $members->where('ward_id', $ward->id);
    //         } elseif ($lga = LocalGovernmentArea::where('uuid', $uuid)->first()) {
    //             $members->where('lga_id', $lga->id);
    //         } elseif ($state = State::where('uuid', $uuid)->first()) {
    //             $members->where('state_id', $state->id);
    //         } elseif ($region = Region::where('uuid', $uuid)->first()) {
    //             $members->where('region_id', $region->id);
    //         } elseif ($country = Country::where('uuid', $uuid)->first()) {
    //             $members->where('country_id', $country->id);
    //         }
    //     } 


    //      return datatables()->eloquent($members)
    //             ->filterColumn('name', function ($query, $keyword) {
    //                 $query->where('firstname', 'like', "%{$keyword}%")
    //                     ->orWhere('lastname', 'like', "%{$keyword}%");
    //             })
    //          ->addIndexColumn() // This will automatically add a serial number column
    //          ->addColumn('name', function($member) {
    //              return $member->firstname . ' ' . $member->lastname;
    //          })

    //          ->addColumn('access_level', function($member) {
    //              return $member->access_level;
    //          })
    //          ->addColumn('role', function($member) {
    //              return $member->roles->map(function($role) {
    //                  return '<span class="badge badge-pill bg-secondary">' . $role->name . '</span>';
    //              })->implode(' ');
    //          })
    //          ->addColumn('action', function($member) {
    //              $profileData = $this->getProfileData();

    //              // Check if the member is active or not for Suspend/Activate button
    //              if ($member->status == 'active') {
    //                  $button = '
    //                      <a href="' . route($profileData->access_level.'.member.suspend', $member->uuid) . '">
    //                          <button class="btn btn-warning btn-sm">
    //                              <i class="fas fa-pause"></i> Suspend
    //                          </button>
    //                      </a>
    //                  ';
    //              } else {
    //                  $button = '
    //                      <a href="' . route($profileData->access_level.'.member.suspend', $member->uuid) . '">
    //                          <button class="btn btn-success btn-sm">
    //                              <i class="fas fa-play"></i> Activate
    //                          </button>
    //                      </a>
    //                  ';
    //              }

    //              // Action buttons visible to all
    //              $actionButtons = '
    //                  <a href="' . route($profileData->access_level.'.member.view', $member->uuid) . '">
    //                      <button class="btn btn-primary btn-sm">
    //                          <i class="fas fa-eye"></i> Show
    //                      </button>
    //                  </a>
    //                  ' . $button;

    //              // Conditionally show Edit and Delete buttons based on roles/access_level
    //              if (
    //                  $profileData->hasAnyRole([
    //                      'National ICT/Media Director',
    //                      'Regional ICT/Media Director',
    //                      'State ICT/Media Director',
    //                      'LGA ICT/Media Director',
    //                      'Ward ICT/Media Director',
    //                      'PU ICT/Media Director'
    //                  ]) || $profileData->access_level == 'superadmin'
    //              ) {
    //                  $actionButtons .= '
    //                      <a href="' . route($profileData->access_level.'.member.edit', $member->uuid) . '">
    //                          <button class="btn btn-info btn-sm">
    //                              <i class="fas fa-pencil-alt"></i> Edit
    //                          </button>
    //                      </a>
    //                      <form action="' . route($profileData->access_level.'.member.delete', $member->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
    //                          ' . csrf_field() . method_field('DELETE') . '
    //                          <button type="submit" class="btn btn-danger btn-sm delete-btn">
    //                              <i class="fas fa-trash"></i> Delete
    //                          </button>
    //                      </form>
    //                  ';
    //              }

    //              return $actionButtons;
    //          })
    //          ->rawColumns(['role', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
    //          ->make(true);
    //  }

    
    public function getMembersData($uuid = null)
{
    // Get base query from filterMemberByAccessLevel
    $membersQuery = $this->filterMemberByAccessLevel();

    // Apply additional filtering if UUID is provided
    if ($uuid) {
        if ($pu = PollingUnit::where('uuid', $uuid)->first()) {
            $membersQuery->where('polling_unit_id', $pu->id);
        } elseif ($ward = Ward::where('uuid', $uuid)->first()) {
            $membersQuery->where('ward_id', $ward->id);
        } elseif ($lga = LocalGovernmentArea::where('uuid', $uuid)->first()) {
            $membersQuery->where('lga_id', $lga->id);
        } elseif ($state = State::where('uuid', $uuid)->first()) {
            $membersQuery->where('state_id', $state->id);
        } elseif ($region = Region::where('uuid', $uuid)->first()) {
            $membersQuery->where('region_id', $region->id);
        } elseif ($country = Country::where('uuid', $uuid)->first()) {
            $membersQuery->where('country_id', $country->id);
        }
    }

    // Return datatables response
    return datatables()->eloquent($membersQuery)
        ->filterColumn('name', function ($query, $keyword) {
            $query->where('firstname', 'like', "%{$keyword}%")
                ->orWhere('lastname', 'like', "%{$keyword}%");
        })
        ->addIndexColumn()
        ->addColumn('name', function ($member) {
            return $member->firstname . ' ' . $member->lastname;
        })
        ->addColumn('access_level', function ($member) {
            return $member->access_level;
        })
        ->addColumn('role', function ($member) {
            return $member->roles->map(function ($role) {
                return '<span class="badge badge-pill bg-secondary">' . $role->name . '</span>';
            })->implode(' ');
        })
        ->addColumn('action', function ($member) {
            $profileData = $this->getProfileData();

            // Determine Suspend/Activate button
            $statusButton = ($member->status === 'active')
                ? '<a href="' . route($profileData->access_level . '.member.suspend', $member->uuid) . '">
                       <button class="btn btn-warning btn-sm"><i class="fas fa-pause"></i> Suspend</button>
                   </a>'
                : '<a href="' . route($profileData->access_level . '.member.suspend', $member->uuid) . '">
                       <button class="btn btn-success btn-sm"><i class="fas fa-play"></i> Activate</button>
                   </a>';

            // Add action buttons
            $actionButtons = '
                <a href="' . route($profileData->access_level . '.member.view', $member->uuid) . '">
                    <button class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> Show</button>
                </a>' . $statusButton;

            // Conditionally add Edit/Delete buttons
            if ($profileData->hasAnyRole([
                    'National ICT/Media Director',
                    'Regional ICT/Media Director',
                    'State ICT/Media Director',
                    'LGA ICT/Media Director',
                    'Ward ICT/Media Director',
                    'PU ICT/Media Director'
                ]) || $profileData->access_level == 'superadmin') {
                $actionButtons .= '
                    <a href="' . route($profileData->access_level . '.member.edit', $member->uuid) . '">
                        <button class="btn btn-info btn-sm"><i class="fas fa-pencil-alt"></i> Edit</button>
                    </a>
                    <form action="' . route($profileData->access_level . '.member.delete', $member->uuid) . '" method="POST" style="display:inline-block;">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="btn btn-danger btn-sm delete-btn">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>';
            }

            return $actionButtons;
        })
        ->rawColumns(['role', 'action'])
        ->make(true);
}

    public function allExcoMembers($uuid = null)
     {
         if ($uuid) {
             $PuLgaStateRegionCountryUUID = $uuid;
         } else {
             // Get system Country
             $Setting = SystemSetting::find(1);
             $countryName = $Setting->system_country;
             $country = Country::where('name', $countryName)->first();
             $PuLgaStateRegionCountryUUID = $country->uuid;
         }

         // Check the UUID type and get the corresponding location
         $region = $state = $lga = $ward = $pu = null;

         if ($pu = PollingUnit::where('uuid', $PuLgaStateRegionCountryUUID)->first()) {
             // UUID belongs to a Polling Unit
         } elseif ($ward = Ward::where('uuid', $PuLgaStateRegionCountryUUID)->first()) {
             // UUID belongs to a Ward
         } elseif ($lga = LocalGovernmentArea::where('uuid', $PuLgaStateRegionCountryUUID)->first()) {
             // UUID belongs to an LGA
         } elseif ($state = State::where('uuid', $PuLgaStateRegionCountryUUID)->first()) {
             // UUID belongs to a State
         } elseif ($region = Region::where('uuid', $PuLgaStateRegionCountryUUID)->first()) {
             // UUID belongs to a Region
         } else {
             // If no match, assume it's the Country level
             $country = Country::where('uuid', $PuLgaStateRegionCountryUUID)->first();
         }

         $profileData = $this->getProfileData();
         $roles = Role::all();

         return view('backend.' . $profileData->access_level . '.member.leaders', compact(
             'profileData', 'roles', 'region', 'state', 'lga', 'ward', 'pu','PuLgaStateRegionCountryUUID'
         ));
     }


     public function getExcoMembersData($uuid = null)
     {

        $members = collect();

        if ($uuid) {
            if ($pu = PollingUnit::where('uuid', $uuid)->first()) {
                // Fetch members directly by Polling Unit ID
                $members = User::query()->where('access_level', '!=', 'superadmin')
				->where('access_level', '!=', 'user')
                ->where('polling_unit_id', $pu->id);

            } elseif ($ward = Ward::where('uuid', $uuid)->first()) {
                // Fetch members directly by Ward ID
                $members = User::query()->where('access_level', '!=', 'superadmin')
                ->where('access_level', '!=', 'user')
                ->where('ward_id', $ward->id);

            } elseif ($lga = LocalGovernmentArea::where('uuid', $uuid)->first()) {
                // Fetch members directly by LGA ID
                $members = User::query()->where('access_level', '!=', 'superadmin')
                ->where('access_level', '!=', 'user')
                ->where('lga_id', $lga->id);

            } elseif ($state = State::where('uuid', $uuid)->first()) {
                // Fetch members directly by State ID
                $members = User::query()->where('access_level', '!=', 'superadmin')
                ->where('access_level', '!=', 'user')
                ->where('state_id', $state->id);

            } elseif ($region = Region::where('uuid', $uuid)->first()) {
                // Fetch members directly by Region ID
                $members = User::query()->where('access_level', '!=', 'superadmin')
                ->where('access_level', '!=', 'user')
                ->where('region_id', $region->id);

            } elseif ($country = Country::where('uuid', $uuid)->first()) {
                // Fetch members directly by Country ID
                $members = User::query()->where('access_level', '!=', 'superadmin')
                ->where('access_level', '!=', 'user')
                ->where('country_id', $country->id);
            }
        } else {
            // If no UUID is provided, fetch all members excluding superadmins
            $members = User::query()->where('access_level', '!=', 'user')
            ->where('access_level', '!=', 'superadmin');
        }


         return datatables()->eloquent($members)

                ->filterColumn('name', function ($query, $keyword) {
                    $query->where('firstname', 'like', "%{$keyword}%")
                        ->orWhere('lastname', 'like', "%{$keyword}%");
                })
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


     public function allRegularMembers($uuid = null)
     {
         if ($uuid) {
             $PuLgaStateRegionCountryUUID = $uuid;
         } else {
             // Get system Country
             $Setting = SystemSetting::find(1);
             $countryName = $Setting->system_country;
             $country = Country::where('name', $countryName)->first();
             $PuLgaStateRegionCountryUUID = $country->uuid;
         }

         // Check the UUID type and get the corresponding location
         $region = $state = $lga = $ward = $pu = null;

         if ($pu = PollingUnit::where('uuid', $PuLgaStateRegionCountryUUID)->first()) {
             // UUID belongs to a Polling Unit
         } elseif ($ward = Ward::where('uuid', $PuLgaStateRegionCountryUUID)->first()) {
             // UUID belongs to a Ward
         } elseif ($lga = LocalGovernmentArea::where('uuid', $PuLgaStateRegionCountryUUID)->first()) {
             // UUID belongs to an LGA
         } elseif ($state = State::where('uuid', $PuLgaStateRegionCountryUUID)->first()) {
             // UUID belongs to a State
         } elseif ($region = Region::where('uuid', $PuLgaStateRegionCountryUUID)->first()) {
             // UUID belongs to a Region
         } else {
             // If no match, assume it's the Country level
             $country = Country::where('uuid', $PuLgaStateRegionCountryUUID)->first();
         }

         $profileData = $this->getProfileData();
         $roles = Role::all();

         return view('backend.' . $profileData->access_level . '.member.regulars', compact(
             'profileData', 'roles', 'region', 'state', 'lga', 'ward', 'pu','PuLgaStateRegionCountryUUID'
         ));
     }


     public function getRegularMembersData($uuid = null)
     {

        $members = collect();

        if ($uuid) {
            if ($pu = PollingUnit::where('uuid', $uuid)->first()) {
                // Fetch members directly by Polling Unit ID
                $members = User::query()->where('access_level', '!=', 'superadmin')
				->where('access_level', '=',  'user')
                ->where('polling_unit_id', $pu->id);

            } elseif ($ward = Ward::where('uuid', $uuid)->first()) {
                // Fetch members directly by Ward ID
                $members = User::query()->where('access_level', '!=', 'superadmin')
                ->where('access_level',  'user')
                ->where('ward_id', $ward->id);

            } elseif ($lga = LocalGovernmentArea::where('uuid', $uuid)->first()) {
                // Fetch members directly by LGA ID
                $members = User::query()->where('access_level', '!=', 'superadmin')
                ->where('access_level',  'user')
                ->where('lga_id', $lga->id);

            } elseif ($state = State::where('uuid', $uuid)->first()) {
                // Fetch members directly by State ID
                $members = User::query()->where('access_level', '!=', 'superadmin')
                ->where('access_level',  'user')
                ->where('state_id', $state->id);

            } elseif ($region = Region::where('uuid', $uuid)->first()) {
                // Fetch members directly by Region ID
                $members = User::query()->where('access_level', '!=', 'superadmin')
                ->where('access_level',  'user')
                ->where('region_id', $region->id);

            } elseif ($country = Country::where('uuid', $uuid)->first()) {
                // Fetch members directly by Country ID
                $members = User::query()->where('access_level', '!=', 'superadmin')
                ->where('access_level',  'user')
                ->where('country_id', $country->id);
            }
        } else {
            // If no UUID is provided, fetch all members excluding superadmins
            $members = User::query()->where('access_level', '=',  'user')
            ->where('access_level', '!=', 'superadmin');
        }


         return datatables()->eloquent($members)

                ->filterColumn('name', function ($query, $keyword) {
                    $query->where('firstname', 'like', "%{$keyword}%")
                        ->orWhere('lastname', 'like', "%{$keyword}%");
                })
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

     public function addMember()
     {
         $profileData = $this->getProfileData();
         $pageTitle = 'Add Member';
         $countries = Country::all();
         $regions = $this->getRegions();
         $states = State::all();
         $lgas =   LocalGovernmentArea::all();
         $wards = Ward::all();


                // Fetch all roles
            $allRoles = Role::all();

            // Filter roles based on active user's access level
            $roles = $allRoles->filter(function ($role) use ($profileData) {
                // Include roles that match the active user's access_level
                // OR roles that can be assigned to members (like 'user')
                return $role->group_name === $profileData->access_level || $role->group_name === 'user';
            });


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
             'country_id' => $request->country_id,
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

         $pageTitle = 'Edit Member';
        //$member = User::with('roles')->where('uuid', $uuid)->first(); // Fetch member with roles

         // Access level hierarchy
            $accessLevels = [
                'superadmin' => 1,
                'nationaladmin' => 2,
                'regionaladmin' => 3,
                'stateadmin' => 4,
                'lgaadmin' => 5,
                'wardadmin' => 6,
                'pollingunitadmin' => 7,
            ];

        // Fetch the logged-in user's profile data
        $profileData = $this->getProfileData();

        // Fetch the member's data with roles
        $member = User::with('roles')->where('uuid', $uuid)->first();

        // Get the numeric values for both access levels
        $userAccessLevel = $accessLevels[$profileData->access_level] ?? null; // Logged-in user
        $memberAccessLevel = $accessLevels[$member->access_level] ?? null; // Target member

        // Check if either access level is undefined
        if ($userAccessLevel === null || $memberAccessLevel === null) {
            $notification = [
                "message" => "An invalid access level was detected. Please contact the administrator.",
                "type" => "error", // This can be 'error', 'success', 'info', or 'warning'
            ];
            return redirect()->back()->with($notification);
        }

        // Compare access levels
        if ($userAccessLevel > $memberAccessLevel) {
            $notification = [
                "message" => "You do not have permission to view or edit a profile with higher privileges than yours.",
                "type" => "warning", // Indicates a permissions issue
            ];
            return redirect()->back()->with($notification);
        }
         // $roles = Role::all();  // Assuming a member can have multiple roles
         $countries = Country::all();
         $regions = $this->getRegions();
         $states = State::all();
         $lgas = LocalGovernmentArea::all();
         $wards = Ward::all();
         $pollingUnits = PollingUnit::all();
         $religions = Religion::all();
         $ageGrades = AgeGrade::all();


         // Fetch all roles
            $allRoles = Role::all();

           // Filter roles based on the active user's access level
        $roles = $allRoles->filter(function ($role) use ($profileData, $member) {
            // Include roles matching the active user's access level
            // OR roles already assigned to the member
            return  $member->roles->contains('id', $role->id);
        });

      
 
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
             'access_level' => 'nullable|string',
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

                 return redirect()->back()->with($notification);
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

          // Access level hierarchy
          $accessLevels = [
            'superadmin' => 1,
            'nationaladmin' => 2,
            'regionaladmin' => 3,
            'stateadmin' => 4,
            'lgaadmin' => 5,
            'wardadmin' => 6,
            'pollingunitadmin' => 7,
        ];


    // Get the numeric values for both access levels
    $userAccessLevel = $accessLevels[$profileData->access_level] ?? null; // Logged-in user
    $memberAccessLevel = $accessLevels[$member->access_level] ?? null; // Target member

    // Check if either access level is undefined
    if ($userAccessLevel === null || $memberAccessLevel === null) {
        $notification = [
            "message" => "An invalid access level was detected. Please contact the administrator.",
            "type" => "error", // This can be 'error', 'success', 'info', or 'warning'
        ];
        return redirect()->back()->with($notification);
    }

    // Compare access levels
    if ($userAccessLevel > $memberAccessLevel) {
        $notification = [
            "message" => "You do not have permission to view or edit a profile with higher privileges than yours.",
            "type" => "error", // Indicates a permissions issue
        ];
        return redirect()->back()->with($notification);
    }






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
         $profileData = $this->getProfileData();
         $member = User::where('uuid', $uuid)->first();


         // Access level hierarchy
         $accessLevels = [
            'superadmin' => 1,
            'nationaladmin' => 2,
            'regionaladmin' => 3,
            'stateadmin' => 4,
            'lgaadmin' => 5,
            'wardadmin' => 6,
            'pollingunitadmin' => 7,
        ];


    // Get the numeric values for both access levels
    $userAccessLevel = $accessLevels[$profileData->access_level] ?? null; // Logged-in user
    $memberAccessLevel = $accessLevels[$member->access_level] ?? null; // Target member

    // Check if either access level is undefined
    if ($userAccessLevel === null || $memberAccessLevel === null) {
        $notification = [
            "message" => "An invalid access level was detected. Please contact the administrator.",
            "type" => "error", // This can be 'error', 'success', 'info', or 'warning'
        ];
        return redirect()->back()->with($notification);
    }

    // Compare access levels
    if ($userAccessLevel > $memberAccessLevel) {
        $notification = [
            "message" => "You do not have permission to suspend or delete a profile with higher privileges than yours.",
            "type" => "error", // Indicates a permissions issue
        ];
        return redirect()->back()->with($notification);
    }

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

        $profileData = $this->getProfileData();
         // Find the member by uuid
         $member = User::where('uuid', $uuid)->first();


         
         // Access level hierarchy
         $accessLevels = [
            'superadmin' => 1,
            'nationaladmin' => 2,
            'regionaladmin' => 3,
            'stateadmin' => 4,
            'lgaadmin' => 5,
            'wardadmin' => 6,
            'pollingunitadmin' => 7,
        ];


    // Get the numeric values for both access levels
    $userAccessLevel = $accessLevels[$profileData->access_level] ?? null; // Logged-in user
    $memberAccessLevel = $accessLevels[$member->access_level] ?? null; // Target member

    // Check if either access level is undefined
    if ($userAccessLevel === null || $memberAccessLevel === null) {
        $notification = [
            "message" => "An invalid access level was detected. Please contact the administrator.",
            "type" => "error", // This can be 'error', 'success', 'info', or 'warning'
        ];
        return redirect()->back()->with($notification);
    }

    // Compare access levels
    if ($userAccessLevel > $memberAccessLevel) {
        $notification = [
            "message" => "You do not have permission to suspend or delete a profile with higher privileges than yours.",
            "type" => "error", // Indicates a permissions issue
        ];
        return redirect()->back()->with($notification);
    }



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

            $members = User::query()->where('access_level', '!=', 'superadmin')
                            ->where('region_id', $region->id);

                            return datatables()->eloquent($members)

                            ->filterColumn('name', function ($query, $keyword) {
                                $query->where('firstname', 'like', "%{$keyword}%")
                                    ->orWhere('lastname', 'like', "%{$keyword}%");
                            })
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


    // *** MEMBERS BY STATES ***
    public function membersByStates($uuid = null)
    {
        $profileData = $this->getProfileData();

        // Initialize $states to avoid undefined variable
        $states = collect();

        if ($uuid) {
            // Check if the $uuid belongs to a region first
            $region = Region::where('uuid', $uuid)->first();

            if ($region) {
                // Fetch states by region ID
                $states = State::where('region_id', $region->id)
                    ->orderBy('name', 'asc')
                    ->get();
            } else {
                // If not a region, check if the $uuid belongs to a country
                $country = Country::where('uuid', $uuid)->first();

                if ($country) {
                    // Fetch regions that belong to the country
                    $regions = Region::where('country_id', $country->id)->pluck('id');

                    // Fetch states that belong to these regions
                    $states = State::whereIn('region_id', $regions)
                        ->orderBy('name', 'asc')
                        ->get();
                }
            }
        } else {
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
        $members = User::query()->where('access_level', '!=', 'superadmin')
                        ->where('state_id', $state->id);

          return datatables()->eloquent($members)

            ->filterColumn('name', function ($query, $keyword) {
                    $query->where('firstname', 'like', "%{$keyword}%")
                    ->orWhere('lastname', 'like', "%{$keyword}%");
             })
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


    // *** MEMBERS BY LOCAL GOVERNMENTS ***
    public function membersByLocalGovernments($uuid = null)

      {
        $profileData = $this->getProfileData();

        // Initialize $lgas to avoid undefined variable issues
        $lgas = collect();

        if($uuid) {
            // Prioritize checking for state, region, then country
            $state = State::where('uuid', $uuid)->first();

            if ($state) {
                // Fetch all LGAs in that state
                $lgas = LocalGovernmentArea::where('state_id', $state->id)
                    ->orderBy('name', 'asc')
                    ->get();
            } else {
                // If not a state, check for region
                $region = Region::where('uuid', $uuid)->first();

                if ($region) {
                    // Fetch LGAs in states that belong to this region
                    $states = State::where('region_id', $region->id)->pluck('id');
                    $lgas = LocalGovernmentArea::whereIn('state_id', $states)
                        ->orderBy('name', 'asc')
                        ->get();
                } else {
                    // If not a region, check for country
                    $country = Country::where('uuid', $uuid)->first();

                    if ($country) {
                        // Fetch LGAs in states under regions in that country
                        $regions = Region::where('country_id', $country->id)->pluck('id');
                        $states = State::whereIn('region_id', $regions)->pluck('id');
                        $lgas = LocalGovernmentArea::whereIn('state_id', $states)
                            ->orderBy('name', 'asc')
                            ->get();
                    }
                }
            }
        } else {
            // If no UUID is provided, fetch all LGAs
            $lgas = LocalGovernmentArea::orderBy('name', 'asc')->get();
        }

        return view('backend.' . $profileData->access_level . '.member.membersByLga', compact('lgas', 'profileData'));
    }

    public function viewMembersByLocalGovernments($uuid)
    {
        $profileData = $this->getProfileData();

        // Find state by UUID or return a 404 error if not found
        $lga = LocalGovernmentArea::where('uuid', $uuid)->firstOrFail();

        // Retrieve members belonging to the region
        $members = User::where('lga_id', $lga->id)->get();

        return view('backend.' . $profileData->access_level . '.member.viewMembersByLga', compact('lga', 'members', 'profileData'));
    }

    public function getLgaMembersData($uuid)
    {

        $lga = LocalGovernmentArea::where('uuid', $uuid)->first();

        $members = User::query()->where('access_level', '!=', 'superadmin')
                        ->where('lga_id', $lga->id);

                        return datatables()->eloquent($members)

                        ->filterColumn('name', function ($query, $keyword) {
                                $query->where('firstname', 'like', "%{$keyword}%")
                                ->orWhere('lastname', 'like', "%{$keyword}%");
                         })
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


      // *** MEMBERS BY WARDS ***
    public function membersByWards($uuid = null)
     {
                $profileData = $this->getProfileData();

                if($uuid) {
                    $LgaStateRegionCountryUUID = $uuid;
                   }else{
                    //get system Country
                    $Setting= SystemSetting::find(1);
                    $countryName = $Setting->system_country;
                    $country = Country::where('name',  $countryName)->first();

                    $LgaStateRegionCountryUUID = $country->uuid;
                   }
                return view('backend.' . $profileData->access_level . '.member.membersByWard', compact('LgaStateRegionCountryUUID', 'profileData'));
    }


    public function viewMembersByWard($uuid){
        $profileData = $this->getProfileData();

        // Find state by UUID or return a 404 error if not found
        $ward = Ward::where('uuid', $uuid)->firstOrFail();

        // Retrieve members belonging to the region
        $members = User::where('ward_id', $ward->id)->get();

        return view('backend.' . $profileData->access_level . '.member.viewMembersByWard', compact('ward', 'members', 'profileData'));


    }


    public function getWardMembersData($uuid)
    {

        $ward = Ward::where('uuid', $uuid)->first();

        $members = User::query()->where('access_level', '!=', 'superadmin')
                        ->where('ward_id', $ward->id);

                        return datatables()->eloquent($members)

                        ->filterColumn('name', function ($query, $keyword) {
                                $query->where('firstname', 'like', "%{$keyword}%")
                                ->orWhere('lastname', 'like', "%{$keyword}%");
                         })
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


     // *** MEMBERS BY PU ***

     public function membersByPus($uuid = null)
     {
                $profileData = $this->getProfileData();

                if($uuid) {
                    $WardLgaStateRegionCountryUUID = $uuid;
                   }else{
                    //get system Country
                    $Setting= SystemSetting::find(1);
                    $countryName = $Setting->system_country;
                    $country = Country::where('name',  $countryName)->first();

                    $WardLgaStateRegionCountryUUID = $country->uuid;
                   }
                return view('backend.' . $profileData->access_level . '.member.membersByPu', compact('WardLgaStateRegionCountryUUID', 'profileData'));
    }


    public function viewMembersByPu($uuid){
        $profileData = $this->getProfileData();

        // Find state by UUID or return a 404 error if not found
        $pu = PollingUnit::where('uuid', $uuid)->firstOrFail();

        // Retrieve members belonging to the region
        $members = User::where('polling_unit_id', $pu->id)->get();

        return view('backend.' . $profileData->access_level . '.member.viewMembersByPu', compact('pu', 'members', 'profileData'));


    }


    public function getPuMembersData($uuid)
    {

        $pu = PollingUnit::where('uuid', $uuid)->first();

        $members = User::query()->where('access_level', '!=', 'superadmin')
                        ->where('polling_unit_id', $pu->id);

                        return datatables()->eloquent($members)

                        ->filterColumn('name', function ($query, $keyword) {
                                $query->where('firstname', 'like', "%{$keyword}%")
                                ->orWhere('lastname', 'like', "%{$keyword}%");
                         })
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
