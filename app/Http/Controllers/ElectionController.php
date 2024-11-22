<?php

namespace App\Http\Controllers;

use App\Models\ElectionIncident;
use App\Models\PoliticalParty;
use App\Models\Vote;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
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
}
