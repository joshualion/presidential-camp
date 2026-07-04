<?php

namespace App\Http\Controllers;

use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\PoliticalParty;
use App\Models\PollingUnit;
use App\Models\SystemSetting;
use App\Models\Vote;
use App\Models\PollingUnitResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use App\Models\Election;
use App\Models\User;
use App\Exports\ElectionPollingUnitResultsExport;
use App\Exports\ElectionSummaryExport;
use App\Services\FileStorageService;
use App\Services\ElectionAnalyticsService;
use App\Services\ElectionOperationsService;
use App\Services\ElectionReportService;
use App\Services\LicensedScopeQueryService;
use App\Services\LocationScopeService;
use App\Services\PackageGovernanceService;
use App\Services\PollingUnitResultPermissionService;
use App\Services\ModuleGateService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
class ElectionController extends Controller
{

    public function __construct(ModuleGateService $modules){
        $modules->requireEnabled('elections');
        $pageTitle = 'Elections';
        View::share('pageTitle', $pageTitle);
    }

     // Function to get the profile data
     private function getProfileData()
     {
         $id = Auth::user()->id;
         return User::find($id);
     }

     protected function canCreateElections(User $profileData): bool
     {
         return $profileData->access_level === 'superadmin';
     }

     protected function isIctDirector(User $profileData): bool
     {
         return $profileData->hasAnyRole([
             'National ICT Director',
             'Regional ICT Director',
             'State ICT Director',
             'Senatorial ICT Director',
             'Federal Constituency ICT Director',
             'Federal ICT Director',
             'LGA ICT Director',
             'Ward ICT Director',
             'PU ICT Director',
         ]);
     }

     protected function isPollingUnitAgent(User $profileData): bool
     {
         if ($profileData->access_level === 'puadmin') {
             return true;
         }

         return $profileData->roles->contains(function ($role) {
             return stripos($role->name, 'agent') !== false;
         });
     }

     protected function pollingUnitIsWithinScope(User $profileData, ?int $pollingUnitId): bool
     {
         if (!$pollingUnitId) {
             return false;
         }

         if ($profileData->access_level === 'superadmin') {
             return !empty($pollingUnitId);
         }

         $query = PollingUnit::query();
         $this->applyPollingUnitScope($query, $profileData);

         return $query->where('polling_units.id', $pollingUnitId)->exists();
     }

     protected function canUploadPollingUnitResults(User $profileData, ?int $pollingUnitId = null): bool
     {
         $permission = app(PollingUnitResultPermissionService::class);
         $pollingUnitId ??= $profileData->polling_unit_id ?: $permission->firstApprovedPollingUnitId($profileData);

         return $permission->userHasApprovedAssignmentForPollingUnit($profileData, $pollingUnitId);
     }

     private function applyVoteScope($query, $profileData)
     {
         return app(LocationScopeService::class)->applyScope($query, $profileData, 'votes', 'votes');
     }

     private function applyPollingUnitScope($query, $profileData, $table = 'polling_units')
     {
         return app(LocationScopeService::class)->applyScope($query, $profileData, 'polling_units', $table);
     }

     private function licensedScope(): LicensedScopeQueryService
     {
         return app(LicensedScopeQueryService::class);
     }

     private function scopedPollingUnitsCount($profileData)
     {
         $query = PollingUnit::query()
             ->leftJoin('wards', 'polling_units.ward_id', '=', 'wards.id')
             ->leftJoin('local_government_areas', 'wards.lga_id', '=', 'local_government_areas.id')
             ->leftJoin('states', 'local_government_areas.state_id', '=', 'states.id');

         $this->applyPollingUnitScope($query, $profileData);

         return (int) $query->distinct()->count('polling_units.id');
     }

     private function scopedPollingUnitResultQuery($profileData)
     {
         $query = PollingUnitResult::query();
         app(LocationScopeService::class)->applyScope($query, $profileData, 'polling_unit_results', 'polling_unit_results');

         return $query;
     }

     private function reportingKeysForPollingUnit(?int $pollingUnitId): array
     {
         $pollingUnit = $pollingUnitId
             ? PollingUnit::with(['ward.localGovernmentArea.state', 'senatorialDistrict', 'federalConstituency'])->find($pollingUnitId)
             : null;

         $ward = $pollingUnit?->ward;
         $lga = $ward?->localGovernmentArea;

         return [
             'state_id' => $lga?->state_id,
             'senatorial_district_id' => $pollingUnit?->senatorial_district_id,
             'federal_constituency_id' => $pollingUnit?->federal_constituency_id,
             'lga_id' => $ward?->lga_id,
             'ward_id' => $pollingUnit?->ward_id,
         ];
     }


     // Election List
     public function allElections(ElectionReportService $reportService){
         $profileData = $this->getProfileData();
         $elections = $reportService->indexRows($profileData);

         return view('backend.'.$profileData->access_level.'.election.elections', compact('elections',  'profileData'));

     }

     public function operationsCenter(ElectionOperationsService $operationsService, ElectionAnalyticsService $analyticsService, ElectionReportService $reportService)
     {
         $profileData = $this->getProfileData();
         $pageTitle = 'Situation Room';
         $operationsCenter = $operationsService->forUser($profileData);
         if (!empty($operationsCenter['active_election'])) {
             $operationsCenter['latest_results'] = $reportService
                 ->pollingUnitRows($profileData, $operationsCenter['active_election'])
                 ->take(10)
                 ->map(fn (array $row) => [
                     'type' => 'result',
                     'title' => 'Result submitted',
                     'polling_unit' => $row['polling_unit_name'],
                     'ward' => $row['ward_name'],
                     'lga' => $row['lga_name'],
                     'actor' => $row['submitted_by'],
                     'time' => $row['submitted_at'] ? \Carbon\Carbon::parse($row['submitted_at']) : null,
                     'verification_status' => ucfirst($row['verification_status']),
                     'dispute_status' => ucfirst($row['dispute_status']),
                     'submitted_by' => $row['submitted_by'],
                     'submitted_at' => $row['submitted_at'],
                 ]);
         }
         $analytics = $analyticsService->forUser($profileData);
         $packageContext = app(PackageGovernanceService::class)->context();

         return view('backend.shared.election.operations-center', compact(
             'profileData',
             'pageTitle',
             'operationsCenter',
             'analytics',
             'packageContext'
         ));
     }

     public function pollingUnitSituationRoom(ElectionOperationsService $operationsService, ElectionAnalyticsService $analyticsService, ElectionReportService $reportService)
     {
         $profileData = $this->getProfileData();
         $pageTitle = 'Polling Unit Situation Room';
         $situationRoom = $operationsService->pollingUnitSituationRoom($profileData);
         if (!empty($situationRoom['active_election'])) {
             $situationRoom['recent_activity'] = $reportService
                 ->pollingUnitRows($profileData, $situationRoom['active_election'])
                 ->take(10)
                 ->map(fn (array $row) => [
                     'type' => 'result',
                     'title' => 'Result submitted',
                     'polling_unit' => $row['polling_unit_name'],
                     'ward' => $row['ward_name'],
                     'lga' => $row['lga_name'],
                     'actor' => $row['submitted_by'],
                     'time' => $row['submitted_at'] ? \Carbon\Carbon::parse($row['submitted_at']) : null,
                     'verification_status' => ucfirst($row['verification_status']),
                     'dispute_status' => ucfirst($row['dispute_status']),
                     'submitted_by' => $row['submitted_by'],
                     'submitted_at' => $row['submitted_at'],
                 ]);
         }
         $analytics = $analyticsService->forUser($profileData);
         $packageContext = app(PackageGovernanceService::class)->context();

         return view('backend.shared.election.polling-unit-situation-room', compact(
             'profileData',
             'pageTitle',
             'situationRoom',
             'analytics',
             'packageContext'
         ));
     }

     // Election Add
     public function addElection(){
         $profileData = $this->getProfileData();
         abort_unless($this->canCreateElections($profileData), 403);
         if(empty($profileData->lga_id) || empty($profileData->ward_id) || empty($profileData->polling_unit_id )){
            $notification =[
                'type'=> 'error',
                'message'=> 'You need to complete you Profile before accessing election data',
            ];
            return redirect()->route($profileData->access_level.'.profile')->with($notification);
        }else{
         $parties = PoliticalParty::all();
         return view('backend.'.$profileData->access_level.'.election.add', compact('profileData', 'parties'));
        }  
    }

     // Election Store
        public function storeElection(Request $request){

            $profileData = $this->getProfileData();
            abort_unless($this->canCreateElections($profileData), 403);
            $request->validate([
                'name' => 'string|required',
                'year' => 'required',
                'party_id' => 'required',
                'description' => 'string|nullable',
            ]);

            $election = new Election();
            $election->name = $request->name;
            $election->year = $request->year;
            $election->party_id = $request->party_id;
            $election->description = $request->description;
            $election->save();

            $notification = array(
                'message' => 'Election Added Successfully',
                'alert-type' => 'success'
            );

            return redirect()->route($profileData->access_level.'.elections')->with($notification);

        }


        // Election Edit
        public function editElection($uuid){
            $profileData = $this->getProfileData();
            abort_unless($this->canCreateElections($profileData), 403);
            $parties = PoliticalParty::all();
            $election = Election::where('uuid', $uuid)->first();
            $statuses = Election::STATUSES;
            return view('backend.'.$profileData->access_level.'.election.edit', compact('election', 'profileData','parties', 'statuses'));
        }


        // Election Update
        public function updateElection(Request $request, $uuid){
            $profileData = $this->getProfileData();
            abort_unless($this->canCreateElections($profileData), 403);
            $request->validate([
                'name' => 'string|required',
                'year' => 'required',
                'party_id' => 'required',
                'status' => 'required',
                'description' => 'string|nullable',
            ]);

            $election = Election::where('uuid', $uuid)->first();
            $election->name = $request->name;
            $election->year = $request->year;
            $election->party_id = $request->party_id;
            $election->status = $request->status;
            $election->description = $request->description;
            $election->save();

            $notification = array(
                'message' => 'Election Updated Successfully',
                'alert-type' => 'success'
            );

            return redirect()->route($profileData->access_level.'.elections')->with($notification);

        }

        // Election Delete
        public function deleteElection($uuid){
            $profileData = $this->getProfileData();
            abort_unless($this->canCreateElections($profileData), 403);
            $election = Election::where('uuid', $uuid)->first();
            //check if votes or incidents exist by deletion
            $vote = Vote::where('election_id', $election->id)->first();
            $incident = ElectionIncident::where('election_id', $election->id)->first();

            if(!$vote && !$incident){
            $election->delete();
            $notification = array(
                'message' => 'Election Deleted Successfully',
                'alert-type' => 'success'
            );
            return redirect()->route($profileData->access_level.'.elections')->with($notification);
            }else{
                $notification = array(
                    'message' => 'Election cannot be deleted because it has associated Votes or Incident Reports',
                    'alert-type' => 'info'
                );
                return redirect()->route($profileData->access_level.'.elections')->with($notification);

            }

        }


        /*  manage polls/votes */

        public function addVote(Request $request)
        {
            $profileData = $this->getProfileData();
            $permission = app(PollingUnitResultPermissionService::class);
            $targetPollingUnitId = (int) ($request->query('polling_unit_id') ?: $profileData->polling_unit_id ?: $permission->firstApprovedPollingUnitId($profileData));
            abort_unless($permission->userHasApprovedAssignmentForPollingUnit($profileData, $targetPollingUnitId), 403);

            $election = Election::whereDate('year', now())
                ->orWhere('status', 'ongoing')
                ->first();
        
            if (!$election) {
                $notification = [
                    'message' => 'No ongoing or current election found.',
                    'alert-type' => 'info',
                ];
                return redirect()->route($profileData->access_level . '.elections')->with($notification);
            }
        
            $parties = PoliticalParty::all();
        
            $pollingUnitResult = PollingUnitResult::where([
                'election_id' => $election->id,
                'polling_unit_id' => $targetPollingUnitId,
            ])
                ->where(function ($query) {
                    $query->whereNull('result_status')
                        ->orWhereIn('result_status', PollingUnitResultPermissionService::ACTIVE_RESULT_STATUSES);
                })
                ->first();

            if ($pollingUnitResult && !$permission->canEditPollingUnitResult($profileData, $pollingUnitResult)) {
                return redirect()->route($profileData->access_level.'.election.votesByPu', $election->uuid)->with([
                    'message' => 'An official result already exists for this polling unit. You may view it, but only the original submitter can edit it while it is still pending.',
                    'alert-type' => 'info',
                ]);
            }
        
            $votes = Vote::where([
                'election_id' => $election->id,
                'polling_unit_id' => $targetPollingUnitId,
            ])->pluck('quantity', 'party_id');
        
            return view('backend.' . $profileData->access_level . '.election.add-vote', compact('election', 'parties', 'profileData', 'pollingUnitResult', 'votes', 'targetPollingUnitId'));
        }
        
        // public function storeVote(Request $request)
        // {
        //         $profileData = $this->getProfileData();
            
        //         $data = $request->validate([
        //             'election_id' => 'required|exists:elections,id',
        //             'votes' => 'required|array',
        //             'votes.*' => 'nullable|integer|min:0',
        //             'result_sheet' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:2048',
        //         ]);
        
        //         $pollingUnitResult = PollingUnitResult::firstOrCreate(
        //             [
        //                 'election_id' => $data['election_id'],
        //                 'polling_unit_id' => $profileData->polling_unit_id,
        //             ]
        //         );
        
        //         if ($request->hasFile('result_sheet')) {
        //             $file = $request->file('result_sheet');
        //             $result_sheet_filename = date('YmdHi') . $file->getClientOriginalName();
        //             $file->move(public_path('uploads/system_images/election_result_sheets/'), $result_sheet_filename);
            
        //             if ($pollingUnitResult->result_sheet) {
        //                 $oldFilePath = public_path('uploads/system_images/election_result_sheets/') . $pollingUnitResult->result_sheet;
        //                 if (file_exists($oldFilePath)) {
        //                     unlink($oldFilePath);
        //                 }
        //             }
            
        //             $pollingUnitResult->result_sheet = $result_sheet_filename;
        //             $pollingUnitResult->save();
        //         }
            
        //         foreach ($data['votes'] as $partyId => $quantity) {
        //             Vote::updateOrCreate(
        //                 [
        //                     'election_id' => $data['election_id'],
        //                     'party_id' => $partyId,
        //                     'polling_unit_id' => $profileData->polling_unit_id,
        //                 ],
        //                 [
        //                     'agent_id' => $profileData->id,
        //                     'region_id' => $profileData->region_id,
        //                     'state_id' => $profileData->state_id,
        //                     'lga_id' => $profileData->lga_id,
        //                     'ward_id' => $profileData->ward_id,
        //                     'quantity' => $quantity,
        //                     'polling_unit_result_id' => $pollingUnitResult->id,
        //                 ]
        //             );
        //         }
            
        //         $redirectTo = $request->input('redirect_to'); // Fetch user decision from hidden field
        //         if ($redirectTo === 'incident') {
        //             return redirect()->route($profileData->access_level . '.incident.add')->with('message', 'Votes recorded. Proceed to report the incident.');
        //         }
            
        //         return redirect()->back()->with([
        //             'message' => $profileData->pollingUnit->name . ' votes uploaded successfully',
        //             'alert-type' => 'success',
        //         ]);
        // }
        
        

        public function storeVote(Request $request)
        {
            // Fetch user profile data
            $profileData = $this->getProfileData();
            $permission = app(PollingUnitResultPermissionService::class);

            // Validate incoming request
            $data = $request->validate([
                'election_id' => 'required|exists:elections,id',
                'polling_unit_id' => 'nullable|exists:polling_units,id',
                'votes' => 'required|array',
                'votes.*' => 'nullable|integer|min:0',
                'result_sheet' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:2048',
            ]);

            $targetPollingUnitId = (int) ($data['polling_unit_id'] ?? $profileData->polling_unit_id ?? $permission->firstApprovedPollingUnitId($profileData));
            $election = Election::findOrFail($data['election_id']);
            $this->licensedScope()->assertPayloadWithinScope(['polling_unit_id' => $targetPollingUnitId]);

            abort_unless($permission->canSubmitPollingUnitResult($profileData, $targetPollingUnitId, $election), 403);

            $reportingKeys = $this->reportingKeysForPollingUnit($targetPollingUnitId);
            $pollingUnitResult = $permission->activeOfficialResult($election, $targetPollingUnitId);

            if ($pollingUnitResult) {
                abort_unless($permission->canEditPollingUnitResult($profileData, $pollingUnitResult), 403);
            } else {
                $pollingUnitResult = PollingUnitResult::create([
                    'election_id' => $election->id,
                    'polling_unit_id' => $targetPollingUnitId,
                    'state_id' => $reportingKeys['state_id'] ?? $profileData->state_id,
                    'senatorial_district_id' => $reportingKeys['senatorial_district_id'],
                    'federal_constituency_id' => $reportingKeys['federal_constituency_id'],
                    'lga_id' => $reportingKeys['lga_id'] ?? $profileData->lga_id,
                    'ward_id' => $reportingKeys['ward_id'] ?? $profileData->ward_id,
                    'submitted_at' => now(),
                    'submitted_by' => $profileData->id,
                    'review_status' => 'pending',
                    'verification_status' => 'submitted',
                    'dispute_status' => 'normal',
                    'result_status' => 'submitted',
                ]);
            }

            $pollingUnitResult->fill([
                'state_id' => $reportingKeys['state_id'] ?? $profileData->state_id,
                'senatorial_district_id' => $reportingKeys['senatorial_district_id'],
                'federal_constituency_id' => $reportingKeys['federal_constituency_id'],
                'lga_id' => $reportingKeys['lga_id'] ?? $profileData->lga_id,
                'ward_id' => $reportingKeys['ward_id'] ?? $profileData->ward_id,
            ])->save();

            if (empty($pollingUnitResult->submitted_at) || empty($pollingUnitResult->submitted_by) || empty($pollingUnitResult->review_status)) {
                $pollingUnitResult->forceFill([
                    'submitted_at' => $pollingUnitResult->submitted_at ?? now(),
                    'submitted_by' => $pollingUnitResult->submitted_by ?? $profileData->id,
                    'review_status' => $pollingUnitResult->review_status ?: 'pending',
                    'verification_status' => $pollingUnitResult->verification_status ?: 'submitted',
                    'dispute_status' => $pollingUnitResult->dispute_status ?: 'normal',
                    'result_status' => $pollingUnitResult->result_status ?: 'submitted',
                ])->save();
            }

            // Handle result sheet file upload using FileStorageService
            if ($request->hasFile('result_sheet')) {
                $fileStorageService = new FileStorageService();

                // Define storage path and old file name
                $storagePath = 'system_images/election_result_sheets';
                $oldFile = $pollingUnitResult->result_sheet;

                // Store new file and get its filename
                $newFilename = $fileStorageService->storeFile($request->file('result_sheet'), $storagePath, $oldFile);

                if ($newFilename) {
                    $pollingUnitResult->result_sheet = $newFilename;
                    $pollingUnitResult->save();
                }
            }

            // Update or create votes for each party
            foreach ($data['votes'] as $partyId => $quantity) {
                Vote::updateOrCreate(
                    [
                        'election_id' => $election->id,
                        'party_id' => $partyId,
                        'polling_unit_id' => $targetPollingUnitId,
                        'polling_unit_result_id' => $pollingUnitResult->id,
                    ],
                    [
                        'agent_id' => $profileData->id,
                        'region_id' => $profileData->region_id,
                        'state_id' => $reportingKeys['state_id'] ?? $profileData->state_id,
                        'senatorial_district_id' => $reportingKeys['senatorial_district_id'],
                        'federal_constituency_id' => $reportingKeys['federal_constituency_id'],
                        'lga_id' => $reportingKeys['lga_id'] ?? $profileData->lga_id,
                        'ward_id' => $reportingKeys['ward_id'] ?? $profileData->ward_id,
                        'quantity' => $quantity,
                        'polling_unit_result_id' => $pollingUnitResult->id,
                    ]
                );
            }

            // Redirect based on user action
            $redirectTo = $request->input('redirect_to'); // Fetch user decision from hidden field
            if ($redirectTo === 'incident') {
                return redirect()
                    ->route($profileData->access_level . '.incident.add')
                    ->with('message', 'Votes recorded. Proceed to report the incident.');
            }

            return redirect()->back()->with([
                'message' => optional(PollingUnit::find($targetPollingUnitId))->name . ' votes uploaded successfully',
                'alert-type' => 'success',
            ]);
        }

        public function addIncident()
        {
            $profileData = $this->getProfileData();
                if(empty($profileData->lga_id) || empty($profileData->ward_id) || empty($profileData->polling_unit_id )){
                    $notification =[
                        'type'=> 'error',
                        'message'=> 'You need to complete you Profile before accessing election data',
                    ];
                    return redirect()->route($profileData->access_level.'.profile')->with($notification);
                }

            // Fetch the ongoing or current election
            $election = Election::whereDate('year', now())
                ->orWhere('status', 'ongoing')
                ->first();

            if (!$election) {
                $notification = [
                    'message' => 'No ongoing or current election found.',
                    'alert-type' => 'info',
                ];
                return redirect()->route($profileData->access_level . '.elections')->with($notification);
            }

            // Fetch the logged-in user's associated location data
            $regionId = $profileData->region_id;
            $stateId = $profileData->state_id;
            $lgaId = $profileData->lga_id;
            $wardId = $profileData->ward_id;
            $pollingUnitId = $profileData->polling_unit_id;

            return view('backend.'.$profileData->access_level.'.election.add-incident', compact(
                'election',
                'profileData',
                'regionId',
                'stateId',
                'lgaId',
                'wardId',
                'pollingUnitId'
            ));
        }


        
        public function electionResult($uuid, ElectionReportService $reportService)
        {
            $pageTitle = 'Election Result';
            $election = Election::where('uuid', $uuid)->firstOrFail();
            $profileData = $this->getProfileData();
            $report = $reportService->summary($profileData, $election, $this->reportFilters());
            $summary = $report['summary'];
            $summaryStats = $report['stats'];
            $packageContext = $reportService->packageContext();

            return view('backend.' . $profileData->access_level . '.election.election_results', compact('summary', 'summaryStats', 'profileData', 'election','pageTitle', 'packageContext'));
        }


    

        public function getElectionResultChartData($uuid, ElectionReportService $reportService)
        {
            // Fetch the election based on the UUID
            $election = Election::where('uuid', $uuid)->firstOrFail();
            $profileData = $this->getProfileData();
            $summary = $reportService->summary($profileData, $election, $this->reportFilters())['summary'];
            $labels = $summary->pluck('party_acronym')->map(fn ($party) => ' '.$party)->toArray();
            $data = $summary->pluck('total_votes')->toArray();

            // Define an array of colors
            $colors = [
                '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b',
                '#34495e', '#27ae60', '#2980b9', '#8e44ad', '#e67e22', '#16a085', '#bdc3c7', '#f1c40f',
                '#95a5a6', '#2c3e50', '#ec7063', '#7dcea0'
            ];

            $backgroundColors = array_slice($colors, 0, count($labels));

            // Prepare the data structure for the bar chart
            $chartData = [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Election Results',
                        'data' => $data,
                        'backgroundColor' => $backgroundColors,
                        'borderColor' => '#2c3e50',
                        'borderWidth' => 1,
                    ]
                ],
            ];

            // Return the bar chart data as JSON
            return response()->json($chartData);
        }
        public function manageVotesByPu($uuid = null, ElectionReportService $reportService = null){

                // Fetch the election by UUID
                $reportService ??= app(ElectionReportService::class);
                $election = $uuid
                    ? Election::where('uuid', $uuid)->firstOrFail()
                    : $reportService->latestElection();

                abort_unless($election, 404);

                $pageTitle = 'Report: Votes & Election Incidents';

            // Fetch the logged-in user's profile data
                $profileData = $this->getProfileData();
            
            $governance = app(PackageGovernanceService::class);
            $packageContext = $governance->context();
            $permission = app(PollingUnitResultPermissionService::class);
            $canVerifyResults = $permission->canReviewResultsWithinScope($profileData);
            $canManageDisputes = $permission->canReviewResultsWithinScope($profileData);
            $canUploadPollingUnitResults = $permission->userHasAnyApprovedAssignment($profileData);
            $filters = $this->reportFilters();
            $filterOptions = $reportService->filterOptions($profileData);
            $scopeLabel = $reportService->scopeLabel($profileData);
            $scopeSnapshot = $reportService->scopeSnapshot($profileData, $election, $filters);

            return view('backend.shared.election.pu-votes-obtained', compact(
                'profileData',
                'election',
                'packageContext',
                'canVerifyResults',
                'canManageDisputes',
                'canUploadPollingUnitResults',
                'filters',
                'filterOptions',
                'scopeLabel',
                'scopeSnapshot'
            ));

        }


        public function VotesByPuData($uuid, ElectionReportService $reportService)
        {
            $pageTitle = 'Report: Votes & Election Incidents';

            // Fetch the election by UUID
            $election = Election::where('uuid', $uuid)->firstOrFail();

            // Fetch the logged-in user's profile data
            $profileData = $this->getProfileData();

            $query = $reportService->pollingUnitResultsQuery($profileData, $election, $this->reportFilters());

            

            // Return server-side response for DataTables
            return datatables()->eloquent($query)
                ->filterColumn('polling_unit_name', function ($query, $keyword) {
                    $query->where('polling_units.name', 'like', "%{$keyword}%");
                })
                ->filterColumn('incident_report', function ($query, $keyword) {
                    $query->where('incident_reports.incident_report', 'like', "%{$keyword}%");
                })
                ->addIndexColumn()
                ->addColumn('result_sheet', function ($row) {
                    if (empty($row->result_uuid)) {
                        return '<span class="text-muted">No Result</span>';
                    }

                    $sheetRoute = route('election.result.sheet', $row->result_uuid);
                    $label = $row->result_sheet ? 'EC8A' : 'Details';

                    return '<a class="font-weight-bold" href="'.$sheetRoute.'" title="Open result details">'.$label.'</a>';
                })
                
                
                
                ->addColumn('incident_report', function ($row) use ($election) {
                    if ($row->incident_report) {
                        $profileData = $this->getProfileData();
                
                        // Use the uuid from the row and the polling_unit_id to generate the route
                        $incidentRoute = route($profileData->access_level . '.election.incident', [
                            'uuid' => $election->uuid,
                            'polling_unit_id' => $row->polling_unit_id, // The ID of the polling unit
                        ]);
                
                        return "<a class='btn btn-dark btn-xs text-white' href='{$incidentRoute}' target='_blank' title='Open incident report'>1</a>";
                    }
                    return '<span class="text-muted">0</span>';
                })
                ->addColumn('status', function ($row) {
                    $status = $row->verification_status ?: 'submitted';
                    $badgeClass = match ($status) {
                        'verified' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        default => 'secondary',
                    };

                    return '<span class="badge badge-'.$badgeClass.'">'.e(ucfirst($status)).'</span>';
                })
                ->addColumn('governance_actions', function ($row) use ($profileData) {
                    if (empty($row->result_uuid)) {
                        return '<span class="text-muted">No result record</span>';
                    }

                    $permission = app(PollingUnitResultPermissionService::class);
                    $result = PollingUnitResult::where('uuid', $row->result_uuid)->first();
                    $buttons = [];

                    if ($permission->canVerifyPollingUnitResult($profileData, $result) && $row->verification_status !== 'verified') {
                        $buttons[] = '<form method="POST" action="'.route('election.result.verify', $row->result_uuid).'" class="d-inline">'
                            .csrf_field()
                            .'<input type="hidden" name="verification_notes" value="Verified from result report table">'
                            .'<button type="submit" class="btn btn-success btn-xs"><i class="fas fa-check"></i> Verify</button>'
                            .'</form>';
                    }

                    if ($permission->canDisputePollingUnitResult($profileData, $result)) {
                        if ($row->dispute_status === 'disputed') {
                            $buttons[] = '<form method="POST" action="'.route('election.result.dispute.clear', $row->result_uuid).'" class="d-inline">'
                                .csrf_field()
                                .'<button type="submit" class="btn btn-secondary btn-xs"><i class="fas fa-undo"></i> Clear</button>'
                                .'</form>';
                        } else {
                            $buttons[] = '<form method="POST" action="'.route('election.result.dispute', $row->result_uuid).'" class="d-inline">'
                                .csrf_field()
                                .'<input type="hidden" name="dispute_reason" value="Flagged from result report table">'
                                .'<button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-flag"></i> Dispute</button>'
                                .'</form>';
                        }
                    }

                    if (!empty($row->verification_notes) || !empty($row->dispute_reason)) {
                        $notes = e(trim(($row->verification_notes ? 'Verification: '.$row->verification_notes.' ' : '').($row->dispute_reason ? 'Dispute: '.$row->dispute_reason : '')));
                        $buttons[] = '<button type="button" class="btn btn-info btn-xs" title="'.$notes.'"><i class="fas fa-sticky-note"></i> Notes</button>';
                    }

                    return $buttons ? implode(' ', $buttons) : '<span class="text-muted">No actions</span>';
                })
                
                
                
                
                ->rawColumns(['result_sheet', 'incident_report', 'status', 'governance_actions'])
                ->make(true);
        }

        public function votesScopeSnapshot(string $uuid, ElectionReportService $reportService)
        {
            $election = Election::where('uuid', $uuid)->firstOrFail();
            $profileData = $this->getProfileData();

            return response()->json($reportService->scopeSnapshot($profileData, $election, $this->reportFilters()));
        }

        public function printPollingUnitReport(string $uuid, ElectionReportService $reportService)
        {
            $election = Election::where('uuid', $uuid)->firstOrFail();
            $profileData = $this->getProfileData();
            $filters = $this->reportFilters();
            $summaryReport = $reportService->summary($profileData, $election, $filters);

            return view('backend.shared.election.print-report', [
                'profileData' => $profileData,
                'election' => $election,
                'filters' => $filters,
                'packageContext' => $reportService->packageContext(),
                'scopeLabel' => $reportService->scopeLabel($profileData),
                'summary' => $summaryReport['summary'],
                'summaryStats' => $summaryReport['stats'],
                'rows' => $reportService->pollingUnitRows($profileData, $election, $filters),
            ]);
        }

        public function exportPollingUnitResultsCsv(string $uuid, ElectionReportService $reportService)
        {
            $election = Election::where('uuid', $uuid)->firstOrFail();
            $profileData = $this->getProfileData();
            $filters = $this->reportFilters();
            $filename = 'polling-unit-results-'.$election->uuid.'.csv';

            return response()->streamDownload(function () use ($reportService, $profileData, $election, $filters) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $reportService->exportHeadings());

                foreach ($reportService->exportRows($profileData, $election, $filters) as $row) {
                    fputcsv($handle, $row);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        public function exportElectionSummaryCsv(string $uuid, ElectionReportService $reportService)
        {
            $election = Election::where('uuid', $uuid)->firstOrFail();
            $profileData = $this->getProfileData();
            $filters = $this->reportFilters();
            $filename = 'election-summary-'.$election->uuid.'.csv';

            return response()->streamDownload(function () use ($reportService, $profileData, $election, $filters) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Rank', 'Party', 'Votes', 'Comment']);

                foreach ($reportService->summaryExportRows($profileData, $election, $filters) as $row) {
                    fputcsv($handle, $row);
                }

                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        public function exportPollingUnitResultsExcel(string $uuid, ElectionReportService $reportService)
        {
            $election = Election::where('uuid', $uuid)->firstOrFail();
            $profileData = $this->getProfileData();
            $filters = $this->reportFilters();

            return Excel::download(
                new ElectionPollingUnitResultsExport(
                    $reportService->exportRows($profileData, $election, $filters),
                    $reportService->exportHeadings()
                ),
                'polling-unit-results-'.$election->uuid.'.xlsx'
            );
        }

        public function exportElectionSummaryExcel(string $uuid, ElectionReportService $reportService)
        {
            $election = Election::where('uuid', $uuid)->firstOrFail();
            $profileData = $this->getProfileData();
            $filters = $this->reportFilters();

            return Excel::download(
                new ElectionSummaryExport($reportService->summaryExportRows($profileData, $election, $filters)),
                'election-summary-'.$election->uuid.'.xlsx'
            );
        }

        public function resultSheetView(string $uuid)
        {
            $profileData = $this->getProfileData();
            $result = $this->scopedPollingUnitResultQuery($profileData)
                ->with(['pollingUnit.ward.localGovernmentArea.state', 'submittedBy', 'verifiedBy', 'disputedBy', 'election'])
                ->where('uuid', $uuid)
                ->firstOrFail();

            $fileUrl = null;
            if ($result->result_sheet) {
                $fileUrl = app(FileStorageService::class)->getFileUrl('system_images/election_result_sheets/' . $result->result_sheet);
            }

            return view('backend.shared.election.result-sheet-view', [
                'profileData' => $profileData,
                'result' => $result,
                'fileUrl' => $fileUrl,
                'packageContext' => app(PackageGovernanceService::class)->context(),
                'pageTitle' => 'Result Sheet',
            ]);
        }

        public function verifyResult(Request $request, string $uuid, PackageGovernanceService $governance)
        {
            $profileData = $this->getProfileData();

            $result = $this->scopedPollingUnitResultQuery($profileData)
                ->where('uuid', $uuid)
                ->firstOrFail();
            abort_unless(app(PollingUnitResultPermissionService::class)->canVerifyPollingUnitResult($profileData, $result), 403);

            $result->forceFill([
                'result_status' => 'verified',
                'verification_status' => 'verified',
                'verified_at' => now(),
                'verified_by' => $profileData->id,
                'verification_notes' => $request->input('verification_notes'),
            ])->save();

            return redirect()->back()->with([
                'message' => 'Result verified and preserved successfully.',
                'alert-type' => 'success',
            ]);
        }

        public function markResultDisputed(Request $request, string $uuid, PackageGovernanceService $governance)
        {
            $profileData = $this->getProfileData();

            $result = $this->scopedPollingUnitResultQuery($profileData)
                ->where('uuid', $uuid)
                ->firstOrFail();
            abort_unless(app(PollingUnitResultPermissionService::class)->canDisputePollingUnitResult($profileData, $result), 403);

            $result->forceFill([
                'result_status' => 'disputed',
                'dispute_status' => 'disputed',
                'disputed_at' => now(),
                'disputed_by' => $profileData->id,
                'dispute_reason' => $request->input('dispute_reason'),
            ])->save();

            return redirect()->back()->with([
                'message' => 'Result marked as disputed for campaign review.',
                'alert-type' => 'success',
            ]);
        }

        public function clearResultDispute(string $uuid, PackageGovernanceService $governance)
        {
            $profileData = $this->getProfileData();

            $result = $this->scopedPollingUnitResultQuery($profileData)
                ->where('uuid', $uuid)
                ->firstOrFail();
            abort_unless(app(PollingUnitResultPermissionService::class)->canDisputePollingUnitResult($profileData, $result), 403);

            $result->forceFill([
                'result_status' => $result->verification_status === 'verified' ? 'verified' : 'submitted',
                'dispute_status' => 'normal',
                'disputed_at' => null,
                'disputed_by' => null,
                'dispute_reason' => null,
            ])->save();

            return redirect()->back()->with([
                'message' => 'Result dispute flag removed.',
                'alert-type' => 'success',
            ]);
        }

    

    public function storeIcident(Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'election_id' => 'required|exists:elections,id',
            'region_id' => 'required|exists:regions,id',
            'state_id' => 'required|exists:states,id',
            'lga_id' => 'required|exists:local_government_areas,id',
            'ward_id' => 'required|exists:wards,id',
            'polling_unit_id' => 'required|exists:polling_units,id',
            'incident_type' => 'nullable|string|max:255',
            'severity' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'remarks' => 'nullable|string|max:255',
            'pictures.*' => 'nullable|image|max:2048',
        ]);

        $profileData = $this->getProfileData();
        $this->licensedScope()->assertPayloadWithinScope($validated);
        abort_unless($this->canUploadPollingUnitResults($profileData, (int) $validated['polling_unit_id']), 403);

        $reportingKeys = $this->reportingKeysForPollingUnit($validated['polling_unit_id']);

        // Create the incident record
        $incident = ElectionIncident::create([
            'election_id' => $validated['election_id'],
            'region_id' => $validated['region_id'],
            'state_id' => $reportingKeys['state_id'] ?? $validated['state_id'],
            'senatorial_district_id' => $reportingKeys['senatorial_district_id'],
            'federal_constituency_id' => $reportingKeys['federal_constituency_id'],
            'lga_id' => $reportingKeys['lga_id'] ?? $validated['lga_id'],
            'ward_id' => $reportingKeys['ward_id'] ?? $validated['ward_id'],
            'polling_unit_id' => $validated['polling_unit_id'],
            'incident_type' => $validated['incident_type'] ?? null,
            'severity' => $validated['severity'] ?? 'medium',
            'status' => 'open',
            'remarks' => $validated['remarks'],
            'agent_id' => auth()->id(), // Assuming the logged-in user is the agent
        ]);

        // Handle picture evidence uploads
        if ($request->has('pictures')) {
            foreach ($request->file('pictures') as $picture) {
                $path = $picture->store('evidences/pictures', 'public');
                PictureEvidence::create([
                    'election_incident_id' => $incident->id,
                    'file_path' => $path,
                    'uploaded_by' => auth()->id(),
                    'verification_status' => 'pending',
                ]);
            }
        }

        return redirect()->back()->with('success', 'Incident recorded successfully.');
    }


        public function manageIncidentByPu($uuid, $polling_unit_id)
        {
            $profileData = $this->getProfileData();
            // Fetch the election using the UUID
            $election = Election::where('uuid', $uuid)->firstOrFail();

            // Fetch the polling unit with its related ward, LGA, and state
            $pollingUnitQuery = PollingUnit::with('ward.localGovernmentArea.state')
                ->where('polling_units.id', $polling_unit_id);
            $this->applyPollingUnitScope($pollingUnitQuery, $profileData);
            $pollingUnit = $pollingUnitQuery->firstOrFail();

            $pageTitle = $election->name;

                // Fetch the incidents for the specific polling unit and election
            $incidentQuery = ElectionIncident::with(['pictureEvidences', 'videoEvidences', 'user'])
                ->where('election_id', $election->id)
                ->where('polling_unit_id', $polling_unit_id);
            app(LocationScopeService::class)->applyScope($incidentQuery, $profileData, 'election_incidents', 'election_incidents');
            $incidents = $incidentQuery->get();

            // Return a view to display the incidents
            return view('backend.'.$profileData->access_level.'.election.incident_report', [
                'election' => $election,
                'pollingUnit' => $pollingUnit,
                'incidents' => $incidents,
                'pageTitle' => $pageTitle,
                'profileData' => $profileData,
            ]);
        }

        private function reportFilters(): array
        {
            return request()->only([
                'region_id',
                'verification_status',
                'dispute_status',
                'state_id',
                'senatorial_district_id',
                'federal_constituency_id',
                'lga_id',
                'ward_id',
                'polling_unit_id',
            ]);
        }


    
    
    
    

    
    



        

}
