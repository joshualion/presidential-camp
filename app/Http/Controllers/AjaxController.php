<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\State;
use App\Models\LGA;
use App\Models\LocalGovernmentArea;
use App\Models\Ward;
use App\Models\PollingUnit;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class AjaxController extends Controller
{

    private function getProfileData()
    {
        $id = Auth::user()->id;
        return User::find($id);
    }

    public function ajaxGetStates(Request $request)
    {
        $state['states'] = State::where("region_id", $request->region_id)
                                ->get(["name", "id"]);

        return response()->json($state);
    }


    public function ajaxGetLgas(Request $request)
    {
        $lga['lgas'] = LocalGovernmentArea::where("state_id", $request->state_id)
                                ->get(["name", "id"]);
        return response()->json($lga);
    }

    public function ajaxGetWards(Request $request)
    {
        $ward['wards'] = Ward::where("lga_id", $request->lga_id)
                                ->get(["name", "id"]);
        return response()->json($ward);
    }

    public function ajaxGetPollingUnits(Request $request)
    {
        $pollingUnit['pollingUnits'] = PollingUnit::where("ward_id", $request->ward_id)
                                ->get(["name", "id"]);
        return response()->json($pollingUnit);
    }


    public function getRolesByAccessLevel($accessLevel)
        {
            // Find roles where 'role_group' matches the selected 'access_level'
            $roles = Role::where('group_name', $accessLevel)->get();
            // Return the roles as a JSON response
            return response()->json($roles);
        }


        public function  getLgaData()
        {

            $lgas = LocalGovernmentArea::with('state')->orderBy('name');

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


                    ->addColumn('action', function($lga) {
                        $profileData = $this->getProfileData();
                        $button = '';

                        return '
                            <a href="' . route($profileData->access_level.'.location.lga.edit', $lga->uuid) . '">
                                <button class="btn btn-success ">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                            </a>
                            ' . $button . '
                            <form action="' . route($profileData->access_level.'.location.lga.delete', $lga->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                                ' . csrf_field() . method_field('DELETE') . '
                                <button type="submit" class="btn btn-danger delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        ';
                    })

                    ->rawColumns(['region', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
                    ->make(true);

        }

        public function getWardData()
        {
            $wards = Ward::with('localGovernmentArea.state.region');


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

                    ->addColumn('action', function($ward) {
                        $profileData = $this->getProfileData();
                        $button = '';

                        return '
                            <a href="' . route($profileData->access_level.'.location.ward.edit', $ward->uuid) . '">
                                <button class="btn btn-success ">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                            </a>
                            ' . $button . '
                            <form action="' . route($profileData->access_level.'.location.ward.delete', $ward->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                                ' . csrf_field() . method_field('DELETE') . '
                                <button type="submit" class="btn btn-danger delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        ';
                    })

                    ->rawColumns(['region', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
                    ->make(true);

        }



    public function getPUData()
    {
        $pus = PollingUnit::with('ward.localGovernmentArea.state.region');
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
            ->addColumn('action', function($pu) {
                $profileData = $this->getProfileData();
                return '
                    <a href="' . route($profileData->access_level . '.location.pu.edit', $pu->uuid) . '">
                        <button class="btn btn-success btn-sm">
                            <i class="fas fa-pencil-alt"></i> Edit
                        </button>
                    </a>
                    <form action="' . route($profileData->access_level . '.location.pu.delete', $pu->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="btn btn-danger btn-sm delete-btn">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
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
                   } else {
                       // If no UUID is provided, fetch all Wards
                       $wards = Ward::with('localGovernmentArea');
                                        //->orderBy('name', 'ASC');
                   }


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
                     return $ward->users()->count();
                   })
                   ->addColumn('action', function($ward) {
                    $profileData = $this->getProfileData();
                    $button = '';

                    return '
                        <a href="' . route($profileData->access_level.'.member.ward.view', $ward->uuid) . '">
                            <button class="btn btn-danger ">
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

                   ->addColumn('ward', function($pollingUnits) {
                       return optional($pollingUnits->ward)->name;
                   })
                   ->addColumn('pu', function($pollingUnits) {
                    return $pollingUnits->name;
                })
                   ->addColumn('members', function($pollingUnits) {
                     return $pollingUnits->users()->count();
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



}
