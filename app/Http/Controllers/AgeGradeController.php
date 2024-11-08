<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\AgeGrade;
use App\Models\User;

class AgeGradeController extends Controller
{
    public function __construct(){
    $pageTitle = 'Age Grades List';
        View::share('pageTitle', $pageTitle);
    }

     // Function to get the profile data
     private function getProfileData()
     {
         $id = Auth::user()->id;
         return User::find($id);
     }


     public function allAgeGrades(){
         $agegrades = AgeGrade::all();
         $profileData = $this->getProfileData();
         return view('backend.'.$profileData->access_level.'.settings.agegrade.agegrades', compact('agegrades', 'profileData'));
     }


     public function addAgeGrade(){
         $profileData = $this->getProfileData();
         return view('backend.'.$profileData->access_level.'.settings.agegrade.add_agegrade', compact('profileData'));
     }


     public function storeAgeGrade(Request $request){
         $request->validate([
             'name' => 'required',
             'description' => 'string',
         ]);

         $profileData = $this->getProfileData();

         $agegrade = new agegrade();
         $agegrade->name = $request->name;
         $agegrade->description = $request->description;
         $agegrade->save();

         $notification = array(
             'message' => 'agegrade added successfully',
             'alert-type' => 'success'
         );

         return redirect()->route($profileData->access_level.'.agegrade')->with($notification);
     }


     public function editAgeGrade($uuid){
         $agegrade = AgeGrade::where('uuid', $uuid)->first();
         $profileData = $this->getProfileData();
         return view('backend.'.$profileData->access_level.'.settings.agegrade.edit_agegrade', compact('agegrade', 'profileData'));
     }


     public function updateAgeGrade(Request $request, $uuid){
         $request->validate([
             'name' => 'required',
             'description' => 'string',
         ]);

         $profileData = $this->getProfileData();

         $agegrade = AgeGrade::where('uuid', $uuid)->first();
         $agegrade->name = $request->name;
         $agegrade->description = $request->description;
         $agegrade->save();

         $notification = array(
             'message' => 'agegrade updated successfully',
             'alert-type' => 'success'
         );

         return redirect()->route($profileData->access_level.'.agegrade')->with($notification);
     }



     public function deleteAgeGrade($uuid){
         $agegrade = AgeGrade::where('uuid', $uuid)->first();
         $agegrade->delete();
         $notification = array(
             'message' => 'agegrade deleted successfully',
             'alert-type' => 'success'
         );
         return redirect()->route('nationaladmin.agegrade')->with($notification);
     }

}
