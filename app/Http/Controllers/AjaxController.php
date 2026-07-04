<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Election;
use App\Models\FederalConstituency;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\Request;
use App\Models\State;
use App\Models\LGA;
use App\Models\LocalGovernmentArea;
use App\Models\Ward;
use App\Models\PollingUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Services\LocationScopeService;
use App\Services\StructuralLocationAccessService;
use App\Services\CampaignPackageRoleService;
use App\Services\LicensedScopeQueryService;

class AjaxController extends Controller
{

    private function getProfileData()
    {
        $id = Auth::user()->id;
        return User::find($id);
    }

    private function structuralAccess(): StructuralLocationAccessService
    {
        return app(StructuralLocationAccessService::class);
    }

    private function applyLocationOptionScope($query, string $subject): void
    {
        $actor = $this->getProfileData();

        // New members do not have a structural polling-unit boundary until
        // onboarding is complete. Their choices must come from the licensed
        // installation boundary instead of an as-yet nonexistent user scope.
        if ($actor?->access_level === 'user' && !$actor->polling_unit_id) {
            app(LicensedScopeQueryService::class)->applyToSubject($query, $subject);

            return;
        }

        $this->structuralAccess()->applyScope($query, $actor, $subject);
    }

    private function canDeleteStructuralRecords(): bool
    {
        return $this->structuralAccess()->canCreateOrDelete($this->getProfileData());
    }

    public function getRegions()
    {
        $query = Region::query()->orderBy('name');
        $this->applyLocationOptionScope($query, 'regions');

        return response()->json([
            'regions' => $query->get(['name', 'id']),
        ]);
    }

    public function ajaxGetStates(Request $request)
    {
        $query = State::where("region_id", $request->region_id);
        $this->applyLocationOptionScope($query, 'states');
        $state['states'] = $query->get(["name", "id"]);

        return response()->json($state);
    }


    public function ajaxGetLgas(Request $request)
    {
        $query = LocalGovernmentArea::where("state_id", $request->state_id);
        $this->applyLocationOptionScope($query, 'local_government_areas');
        $lga['lgas'] = $query->get(["name", "id"]);
        return response()->json($lga);
    }

    public function ajaxGetWards(Request $request)
    {
        $query = Ward::where("lga_id", $request->lga_id);
        $this->applyLocationOptionScope($query, 'wards');
        $ward['wards'] = $query->get(["name", "id"]);
        return response()->json($ward);
    }

    public function ajaxGetPollingUnits(Request $request)
    {
        $query = PollingUnit::where("ward_id", $request->ward_id);
        $this->applyLocationOptionScope($query, 'polling_units');
        $pollingUnit['pollingUnits'] = $query->get(["name", "id"]);
        return response()->json($pollingUnit);
    }

    public function ajaxGetSenatorialDistricts(Request $request)
    {
        $query = SenatorialDistrict::where('state_id', $request->state_id)
            ->orderBy('name');
        $this->applyLocationOptionScope($query, 'senatorial_districts');
        $senatorialDistricts['senatorialDistricts'] = $query->get(['name', 'id']);

        return response()->json($senatorialDistricts);
    }

    public function ajaxGetFederalConstituenciesByState(Request $request)
    {
        $query = FederalConstituency::where('state_id', $request->state_id)
            ->orderBy('name');
        $this->applyLocationOptionScope($query, 'federal_constituencies');
        $federalConstituencies['federalConstituencies'] = $query->get(['name', 'id']);

        return response()->json($federalConstituencies);
    }

    public function ajaxGetFederalConstituenciesBySenatorialDistrict(Request $request)
    {
        $query = FederalConstituency::where('senatorial_district_id', $request->senatorial_district_id)
            ->orderBy('name');
        $this->applyLocationOptionScope($query, 'federal_constituencies');
        $federalConstituencies['federalConstituencies'] = $query->get(['name', 'id']);

        return response()->json($federalConstituencies);
    }


    public function getRolesByAccessLevel($accessLevel)
        {
            $roles = app(CampaignPackageRoleService::class)
                ->rolesForAccessLevel($this->getProfileData(), $accessLevel)
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'group_name' => $role->group_name,
                ])
                ->values();

            return response()->json($roles);
        }


        public function  getLgaData()
        {

            $profileData = $this->getProfileData();
            $lgas = LocalGovernmentArea::with(['state.region', 'senatorialDistrict', 'federalConstituency'])->orderBy('name');
            $this->structuralAccess()->applyScope($lgas, $profileData, 'local_government_areas');

                return datatables()->eloquent($lgas)
                            ->filterColumn('name', function ($query, $keyword) {
                                $query->where('name', 'like', "%{$keyword}%");
                            })

                            ->filterColumn('state', function ($query, $keyword) {
                                $query->whereHas('state', function ($q) use ($keyword) {
                                    $q->where('name', 'like', "%{$keyword}%");
                                });
                            })
                            ->filterColumn('region', function ($query, $keyword) {
                                $query->whereHas('state.region', function ($q) use ($keyword) {
                                    $q->where('name', 'like', "%{$keyword}%");
                                });
                            })
                            ->filterColumn('senatorial_district', function ($query, $keyword) {
                                $query->whereHas('senatorialDistrict', function ($q) use ($keyword) {
                                    $q->where('name', 'like', "%{$keyword}%");
                                });
                            })
                            ->filterColumn('federal_constituency', function ($query, $keyword) {
                                $query->whereHas('federalConstituency', function ($q) use ($keyword) {
                                    $q->where('name', 'like', "%{$keyword}%");
                                });
                            })

                    ->addIndexColumn() // This will automatically add a serial number column
                    ->addColumn('name', function($lga) {
                        return $lga->name;
                    })

                    ->addColumn('state', function($lga) {
                        return optional($lga->state)->name;
                    })
                    ->addColumn('region', function($lga) {
                        return optional($lga->state->region)->name;
                    })
                    ->addColumn('senatorial_district', function($lga) {
                        return optional($lga->senatorialDistrict)->name;
                    })
                    ->addColumn('federal_constituency', function($lga) {
                        return optional($lga->federalConstituency)->name;
                    })


                    ->addColumn('action', function($lga) {
                        $profileData = $this->getProfileData();
                        $deleteButton = '';

                        if ($this->canDeleteStructuralRecords()) {
                            $deleteButton = '
                                <form action="' . route($profileData->access_level.'.location.lga.delete', $lga->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                                    ' . csrf_field() . method_field('DELETE') . '
                                    <button type="submit" class="btn btn-danger delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            ';
                        }

                        return '
                            <a href="' . route($profileData->access_level.'.location.lga.edit', $lga->uuid) . '">
                                <button class="btn btn-success ">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                            </a>
                            ' . $deleteButton . '
                        ';
                    })

                    ->rawColumns(['region', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
                    ->make(true);

        }

        public function getSenatorialDistrictData()
        {
            $profileData = $this->getProfileData();
            $districts = SenatorialDistrict::with('state')->orderBy('name');
            $this->structuralAccess()->applyScope($districts, $profileData, 'senatorial_districts');

            return datatables()->eloquent($districts)
                ->filterColumn('name', function ($query, $keyword) {
                    $query->where('name', 'like', "%{$keyword}%");
                })
                ->filterColumn('state', function ($query, $keyword) {
                    $query->whereHas('state', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addIndexColumn()
                ->addColumn('name', function($district) {
                    return $district->name;
                })
                ->addColumn('state', function($district) {
                    return optional($district->state)->name;
                })
                ->addColumn('action', function($district) {
                    $profileData = $this->getProfileData();

                    $deleteButton = '';
                    if ($this->canDeleteStructuralRecords()) {
                        $deleteButton = '
                            <form action="' . route($profileData->access_level.'.location.senatorial-district.delete', $district->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                                ' . csrf_field() . method_field('DELETE') . '
                                <button type="submit" class="btn btn-danger delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        ';
                    }

                    return '
                        <a href="' . route($profileData->access_level.'.location.senatorial-district.edit', $district->uuid) . '">
                            <button class="btn btn-success">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </a>
                        ' . $deleteButton . '
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        public function getFederalConstituencyData()
        {
            $profileData = $this->getProfileData();
            $constituencies = FederalConstituency::with(['state', 'senatorialDistrict'])->orderBy('name');
            $this->structuralAccess()->applyScope($constituencies, $profileData, 'federal_constituencies');

            return datatables()->eloquent($constituencies)
                ->filterColumn('name', function ($query, $keyword) {
                    $query->where('name', 'like', "%{$keyword}%");
                })
                ->filterColumn('state', function ($query, $keyword) {
                    $query->whereHas('state', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('senatorial_district', function ($query, $keyword) {
                    $query->whereHas('senatorialDistrict', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addIndexColumn()
                ->addColumn('name', function($constituency) {
                    return $constituency->name;
                })
                ->addColumn('state', function($constituency) {
                    return optional($constituency->state)->name;
                })
                ->addColumn('senatorial_district', function($constituency) {
                    return optional($constituency->senatorialDistrict)->name;
                })
                ->addColumn('action', function($constituency) {
                    $profileData = $this->getProfileData();

                    $deleteButton = '';
                    if ($this->canDeleteStructuralRecords()) {
                        $deleteButton = '
                            <form action="' . route($profileData->access_level.'.location.federal-constituency.delete', $constituency->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                                ' . csrf_field() . method_field('DELETE') . '
                                <button type="submit" class="btn btn-danger delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        ';
                    }

                    return '
                        <a href="' . route($profileData->access_level.'.location.federal-constituency.edit', $constituency->uuid) . '">
                            <button class="btn btn-success">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </a>
                        ' . $deleteButton . '
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        public function getWardData()
        {
            $wards = Ward::with([
                'localGovernmentArea.state.region',
                'localGovernmentArea.senatorialDistrict',
                'localGovernmentArea.federalConstituency'
            ]);
            $this->structuralAccess()->applyScope($wards, $this->getProfileData(), 'wards');


                return datatables()->eloquent($wards)
                ->filterColumn('name', function ($query, $keyword) {
                    $query->where('name', 'like', "%{$keyword}%");
                })
                ->filterColumn('lga', function ($query, $keyword) {
                    $query->whereHas('localGovernmentArea', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('state', function ($query, $keyword) {
                    $query->whereHas('localGovernmentArea.state', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('region', function ($query, $keyword) {
                    $query->whereHas('localGovernmentArea.state.region', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('senatorial_district', function ($query, $keyword) {
                    $query->whereHas('localGovernmentArea.senatorialDistrict', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('federal_constituency', function ($query, $keyword) {
                    $query->whereHas('localGovernmentArea.federalConstituency', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                    ->addIndexColumn() // This will automatically add a serial number column
                    ->addColumn('name', function($ward) {
                        return $ward->name;
                    })
                    ->addColumn('lga', function($ward) {
                        return optional($ward->localGovernmentArea)->name;
                    })
                    ->addColumn('state', function($ward) {
                        return optional($ward->localGovernmentArea->state)->name;
                    })
                    ->addColumn('region', function($ward) {
                        return optional($ward->localGovernmentArea->state->region)->name;
                    })
                    ->addColumn('senatorial_district', function($ward) {
                        return optional($ward->localGovernmentArea->senatorialDistrict)->name;
                    })
                    ->addColumn('federal_constituency', function($ward) {
                        return optional($ward->localGovernmentArea->federalConstituency)->name;
                    })

                    ->addColumn('action', function($ward) {
                        $profileData = $this->getProfileData();
                        $deleteButton = '';

                        if ($this->canDeleteStructuralRecords()) {
                            $deleteButton = '
                                <form action="' . route($profileData->access_level.'.location.ward.delete', $ward->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                                    ' . csrf_field() . method_field('DELETE') . '
                                    <button type="submit" class="btn btn-danger delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            ';
                        }

                        return '
                            <a href="' . route($profileData->access_level.'.location.ward.edit', $ward->uuid) . '">
                                <button class="btn btn-success ">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                            </a>
                            ' . $deleteButton . '
                        ';
                    })

                    ->rawColumns(['region', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
                    ->make(true);

        }



        public function getPUData()
        {
            $pus = PollingUnit::with([
                'ward.localGovernmentArea.state.region',
                'ward.localGovernmentArea.senatorialDistrict',
                'ward.localGovernmentArea.federalConstituency',
                'senatorialDistrict',
                'federalConstituency'
            ]);
            $this->structuralAccess()->applyScope($pus, $this->getProfileData(), 'polling_units');
            return datatables()->eloquent($pus)
                    ->filterColumn('name', function ($query, $keyword) {
                        $query->where('name', 'like', "%{$keyword}%");
                    })
                    ->filterColumn('ward', function ($query, $keyword) {
                        $query->whereHas('ward', function ($q) use ($keyword) {
                            $q->where('name', 'like', "%{$keyword}%");
                        });
                    })

                    ->filterColumn('state', function ($query, $keyword) {
                        $query->whereHas('ward.localGovernmentArea.state', function ($q) use ($keyword) {
                            $q->where('name', 'like', "%{$keyword}%");
                        });
                    })
                    ->filterColumn('region', function ($query, $keyword) {
                        $query->whereHas('ward.localGovernmentArea.state.region', function ($q) use ($keyword) {
                            $q->where('name', 'like', "%{$keyword}%");
                        });
                    })
                    ->filterColumn('senatorial_district', function ($query, $keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->whereHas('senatorialDistrict', function ($subQuery) use ($keyword) {
                                $subQuery->where('name', 'like', "%{$keyword}%");
                            })->orWhereHas('ward.localGovernmentArea.senatorialDistrict', function ($subQuery) use ($keyword) {
                                $subQuery->where('name', 'like', "%{$keyword}%");
                            });
                        });
                    })
                    ->filterColumn('federal_constituency', function ($query, $keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->whereHas('federalConstituency', function ($subQuery) use ($keyword) {
                                $subQuery->where('name', 'like', "%{$keyword}%");
                            })->orWhereHas('ward.localGovernmentArea.federalConstituency', function ($subQuery) use ($keyword) {
                                $subQuery->where('name', 'like', "%{$keyword}%");
                            });
                        });
                    })
                ->addIndexColumn()
                ->addColumn('name', function($pu) {
                    return $pu->name;
                })
                ->addColumn('ward', function($pu) {
                    return optional($pu->ward)->name;
                })
                ->addColumn('lga', function($pu) {
                    return optional($pu->ward->localGovernmentArea)->name;
                })
                ->addColumn('state', function($pu) {
                    return optional($pu->ward->localGovernmentArea->state)->name;
                })
                ->addColumn('region', function($pu) {
                    return optional($pu->ward->localGovernmentArea->state->region)->name;
                })
                ->addColumn('senatorial_district', function($pu) {
                    return optional($pu->senatorialDistrict)->name ?: optional($pu->ward->localGovernmentArea->senatorialDistrict)->name;
                })
                ->addColumn('federal_constituency', function($pu) {
                    return optional($pu->federalConstituency)->name ?: optional($pu->ward->localGovernmentArea->federalConstituency)->name;
                })
                ->addColumn('action', function($pu) {
                    $profileData = $this->getProfileData();
                    $deleteButton = '';

                    if ($this->canDeleteStructuralRecords()) {
                        $deleteButton = '
                            <form action="' . route($profileData->access_level . '.location.pu.delete', $pu->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                                ' . csrf_field() . method_field('DELETE') . '
                                <button type="submit" class="btn btn-danger btn-sm delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        ';
                    }

                    return '
                        <a href="' . route($profileData->access_level . '.location.pu.edit', $pu->uuid) . '">
                            <button class="btn btn-success btn-sm">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                        </a>
                        ' . $deleteButton . '
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }



    public function membersByWardsAjax($uuid = null)
    {

                   if ($uuid) {
                       // Check if UUID corresponds to LGA, state, region, or country in that order
                       $lga = LocalGovernmentArea::where('uuid', $uuid)->first();
                       if ($lga) {
                           // Fetch all Wards in that LGA

                           $wards = Ward::with('localGovernmentArea')
                                    ->where('lga_id', $lga->id)
                                    ->orderBy('name', 'ASC');
                       } else {
                           $state = State::where('uuid', $uuid)->first();

                           if ($state) {
                               // Fetch all Wards in LGAs within that state
                               $lgas = LocalGovernmentArea::where('state_id', $state->id)->pluck('id');
                               $wards = Ward::with('localGovernmentArea')
                                        ->whereIn('lga_id', $lgas)
                                        ->orderBy('name', 'asc');
                           } else {
                               $senatorialDistrict = SenatorialDistrict::where('uuid', $uuid)->first();

                               if ($senatorialDistrict) {
                                   $lgas = LocalGovernmentArea::where('senatorial_district_id', $senatorialDistrict->id)->pluck('id');
                                   $wards = Ward::with('localGovernmentArea')
                                            ->whereIn('lga_id', $lgas)
                                            ->orderBy('name', 'asc');
                               } else {
                               $federalConstituency = FederalConstituency::where('uuid', $uuid)->first();

                               if ($federalConstituency) {
                                   $lgas = LocalGovernmentArea::where('federal_constituency_id', $federalConstituency->id)->pluck('id');
                                   $wards = Ward::with('localGovernmentArea')
                                            ->whereIn('lga_id', $lgas)
                                            ->orderBy('name', 'asc');
                               } else {
                               $region = Region::where('uuid', $uuid)->first();

                               if ($region) {
                                   // Fetch all Wards in LGAs within states that belong to this region
                                   $states = State::where('region_id', $region->id)->pluck('id');
                                   $lgas = LocalGovernmentArea::whereIn('state_id', $states)->pluck('id');
                                   $wards = Ward::with('localGovernmentArea')
                                            ->whereIn('lga_id', $lgas)
                                            ->orderBy('name', 'asc');
                               } else {
                                   $country = Country::where('uuid', $uuid)->first();

                                   if ($country) {
                                       // Fetch all Wards in LGAs within states under regions in that country
                                       $regions = Region::where('country_id', $country->id)->pluck('id');
                                       $states = State::whereIn('region_id', $regions)->pluck('id');
                                       $lgas = LocalGovernmentArea::whereIn('state_id', $states)->pluck('id');
                                       $wards = Ward::with('localGovernmentArea')->
                                                whereIn('lga_id', $lgas)
                                                ->orderBy('name', 'asc');
                                   }
                               }
                               }
                               }
                           }
                       }
                   } else {
                       // If no UUID is provided, fetch all Wards
                       $wards = Ward::with('localGovernmentArea');
                                        //->orderBy('name', 'ASC');
                   }


                   $this->structuralAccess()->applyScope($wards, $this->getProfileData(), 'wards');
                   $wards->withCount(['users as members_count' => function ($query) {
                       $query->where('access_level', '!=', 'superadmin');
                   }]);

                   return datatables()->eloquent($wards)
                   ->filterColumn('lga', function ($query, $keyword) {
                    $query->whereHas('localGovernmentArea', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                        });
                    })
                   ->filterColumn('ward', function ($query, $keyword) {
                       $query->where('name', 'like', "%{$keyword}%");
                   })
                   ->addIndexColumn() // This will automatically add a serial number column
                   ->addColumn('ward', function($ward) {
                       return $ward->name;
                   })
                   ->addColumn('lga', function($ward) {
                       return optional($ward->localGovernmentArea)->name;
                   })
                   ->addColumn('members', function($ward) {
                     return  '<span class="badge badge-secondary">'. $ward->members_count.'</span>';
                   })
                   ->addColumn('action', function($ward) {
                    $profileData = $this->getProfileData();
                    $button = '';

                    return '
                        <a href="' . route($profileData->access_level.'.member.ward.view', $ward->uuid) . '">
                            <button class="btn btn-secondary">
                                <i class="fas fa-eye"></i> View Members
                            </button>
                        </a>
                        ' . $button . '
                            <a href="' . route($profileData->access_level.'.location.ward.dashboard', $ward->uuid) . '">

                                <button class="btn btn-success">
                                    <i class="fas fa-chart-bar"></i> Ward Dashboard
                                </button>
                            </a>
                    ';
                   })

                ->rawColumns(['members', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
                ->make(true);

    }

    public function membersByPusAjax($uuid = null)
    {

        if ($uuid) {
            // Check if the UUID corresponds to a Ward
            if ($ward = Ward::where('uuid', $uuid)->first()) {
                // Fetch all Polling Units in that Ward
                $pollingUnits = PollingUnit::where('ward_id', $ward->id)
                                            ->orderBy('name', 'ASC');

            } elseif ($lga = LocalGovernmentArea::where('uuid', $uuid)->first()) {
                // Fetch all Polling Units in Wards within that LGA
                $wards = Ward::where('lga_id', $lga->id)->pluck('id');
                $pollingUnits = PollingUnit::whereIn('ward_id', $wards)
                                            ->orderBy('name', 'ASC');

            } elseif ($state = State::where('uuid', $uuid)->first()) {
                // Fetch all Polling Units in Wards within LGAs in that State
                $lgas = LocalGovernmentArea::where('state_id', $state->id)->pluck('id');
                $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');
                $pollingUnits = PollingUnit::whereIn('ward_id', $wards)
                                            ->orderBy('name', 'ASC');

            } elseif ($senatorialDistrict = SenatorialDistrict::where('uuid', $uuid)->first()) {
                $lgas = LocalGovernmentArea::where('senatorial_district_id', $senatorialDistrict->id)->pluck('id');
                $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');
                $pollingUnits = PollingUnit::where(function ($query) use ($senatorialDistrict, $wards) {
                                                $query->where('senatorial_district_id', $senatorialDistrict->id)
                                                    ->orWhereIn('ward_id', $wards);
                                            })
                                            ->orderBy('name', 'ASC');

            } elseif ($federalConstituency = FederalConstituency::where('uuid', $uuid)->first()) {
                $lgas = LocalGovernmentArea::where('federal_constituency_id', $federalConstituency->id)->pluck('id');
                $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');
                $pollingUnits = PollingUnit::where(function ($query) use ($federalConstituency, $wards) {
                                                $query->where('federal_constituency_id', $federalConstituency->id)
                                                    ->orWhereIn('ward_id', $wards);
                                            })
                                            ->orderBy('name', 'ASC');

            } elseif ($region = Region::where('uuid', $uuid)->first()) {
                // Fetch all Polling Units in Wards within LGAs in States of that Region
                $states = State::where('region_id', $region->id)->pluck('id');
                $lgas = LocalGovernmentArea::whereIn('state_id', $states)->pluck('id');
                $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');
                $pollingUnits = PollingUnit::whereIn('ward_id', $wards)
                                            ->orderBy('name', 'ASC');

            } elseif ($country = Country::where('uuid', $uuid)->first()) {
                // Fetch all Polling Units in Wards within LGAs in States in Regions of that Country
                $regions = Region::where('country_id', $country->id)->pluck('id');
                $states = State::whereIn('region_id', $regions)->pluck('id');
                $lgas = LocalGovernmentArea::whereIn('state_id', $states)->pluck('id');
                $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');
                $pollingUnits = PollingUnit::whereIn('ward_id', $wards)
                                            ->orderBy('name', 'ASC');
            }
        } else {
            // If no UUID is provided, fetch all Polling Units
            $pollingUnits = PollingUnit::query()->orderBy('name', 'ASC');
        }

                   $this->structuralAccess()->applyScope($pollingUnits, $this->getProfileData(), 'polling_units');
                   $pollingUnits->withCount(['users as members_count' => function ($query) {
                       $query->where('access_level', '!=', 'superadmin');
                   }]);

                   return datatables()->eloquent($pollingUnits)
                   ->filterColumn('ward', function ($query, $keyword) {
                    $query->whereHas('Ward', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                        });
                    })
                   ->filterColumn('pu', function ($query, $keyword) {
                       $query->where('name', 'like', "%{$keyword}%");
                   })
                   ->addIndexColumn() // This will automatically add a serial number column


                   ->addColumn('pu', function($pollingUnits) {
                    return $pollingUnits->name;
                })
                    ->addColumn('members', function($ward) {
                     return  '<span class="badge badge-secondary">'. $ward->members_count.'</span>';
                   })
                   ->addColumn('action', function($pollingUnits) {
                    $profileData = $this->getProfileData();
                    $button = '';

                    return '
                        <a href="' . route($profileData->access_level.'.member.pu.view', $pollingUnits->uuid) . '">
                            <button class="btn btn-danger ">
                                <i class="fas fa-eye"></i> View Members
                            </button>
                        </a>
                        ' . $button . '
                            <a href="' . route($profileData->access_level.'.location.pu.dashboard', $pollingUnits->uuid) . '">

                                <button class="btn btn-success">
                                    <i class="fas fa-chart-bar"></i> PU Dashboard
                                </button>
                            </a>
                    ';
                   })

                ->rawColumns(['members', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
                ->make(true);

    }

    public function eligibleMembersByPusAjax($uuid = null)
    {
        if ($uuid) {
            if ($ward = Ward::where('uuid', $uuid)->first()) {
                $pollingUnits = PollingUnit::where('ward_id', $ward->id)->orderBy('name', 'ASC');
            } elseif ($lga = LocalGovernmentArea::where('uuid', $uuid)->first()) {
                $wards = Ward::where('lga_id', $lga->id)->pluck('id');
                $pollingUnits = PollingUnit::whereIn('ward_id', $wards)->orderBy('name', 'ASC');
            } elseif ($state = State::where('uuid', $uuid)->first()) {
                $lgas = LocalGovernmentArea::where('state_id', $state->id)->pluck('id');
                $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');
                $pollingUnits = PollingUnit::whereIn('ward_id', $wards)->orderBy('name', 'ASC');
            } elseif ($region = Region::where('uuid', $uuid)->first()) {
                $states = State::where('region_id', $region->id)->pluck('id');
                $lgas = LocalGovernmentArea::whereIn('state_id', $states)->pluck('id');
                $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');
                $pollingUnits = PollingUnit::whereIn('ward_id', $wards)->orderBy('name', 'ASC');
            } elseif ($country = Country::where('uuid', $uuid)->first()) {
                $regions = Region::where('country_id', $country->id)->pluck('id');
                $states = State::whereIn('region_id', $regions)->pluck('id');
                $lgas = LocalGovernmentArea::whereIn('state_id', $states)->pluck('id');
                $wards = Ward::whereIn('lga_id', $lgas)->pluck('id');
                $pollingUnits = PollingUnit::whereIn('ward_id', $wards)->orderBy('name', 'ASC');
            }
        } else {
            $pollingUnits = PollingUnit::query()->orderBy('name', 'ASC');
        }

        $this->structuralAccess()->applyScope($pollingUnits, $this->getProfileData(), 'polling_units');

        return datatables()->eloquent($pollingUnits)
            ->addIndexColumn()
            ->addColumn('pu', fn($pu) => $pu->name)
            ->addColumn('members', fn($pu) => $pu->users()->where('validVoter', 'yes')->count())
            ->addColumn('action', function ($pu) {
                $profileData = $this->getProfileData();
                return '
                    <a href="' . route($profileData->access_level.'.eligible.member.pu.view', $pu->uuid) . '">
                        <button class="btn btn-danger">
                            <i class="fas fa-eye"></i> View Eligible Members
                        </button>
                    </a>
                    <a href="' . route($profileData->access_level.'.location.pu.dashboard', $pu->uuid) . '">
                        <button class="btn btn-success">
                            <i class="fas fa-chart-bar"></i> PU Dashboard
                        </button>
                    </a>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }









    //SMS Ajax Methods

public function getGroupRecipients(Request $request)
{

    $recipientsQuery = User::query()
    ->where('access_level' ,'!=', 'superadmin')
    ->whereNotNull('phone');

    if ($request->filled('country_id')) {
        $recipientsQuery->where('country_id', $request->country_id);
    }
    if ($request->filled('region_id')) {
        $recipientsQuery->where('region_id', $request->region_id);
    }
    if ($request->filled('state_id')) {
        $recipientsQuery->where('state_id', $request->state_id);
    }
    if ($request->filled('lga_id')) {
        $recipientsQuery->where('lga_id', $request->lga_id);
    }
    if ($request->filled('ward_id')) {
        $recipientsQuery->where('ward_id', $request->ward_id);
    }
    if ($request->filled('pu_id')) {
        $recipientsQuery->where('polling_unit_id', $request->pu_id);
    }

    app(LocationScopeService::class)->applyScope($recipientsQuery, $this->getProfileData(), 'users', 'users');



    $total = $recipientsQuery->count();

    return response()->json(['total' => $total]);
}








}
