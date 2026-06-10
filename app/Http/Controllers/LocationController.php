<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Ward;
use App\Exports\LocalGovernmentExport;
use App\Imports\LocalGovernmentImport;
use App\Exports\PollingUnitExport;
use App\Imports\PollingUnitImport;
use App\Exports\WardExport;
use App\Imports\WardImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LocationController extends Controller
{

        public function __construct()
        {
            $pageTitle = 'Locations';
            View::share('pageTitle', $pageTitle);
        }
        
       // Function to get the profile data
       private function getProfileData()
       {
           $id = Auth::user()->id;
           return User::find($id);
       }

       //system country
       private function getSystemCountryId(){
          // Retrieve the value of the default_country setting
          $defaultCountry = SystemSetting::find(1);

          // Retrieve the ID of the default country
          return Country::where('name', $defaultCountry->system_country)->first();
       }

       //Managem regions
       public function allRegions()
       {
           $profileData = $this->getProfileData();
           $regions = Region::all();
          if($profileData->access_level == 'superadmin'){

               return view('backend.superadmin.region.regions', compact('profileData', 'regions'));
           }

           if($profileData->access_level == 'nationaladmin'){
           return view('backend.nationaladmin.region.regions', compact('profileData', 'regions'));
           }
       }


       public function addRegion()
       {
           $profileData = $this->getProfileData();
           if($profileData->access_level == 'superadmin'){
               return view('backend.superadmin.region.add-region', compact('profileData'));
           }
           if($profileData->access_level == 'nationaladmin'){
               return view('backend.nationaladmin.region.add-region', compact('profileData'));
           }
       }

       public function storeRegion(Request $request)
       {
           $request->validate([
               'name' => 'required',
           ]);

           $countryId = $this->getSystemCountryId()->id;

           $profileData = $this->getProfileData();

            // Prepare the region data
            $regionData = $request->except(['_token', '_method']);
            $regionData['country_id'] = $countryId; // Add country ID to the region data
            $regionData['user_id'] = $profileData->id;

           if($profileData->access_level == 'superadmin'){
                    Region::create($regionData);
               $notification = array(
                   'message' => 'Region added successfully',
                   'alert-type' => 'success'
               );

               return redirect()->route('superadmin.location.regions')->with($notification);
           }
           if($profileData->access_level == 'nationaladmin'){
                    Region::create($regionData);
               $notification = array(
                   'message' => 'Region added successfully',
                   'alert-type' => 'success'
               );

               return redirect()->route('nationaladmin.location.regions')->with($notification);
           }
       }

       public function editRegion($uuid)
       {
           $profileData = $this->getProfileData();
           $region = Region::where('uuid', $uuid)->first();
           if($profileData->access_level == 'superadmin'){
               return view('backend.superadmin.region.edit-region', compact('profileData', 'region'));
           }
           if($profileData->access_level == 'nationaladmin'){
               return view('backend.nationaladmin.region.edit-region', compact('profileData', 'region'));
           }
       }

       public function updateRegion(Request $request, $uuid)
       {
           $request->validate([
               'name' => 'required',
           ]);

           $profileData = $this->getProfileData();
           if($profileData->access_level == 'superadmin'){
               Region::where('uuid', $uuid)->update($request->except(['_token', '_method']));
               $notification = array(
                   'message' => 'Region updated successfully',
                   'alert-type' => 'success'
               );

               return redirect()->route('superadmin.location.regions')->with($notification);
           }

           if($profileData->access_level == 'nationaladmin'){
               Region::where('uuid', $uuid)->update($request->except(['_token', '_method']));
               $notification = array(
                   'message' => 'Region updated successfully',
                   'alert-type' => 'success'
               );

               return redirect()->route('nationaladmin.location.regions')->with($notification);
           }
       }

       public function deleteRegion($uuid) {
        $profileData = $this->getProfileData();
        $region = Region::where('uuid', $uuid)->first(); // Retrieve the region

        // Check if the region has any states or users associated with it
        if ($region->states()->count() > 0 || $region->users()->count() > 0) {
            $notification = [
                'message' => 'Cannot delete region. States or users are associated with this region.',
                'alert-type' => 'error'
            ];

            // Redirect back with the notification
            return redirect()->back()->with($notification);
        }

        // If no associated states or users, proceed with deletion
        $region->delete();

        $notification = [
            'message' => 'Region deleted successfully',
            'alert-type' => 'success'
        ];

        // Redirect based on access level
        if ($profileData->access_level == 'superadmin') {
            return redirect()->route('superadmin.location.regions')->with($notification);
        }

        if ($profileData->access_level == 'nationaladmin') {
            return redirect()->route('nationaladmin.location.regions')->with($notification);
        }
    }


       /*  State Management funtions*/

       public function allStates(){
        $profileData = $this->getProfileData();
        //Get states in alphetical order
        $states = State::orderBy('name')->get();

            if($profileData->access_level == 'superadmin'){
                return view('backend.superadmin.state.states', compact('profileData', 'states'));
            }

            if($profileData->access_level == 'nationaladmin'){
                return view('backend.nationaladmin.state.states', compact('profileData', 'states'));
            }


       }

       public function addState(){
        $profileData = $this->getProfileData();
         $regions = Region::all();
            if($profileData->access_level == 'superadmin'){
                return view('backend.superadmin.state.add-state', compact('profileData' , 'regions'));
            }
            if($profileData->access_level == 'nationaladmin'){
                return view('backend.nationaladmin.state.add-state', compact('profileData' , 'regions'));
            }
       }

       public function storeState(Request $request){
            $request->validate([
                'name' => 'required',
                'region_id'=>'required',
            ]);
            $profileData = $this->getProfileData();

            $stateData = $request->except(['_token', '_method']);
            $stateData['user_id'] = $profileData->id;
                if($profileData->access_level == 'superadmin'){
                    State::create($stateData);
                    $notification = array(
                        'message' => 'State added successfully',
                        'alert-type' => 'success'
                    );

                    return redirect()->route('superadmin.location.states')->with($notification);
                }

                if($profileData->access_level == 'nationaladmin'){
                    State::create($stateData);
                    $notification = array(
                        'message' => 'State added successfully',
                        'alert-type' => 'success'
                    );

                    return redirect()->route('nationaladmin.location.states')->with($notification);
                }
       }

       public function editState($uuid){
        $profileData = $this->getProfileData();
        $state = State::where('uuid', $uuid)->first();
        $regions = Region::all();
            if($profileData->access_level == 'superadmin'){
                return view('backend.superadmin.state.edit-state', compact('profileData', 'state', 'regions'));
            }
            if($profileData->access_level == 'nationaladmin'){
                return view('backend.nationaladmin.state.edit-state', compact('profileData', 'state', 'regions'));
            }
       }

       public function updateState(Request $request, $uuid)
{
            $request->validate([
                'name' => 'required',
                'region_id' => 'required',
            ]);

            $profileData = $this->getProfileData();
            $stateData = $request->except(['_token', '_method']);
            $stateData['user_id'] = $profileData->id;

            // Find the state by uuid first
            $state = State::where('uuid', $uuid)->firstOrFail();

            if ($profileData->access_level == 'superadmin') {
                $state->update($stateData); // Update the state directly
                $notification = array(
                    'message' => 'State updated successfully',
                    'alert-type' => 'success'
                );

                return redirect()->route('superadmin.location.states')->with($notification);
            }

            if ($profileData->access_level == 'nationaladmin') {
                $state->update($stateData); // Update the state directly
                $notification = array(
                    'message' => 'State updated successfully',
                    'alert-type' => 'success'
                );

                return redirect()->route('nationaladmin.location.states')->with($notification);
            }
        }



       public function deleteState($uuid) {
        $profileData = $this->getProfileData();
        $state = State::where('uuid', $uuid)->first(); // Retrieve the state

        // Check if the state has any users associated with it
        if ($state->users()->count() > 0) {
            $notification = [
                'message' => 'Cannot delete state. Users are associated with this state.',
                'alert-type' => 'error'
            ];

            // Redirect back with the notification
            return redirect()->back()->with($notification);
        }

        // If no users are associated, proceed with deletion
        if($profileData->access_level == 'superadmin') {
            $state->delete();
            $notification = [
                'message' => 'State deleted successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('superadmin.location.states')->with($notification);
        }

        if($profileData->access_level == 'nationaladmin') {
            $state->delete();
            $notification = [
                'message' => 'State deleted successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('nationaladmin.location.states')->with($notification);
        }
    }



    /*  Senatorial District Management functions*/
    public function allSenatorialDistricts()
    {
        $profileData = $this->getProfileData();
        $districts = SenatorialDistrict::with('state')->orderBy('name')->get();

        return view('backend.'.$profileData->access_level.'.senatorial-district.senatorial-districts', compact('profileData', 'districts'));
    }

    public function addSenatorialDistrict()
    {
        $profileData = $this->getProfileData();
        $states = State::orderBy('name', 'ASC')->get();

        return view('backend.'.$profileData->access_level.'.senatorial-district.add-senatorial-district', compact('profileData', 'states'));
    }

    public function storeSenatorialDistrict(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'state_id' => 'required',
        ]);

        SenatorialDistrict::create([
            'name' => $request->name,
            'normalized_name' => Str::lower(trim($request->name)),
            'state_id' => $request->state_id,
        ]);

        $profileData = $this->getProfileData();
        $notification = [
            'message' => 'Senatorial District added successfully',
            'alert-type' => 'success'
        ];

        return redirect()->route($profileData->access_level.'.location.senatorial-districts')->with($notification);
    }

    public function editSenatorialDistrict($uuid)
    {
        $profileData = $this->getProfileData();
        $district = SenatorialDistrict::where('uuid', $uuid)->firstOrFail();
        $states = State::orderBy('name', 'ASC')->get();

        return view('backend.'.$profileData->access_level.'.senatorial-district.edit-senatorial-district', compact('profileData', 'district', 'states'));
    }

    public function updateSenatorialDistrict(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required',
            'state_id' => 'required',
        ]);

        SenatorialDistrict::where('uuid', $uuid)->update([
            'name' => $request->name,
            'normalized_name' => Str::lower(trim($request->name)),
            'state_id' => $request->state_id,
        ]);

        $profileData = $this->getProfileData();
        $notification = [
            'message' => 'Senatorial District updated successfully',
            'alert-type' => 'success'
        ];

        return redirect()->route($profileData->access_level.'.location.senatorial-districts')->with($notification);
    }

    public function deleteSenatorialDistrict($uuid)
    {
        $profileData = $this->getProfileData();
        $district = SenatorialDistrict::where('uuid', $uuid)->firstOrFail();

        if (
            $district->federalConstituencies()->count() > 0 ||
            $district->localGovernmentAreas()->count() > 0 ||
            $district->pollingUnits()->count() > 0 ||
            $district->users()->count() > 0
        ) {
            $notification = [
                'message' => 'Cannot delete Senatorial District. Federal constituencies, LGAs, polling units, or users are associated with this district.',
                'alert-type' => 'error'
            ];

            return redirect()->back()->with($notification);
        }

        $district->delete();
        $notification = [
            'message' => 'Senatorial District deleted successfully',
            'alert-type' => 'success'
        ];

        return redirect()->route($profileData->access_level.'.location.senatorial-districts')->with($notification);
    }

    /*  Federal Constituency Management functions*/
    public function allFederalConstituencies()
    {
        $profileData = $this->getProfileData();
        $constituencies = FederalConstituency::with(['state', 'senatorialDistrict'])->orderBy('name')->get();

        return view('backend.'.$profileData->access_level.'.federal-constituency.federal-constituencies', compact('profileData', 'constituencies'));
    }

    public function addFederalConstituency()
    {
        $profileData = $this->getProfileData();
        $states = State::orderBy('name', 'ASC')->get();
        $senatorialDistricts = SenatorialDistrict::with('state')->orderBy('name', 'ASC')->get();

        return view('backend.'.$profileData->access_level.'.federal-constituency.add-federal-constituency', compact('profileData', 'states', 'senatorialDistricts'));
    }

    public function storeFederalConstituency(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'state_id' => 'required',
            'senatorial_district_id' => 'nullable',
        ]);

        FederalConstituency::create([
            'name' => $request->name,
            'normalized_name' => Str::lower(trim($request->name)),
            'state_id' => $request->state_id,
            'senatorial_district_id' => $request->senatorial_district_id,
        ]);

        $profileData = $this->getProfileData();
        $notification = [
            'message' => 'Federal Constituency added successfully',
            'alert-type' => 'success'
        ];

        return redirect()->route($profileData->access_level.'.location.federal-constituencies')->with($notification);
    }

    public function editFederalConstituency($uuid)
    {
        $profileData = $this->getProfileData();
        $constituency = FederalConstituency::where('uuid', $uuid)->firstOrFail();
        $states = State::orderBy('name', 'ASC')->get();
        $senatorialDistricts = SenatorialDistrict::with('state')->orderBy('name', 'ASC')->get();

        return view('backend.'.$profileData->access_level.'.federal-constituency.edit-federal-constituency', compact('profileData', 'constituency', 'states', 'senatorialDistricts'));
    }

    public function updateFederalConstituency(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required',
            'state_id' => 'required',
            'senatorial_district_id' => 'nullable',
        ]);

        FederalConstituency::where('uuid', $uuid)->update([
            'name' => $request->name,
            'normalized_name' => Str::lower(trim($request->name)),
            'state_id' => $request->state_id,
            'senatorial_district_id' => $request->senatorial_district_id,
        ]);

        $profileData = $this->getProfileData();
        $notification = [
            'message' => 'Federal Constituency updated successfully',
            'alert-type' => 'success'
        ];

        return redirect()->route($profileData->access_level.'.location.federal-constituencies')->with($notification);
    }

    public function deleteFederalConstituency($uuid)
    {
        $profileData = $this->getProfileData();
        $constituency = FederalConstituency::where('uuid', $uuid)->firstOrFail();

        if (
            $constituency->localGovernmentAreas()->count() > 0 ||
            $constituency->pollingUnits()->count() > 0 ||
            $constituency->users()->count() > 0
        ) {
            $notification = [
                'message' => 'Cannot delete Federal Constituency. LGAs, polling units, or users are associated with this constituency.',
                'alert-type' => 'error'
            ];

            return redirect()->back()->with($notification);
        }

        $constituency->delete();
        $notification = [
            'message' => 'Federal Constituency deleted successfully',
            'alert-type' => 'success'
        ];

        return redirect()->route($profileData->access_level.'.location.federal-constituencies')->with($notification);
    }



    /*  LGA Management funtions*/
    public function allLocalGovernments()
    {
        $profileData = $this->getProfileData();
        $lgas = LocalGovernmentArea::all();
        if($profileData->access_level == 'superadmin'){
            return view('backend.superadmin.lga.lgas', compact('profileData', 'lgas'));
        }
        if($profileData->access_level == 'nationaladmin'){
            return view('backend.nationaladmin.lga.lgas', compact('profileData', 'lgas'));
        }
    }

    public function addLocalGovernment(){

        $profileData = $this->getProfileData();
        $states = State::orderBy('name', 'ASC')->get();

        return view('backend.'.$profileData->access_level.'.lga.add-lga', compact('profileData', 'states'));


    }


    public function storeLocalGovernment(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'state' => 'required',
        ]);

        $profileData = $this->getProfileData(); // Get the profile data


            $lga = LocalGovernmentArea::create([
                'name' => $request->name,
                'state_id' => $request->state,
                'user_id' => $profileData->id,
            ]);

            $notification = array(
                'message' => 'Local Government Area added successfully',
                'alert-type' => 'success'
            );

            return redirect()->route($profileData->access_level.'.location.lgas')->with($notification);

    }


    public function editLocalGovernment($uuid)
    {
        $profileData = $this->getProfileData();
        $lga = LocalGovernmentArea::where('uuid', $uuid)->firstOrFail();
        $states = State::orderBy('name', 'ASC')->get();
        return view('backend.'.$profileData->access_level.'.lga.edit-lga', compact('profileData', 'lga', 'states'));
    }

    public function updateLocalGovernment(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required',
            'state_id' => 'required',
        ]);

        $profileData = $this->getProfileData();
        $lgaData = $request->except(['_token', '_method']);
        $lgaData['user_id'] = $profileData->id;
        $lga = LocalGovernmentArea::where('uuid', $uuid)->update($lgaData);
        $notification = array(
            'message' => 'Local Government Area updated successfully',
            'alert-type' => 'success'
        );
        return redirect()->route($profileData->access_level.'.location.lgas')->with($notification);
    }



    public function deleteLocalGovernment($uuid) {
        $profileData = $this->getProfileData();
        $lga = LocalGovernmentArea::where('uuid', $uuid)->first(); // Retrieve the state

        // Check if the lga has any users or wards associated with it
        if ($lga->users()->count() > 0 || $lga->wards()->count() > 0) {
            $notification = [
                'message' => 'Cannot delete LGA. Users/Wards are associated with this LGA.',
                'alert-type' => 'error'
            ];

            // Redirect back with the notification
            return redirect()->back()->with($notification);
        }



        // If no database  are associated, proceed with deletion

            $lga->delete();
            $notification = [
                'message' => 'Local Government Area deleted successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route($profileData->access_level.'.location.lgas')->with($notification);
    }


    public function importLgasForm()
    {
        $profileData = $this->getProfileData();
        return view('backend.'.$profileData->access_level.'.lga.import-lga', compact('profileData'));
    }
 // For Export
    public function exportLgas()
    {
        return Excel::download(new LocalGovernmentExport, 'local-governments.xlsx');
    }

    public function importLgas(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        $import = new LocalGovernmentImport;
        Excel::import($import, $request->file('file'));

        $successCount = $import->successfulInserts;
        $failureCount = $import->failedInserts;
        $duplicateCount = $import->duplicates;

        // Notification messages based on outcome
        if ($successCount > 0 && $failureCount > 0) {
            // Partial Success
            $message = "Some data were imported correctly; however, check the imported file for spelling errors or ensure that the target State exists.";
            $alertType = 'warning';
        } elseif ($failureCount > 0 && $successCount === 0) {
            // Complete Failure
            $message = "Your data is incorrect. Please check that the target States exist and that all spellings are correct.";
            $alertType = 'error';
        } elseif ($duplicateCount > 0 && $successCount === 0 && $failureCount === 0) {
            // Duplicate Data
            $message = "Duplicate data. The data you are trying to import already exists.";
            $alertType = 'info';
        } else {
            // Full Success
            $message = "{$successCount} Local Government(s) imported successfully.";
            $alertType = 'success';
        }

        return redirect()->route($this->getProfileData()->access_level.'.location.lgas')->with([
            'message' => $message,
            'alert-type' => $alertType
        ]);
    }



    /* Wards Management Functions  */

    public function allWards()
    {
        $profileData = $this->getProfileData();
        $wardCount = Ward::count();

        return view('backend.'.$profileData->access_level.'.ward.wards', compact('profileData', 'wardCount'));


    }

    public function addWard()
    {

        $profileData = $this->getProfileData();
        $lgas = LocalGovernmentArea::with('state')
                    ->join('states', 'local_government_areas.state_id', '=', 'states.id')
                    ->orderBy('states.name', 'ASC')
                    ->orderBy('local_government_areas.name', 'ASC')
                    ->get(['local_government_areas.*']);

        return view('backend.'.$profileData->access_level.'.ward.add-ward', compact('profileData', 'lgas'));
    }


    public function storeWard(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'lga_id' => 'required',
        ]);

        $profileData = $this->getProfileData();

        $ward = Ward::create([
            'name' => $request->name,
            'lga_id' => $request->lga_id,
            'user_id' => $profileData->id,
        ]);

        $notification = array(
            'message' => 'Ward added successfully',
            'alert-type' => 'success'
        );

        return redirect()->route($profileData->access_level.'.location.wards')->with($notification);
    }


    public function editWard($uuid)
    {
        $profileData = $this->getProfileData();
        $ward = Ward::where('uuid', $uuid)->firstOrFail();
        $lgas = LocalGovernmentArea::with('state')
                    ->join('states', 'local_government_areas.state_id', '=', 'states.id')
                    ->orderBy('states.name', 'ASC')
                    ->orderBy('local_government_areas.name', 'ASC')
                    ->get(['local_government_areas.*']);
        return view('backend.'.$profileData->access_level.'.ward.edit-ward', compact('profileData', 'ward', 'lgas'));
    }


    public function updateWard(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required',
            'lga_id' => 'required',
        ]);

        $profileData = $this->getProfileData();
        $wardData = $request->except(['_token', '_method']);
        $wardData['user_id'] = $profileData->id;
        $ward = Ward::where('uuid', $uuid)->update($wardData);
        $notification = array(
            'message' => 'Ward updated successfully',
            'alert-type' => 'success'
        );
        return redirect()->route($profileData->access_level.'.location.wards')->with($notification);
    }


    public function deleteWard($uuid) {
        $profileData = $this->getProfileData();
        $ward = Ward::where('uuid', $uuid)->first(); // Retrieve the state

        // Check if the lga has any users or wards associated with it
        if ($ward->users()->count() > 0 ||$ward->pollingUnits()->count() > 0) {
            $notification = [
                'message' => 'Cannot delete Ward. Users/Polling Units are associated with this Ward.',
                'alert-type' => 'error'
            ];
            return redirect()->back()->with($notification);
        }

        // If no database  are associated, proceed with deletion
        $ward->delete();
        $notification = [
            'message' => 'Ward deleted successfully',
            'alert-type' => 'success'
        ];
        return redirect()->route($profileData->access_level.'.location.wards')->with($notification);
    }

    public function importWardsForm()
    {
        $profileData = $this->getProfileData();
        return view('backend.'.$profileData->access_level.'.ward.import-ward', compact('profileData'));
    }
     // For Export
    public function exportWards()
    {
        return Excel::download(new WardExport, 'wards.xlsx');
    }

    public function importWards(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx',
    ]);

    $import = new WardImport;
    Excel::import($import, $request->file('file'));

    $successCount = $import->successfulInserts;
    $failureCount = $import->failedInserts;
    $duplicateCount = $import->duplicates;

    // Notification messages based on outcome
    if ($successCount > 0 && $failureCount > 0) {
        // Partial Success
        $message = "Some data were imported correctly; however, check the imported file for spelling errors or ensure that the target local government exists.";
        $alertType = 'warning';
    } elseif ($failureCount > 0 && $successCount === 0) {
        // Complete Failure
        $message = "Your data is incorrect. Please check that the target local governments exist and that all spellings are correct.";
        $alertType = 'error';
    } elseif ($duplicateCount > 0 && $successCount === 0 && $failureCount === 0) {
        // Duplicate Data
        $message = "Duplicate data. The data you are trying to import already exists.";
        $alertType = 'info';
    } else {
        // Full Success
        $message = "{$successCount} wards imported successfully.";
        $alertType = 'success';
    }

    return redirect()->route($this->getProfileData()->access_level.'.location.wards')->with([
        'message' => $message,
        'alert-type' => $alertType
    ]);
    }


    /* POlling Units Management Functions  */

    public function allPollingUnits()
    {
        $profileData = $this->getProfileData();
        $puCount = PollingUnit::count();
        return view('backend.'.$profileData->access_level.'.pu.pus', compact('profileData', 'puCount'));
    }


    public function addPollingUnit()
    {

        $profileData = $this->getProfileData();

        $lgas = LocalGovernmentArea::with('state')
                    ->join('states', 'local_government_areas.state_id', '=', 'states.id')
                    ->orderBy('states.name', 'ASC')
                    ->orderBy('local_government_areas.name', 'ASC')
                    ->get(['local_government_areas.*']);

        $wards = Ward::with('localGovernmentArea') // Ensures the relationship is loaded
                    ->join('local_government_areas', 'wards.lga_id', '=', 'local_government_areas.id')
                    ->orderBy('local_government_areas.name', 'ASC')
                    ->orderBy('wards.name', 'ASC')
                    ->get(['wards.*', 'local_government_areas.name as lga_name']);

        return view('backend.'.$profileData->access_level.'.pu.add-pu', compact('profileData', 'wards', 'lgas'));
    }



    public function storePollingUnit(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'ward_id' => 'required',
        ]);

        $profileData = $this->getProfileData();
        $pu = PollingUnit::create([
            'name' => $request->name,
            'ward_id' => $request->ward_id,
            'user_id' => $profileData->id,
        ]);

        $notification = array(
            'message' => 'Polling Unit added successfully',
            'alert-type' => 'success'
        );

        return redirect()->route($profileData->access_level.'.location.pus')->with($notification);
    }



    public function editPollingUnit($uuid)
    {
        $profileData = $this->getProfileData();
        $pu = PollingUnit::where('uuid', $uuid)->firstOrFail();

            $lgas = LocalGovernmentArea::with('state')
            ->join('states', 'local_government_areas.state_id', '=', 'states.id')
            ->orderBy('states.name', 'ASC')
            ->orderBy('local_government_areas.name', 'ASC')
            ->get(['local_government_areas.*']);

            $wards = Ward::with('localGovernmentArea') // Ensures the relationship is loaded
            ->join('local_government_areas', 'wards.lga_id', '=', 'local_government_areas.id')
            ->orderBy('local_government_areas.name', 'ASC')
            ->orderBy('wards.name', 'ASC')
            ->get(['wards.*', 'local_government_areas.name as lga_name']);
            return view('backend.'.$profileData->access_level.'.pu.edit-pu', compact('profileData', 'pu', 'wards', 'lgas'));
            }


    public function updatePollingUnit(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required',
            'ward_id' => 'required',
        ]);

        $profileData = $this->getProfileData();
        $puData = $request->except(['lga_id', '_token', '_method']);
        $puData['user_id'] = $profileData->id;
        $pu = PollingUnit::where('uuid', $uuid)->update($puData);
        $notification = array(
            'message' => 'Polling Unit updated successfully',
            'alert-type' => 'success'
        );
        return redirect()->route($profileData->access_level.'.location.pus')->with($notification);
    }


    public function deletePollingUnit($uuid) {
        $profileData = $this->getProfileData();
        $pu = PollingUnit::where('uuid', $uuid)->first(); // Retrieve the Pu

        // Check if the PU has any users  associated with it
        if ($pu->users()->count() > 0 ) {
            $notification = [
                'message' => 'Cannot delete Polling Unit. Users are associated with this Polling Unit.',
                'alert-type' => 'error'
            ];
            return redirect()->back()->with($notification);
        }

        // If no database  are associated, proceed with deletion
        $pu->delete();
        $notification = [
            'message' => 'Polling Unit deleted successfully',
            'alert-type' => 'success'
        ];
        return redirect()->route($profileData->access_level.'.location.pus')->with($notification);
    }


    public function importPollingUnitsForm()
    {
        $profileData = $this->getProfileData();
        return view('backend.'.$profileData->access_level.'.pu.import-polling-unit', compact('profileData'));
    }


     // For Export
    public function exportPollingUnits()
    {
        return Excel::download(new PollingUnitExport, 'polling_units.xlsx');
    }

    // For Import
    public function importPollingUnits(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        $import = new PollingUnitImport;
        Excel::import($import, $request->file('file'));

        $successCount = $import->successfulInserts;
        $failureCount = $import->failedInserts;
        $duplicatesCount = $import->duplicates;

        // Create notification message based on counts
        if ($failureCount > 0 && $successCount > 0) {
            $message = "Some polling units were imported successfully. However, there were issues with some rows. Please check for potential misspellings or missing local governments in your file.";
            $alertType = 'warning';
        } elseif ($failureCount > 0) {
            $message = "No polling units were imported. Please check that the local government and ward names in your file are correct.";
            $alertType = 'error';
        } else {
            $message = "{$successCount} polling units imported successfully.";
            $alertType = 'success';
        }

        // Include duplicates count in the message if applicable
        if ($duplicatesCount > 0) {
            $message .= " {$duplicatesCount} polling units were not imported due to duplicates.";
        }

        $notification = [
            'message' => $message,
            'alert-type' => $alertType
        ];

        return redirect()->route($this->getProfileData()->access_level.'.location.pus')->with($notification);
    }









}
