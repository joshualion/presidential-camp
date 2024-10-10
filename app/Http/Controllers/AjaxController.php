<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\State;
use App\Models\LGA;
use App\Models\Ward;
use App\Models\PollingUnit;

class AjaxController extends Controller
{
    

     

    public function ajaxGetStates(Request $request)
    {
        $state['states'] = State::where("region_id", $request->region_id)
                                ->get(["name", "id"]);
  
        return response()->json($state);
    } 


    public function ajaxGetLgas(Request $request)
    {
        $lga['lgas'] = LGA::where("state_id", $request->state_id)
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

}
