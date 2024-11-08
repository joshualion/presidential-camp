<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\Religion;
use App\Models\User;

class ReligionController extends Controller
{
    public function __construct(){
        $pageTitle = 'Religions';
        View::share('pageTitle', $pageTitle);
    }

     // Function to get the profile data
     private function getProfileData()
     {
         $id = Auth::user()->id;
         return User::find($id);
     }


     public function allReligions(){
         $religions = Religion::all();
         $profileData = $this->getProfileData();
         return view('backend.'.$profileData->access_level.'.settings.religion.religions', compact('religions', 'profileData'));
     }


     public function addReligion(){
         $profileData = $this->getProfileData();
         return view('backend.'.$profileData->access_level.'.settings.religion.add_religion', compact('profileData'));
     }


     public function storeReligion(Request $request){
         $request->validate([
             'name' => 'required',
             'description' => 'string',
         ]);

         $profileData = $this->getProfileData();

         $religion = new Religion();
         $religion->name = $request->name;
         $religion->description = $request->description;
         $religion->save();

         $notification = array(
             'message' => 'Religion added successfully',
             'alert-type' => 'success'
         );

         return redirect()->route($profileData->access_level.'.religion')->with($notification);
     }


     public function editReligion($uuid){
         $religion = Religion::where('uuid', $uuid)->first();
         $profileData = $this->getProfileData();
         return view('backend.'.$profileData->access_level.'.settings.religion.edit_religion', compact('religion', 'profileData'));
     }


     public function updateReligion(Request $request, $uuid){
         $request->validate([
             'name' => 'required',
             'description' => 'string',
         ]);

         $profileData = $this->getProfileData();

         $religion = Religion::where('uuid', $uuid)->first();
         $religion->name = $request->name;
         $religion->description = $request->description;
         $religion->save();

         $notification = array(
             'message' => 'Religion updated successfully',
             'alert-type' => 'success'
         );

         return redirect()->route($profileData->access_level.'.religion')->with($notification);
     }



     public function deleteReligion($uuid){
         $religion = Religion::where('uuid', $uuid)->first();
         $religion->delete();
         $notification = array(
             'message' => 'Religion deleted successfully',
             'alert-type' => 'success'
         );
         return redirect()->route('nationaladmin.religion')->with($notification);
     }

     
}
