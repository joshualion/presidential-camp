<?php

namespace App\Http\Controllers;

use App\Models\ElectionIncident;
use App\Models\PoliticalParty;
use App\Models\Vote;
use App\Models\PollingUnitResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use App\Models\Election;
use App\Models\User;
use Illuminate\Validation\Rule;
class ElectionController extends Controller
{

    public function __construct(){
        $pageTitle = 'Elections';
        View::share('pageTitle', $pageTitle);
    }

     // Function to get the profile data
     private function getProfileData()
     {
         $id = Auth::user()->id;
         return User::find($id);
     }


     // Election List
     public function allElections(){
         $profileData = $this->getProfileData();
         $elections = Election::with(['party', 'votes'])->get();

         return view('backend.'.$profileData->access_level.'.election.elections', compact('elections',  'profileData'));

     }

     // Election Add
     public function addElection(){
         $profileData = $this->getProfileData();
         $parties = PoliticalParty::all();
         return view('backend.'.$profileData->access_level.'.election.add', compact('profileData', 'parties'));
     }

     // Election Store
        public function storeElection(Request $request){

            $profileData = $this->getProfileData();
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
            $parties = PoliticalParty::all();
            $election = Election::where('uuid', $uuid)->first();
            $statuses = Election::STATUSES;
            return view('backend.'.$profileData->access_level.'.election.edit', compact('election', 'profileData','parties', 'statuses'));
        }


        // Election Update
        public function updateElection(Request $request, $uuid){
            $profileData = $this->getProfileData();
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

        public function addVote()
        {
            $profileData = $this->getProfileData();
        
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
                'polling_unit_id' => $profileData->polling_unit_id,
            ])->first();
        
            $votes = Vote::where([
                'election_id' => $election->id,
                'polling_unit_id' => $profileData->polling_unit_id,
            ])->pluck('quantity', 'party_id');
        
            return view('backend.' . $profileData->access_level . '.election.add-vote', compact('election', 'parties', 'profileData', 'pollingUnitResult', 'votes'));
        }
        

        public function storeVote(Request $request)
        {
            $profileData = $this->getProfileData();
        
            $data = $request->validate([
                'election_id' => 'required|exists:elections,id',
                'votes' => 'required|array',
                'votes.*' => 'nullable|integer|min:0',
                'result_sheet' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:2048',
            ]);
        
            $pollingUnitResult = PollingUnitResult::firstOrCreate(
                [
                    'election_id' => $data['election_id'],
                    'polling_unit_id' => $profileData->polling_unit_id,
                     'result_sheet'=> $data['result_sheet'],
                ]
            );
        
            if ($request->hasFile('result_sheet')) {
                $file = $request->file('result_sheet');
                $result_sheet_filename = date('YmdHi') . $file->getClientOriginalName();
                $file->move(public_path('uploads/system_images/election_result_sheets/'), $result_sheet_filename);
        
                if ($pollingUnitResult->result_sheet) {
                    $oldFilePath = public_path('uploads/system_images/election_result_sheets/') . $pollingUnitResult->result_sheet;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
        
                $pollingUnitResult->result_sheet = $result_sheet_filename;
                $pollingUnitResult->save();
            }
        
            foreach ($data['votes'] as $partyId => $quantity) {
                Vote::updateOrCreate(
                    [
                        'election_id' => $data['election_id'],
                        'party_id' => $partyId,
                        'polling_unit_id' => $profileData->polling_unit_id,
                    ],
                    [
                        'agent_id' => $profileData->id,
                        'region_id' => $profileData->region_id,
                        'state_id' => $profileData->state_id,
                        'lga_id' => $profileData->lga_id,
                        'ward_id' => $profileData->ward_id,
                        'quantity' => $quantity,
                        'polling_unit_result_id' => $pollingUnitResult->id,
                    ]
                );
            }
        
            $notification = [
                'message' => $profileData->pollingUnit->name . ' votes uploaded successfully',
                'alert-type' => 'success',
            ];
        
            return redirect()->back()->with($notification);
        }
        
    public function electionResult($uuid)
    {
        $pageTitle = 'Election Result';
        // Fetch the election by UUID
        $election = Election::where('uuid', $uuid)->firstOrFail();
        $profileData = $this->getProfileData();

        // Base query for votes, scoped by the user's access level
        $query = Vote::select('party_id', DB::raw('SUM(quantity) as total_votes'))
            ->where('election_id', $election->id)
            ->groupBy('party_id');

        // Apply access-level-specific filters
        if ($profileData->access_level === 'regionaladmin') {
            $query->where('region_id', $profileData->region_id);
        } elseif ($profileData->access_level === 'stateadmin') {
            $query->where('state_id', $profileData->state_id);
        } elseif ($profileData->access_level === 'lgaadmin') {
            $query->where('lga_id', $profileData->lga_id);
        } elseif ($profileData->access_level === 'wardadmin') {
            $query->where('ward_id', $profileData->ward_id);
        } elseif ($profileData->access_level === 'pollingunitadmin') {
            $query->where('polling_unit_id', $profileData->polling_unit_id);
        }

        // Retrieve results and rank them
        $results = $query->with('politicalParty')
            ->orderByDesc('total_votes')
            ->get();

        // Prepare rank and comments
        $summary = $results
        ->sortByDesc('total_votes') // Sort by total_votes in descending order
        ->values() // Reset the keys to ensure correct indexing after sorting
        ->map(function ($result, $index) {
            $party = PoliticalParty::find($result->party_id);
            $rank = $index + 1; // Rank based on the position in the sorted array
    
            // Update the comment to include "4th Place"
            $comment = match ($rank) {
                1 => '1st Place',
                2 => '2nd Place',
                3 => '3rd Place',
                4 => '4th Place',
                default => "Below 4th",
            };
    
            return [
                'sn' => $rank, // Add serial number
                'party_name' => $party ? $party->name . ' (' . $party->acronym . ')' : 'No Party Assigned',
                'total_votes' => $result->total_votes,
                'rank' => $rank,
                'comment' => $comment,
            ];
        });
    
       


        return view('backend.' . $profileData->access_level . '.election.election_results', compact('summary', 'profileData', 'election','pageTitle'));
    }


    
    public function getElectionResultChartData($uuid)
    {
        // Fetch the election based on the UUID
        $election = Election::where('uuid', $uuid)->firstOrFail();
        $profileData = $this->getProfileData();

        // Query the votes grouped by party
        $voteQuery = Vote::query()
            ->select('party_id', DB::raw('SUM(quantity) as total_votes'))
            ->where('election_id', $election->id)
            ->where('quantity', '>', 0)
            ->groupBy('party_id');

        // Apply regional constraints based on user's access level
        switch ($profileData->access_level) {
            case 'regionaladmin':
                $voteQuery->where('region_id', $profileData->region_id);
                break;
            case 'stateadmin':
                $voteQuery->where('state_id', $profileData->state_id);
                break;
            case 'lgaadmin':
                $voteQuery->where('lga_id', $profileData->lga_id);
                break;
            case 'wardadmin':
                $voteQuery->where('ward_id', $profileData->ward_id);
                break;
            case 'pollingunitadmin':
                $voteQuery->where('polling_unit_id', $profileData->polling_unit_id);
                break;
            default:
                // Superadmin and Nationaladmin have no additional constraints
                break;
        }

        $results = $voteQuery->get();

        // Transform the data for Chart.js
        $chartDataResults = $results->map(function ($result) {
            $party = PoliticalParty::find($result->party_id);
            return [
                'party_name' => $party ? "{$party->acronym}" : 'Unknown Party',
                'total_votes' => $result->total_votes,
            ];
        });

        // Extract labels and data
        $labels = $chartDataResults->pluck('party_name')->toArray();
        $data = $chartDataResults->pluck('total_votes')->toArray();

        // Define an array of colors
        $colors = [
            '#3498db', // Blue
            '#e74c3c', // Red
            '#2ecc71', // Green
            '#f39c12', // Orange
            '#9b59b6', // Purple
            '#1abc9c', // Teal
            '#d35400', // Dark Orange
            '#c0392b', // Crimson
            '#34495e', // Dark Blue-Grey
            '#27ae60', // Emerald Green
            '#2980b9', // Strong Blue
            '#8e44ad', // Dark Purple
            '#e67e22', // Bright Orange
            '#16a085', // Aquamarine
            '#bdc3c7', // Light Grey
            '#f1c40f', // Yellow
            '#95a5a6', // Grey-Blue
            '#2c3e50', // Navy Blue
            '#ec7063', // Soft Red
            '#7dcea0'  // Light Green
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

    



        

}
