<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
//use App\Models\PollingUnitAgentAssignment;
use App\Models\Region;
use App\Models\Religion;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\Ward;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Services\CampaignPackagePermissionService;
use App\Services\CampaignPackageLocationFormService;
use App\Services\CampaignPackageRoleService;
use App\Services\LocationScopeService;
use App\Services\LicensedScopeQueryService;
use App\Services\MemberImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MembersExport;
use Illuminate\Validation\ValidationException;



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

     protected function canDeleteUsers(User $profileData): bool
     {
         return $this->packagePermissions()->canDeleteUsers($profileData);
     }

     protected function accessLevelRank(?string $accessLevel): ?int
     {
         return [
             'superadmin' => 1,
             'nationaladmin' => 2,
             'regionaladmin' => 3,
             'regionadmin' => 3,
             'stateadmin' => 4,
             'senatorialadmin' => 5,
             'federaladmin' => 5,
             'lgaadmin' => 6,
             'wardadmin' => 7,
             'puadmin' => 8,
             'pollingunitadmin' => 8,
             'user' => 9,
         ][$accessLevel] ?? null;
     }

     protected function accessLevelLabels(): array
     {
         return $this->packageRoles()->accessLevelLabels();
     }

     protected function assignableAccessLevels(User $profileData): array
     {
         return $this->packageRoles()->assignableAccessLevels($profileData);
     }

     protected function assignableRoles(User $profileData, ?User $member = null)
     {
         return $this->packageRoles()->assignableRoles($profileData, $member);
     }

     protected function canAssignRole(User $profileData, Role $role, ?string $accessLevel = null): bool
     {
         return $this->packageRoles()->canAssignRole($profileData, $role, $accessLevel);
     }

     protected function packageRoles(): CampaignPackageRoleService
     {
         return app(CampaignPackageRoleService::class);
     }

     protected function packagePermissions(): CampaignPackagePermissionService
     {
         return app(CampaignPackagePermissionService::class);
     }

     protected function canAccessMember(User $profileData, ?User $member): bool
     {
         if (!$member) {
             return false;
         }

         $userAccessLevel = $this->accessLevelRank($profileData->access_level);
         $memberAccessLevel = $this->accessLevelRank($member->access_level);

         if ($userAccessLevel === null || $memberAccessLevel === null) {
             return false;
         }

         if ($userAccessLevel > $memberAccessLevel) {
             return false;
         }

         return $this->memberIsWithinScope($profileData, $member);
     }

     protected function canEditMember(User $profileData, ?User $member): bool
     {
         if (!$this->canAccessMember($profileData, $member)) {
             return false;
         }

         return $this->packagePermissions()->canEditUsers($profileData);
     }

     protected function memberIsWithinScope(User $profileData, User $member): bool
     {
         $query = User::query()->whereKey($member->id);
         app(LocationScopeService::class)->applyScope($query, $profileData->access_level === 'superadmin' ? null : $profileData, 'users', 'users');

         return $query->exists();
     }

     private function licensedScope(): LicensedScopeQueryService
     {
         return app(LicensedScopeQueryService::class);
     }

     private function locationForms(): CampaignPackageLocationFormService
     {
         return app(CampaignPackageLocationFormService::class);
     }

     private function validateLicensedPayload(array $payload): void
     {
         $errors = $this->licensedScope()->payloadErrors($payload);

         if ($errors !== []) {
             throw ValidationException::withMessages($errors);
         }
     }

     private function invalidMemberAccessNotification(): array
     {
         return [
             'message' => 'An invalid access level was detected. Please contact the administrator.',
             'alert-type' => 'error',
         ];
     }

     private function unauthorizedMemberAccessNotification(string $action = 'view or edit'): array
     {
         return [
             'message' => "You do not have permission to {$action} this profile.",
             'alert-type' => 'warning',
         ];
     }

     private function memberDeleteButton(User $member): string
     {
         $profileData = $this->getProfileData();

         if (!$this->canDeleteUsers($profileData)) {
             return '';
         }

         return '
             <form action="' . route($profileData->access_level.'.member.delete', $member->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                 ' . csrf_field() . method_field('DELETE') . '
                 <button type="submit" class="btn btn-danger btn-sm delete-btn">
                     <i class="fas fa-trash"></i> Delete
                 </button>
             </form>
         ';
     }

     protected function boundaryValidationRules(): array
     {
         return [
             'senatorial_district_id' => 'nullable|exists:senatorial_districts,id',
             'federal_constituency_id' => 'nullable|exists:federal_constituencies,id',
         ];
     }

     protected function memberLocationRules(string $accessLevel, string $pollingUnitField = 'polling_unit_id'): array
     {
         $rules = [
             'region_id' => 'nullable|exists:regions,id',
             'state_id' => 'nullable|exists:states,id',
             'lga_id' => 'nullable|exists:local_government_areas,id',
             'ward_id' => 'nullable|exists:wards,id',
             $pollingUnitField => 'nullable|exists:polling_units,id',
         ];

         return match ($accessLevel) {
             'regionaladmin' => array_merge($rules, ['region_id' => 'required|exists:regions,id']),
             'stateadmin' => array_merge($rules, ['state_id' => 'required|exists:states,id']),
             'senatorialadmin' => array_merge($rules, ['senatorial_district_id' => 'required|exists:senatorial_districts,id']),
             'federaladmin' => array_merge($rules, ['federal_constituency_id' => 'required|exists:federal_constituencies,id']),
             'lgaadmin' => array_merge($rules, ['lga_id' => 'required|exists:local_government_areas,id']),
             'wardadmin' => array_merge($rules, ['ward_id' => 'required|exists:wards,id']),
             'puadmin', 'user' => array_merge($rules, [$pollingUnitField => 'required|exists:polling_units,id']),
             default => $rules,
         };
     }

     protected function resolveBoundaryAssignments(Request $request, string $accessLevel)
     {
         $senatorialDistrictId = $request->senatorial_district_id;
         $federalConstituencyId = $request->federal_constituency_id;

         if ($request->filled('polling_unit_id') || $request->filled('pu_id')) {
             $pollingUnitId = $request->polling_unit_id ?: $request->pu_id;
             $pollingUnit = PollingUnit::find($pollingUnitId);

             if ($pollingUnit) {
                 $senatorialDistrictId = $senatorialDistrictId ?: $pollingUnit->senatorial_district_id;
                 $federalConstituencyId = $federalConstituencyId ?: $pollingUnit->federal_constituency_id;
             }
         }

         if ($federalConstituencyId && !$senatorialDistrictId) {
             $federalConstituency = FederalConstituency::find($federalConstituencyId);
             $senatorialDistrictId = $federalConstituency?->senatorial_district_id;
         }

         $stateId = $request->state_id;

         if (in_array($accessLevel, ['senatorialadmin', 'federaladmin'], true) && !$stateId) {
             return redirect()->back()
                 ->withInput()
                 ->withErrors(['state_id' => 'Please select the state this admin is allowed to manage within.']);
         }

         if ($accessLevel === 'senatorialadmin' && !$senatorialDistrictId) {
             return redirect()->back()
                 ->withInput()
                 ->withErrors(['senatorial_district_id' => 'Please select the senatorial district this admin is allowed to manage.']);
         }

         if ($accessLevel === 'federaladmin' && !$federalConstituencyId) {
             return redirect()->back()
                 ->withInput()
                 ->withErrors(['federal_constituency_id' => 'Please select the federal constituency this admin is allowed to manage.']);
         }

         if ($senatorialDistrictId) {
             $senatorialDistrict = SenatorialDistrict::find($senatorialDistrictId);

             if (!$senatorialDistrict) {
                 return redirect()->back()
                     ->withInput()
                     ->withErrors(['senatorial_district_id' => 'The selected senatorial district is invalid.']);
             }

             if ($stateId && $senatorialDistrict->state_id && (string) $senatorialDistrict->state_id !== (string) $stateId) {
                 return redirect()->back()
                     ->withInput()
                     ->withErrors(['senatorial_district_id' => 'The selected senatorial district does not belong to the selected state.']);
             }
         }

         if ($federalConstituencyId) {
             $federalConstituency = FederalConstituency::find($federalConstituencyId);

             if (!$federalConstituency) {
                 return redirect()->back()
                     ->withInput()
                     ->withErrors(['federal_constituency_id' => 'The selected federal constituency is invalid.']);
             }

             if ($stateId && $federalConstituency->state_id && (string) $federalConstituency->state_id !== (string) $stateId) {
                 return redirect()->back()
                     ->withInput()
                     ->withErrors(['federal_constituency_id' => 'The selected federal constituency does not belong to the selected state.']);
             }

             if ($senatorialDistrictId && $federalConstituency->senatorial_district_id && (string) $federalConstituency->senatorial_district_id !== (string) $senatorialDistrictId) {
                 return redirect()->back()
                     ->withInput()
                     ->withErrors(['federal_constituency_id' => 'The selected federal constituency does not belong to the selected senatorial district.']);
             }
         }

         return [
             'senatorial_district_id' => $senatorialDistrictId,
             'federal_constituency_id' => $federalConstituencyId,
         ];
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
             $query = Region::where('country_id', $country_id)->where('name', '!=', 'No-region');
         }else{
             $query = Region::where('country_id', $country_id);
         }

         $this->licensedScope()->applyToRegionsQuery($query);
         $region = $query->get();

         $region->loadCount(['users as members_count' => function ($query) {
             $query->where('access_level', '!=', 'superadmin');
         }]);

         return($region);
     }



     private function accessLevelHierarchy(): array
     {
         return [
            'superadmin',
            'nationaladmin',
            'regionaladmin',
            'stateadmin',
            'senatorialadmin',
            'federaladmin',
            'lgaadmin',
            'wardadmin',
            'puadmin',
            'user',
        ];
     }

    protected function scopedMembersQuery(?User $profileData = null, bool $includeSuperadmin = false): Builder
    {
        $profileData ??= $this->getProfileData();
        $accessLevels = $this->accessLevelHierarchy();
        $currentLevelIndex = array_search($profileData->access_level, $accessLevels, true);

        if ($currentLevelIndex === false) {
            throw new \Exception('Invalid access level: ' . $profileData->access_level);
        }

        $includedLevels = array_slice($accessLevels, $currentLevelIndex);
        if (!$includeSuperadmin && $profileData->access_level !== 'superadmin') {
            $includedLevels = array_values(array_diff($includedLevels, ['superadmin']));
        }

        $membersQuery = User::query()->whereIn('access_level', $includedLevels);
        app(LocationScopeService::class)->applyScope($membersQuery, $profileData, 'users', 'users');
        $this->enforceParentScopeConsistency($membersQuery, $profileData);

        return $membersQuery;
    }

    private function enforceParentScopeConsistency(Builder $query, User $profileData): void
    {
        if ($profileData->access_level === 'senatorialadmin') {
            $district = $profileData->senatorialDistrict;

            if ($district?->state_id) {
                $query->where(function (Builder $stateQuery) use ($district) {
                    $stateQuery->whereNull('users.state_id')
                        ->orWhere('users.state_id', $district->state_id);
                });
            }
        }

        if ($profileData->access_level === 'federaladmin') {
            $constituency = $profileData->federalConstituency;

            if ($constituency?->state_id) {
                $query->where(function (Builder $stateQuery) use ($constituency) {
                    $stateQuery->whereNull('users.state_id')
                        ->orWhere('users.state_id', $constituency->state_id);
                });
            }

            if ($constituency?->senatorial_district_id) {
                $query->where(function (Builder $districtQuery) use ($constituency) {
                    $districtQuery->whereNull('users.senatorial_district_id')
                        ->orWhere('users.senatorial_district_id', $constituency->senatorial_district_id);
                });
            }
        }
    }

    private function filterMemberByAccessLevel()
    {
        return $this->scopedMembersQuery();
    }



     public function allMembers($uuid = null)
     {
         // A missing UUID means "all members within the authenticated user's
         // scope". Do not silently replace it with a country filter.
         $PuLgaStateRegionCountryUUID = $uuid;

         $profileData = $this->getProfileData();
         $roles   = Role::all();
         return view('backend.'.$profileData->access_level.'.member.members', compact( 'profileData',  'roles', 'PuLgaStateRegionCountryUUID'));
     }


    public function getMembersData($uuid = null)
{
    // Get base query from filterMemberByAccessLevel
    $membersQuery = $this->filterMemberByAccessLevel();

    // Apply an optional explicit boundary without replacing the actor/package scope.
    $this->applyLocationScopeFromUuid($membersQuery, $uuid);

    // Return datatables response
    return datatables()->eloquent($membersQuery)
        ->filterColumn('name', function ($query, $keyword) {
            $query->where(function ($nameQuery) use ($keyword) {
                $nameQuery->where('firstname', 'like', "%{$keyword}%")
                    ->orWhere('lastname', 'like', "%{$keyword}%");
            });
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

        ->addColumn('Voter_status', function ($member) {
            return ucfirst($member->validVoter);
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
                    'National ICT Director',
                    'Regional ICT Director',
                    'State ICT Director',
                    'Senatorial ICT Director',
                    'Federal Constituency ICT Director',
                    'Federal ICT Director',
                    'LGA ICT Director',
                    'Ward ICT Director',
                    'PU ICT Director'
                ]) || $profileData->access_level == 'superadmin') {
                $actionButtons .= '
                    <a href="' . route($profileData->access_level . '.member.edit', $member->uuid) . '">
                        <button class="btn btn-info btn-sm"><i class="fas fa-pencil-alt"></i> Edit</button>
                    </a>';
            }

            $actionButtons .= $this->memberDeleteButton($member);
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
        $members = $this->scopedMembersQuery()
            ->where('access_level', '!=', 'user')
            ->where('access_level', '!=', 'puadmin');
        $this->applyLocationScopeFromUuid($members, $uuid);


         return datatables()->eloquent($members)

                ->filterColumn('name', function ($query, $keyword) {
                    $query->where(function ($nameQuery) use ($keyword) {
                        $nameQuery->where('firstname', 'like', "%{$keyword}%")
                            ->orWhere('lastname', 'like', "%{$keyword}%");
                    });
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

             ->addColumn('Voter_status', function($member) {
                return ucfirst($member->validVoter);
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
                         'National ICT Director',
                         'Regional ICT Director',
                         'State ICT Director',
                         'Senatorial ICT Director',
                         'Federal Constituency ICT Director',
                         'Federal ICT Director',
                         'LGA ICT Director',
                         'Ward ICT Director',
                         'PU ICT Director'
                     ]) || $profileData->access_level == 'superadmin'
                 ) {
                     $actionButtons .= '
                         <a href="' . route($profileData->access_level.'.member.edit', $member->uuid) . '">
                             <button class="btn btn-info btn-sm">
                                 <i class="fas fa-pencil-alt"></i> Edit
                             </button>
                         </a>
                     ';
                 }

                 $actionButtons .= $this->memberDeleteButton($member);
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
        $members = $this->scopedMembersQuery()->where('access_level', 'user');
        $this->applyLocationScopeFromUuid($members, $uuid);


         return datatables()->eloquent($members)

                ->filterColumn('name', function ($query, $keyword) {
                    $query->where(function ($nameQuery) use ($keyword) {
                        $nameQuery->where('firstname', 'like', "%{$keyword}%")
                            ->orWhere('lastname', 'like', "%{$keyword}%");
                    });
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

                ->addColumn('Voter_status', function($member) {
                    return ucfirst($member->validVoter);
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
                         'National ICT Director',
                         'Regional ICT Director',
                         'State ICT Director',
                         'Senatorial ICT Director',
                         'Federal Constituency ICT Director',
                         'Federal ICT Director',
                         'LGA ICT Director',
                         'Ward ICT Director',
                         'PU ICT Director'
                     ]) || $profileData->access_level == 'superadmin'
                 ) {
                     $actionButtons .= '
                         <a href="' . route($profileData->access_level.'.member.edit', $member->uuid) . '">
                             <button class="btn btn-info btn-sm">
                                 <i class="fas fa-pencil-alt"></i> Edit
                             </button>
                         </a>
                     ';
                 }

                 $actionButtons .= $this->memberDeleteButton($member);
            return $actionButtons;
             })
             ->rawColumns(['role', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
             ->make(true);
     }

     public function allPeopleMetricMembers(string $metric, $uuid = null)
     {
         abort_unless(array_key_exists($metric, $this->peopleMetricLabels()), 404);

         $profileData = $this->getProfileData();
         $PuLgaStateRegionCountryUUID = $uuid ?? $this->defaultPeopleMetricScopeUuid($profileData);

         $region = $state = $lga = $ward = $pu = null;

         if ($PuLgaStateRegionCountryUUID) {
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
             }
         }

         $roles = Role::all();
         $metricTitle = $this->peopleMetricLabels()[$metric];

         return view('backend.shared.member.people-metric-members', compact(
             'profileData',
             'roles',
             'region',
             'state',
             'lga',
             'ward',
             'pu',
             'PuLgaStateRegionCountryUUID',
             'metric',
             'metricTitle'
         ));
     }

     public function getPeopleMetricMembersData(string $metric, $uuid = null)
     {
         abort_unless(array_key_exists($metric, $this->peopleMetricLabels()), 404);

         $profileData = $this->getProfileData();
         $members = $this->peopleMetricQuery($metric);
         $this->applyProfileScope($members, $profileData);
         $this->applyLocationScopeFromUuid($members, $uuid);

         return datatables()->eloquent($members)
             ->filterColumn('name', function ($query, $keyword) {
                    $query->where(function ($nameQuery) use ($keyword) {
                        $nameQuery->where('firstname', 'like', "%{$keyword}%")
                            ->orWhere('lastname', 'like', "%{$keyword}%");
                    });
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
             ->addColumn('Voter_status', function ($member) {
                 return ucfirst($member->validVoter);
             })
             ->addColumn('action', function ($member) {
                 return $this->memberActionButtons($member);
             })
             ->rawColumns(['role', 'action'])
             ->make(true);
     }

     private function peopleMetricLabels(): array
     {
         return [
             'agents' => 'Polling Unit Agents',
             'eligible-voters' => 'Eligible Voters',
             'without-voter-card' => 'Without Voter Card',
             'new-today' => 'New Today',
             'new-this-week' => 'New This Week',
             'new-this-month' => 'New This Month',
         ];
     }

     private function peopleMetricQuery(string $metric): Builder
     {
         $query = User::query()->where('access_level', '!=', 'superadmin');

         return match ($metric) {
             'agents' => $query->whereHas('pollingUnitAgentAssignments', function (Builder $assignmentQuery) {
                 $assignmentQuery->approved();
             }),
             'eligible-voters' => $query->whereIn('validVoter', ['yes', 'Yes', 'YES']),
             'without-voter-card' => $query->where(function (Builder $query) {
                 $query->whereNull('validVoter')
                     ->orWhereNotIn('validVoter', ['yes', 'Yes', 'YES']);
             }),
             'new-today' => $query->where('created_at', '>=', Carbon::now()->subDay()),
             'new-this-week' => $query->where('created_at', '>=', Carbon::now()->subWeek()),
             'new-this-month' => $query->where('created_at', '>=', Carbon::now()->subMonth()),
         };
     }

     private function applyLocationScopeFromUuid(Builder $query, ?string $uuid): void
     {
         if (!$uuid) {
             return;
         }

         $scope = app(LocationScopeService::class);

         if ($pu = PollingUnit::where('uuid', $uuid)->first()) {
             $scope->applyBoundaryFilter($query, LocationScopeService::POLLING_UNIT, $pu->id, 'users', 'users');
         } elseif ($ward = Ward::where('uuid', $uuid)->first()) {
             $scope->applyBoundaryFilter($query, LocationScopeService::WARD, $ward->id, 'users', 'users');
         } elseif ($lga = LocalGovernmentArea::where('uuid', $uuid)->first()) {
             $scope->applyBoundaryFilter($query, LocationScopeService::LGA, $lga->id, 'users', 'users');
         } elseif ($federalConstituency = FederalConstituency::where('uuid', $uuid)->first()) {
             $scope->applyBoundaryFilter($query, LocationScopeService::FEDERAL, $federalConstituency->id, 'users', 'users');
         } elseif ($senatorialDistrict = SenatorialDistrict::where('uuid', $uuid)->first()) {
             $scope->applyBoundaryFilter($query, LocationScopeService::SENATORIAL, $senatorialDistrict->id, 'users', 'users');
         } elseif ($state = State::where('uuid', $uuid)->first()) {
             $scope->applyBoundaryFilter($query, LocationScopeService::STATE, $state->id, 'users', 'users');
         } elseif ($region = Region::where('uuid', $uuid)->first()) {
             $scope->applyBoundaryFilter($query, LocationScopeService::REGION, $region->id, 'users', 'users');
         } elseif ($country = Country::where('uuid', $uuid)->first()) {
             $query->where('country_id', $country->id);
         } else {
             // Never turn a malformed or stale scoped link into an unfiltered list.
             $query->whereRaw('1 = 0');
         }
     }

     private function defaultPeopleMetricScopeUuid(User $profileData): ?string
     {
         return match ($profileData->access_level) {
             'regionaladmin' => optional($profileData->region)->uuid,
             'stateadmin' => optional($profileData->state)->uuid,
             'senatorialadmin' => optional($profileData->senatorialDistrict)->uuid,
             'federaladmin' => optional($profileData->federalConstituency)->uuid,
             'lgaadmin' => optional($profileData->lga)->uuid,
             'wardadmin' => optional($profileData->ward)->uuid,
             'puadmin' => optional($profileData->pollingUnit)->uuid,
             default => $this->defaultCountryUuid(),
         };
     }

     private function defaultCountryUuid(): ?string
     {
         $Setting = SystemSetting::find(1);
         $countryName = $Setting?->system_country;

         return $countryName ? optional(Country::where('name', $countryName)->first())->uuid : null;
     }

     private function applyProfileScope(Builder $query, User $profileData): void
     {
         app(LocationScopeService::class)->applyScope($query, $profileData, 'users', 'users');
         $this->enforceParentScopeConsistency($query, $profileData);
     }

     private function memberActionButtons(User $member): string
     {
         $profileData = $this->getProfileData();

         if (!$this->canAccessMember($profileData, $member)) {
             return '';
         }

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

         $actionButtons = '
             <a href="' . route($profileData->access_level.'.member.view', $member->uuid) . '">
                 <button class="btn btn-primary btn-sm">
                     <i class="fas fa-eye"></i> Show
                 </button>
             </a>
             ' . $button;

         if ($this->canEditMember($profileData, $member)) {
             $actionButtons .= '
                 <a href="' . route($profileData->access_level.'.member.edit', $member->uuid) . '">
                     <button class="btn btn-info btn-sm">
                         <i class="fas fa-pencil-alt"></i> Edit
                     </button>
                 </a>
             ';
         }

         $actionButtons .= $this->memberDeleteButton($member);
            return $actionButtons;
     }

     public function addMember()
     {
         $profileData = $this->getProfileData();
         $pageTitle = 'Add Member';
         $locationOptions = $this->locationForms()->options($profileData);
         $locationForm = $this->locationForms()->context($profileData);
         $countries = Country::all();
         $regions = $locationOptions['regions'];
         $states = $locationOptions['states'];
         $lgas = $locationOptions['lgas'];
         $wards = $locationOptions['wards'];
         $pus = $locationOptions['pollingUnits'];
         $pollingUnits = $locationOptions['pollingUnits'];
         $senatorialDistricts = $locationOptions['senatorialDistricts'];
         $federalConstituencies = $locationOptions['federalConstituencies'];


            $roles = $this->assignableRoles($profileData);
            $assignableAccessLevels = $this->assignableAccessLevels($profileData);


         return view('backend.'.$profileData->access_level.'.member.add', compact( 'profileData', 'pageTitle', 'roles', 'assignableAccessLevels', 'countries', 'regions', 'states', 'lgas', 'wards', 'pus', 'pollingUnits', 'senatorialDistricts', 'federalConstituencies', 'locationForm'));
     }

     public function importMembers()
     {
         $profileData = $this->getProfileData();
         $pageTitle = 'Import Members';
         $assignableAccessLevels = $this->assignableAccessLevels($profileData);
         $roles = $this->assignableRoles($profileData);
         $locationForm = $this->locationForms()->context($profileData);
         $locationOptions = $this->locationForms()->options($profileData);
         $regions = $locationOptions['regions'];
         $states = $locationOptions['states'];

         return view('backend.shared.member.import', compact('profileData', 'pageTitle', 'assignableAccessLevels', 'roles', 'locationForm', 'regions', 'states'));
     }

     public function downloadMemberImportTemplate(MemberImportService $importer)
     {
         $profileData = $this->getProfileData();
         $filename = 'campaign-manager-member-import-template.csv';

         return response()->streamDownload(function () use ($importer, $profileData): void {
             $out = fopen('php://output', 'wb');
             foreach ($importer->templateRows($profileData) as $row) {
                 fputcsv($out, $row);
             }
             fclose($out);
         }, $filename, [
             'Content-Type' => 'text/csv',
         ]);
     }

     public function storeMemberImport(Request $request, MemberImportService $importer)
     {
         $profileData = $this->getProfileData();

         $validated = $request->validate([
             'import_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
             'region_id' => 'nullable|integer|exists:regions,id',
             'state_id' => 'nullable|integer|exists:states,id',
             'access_level' => 'required|string|max:40',
             'role_id' => 'required|integer|exists:roles,id',
             'default_password' => 'nullable|string|min:8|max:128',
         ]);

         $batch = $importer->import($profileData, $validated['import_file'], $validated);
         $pageTitle = 'Member Import Report';

         return view('backend.shared.member.import-result', compact('profileData', 'pageTitle', 'batch'));
     }

     public function storeMember(Request $request)
     {
        $profileData = $this->getProfileData();
         $request->merge($this->locationForms()->mergeFixedPayload($request->all()));
         $request->validate([
             'username' => 'required|string|max:255|unique:users',
             'firstname' => 'required|string',
             'lastname' => 'required|string',
             'email' => 'required|email|unique:users,email',
             'password' => 'required|confirmed|min:6',
             'access_level' => 'required|string',
             'roles' => 'nullable|integer|exists:roles,id',
             'gender' => 'required|in:male,female',
         ] + $this->boundaryValidationRules());

          // 👇 determine if this new user should be forced into credentials update
    $requiresUpdate = false;

    if (str_contains($request->email, '@example.com') || $request->password === 'password') {
        $requiresUpdate = true;
    }

         $access_level = $request->access_level;

         $requestedAccessRank = $this->accessLevelRank($access_level);
         $profileAccessRank = $this->accessLevelRank($profileData->access_level);

         if ($requestedAccessRank === null || $profileAccessRank === null) {
             return redirect()->back()->with($this->invalidMemberAccessNotification());
         }

         if (!$this->packageRoles()->canAssignAccessLevel($profileData, $access_level)) {
             throw ValidationException::withMessages([
                 'access_level' => 'The selected access level is not available for this licensed campaign package.',
             ]);
         }

         if ($profileAccessRank > $requestedAccessRank) {
             return redirect()->back()->with($this->unauthorizedMemberAccessNotification('assign this access level to'));
         }

         $role = $request->filled('roles')
             ? Role::find($request->integer('roles'))
             : $this->packageRoles()->rolesForAccessLevel($profileData, $access_level)->first();

         if (!$role || !$this->canAssignRole($profileData, $role, $access_level)) {
             throw ValidationException::withMessages([
                 'roles' => 'Select a role that belongs to the requested access level and campaign package.',
             ]);
         }

         $request->validate($this->memberLocationRules($access_level, 'pu_id'));

         $boundaryAssignments = $this->resolveBoundaryAssignments($request, $access_level);

         if (!is_array($boundaryAssignments)) {
             return $boundaryAssignments;
         }

         $this->validateLicensedPayload([
             'state_id' => $request->state_id,
             'senatorial_district_id' => $boundaryAssignments['senatorial_district_id'],
             'federal_constituency_id' => $boundaryAssignments['federal_constituency_id'],
             'lga_id' => $request->lga_id,
             'ward_id' => $request->ward_id,
             'polling_unit_id' => $request->pu_id,
         ]);

         $user = DB::transaction(function () use ($request, $access_level, $boundaryAssignments, $requiresUpdate, $role) {
             $user = User::create([
                 'uuid' => Str::uuid()->toString(),
                 'username' => $request->username,
                 'firstname' => $request->firstname,
                 'lastname' => $request->lastname,
                 'email' => $request->email,
                 'password' => Hash::make($request->password),
                 'access_level' => $access_level,
                 'gender' => $request->gender,
                 'country_id' => $request->country_id,
                 'region_id' => $request->region_id,
                 'state_id' => $request->state_id,
                 'senatorial_district_id' => $boundaryAssignments['senatorial_district_id'],
                 'federal_constituency_id' => $boundaryAssignments['federal_constituency_id'],
                 'lga_id' => $request->lga_id,
                 'ward_id' => $request->ward_id,
                 'polling_unit_id' => $request->pu_id,
                 'requires_update' => $requiresUpdate,
             ]);

             $user->syncRoles([$role->name]);

             return $user;
         });

         $notification = [
             'message' => 'Member Added Successfully',
             'alert-type' => 'success'
         ];
         return redirect()->route($profileData->access_level.'.members')->with($notification);
     }

     public function editMember($uuid)
     {

         $pageTitle = 'Edit Member';

        // Fetch the logged-in user's profile data
        $profileData = $this->getProfileData();

        // Fetch the member's data with roles
        $member = User::with('roles')->where('uuid', $uuid)->first();

        if (!$member || $this->accessLevelRank($profileData->access_level) === null || $this->accessLevelRank($member->access_level) === null) {
            return redirect()->back()->with($this->invalidMemberAccessNotification());
        }

        if (!$this->canEditMember($profileData, $member)) {
            return redirect()->back()->with($this->unauthorizedMemberAccessNotification('edit'));
        }
         // $roles = Role::all();  // Assuming a member can have multiple roles
         $countries = Country::all();
         $locationOptions = $this->locationForms()->options($profileData);
         $locationForm = $this->locationForms()->context($profileData, $member, $member->access_level);
         $regions = $locationOptions['regions'];
         $states = $locationOptions['states'];
         $lgas = $locationOptions['lgas'];
         $wards = $locationOptions['wards'];
         $pollingUnits = $locationOptions['pollingUnits'];
         $senatorialDistricts = $locationOptions['senatorialDistricts'];
         $federalConstituencies = $locationOptions['federalConstituencies'];
         $religions = Religion::all();
         $ageGrades = AgeGrade::all();


        $roles = $this->packageRoles()->rolesForAccessLevel($profileData, $member->access_level, $member);
        $assignableAccessLevels = $this->assignableAccessLevels($profileData);
        $invalidPackageRoleWarning = $member->roles
            ->map(fn (Role $role) => $this->packageRoles()->invalidRoleMessage($role, $member->access_level))
            ->filter()
            ->first();



         return view('backend.'.$profileData->access_level.'.member.member', compact(
             'profileData', 'pageTitle', 'member', 'roles', 'countries',
             'assignableAccessLevels', 'invalidPackageRoleWarning', 'regions', 'states', 'lgas', 'wards', 'pollingUnits', 'senatorialDistricts', 'federalConstituencies', 'religions', 'ageGrades', 'locationForm'
         ));
     }


     public function updateMember(Request $request) // Add $id to match the route
     {

         $profileData = $this->getProfileData();
         // Find the member by ID and update
         $member = User::findOrFail($request->id);
         $request->merge($this->locationForms()->mergeFixedPayload($request->all()));

         if ($this->accessLevelRank($profileData->access_level) === null || $this->accessLevelRank($member->access_level) === null) {
             return redirect()->back()->with($this->invalidMemberAccessNotification());
         }

         if (!$this->canEditMember($profileData, $member)) {
             return redirect()->back()->with($this->unauthorizedMemberAccessNotification('edit'));
         }

         // Validation rules
         $requestedAccessLevel = $request->access_level ?: $member->access_level;
         $validatedData = $request->validate([
             'firstname' => 'required|string|max:255',
             'lastname' => 'required|string|max:255',
             'email' => 'required|email|max:255',
             'phone' => 'required|string|max:15|unique:users,phone,' .$member->id,
             'validvoter' => 'required|in:yes,no',
             'vin' => 'nullable|string|max:255',
             'age_grade_id' => 'required|exists:age_grades,id',
             'gender' => 'required|in:male,female',
             'access_level' => 'nullable|string',
             'religion_id' => 'required|exists:religions,id',
             'address' => 'nullable|string|max:255',
             'occupation' => 'nullable|string|max:255',
             'qualification' => 'nullable|string|max:255',
             'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
         ] + $this->memberLocationRules($requestedAccessLevel, 'polling_unit_id') + $this->boundaryValidationRules());
         $requestedAccessRank = $this->accessLevelRank($requestedAccessLevel);
         $profileAccessRank = $this->accessLevelRank($profileData->access_level);

         if ($requestedAccessRank === null || $profileAccessRank === null) {
             return redirect()->back()->with($this->invalidMemberAccessNotification());
         }

         if (!$this->packageRoles()->canAssignAccessLevel($profileData, $requestedAccessLevel)) {
             throw ValidationException::withMessages([
                 'access_level' => 'The selected access level is not available for this licensed campaign package.',
             ]);
         }

         if ($profileAccessRank > $requestedAccessRank) {
             return redirect()->back()->with($this->unauthorizedMemberAccessNotification('assign this access level to'));
         }

         $boundaryAssignments = $this->resolveBoundaryAssignments($request, $requestedAccessLevel);

         if (!is_array($boundaryAssignments)) {
             return $boundaryAssignments;
         }

         $this->validateLicensedPayload([
             'state_id' => $request->state_id,
             'senatorial_district_id' => $boundaryAssignments['senatorial_district_id'],
             'federal_constituency_id' => $boundaryAssignments['federal_constituency_id'],
             'lga_id' => $request->lga_id,
             'ward_id' => $request->ward_id,
             'polling_unit_id' => $request->polling_unit_id,
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
             'senatorial_district_id' => $boundaryAssignments['senatorial_district_id'],
             'federal_constituency_id' => $boundaryAssignments['federal_constituency_id'],
             'lga_id' => $request->lga_id,
             'ward_id' => $request->ward_id,
             'polling_unit_id' => $request->polling_unit_id,
             'access_level' => $requestedAccessLevel,
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
         $role = Role::find($roleId);

             if ($role && $this->canAssignRole($profileData, $role, $requestedAccessLevel)) {
                 // Sync the role to the member using its name
                 $member->syncRoles([$role->name]);
             } else {
                 // Handle the case where the role is not found
                 $notification = [
                     'message' => 'Invalid role selected.',
                     'alert-type' => 'error'
                 ];

                 throw ValidationException::withMessages([
                     'roles' => 'The selected role is not available for this access level or campaign package.',
                 ]);
             }
             } else {
                 $defaultRole = $this->packageRoles()->defaultRole($profileData);

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

         if (!$member || $this->accessLevelRank($profileData->access_level) === null || $this->accessLevelRank($member->access_level) === null) {
             return redirect()->back()->with($this->invalidMemberAccessNotification());
         }

         if (!$this->canAccessMember($profileData, $member)) {
             return redirect()->back()->with($this->unauthorizedMemberAccessNotification('view'));
         }


         $roles = Role::all();  // Assuming a member can have multiple roles
         $countries = Country::all();
         $locationOptions = $this->locationForms()->options($profileData);
         $locationForm = $this->locationForms()->context($profileData, $member, $member->access_level);
         $regions = $locationOptions['regions'];
         $states = $locationOptions['states'];
         $lgas = $locationOptions['lgas'];
         $wards = $locationOptions['wards'];
         $pollingUnits = $locationOptions['pollingUnits'];
         $religions = Religion::all();
         $ageGrades = AgeGrade::all();

         return view('backend.'.$profileData->access_level.'.member.show_member', compact(
             'profileData', 'pageTitle', 'member', 'roles', 'countries',
             'regions', 'states', 'lgas', 'wards', 'pollingUnits', 'religions', 'ageGrades', 'locationForm'
         ));

     }

     public function suspendMember($uuid){
         $profileData = $this->getProfileData();
         $member = User::where('uuid', $uuid)->first();

         if (!$member || $this->accessLevelRank($profileData->access_level) === null || $this->accessLevelRank($member->access_level) === null) {
             return redirect()->back()->with($this->invalidMemberAccessNotification());
         }

         if (!$this->canAccessMember($profileData, $member)) {
             return redirect()->back()->with($this->unauthorizedMemberAccessNotification('suspend'));
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
        abort_unless($this->canDeleteUsers($profileData), 403);
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



         if ($this->accessLevelRank($profileData->access_level) === null || $this->accessLevelRank($member->access_level) === null) {
             return redirect()->back()->with($this->invalidMemberAccessNotification());
         }

         if (!$this->canAccessMember($profileData, $member)) {
             return redirect()->back()->with($this->unauthorizedMemberAccessNotification('delete'));
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
         $regions = $this->locationForms()->options($profileData)['regions'];
         $regions->loadCount(['users as members_count' => function ($query) use ($profileData) {
             $this->applyProfileScope($query, $profileData);
             $query->where('access_level', '!=', 'superadmin');
         }]);

         return view('backend.'.$profileData->access_level.'.member.membersByRegion', compact('regions', 'profileData'));
     }


    public function ViewMembersByRegion($uuid)
        {
            $profileData = $this->getProfileData();

            // Find region by UUID or return a 404 error if not found
            $region = Region::where('uuid', $uuid)->firstOrFail();

            $members = $this->scopedMembersQuery($profileData)
                ->where('region_id', $region->id)
                ->get();

            return view('backend.' . $profileData->access_level . '.member.viewMembersByRegion', compact('region', 'members', 'profileData'));
    }



    public function getRegionMembersData($uuid)
        {

            $region = Region::where('uuid', $uuid)->first();

            $members = $this->scopedMembersQuery()
                            ->where('region_id', $region->id);

                            return datatables()->eloquent($members)

                            ->filterColumn('name', function ($query, $keyword) {
                                    $query->where(function ($nameQuery) use ($keyword) {
                                        $nameQuery->where('firstname', 'like', "%{$keyword}%")
                                            ->orWhere('lastname', 'like', "%{$keyword}%");
                                    });
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
                            'National ICT Director',
                            'Regional ICT Director',
                            'State ICT Director',
                            'LGA ICT Director',
                            'Ward ICT Director',
                            'PU ICT Director'
                        ]) || $profileData->access_level == 'superadmin'
                    ) {
                        $actionButtons .= '
                            <a href="' . route($profileData->access_level.'.member.edit', $member->uuid) . '">
                                <button class="btn btn-info btn-sm">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                            </a>
                        ';
                    }

                    $actionButtons .= $this->memberDeleteButton($member);
            return $actionButtons;
                })
                ->rawColumns(['role', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
                ->make(true);
    }


    // *** MEMBERS BY STATES ***
    public function membersByStates($uuid = null)
    {
        $profileData = $this->getProfileData();
        $states = $this->locationForms()->options($profileData)['states'];

        if ($uuid) {
            $region = Region::where('uuid', $uuid)->first();

            if ($region) {
                $states = $states->where('region_id', $region->id)->values();
            } else {
                $country = Country::where('uuid', $uuid)->first();

                if ($country) {
                    $regions = Region::where('country_id', $country->id)->pluck('id');
                    $states = $states->whereIn('region_id', $regions)->values();
                } else {
                    $states = collect();
                }
            }
        }

        $states->loadCount(['users as members_count' => function ($query) use ($profileData) {
            $this->applyProfileScope($query, $profileData);
            $query->where('access_level', '!=', 'superadmin');
        }]);

        return view('backend.'.$profileData->access_level.'.member.membersByState', compact('states', 'profileData'));
    }


    public function ViewMembersByState($uuid)
    {
        $profileData = $this->getProfileData();

        // Find state by UUID or return a 404 error if not found
        $state = State::where('uuid', $uuid)->firstOrFail();

        $members = $this->scopedMembersQuery($profileData)
            ->where('state_id', $state->id)
            ->get();

        return view('backend.' . $profileData->access_level . '.member.viewMembersByState', compact('state', 'members', 'profileData'));
    }

    public function getStateMembersData($uuid)
    {

        $state = State::where('uuid', $uuid)->first();
        $members = $this->scopedMembersQuery()
                        ->where('state_id', $state->id);

          return datatables()->eloquent($members)

            ->filterColumn('name', function ($query, $keyword) {
                    $query->where(function ($nameQuery) use ($keyword) {
                        $nameQuery->where('firstname', 'like', "%{$keyword}%")
                            ->orWhere('lastname', 'like', "%{$keyword}%");
                    });
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
            ->addColumn('Voter_status', function($member) {
                return ucfirst($member->validVoter);
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
                        'National ICT Director',
                        'Regional ICT Director',
                        'State ICT Director',
                        'LGA ICT Director',
                        'Ward ICT Director',
                        'PU ICT Director'
                    ]) || $profileData->access_level == 'superadmin'
                ) {
                    $actionButtons .= '
                        <a href="' . route($profileData->access_level.'.member.edit', $member->uuid) . '">
                            <button class="btn btn-info btn-sm">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </a>
                    ';
                }

                $actionButtons .= $this->memberDeleteButton($member);
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
                $senatorialDistrict = SenatorialDistrict::where('uuid', $uuid)->first();

                if ($senatorialDistrict) {
                    $lgas = LocalGovernmentArea::where('senatorial_district_id', $senatorialDistrict->id)
                        ->orderBy('name', 'asc')
                        ->get();
                } else {
                    $federalConstituency = FederalConstituency::where('uuid', $uuid)->first();

                    if ($federalConstituency) {
                        $lgas = LocalGovernmentArea::where('federal_constituency_id', $federalConstituency->id)
                            ->orderBy('name', 'asc')
                            ->get();
                    } else {
                        // If not a boundary, check for region
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
                }
            }
        } else {
            // If no UUID is provided, fetch all LGAs
            $lgas = LocalGovernmentArea::orderBy('name', 'asc')->get();
        }

        $lgas->loadCount(['users as members_count' => function ($query) {
            $query->where('access_level', '!=', 'superadmin');
        }]);

        return view('backend.' . $profileData->access_level . '.member.membersByLga', compact('lgas', 'profileData'));
    }

    public function viewMembersByLocalGovernments($uuid)
    {
        $profileData = $this->getProfileData();

        // Find state by UUID or return a 404 error if not found
        $lga = LocalGovernmentArea::where('uuid', $uuid)->firstOrFail();

        $members = $this->scopedMembersQuery($profileData)
            ->where('lga_id', $lga->id)
            ->get();

        return view('backend.' . $profileData->access_level . '.member.viewMembersByLga', compact('lga', 'members', 'profileData'));
    }

    public function getLgaMembersData($uuid)
    {

        $lga = LocalGovernmentArea::where('uuid', $uuid)->first();

        $members = $this->scopedMembersQuery()
                        ->where('lga_id', $lga->id);

                        return datatables()->eloquent($members)

                        ->filterColumn('name', function ($query, $keyword) {
                                $query->where(function ($nameQuery) use ($keyword) {
                                    $nameQuery->where('firstname', 'like', "%{$keyword}%")
                                        ->orWhere('lastname', 'like', "%{$keyword}%");
                                });
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

            ->addColumn('Voter_status', function($member) {
                return ucfirst($member->validVoter);
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
                        'National ICT Director',
                        'Regional ICT Director',
                        'State ICT Director',
                        'LGA ICT Director',
                        'Ward ICT Director',
                        'PU ICT Director'
                    ]) || $profileData->access_level == 'superadmin'
                ) {
                    $actionButtons .= '
                        <a href="' . route($profileData->access_level.'.member.edit', $member->uuid) . '">
                            <button class="btn btn-info btn-sm">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </a>
                    ';
                }

                $actionButtons .= $this->memberDeleteButton($member);
            return $actionButtons;
            })
            ->rawColumns(['role', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
            ->make(true);
    }


    public function membersBySenatorialDistricts($uuid = null)
    {
        $profileData = $this->getProfileData();
        $districts = SenatorialDistrict::with('state')
            ->withCount(['users as members_count' => function ($query) {
                $query->where('access_level', '!=', 'superadmin');
            }])
            ->orderBy('name', 'asc');

        if ($uuid) {
            if ($state = State::where('uuid', $uuid)->first()) {
                $districts->where('state_id', $state->id);
            } elseif ($region = Region::where('uuid', $uuid)->first()) {
                $stateIds = State::where('region_id', $region->id)->pluck('id');
                $districts->whereIn('state_id', $stateIds);
            } elseif ($country = Country::where('uuid', $uuid)->first()) {
                $regionIds = Region::where('country_id', $country->id)->pluck('id');
                $stateIds = State::whereIn('region_id', $regionIds)->pluck('id');
                $districts->whereIn('state_id', $stateIds);
            } elseif ($district = SenatorialDistrict::where('uuid', $uuid)->first()) {
                $districts->where('id', $district->id);
            }
        }

        $view = 'backend.' . $profileData->access_level . '.member.membersBySenatorialDistrict';
        if (! View::exists($view)) {
            $view = 'backend.shared.member.membersBySenatorialDistrict';
        }

        return view($view, [
            'districts' => $districts->get(),
            'profileData' => $profileData,
        ]);
    }


    public function membersByFederalConstituencies($uuid = null)
    {
        $profileData = $this->getProfileData();
        $constituencies = FederalConstituency::with(['state', 'senatorialDistrict'])
            ->withCount(['users as members_count' => function ($query) {
                $query->where('access_level', '!=', 'superadmin');
            }])
            ->orderBy('name', 'asc');

        if ($uuid) {
            if ($senatorialDistrict = SenatorialDistrict::where('uuid', $uuid)->first()) {
                $constituencies->where('senatorial_district_id', $senatorialDistrict->id);
            } elseif ($state = State::where('uuid', $uuid)->first()) {
                $constituencies->where('state_id', $state->id);
            } elseif ($region = Region::where('uuid', $uuid)->first()) {
                $stateIds = State::where('region_id', $region->id)->pluck('id');
                $constituencies->whereIn('state_id', $stateIds);
            } elseif ($country = Country::where('uuid', $uuid)->first()) {
                $regionIds = Region::where('country_id', $country->id)->pluck('id');
                $stateIds = State::whereIn('region_id', $regionIds)->pluck('id');
                $constituencies->whereIn('state_id', $stateIds);
            } elseif ($constituency = FederalConstituency::where('uuid', $uuid)->first()) {
                $constituencies->where('id', $constituency->id);
            }
        }

        $view = 'backend.' . $profileData->access_level . '.member.membersByFederalConstituency';
        if (! View::exists($view)) {
            $view = 'backend.shared.member.membersByFederalConstituency';
        }

        return view($view, [
            'constituencies' => $constituencies->get(),
            'profileData' => $profileData,
        ]);
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

        $members = $this->scopedMembersQuery($profileData)
            ->where('ward_id', $ward->id)
            ->get();

        return view('backend.' . $profileData->access_level . '.member.viewMembersByWard', compact('ward', 'members', 'profileData'));


    }


    public function getWardMembersData($uuid)
    {

        $ward = Ward::where('uuid', $uuid)->first();

        $members = $this->scopedMembersQuery()
                        ->where('ward_id', $ward->id);

                        return datatables()->eloquent($members)

                        ->filterColumn('name', function ($query, $keyword) {
                                $query->where(function ($nameQuery) use ($keyword) {
                                    $nameQuery->where('firstname', 'like', "%{$keyword}%")
                                        ->orWhere('lastname', 'like', "%{$keyword}%");
                                });
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

            ->addColumn('Voter_status', function($member) {
                return ucfirst($member->validVoter);
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
                        'National ICT Director',
                        'Regional ICT Director',
                        'State ICT Director',
                        'LGA ICT Director',
                        'Ward ICT Director',
                        'PU ICT Director'
                    ]) || $profileData->access_level == 'superadmin'
                ) {
                    $actionButtons .= '
                        <a href="' . route($profileData->access_level.'.member.edit', $member->uuid) . '">
                            <button class="btn btn-info btn-sm">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </a>
                    ';
                }

                $actionButtons .= $this->memberDeleteButton($member);
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

        $members = $this->scopedMembersQuery($profileData)
            ->where('polling_unit_id', $pu->id)
            ->get();

        return view('backend.' . $profileData->access_level . '.member.viewMembersByPu', compact('pu', 'members', 'profileData'));


    }


    public function getPuMembersData($uuid)
    {

        $pu = PollingUnit::where('uuid', $uuid)->first();

        $members = $this->scopedMembersQuery()
                        ->where('polling_unit_id', $pu->id);

                        return datatables()->eloquent($members)

                        ->filterColumn('name', function ($query, $keyword) {
                                $query->where(function ($nameQuery) use ($keyword) {
                                    $nameQuery->where('firstname', 'like', "%{$keyword}%")
                                        ->orWhere('lastname', 'like', "%{$keyword}%");
                                });
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

            ->addColumn('Voter_status', function($member) {
                return ucfirst($member->validVoter);
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
                        'National ICT Director',
                        'Regional ICT Director',
                        'State ICT Director',
                        'LGA ICT Director',
                        'Ward ICT Director',
                        'PU ICT Director'
                    ]) || $profileData->access_level == 'superadmin'
                ) {
                    $actionButtons .= '
                        <a href="' . route($profileData->access_level.'.member.edit', $member->uuid) . '">
                            <button class="btn btn-info btn-sm">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </a>
                    ';
                }

                $actionButtons .= $this->memberDeleteButton($member);
            return $actionButtons;
            })
            ->rawColumns(['role', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
            ->make(true);
    }


    //Members by Vote Eligibility in PU
     public function puMembersByVoteEligibility($uuid = null){
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
                return view('backend.' . $profileData->access_level . '.member.puMembersByVoteEligibility', compact('WardLgaStateRegionCountryUUID', 'profileData'));
     }


    public function viewPuMembersByVoteEligibility($uuid){
        $profileData = $this->getProfileData();

        // Find state by UUID or return a 404 error if not found
        $pu = PollingUnit::where('uuid', $uuid)->firstOrFail();

        $members = $this->scopedMembersQuery($profileData)
            ->where('polling_unit_id', $pu->id)
            ->where('validVoter', 'yes')
            ->get();

        return view('backend.' . $profileData->access_level . '.member.viewMembersByPu', compact('pu', 'members', 'profileData'));
	}



     public function viewPuMembersByVoteIneligibility($uuid){
        $profileData = $this->getProfileData();

        // Find state by UUID or return a 404 error if not found
        $pu = PollingUnit::where('uuid', $uuid)->firstOrFail();

        $members = $this->scopedMembersQuery($profileData)
            ->where('polling_unit_id', $pu->id)
            ->where('validVoter', 'no')
            ->get();

        return view('backend.' . $profileData->access_level . '.member.viewMembersByPu', compact('pu', 'members', 'profileData'));
	 }

    public function getEligiblePuMembersData($uuid)
    {
        $pu = PollingUnit::where('uuid', $uuid)->firstOrFail();

        $members = $this->scopedMembersQuery()
            ->where('polling_unit_id', $pu->id)
            ->where('validVoter', 'yes'); // ✅ filter eligibility

        return datatables()->eloquent($members)
            ->filterColumn('name', function ($query, $keyword) {
                $query->where(function ($nameQuery) use ($keyword) {
                    $nameQuery->where('firstname', 'like', "%{$keyword}%")
                        ->orWhere('lastname', 'like', "%{$keyword}%");
                });
            })
            ->addIndexColumn()
            ->addColumn('name', fn($member) => $member->firstname . ' ' . $member->lastname)
            ->addColumn('access_level', fn($member) => $member->access_level)
            ->addColumn('role', function ($member) {
                return $member->roles->map(fn($role) =>
                    '<span class="badge badge-pill bg-secondary">' . $role->name . '</span>'
                )->implode(' ');
            })
            ->addColumn('action', function ($member) {
                return $this->memberActionButtons($member);
            })
            ->rawColumns(['role', 'action'])
            ->make(true);
    }


    // Export Members to Excel
    public function exportMembers(Request $request)
    {
        $selectedHeaders = $request->input('headers', []);

        $allowedHeaders = [
            'firstname',
            'lastname',
            'email',
            'phone',
            'gender',
            'validVoter',
            'vin',
            'region_id',
            'state_id',
            'lga_id',
            'ward_id',
            'polling_unit_id',
            'age_grade_id',
            'religion_id',
            'address',
            'occupation',
            'qualification',
            'access_level',
            'created_at',
        ];

        $safeHeaders = array_values(array_intersect($allowedHeaders, (array) $selectedHeaders));

        if (empty($safeHeaders)) {
            return back()->with('error', 'Please select at least one column to export.');
        }

        // Get filtered members (same logic you use for DataTables)
        $membersQuery = $this->filterMemberByAccessLevel()->get($safeHeaders);

        return Excel::download(new MembersExport($membersQuery, $safeHeaders), 'members.xlsx');
    }

}
