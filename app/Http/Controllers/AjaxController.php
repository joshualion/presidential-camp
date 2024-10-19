<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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

            $lgas = LocalGovernmentArea::with('state')->orderBy('name')->get();

                return datatables($lgas)
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
            $wards = Ward::with('localGovernmentArea.state.region')->get();

                return datatables($wards)
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

            $pus = PollingUnit::with('ward.localGovernmentArea.state.region')->get();

                return datatables($pus) // This will automatically add a serial number column   
                    ->addIndexColumn() // This will automatically add a serial number column
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
                        $button = '';

                        return '
                            <a href="' . route($profileData->access_level.'.location.pu.edit', $pu->uuid) . '">   
                                <button class="btn btn-success btn-sm ">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                            </a>
                            ' . $button . '
                            <form action="' . route($profileData->access_level.'.location.pu.delete', $pu->uuid) . '" method="POST" style="display:inline-block;" data-associated-users="false">
                                ' . csrf_field() . method_field('DELETE') . '
                                <button type="submit" class="btn btn-danger btn-sm delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        ';
                    })
                    
                    ->rawColumns(['region', 'action']) // Ensures that HTML in the 'role' and 'action' columns is rendered properly
                    ->make(true);

        }

}
