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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\LicensedScopeQueryService;
use App\Services\StructuralLocationAccessService;

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

       private function structuralAccess(): StructuralLocationAccessService
       {
           return app(StructuralLocationAccessService::class);
       }

       private function licensedScope(): LicensedScopeQueryService
       {
           return app(LicensedScopeQueryService::class);
       }

       private function validateLicensedPayload(array $payload): void
       {
           $errors = $this->licensedScope()->payloadErrors($payload);

           if ($errors !== []) {
               throw ValidationException::withMessages($errors);
           }
       }

       private function superAdminOnly(): void
       {
           $this->structuralAccess()->assertCanCreateOrDelete($this->getProfileData());
       }

       private function redirectToLocation(string $suffix)
       {
           return redirect()->route($this->getProfileData()->access_level.'.location.'.$suffix);
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
           $regions = $this->structuralAccess()
               ->applyScope(Region::query(), $profileData, 'regions')
               ->orderBy('name')
               ->get();

           return view('backend.'.$profileData->access_level.'.region.regions', compact('profileData', 'regions'));
       }


       public function addRegion()
       {
           $profileData = $this->getProfileData();
           $this->superAdminOnly();

           return view('backend.'.$profileData->access_level.'.region.add-region', compact('profileData'));
       }

       public function storeRegion(Request $request)
       {
           $request->validate([
               'name' => 'required',
           ]);

           $countryId = $this->getSystemCountryId()->id;

           $profileData = $this->getProfileData();
           $this->superAdminOnly();
           abort_if($this->licensedScope()->applies(), 403, 'Regions cannot be created inside a scoped self-hosted license.');

            // Prepare the region data
            $regionData = $request->except(['_token', '_method']);
            $regionData['country_id'] = $countryId; // Add country ID to the region data
            $regionData['user_id'] = $profileData->id;

           Region::create($regionData);

           return $this->redirectToLocation('regions')->with([
               'message' => 'Region added successfully',
               'alert-type' => 'success'
           ]);
       }

       public function editRegion($uuid)
       {
           $profileData = $this->getProfileData();
           $region = Region::where('uuid', $uuid)->firstOrFail();
           $this->structuralAccess()->assertCanUpdate($profileData, $region);
           $this->licensedScope()->abortIfRegionNotAllowed($region->id);

           return view('backend.'.$profileData->access_level.'.region.edit-region', compact('profileData', 'region'));
       }

       public function updateRegion(Request $request, $uuid)
       {
           $request->validate([
               'name' => 'required',
           ]);

           $profileData = $this->getProfileData();
           $region = Region::where('uuid', $uuid)->firstOrFail();
           $this->structuralAccess()->assertCanUpdate($profileData, $region);
           $this->licensedScope()->abortIfRegionNotAllowed($region->id);
           $region->update(['name' => $request->name]);

           return $this->redirectToLocation('regions')->with([
               'message' => 'Region updated successfully',
               'alert-type' => 'success'
           ]);
       }

       public function deleteRegion($uuid) {
        $profileData = $this->getProfileData();
        $this->superAdminOnly();
        $region = Region::where('uuid', $uuid)->first(); // Retrieve the region
        $this->licensedScope()->abortIfRegionNotAllowed($region?->id);

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

        return $this->redirectToLocation('regions')->with($notification);
    }


       /*  State Management funtions*/

       public function allStates(){
        $profileData = $this->getProfileData();
        //Get states in alphetical order
        $states = $this->structuralAccess()
            ->applyScope(State::query(), $profileData, 'states')
            ->orderBy('name')
            ->get();

        return view('backend.'.$profileData->access_level.'.state.states', compact('profileData', 'states'));

       }

       public function addState(){
        $profileData = $this->getProfileData();
        $this->superAdminOnly();
         $regions = Region::all();

        return view('backend.'.$profileData->access_level.'.state.add-state', compact('profileData' , 'regions'));
       }

       public function storeState(Request $request){
            $request->validate([
                'name' => 'required',
                'region_id'=>'required',
            ]);
            $profileData = $this->getProfileData();
            $this->superAdminOnly();
            abort_if($this->licensedScope()->applies(), 403, 'States cannot be created inside a scoped self-hosted license.');

            $stateData = $request->except(['_token', '_method']);
            $stateData['user_id'] = $profileData->id;
            State::create($stateData);

            return $this->redirectToLocation('states')->with([
                'message' => 'State added successfully',
                'alert-type' => 'success'
            ]);
       }

       public function editState($uuid){
        $profileData = $this->getProfileData();
        $state = State::where('uuid', $uuid)->firstOrFail();
        $this->structuralAccess()->assertCanUpdate($profileData, $state);
        $this->licensedScope()->abortIfStateNotAllowed($state->id);
        $regions = $profileData->access_level === 'superadmin' ? Region::all() : Region::where('id', $state->region_id)->get();

        return view('backend.'.$profileData->access_level.'.state.edit-state', compact('profileData', 'state', 'regions'));
       }

       public function updateState(Request $request, $uuid)
{
            $request->validate([
                'name' => 'required',
                'region_id' => 'required',
            ]);

            $profileData = $this->getProfileData();
            $stateData = $profileData->access_level === 'superadmin'
                ? $request->except(['_token', '_method'])
                : $request->only(['name']);
            $stateData['user_id'] = $profileData->id;

            // Find the state by uuid first
            $state = State::where('uuid', $uuid)->firstOrFail();

            $this->structuralAccess()->assertCanUpdate($profileData, $state);
            $this->licensedScope()->abortIfStateNotAllowed($state->id);
            $this->validateLicensedPayload(['state_id' => $state->id]);
            $state->update($stateData);

            return $this->redirectToLocation('states')->with([
                'message' => 'State updated successfully',
                'alert-type' => 'success'
            ]);
        }



       public function deleteState($uuid) {
        $profileData = $this->getProfileData();
        $this->superAdminOnly();
        $state = State::where('uuid', $uuid)->first(); // Retrieve the state
        $this->licensedScope()->abortIfStateNotAllowed($state?->id);

        // Check if the state has any users associated with it
        if ($state->users()->count() > 0) {
            $notification = [
                'message' => 'Cannot delete state. Users are associated with this state.',
                'alert-type' => 'error'
            ];

            // Redirect back with the notification
            return redirect()->back()->with($notification);
        }

        $state->delete();
        return $this->redirectToLocation('states')->with([
            'message' => 'State deleted successfully',
            'alert-type' => 'success'
        ]);
    }



    /*  Senatorial District Management functions*/
    public function allSenatorialDistricts()
    {
        $profileData = $this->getProfileData();
        $districts = $this->structuralAccess()
            ->applyScope(SenatorialDistrict::with('state'), $profileData, 'senatorial_districts')
            ->orderBy('name')
            ->get();

        return view('backend.'.$profileData->access_level.'.senatorial-district.senatorial-districts', compact('profileData', 'districts'));
    }

    public function addSenatorialDistrict()
    {
        $profileData = $this->getProfileData();
        $this->superAdminOnly();
        $states = $this->structuralAccess()
            ->applyScope(State::query(), $profileData, 'states')
            ->orderBy('name', 'ASC')
            ->get();

        return view('backend.'.$profileData->access_level.'.senatorial-district.add-senatorial-district', compact('profileData', 'states'));
    }

    public function storeSenatorialDistrict(Request $request)
    {
        $this->superAdminOnly();
        $request->validate([
            'name' => 'required',
            'state_id' => 'required',
        ]);
        $this->validateLicensedPayload($request->only(['state_id']));

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
        $this->structuralAccess()->assertCanUpdate($profileData, $district);
        $this->licensedScope()->abortIfSenatorialDistrictNotAllowed($district->id);
        $states = $profileData->access_level === 'superadmin'
            ? $this->structuralAccess()->applyScope(State::query(), $profileData, 'states')->orderBy('name', 'ASC')->get()
            : State::where('id', $district->state_id)->get();

        return view('backend.'.$profileData->access_level.'.senatorial-district.edit-senatorial-district', compact('profileData', 'district', 'states'));
    }

    public function updateSenatorialDistrict(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required',
            'state_id' => 'required',
        ]);

        $profileData = $this->getProfileData();
        $district = SenatorialDistrict::where('uuid', $uuid)->firstOrFail();
        $this->structuralAccess()->assertCanUpdate($profileData, $district);
        $this->licensedScope()->abortIfSenatorialDistrictNotAllowed($district->id);
        $this->validateLicensedPayload([
            'state_id' => $request->state_id,
            'senatorial_district_id' => $district->id,
        ]);

        $data = [
            'name' => $request->name,
            'normalized_name' => Str::lower(trim($request->name)),
        ];

        if ($profileData->access_level === 'superadmin') {
            $data['state_id'] = $request->state_id;
        }

        $district->update($data);

        $notification = [
            'message' => 'Senatorial District updated successfully',
            'alert-type' => 'success'
        ];

        return redirect()->route($profileData->access_level.'.location.senatorial-districts')->with($notification);
    }

    public function deleteSenatorialDistrict($uuid)
    {
        $profileData = $this->getProfileData();
        $this->superAdminOnly();
        $district = SenatorialDistrict::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfSenatorialDistrictNotAllowed($district->id);

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
        $constituencies = $this->structuralAccess()
            ->applyScope(FederalConstituency::with(['state', 'senatorialDistrict']), $profileData, 'federal_constituencies')
            ->orderBy('name')
            ->get();

        return view('backend.'.$profileData->access_level.'.federal-constituency.federal-constituencies', compact('profileData', 'constituencies'));
    }

    public function addFederalConstituency()
    {
        $profileData = $this->getProfileData();
        $this->superAdminOnly();
        $states = $this->structuralAccess()
            ->applyScope(State::query(), $profileData, 'states')
            ->orderBy('name', 'ASC')
            ->get();
        $senatorialDistricts = $this->structuralAccess()
            ->applyScope(SenatorialDistrict::with('state'), $profileData, 'senatorial_districts')
            ->orderBy('name', 'ASC')
            ->get();

        return view('backend.'.$profileData->access_level.'.federal-constituency.add-federal-constituency', compact('profileData', 'states', 'senatorialDistricts'));
    }

    public function storeFederalConstituency(Request $request)
    {
        $this->superAdminOnly();
        $request->validate([
            'name' => 'required',
            'state_id' => 'required',
            'senatorial_district_id' => 'nullable',
        ]);
        $this->validateLicensedPayload($request->only(['state_id', 'senatorial_district_id']));

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
        $this->structuralAccess()->assertCanUpdate($profileData, $constituency);
        $this->licensedScope()->abortIfFederalConstituencyNotAllowed($constituency->id);
        $states = $profileData->access_level === 'superadmin'
            ? $this->structuralAccess()->applyScope(State::query(), $profileData, 'states')->orderBy('name', 'ASC')->get()
            : State::where('id', $constituency->state_id)->get();
        $senatorialDistricts = $profileData->access_level === 'superadmin'
            ? $this->structuralAccess()->applyScope(SenatorialDistrict::with('state'), $profileData, 'senatorial_districts')->orderBy('name', 'ASC')->get()
            : SenatorialDistrict::with('state')->where('state_id', $constituency->state_id)->orderBy('name', 'ASC')->get();

        return view('backend.'.$profileData->access_level.'.federal-constituency.edit-federal-constituency', compact('profileData', 'constituency', 'states', 'senatorialDistricts'));
    }

    public function updateFederalConstituency(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required',
            'state_id' => 'required',
            'senatorial_district_id' => 'nullable',
        ]);

        $profileData = $this->getProfileData();
        $constituency = FederalConstituency::where('uuid', $uuid)->firstOrFail();
        $this->structuralAccess()->assertCanUpdate($profileData, $constituency);
        $this->licensedScope()->abortIfFederalConstituencyNotAllowed($constituency->id);
        $this->validateLicensedPayload([
            'state_id' => $request->state_id,
            'senatorial_district_id' => $request->senatorial_district_id,
            'federal_constituency_id' => $constituency->id,
        ]);

        $data = [
            'name' => $request->name,
            'normalized_name' => Str::lower(trim($request->name)),
        ];

        if ($profileData->access_level === 'superadmin') {
            $data['state_id'] = $request->state_id;
            $data['senatorial_district_id'] = $request->senatorial_district_id;
        }

        $constituency->update($data);

        $notification = [
            'message' => 'Federal Constituency updated successfully',
            'alert-type' => 'success'
        ];

        return redirect()->route($profileData->access_level.'.location.federal-constituencies')->with($notification);
    }

    public function deleteFederalConstituency($uuid)
    {
        $profileData = $this->getProfileData();
        $this->superAdminOnly();
        $constituency = FederalConstituency::where('uuid', $uuid)->firstOrFail();
        $this->licensedScope()->abortIfFederalConstituencyNotAllowed($constituency->id);

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
        $lgas = $this->structuralAccess()
            ->applyScope(LocalGovernmentArea::query(), $profileData, 'local_government_areas')
            ->get();
        $quickstartOrderUrl = $this->quickstartOrderUrl();

        return view('backend.'.$profileData->access_level.'.lga.lgas', compact('profileData', 'lgas', 'quickstartOrderUrl'));
    }

    public function addLocalGovernment(){

        $profileData = $this->getProfileData();
        $this->superAdminOnly();
        $states = $this->structuralAccess()
            ->applyScope(State::query(), $profileData, 'states')
            ->orderBy('name', 'ASC')
            ->get();
        $senatorialDistricts = $this->structuralAccess()
            ->applyScope(SenatorialDistrict::query(), $profileData, 'senatorial_districts')
            ->orderBy('name', 'ASC')
            ->get();
        $federalConstituencies = $this->structuralAccess()
            ->applyScope(FederalConstituency::query(), $profileData, 'federal_constituencies')
            ->orderBy('name', 'ASC')
            ->get();
        $quickstartOrderUrl = $this->quickstartOrderUrl();

        return view('backend.'.$profileData->access_level.'.lga.add-lga', compact('profileData', 'states', 'senatorialDistricts', 'federalConstituencies', 'quickstartOrderUrl'));


    }


    public function storeLocalGovernment(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'state' => ['required', 'integer', 'exists:states,id'],
            'senatorial_district_id' => [
                'nullable',
                'integer',
                Rule::exists('senatorial_districts', 'id')->where('state_id', $request->input('state')),
            ],
            'federal_constituency_id' => [
                'nullable',
                'integer',
                Rule::exists('federal_constituencies', 'id')->where('state_id', $request->input('state')),
            ],
        ]);
        $this->validateLicensedPayload([
            'state_id' => $request->state,
            'senatorial_district_id' => $request->senatorial_district_id,
            'federal_constituency_id' => $request->federal_constituency_id,
        ]);

        $profileData = $this->getProfileData(); // Get the profile data
        $this->superAdminOnly();


            $lga = LocalGovernmentArea::create([
                'name' => $request->name,
                'state_id' => $request->state,
                'senatorial_district_id' => $request->senatorial_district_id,
                'federal_constituency_id' => $request->federal_constituency_id,
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
        $this->structuralAccess()->assertCanUpdate($profileData, $lga);
        $this->licensedScope()->abortIfLgaNotAllowed($lga->id);
        $states = $profileData->access_level === 'superadmin'
            ? $this->structuralAccess()->applyScope(State::query(), $profileData, 'states')->orderBy('name', 'ASC')->get()
            : State::where('id', $lga->state_id)->get();
        $senatorialDistricts = $this->structuralAccess()->applyScope(SenatorialDistrict::query(), $profileData, 'senatorial_districts')->orderBy('name', 'ASC')->get();
        $federalConstituencies = $this->structuralAccess()->applyScope(FederalConstituency::query(), $profileData, 'federal_constituencies')->orderBy('name', 'ASC')->get();
        return view('backend.'.$profileData->access_level.'.lga.edit-lga', compact('profileData', 'lga', 'states', 'senatorialDistricts', 'federalConstituencies'));
    }

    public function updateLocalGovernment(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required',
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'senatorial_district_id' => [
                'nullable',
                'integer',
                Rule::exists('senatorial_districts', 'id')->where('state_id', $request->input('state_id')),
            ],
            'federal_constituency_id' => [
                'nullable',
                'integer',
                Rule::exists('federal_constituencies', 'id')->where('state_id', $request->input('state_id')),
            ],
        ]);

        $profileData = $this->getProfileData();
        $lga = LocalGovernmentArea::where('uuid', $uuid)->firstOrFail();
        $this->structuralAccess()->assertCanUpdate($profileData, $lga);
        $this->licensedScope()->abortIfLgaNotAllowed($lga->id);
        $this->validateLicensedPayload([
            'state_id' => $request->state_id,
            'senatorial_district_id' => $request->senatorial_district_id,
            'federal_constituency_id' => $request->federal_constituency_id,
            'lga_id' => $lga->id,
        ]);
        $lgaData = $profileData->access_level === 'superadmin'
            ? $request->except(['_token', '_method'])
            : $request->only(['name']);
        $lgaData['user_id'] = $profileData->id;
        $lga->update($lgaData);
        $notification = array(
            'message' => 'Local Government Area updated successfully',
            'alert-type' => 'success'
        );
        return redirect()->route($profileData->access_level.'.location.lgas')->with($notification);
    }



    public function deleteLocalGovernment($uuid) {
        $profileData = $this->getProfileData();
        $this->superAdminOnly();
        $lga = LocalGovernmentArea::where('uuid', $uuid)->first(); // Retrieve the state
        $this->licensedScope()->abortIfLgaNotAllowed($lga?->id);

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
        $this->superAdminOnly();
        $quickstartOrderUrl = $this->quickstartOrderUrl();
        return view('backend.'.$profileData->access_level.'.lga.import-lga', compact('profileData', 'quickstartOrderUrl'));
    }
 // For Export
    public function exportLgas()
    {
        $this->superAdminOnly();
        return Excel::download(new LocalGovernmentExport, 'local-governments.xlsx');
    }

    public function importLgas(Request $request)
    {
        $this->superAdminOnly();
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

    private function quickstartOrderUrl(): string
    {
        $settings = SystemSetting::query()->first();
        $base = rtrim((string) config('campaign.portal_web_base', 'https://campaignmanager.ng'), '/');
        $query = [
            'service' => 'quickstart_geography',
            'package' => $this->portalQuickstartPackageType($settings?->package),
            'scope_type' => $settings?->campaign_scope_type,
        ];

        $query = array_filter($query, fn ($value) => $value !== null && $value !== '');

        return $base.'/order/campaign-manager'.($query ? '?'.http_build_query($query) : '');
    }

    private function portalQuickstartPackageType(?string $packageType): ?string
    {
        return $packageType === 'chairmanship' ? 'lga' : $packageType;
    }



    /* Wards Management Functions  */

    public function allWards()
    {
        $profileData = $this->getProfileData();
        $wardCount = $this->structuralAccess()
            ->applyScope(Ward::query(), $profileData, 'wards')
            ->count();

        return view('backend.'.$profileData->access_level.'.ward.wards', compact('profileData', 'wardCount'));


    }

    public function addWard()
    {

        $profileData = $this->getProfileData();
        $this->superAdminOnly();
        $lgas = LocalGovernmentArea::with('state')
                    ->join('states', 'local_government_areas.state_id', '=', 'states.id')
                    ->orderBy('states.name', 'ASC')
                    ->orderBy('local_government_areas.name', 'ASC')
                    ->tap(fn ($query) => $this->structuralAccess()->applyScope($query, $profileData, 'local_government_areas'))
                    ->get(['local_government_areas.*']);

        return view('backend.'.$profileData->access_level.'.ward.add-ward', compact('profileData', 'lgas'));
    }


    public function storeWard(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'lga_id' => 'required',
        ]);
        $this->validateLicensedPayload($request->only(['lga_id']));

        $profileData = $this->getProfileData();
        $this->superAdminOnly();

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
        $this->structuralAccess()->assertCanUpdate($profileData, $ward);
        $this->licensedScope()->abortIfWardNotAllowed($ward->id);
        $lgas = LocalGovernmentArea::with('state')
                    ->join('states', 'local_government_areas.state_id', '=', 'states.id')
                    ->orderBy('states.name', 'ASC')
                    ->orderBy('local_government_areas.name', 'ASC')
                    ->tap(fn ($query) => $this->structuralAccess()->applyScope($query, $profileData, 'local_government_areas'))
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
        $ward = Ward::where('uuid', $uuid)->firstOrFail();
        $this->structuralAccess()->assertCanUpdate($profileData, $ward);
        $this->licensedScope()->abortIfWardNotAllowed($ward->id);
        $this->validateLicensedPayload([
            'lga_id' => $request->lga_id,
            'ward_id' => $ward->id,
        ]);
        $wardData = $profileData->access_level === 'superadmin'
            ? $request->except(['_token', '_method'])
            : $request->only(['name']);
        $wardData['user_id'] = $profileData->id;
        $ward->update($wardData);
        $notification = array(
            'message' => 'Ward updated successfully',
            'alert-type' => 'success'
        );
        return redirect()->route($profileData->access_level.'.location.wards')->with($notification);
    }


    public function deleteWard($uuid) {
        $profileData = $this->getProfileData();
        $this->superAdminOnly();
        $ward = Ward::where('uuid', $uuid)->first(); // Retrieve the state
        $this->licensedScope()->abortIfWardNotAllowed($ward?->id);

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
        $this->superAdminOnly();
        return view('backend.'.$profileData->access_level.'.ward.import-ward', compact('profileData'));
    }
     // For Export
    public function exportWards()
    {
        $this->superAdminOnly();
        return Excel::download(new WardExport, 'wards.xlsx');
    }

    public function importWards(Request $request)
{
    $this->superAdminOnly();
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
        $puCount = $this->structuralAccess()
            ->applyScope(PollingUnit::query(), $profileData, 'polling_units')
            ->count();
        return view('backend.'.$profileData->access_level.'.pu.pus', compact('profileData', 'puCount'));
    }


    public function addPollingUnit()
    {

        $profileData = $this->getProfileData();
        $this->superAdminOnly();

        $lgas = LocalGovernmentArea::with('state')
                    ->join('states', 'local_government_areas.state_id', '=', 'states.id')
                    ->orderBy('states.name', 'ASC')
                    ->orderBy('local_government_areas.name', 'ASC')
                    ->tap(fn ($query) => $this->structuralAccess()->applyScope($query, $profileData, 'local_government_areas'))
                    ->get(['local_government_areas.*']);

        $wards = Ward::with('localGovernmentArea') // Ensures the relationship is loaded
                    ->join('local_government_areas', 'wards.lga_id', '=', 'local_government_areas.id')
                    ->orderBy('local_government_areas.name', 'ASC')
                    ->orderBy('wards.name', 'ASC')
                    ->tap(fn ($query) => $this->structuralAccess()->applyScope($query, $profileData, 'wards'))
                    ->get(['wards.*', 'local_government_areas.name as lga_name']);

        return view('backend.'.$profileData->access_level.'.pu.add-pu', compact('profileData', 'wards', 'lgas'));
    }



    public function storePollingUnit(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'ward_id' => 'required',
        ]);
        $this->validateLicensedPayload($request->only(['ward_id']));

        $profileData = $this->getProfileData();
        $this->superAdminOnly();
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
        $this->structuralAccess()->assertCanUpdate($profileData, $pu);
        $this->licensedScope()->abortIfPollingUnitNotAllowed($pu->id);

            $lgas = LocalGovernmentArea::with('state')
            ->join('states', 'local_government_areas.state_id', '=', 'states.id')
            ->orderBy('states.name', 'ASC')
            ->orderBy('local_government_areas.name', 'ASC')
            ->tap(fn ($query) => $this->structuralAccess()->applyScope($query, $profileData, 'local_government_areas'))
            ->get(['local_government_areas.*']);

            $wards = Ward::with('localGovernmentArea') // Ensures the relationship is loaded
            ->join('local_government_areas', 'wards.lga_id', '=', 'local_government_areas.id')
            ->orderBy('local_government_areas.name', 'ASC')
            ->orderBy('wards.name', 'ASC')
            ->tap(fn ($query) => $this->structuralAccess()->applyScope($query, $profileData, 'wards'))
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
        $pu = PollingUnit::where('uuid', $uuid)->firstOrFail();
        $this->structuralAccess()->assertCanUpdate($profileData, $pu);
        $this->licensedScope()->abortIfPollingUnitNotAllowed($pu->id);
        $this->validateLicensedPayload([
            'ward_id' => $request->ward_id,
            'polling_unit_id' => $pu->id,
        ]);
        $puData = $profileData->access_level === 'superadmin'
            ? $request->except(['lga_id', '_token', '_method'])
            : $request->only(['name']);
        $puData['user_id'] = $profileData->id;
        $pu->update($puData);
        $notification = array(
            'message' => 'Polling Unit updated successfully',
            'alert-type' => 'success'
        );
        return redirect()->route($profileData->access_level.'.location.pus')->with($notification);
    }


    public function deletePollingUnit($uuid) {
        $profileData = $this->getProfileData();
        $this->superAdminOnly();
        $pu = PollingUnit::where('uuid', $uuid)->first(); // Retrieve the Pu
        $this->licensedScope()->abortIfPollingUnitNotAllowed($pu?->id);

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
        $this->superAdminOnly();
        return view('backend.'.$profileData->access_level.'.pu.import-polling-unit', compact('profileData'));
    }


     // For Export
    public function exportPollingUnits()
    {
        $this->superAdminOnly();
        return Excel::download(new PollingUnitExport, 'polling_units.xlsx');
    }

    // For Import
    public function importPollingUnits(Request $request)
    {
        $this->superAdminOnly();
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
