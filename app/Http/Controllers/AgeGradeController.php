<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
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
         return view('backend.'.$profileData->access_level.'.settings.ageGrade.agegrades', compact('agegrades', 'profileData'));
     }


     public function addAgeGrade(){
         $profileData = $this->getProfileData();
         return view('backend.'.$profileData->access_level.'.settings.ageGrade.add_agegrade', compact('profileData'));
     }


     public function storeAgeGrade(Request $request){
         $request->validate([
             'name' => 'required',
             'description' => 'nullable|string',
         ]);

         $profileData = $this->getProfileData();

         $agegrade = new AgeGrade();
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
         $agegrade = AgeGrade::where('uuid', $uuid)->firstOrFail();
         $profileData = $this->getProfileData();
         return view('backend.'.$profileData->access_level.'.settings.ageGrade.edit_agegrade', compact('agegrade', 'profileData'));
     }


     public function updateAgeGrade(Request $request, $uuid){
         $request->validate([
             'name' => 'required',
             'description' => 'nullable|string',
         ]);

         $profileData = $this->getProfileData();

         $agegrade = AgeGrade::where('uuid', $uuid)->firstOrFail();
         $agegrade->name = $request->name;
         $agegrade->description = $request->description;
         $agegrade->save();

         $notification = array(
             'message' => 'agegrade updated successfully',
             'alert-type' => 'success'
         );

         return redirect()->route($profileData->access_level.'.agegrade')->with($notification);
     }



     public function deleteAgeGrade($uuid)
     {
         $profileData = $this->getProfileData();

         return DB::transaction(function () use ($uuid, $profileData) {
             $agegrade = AgeGrade::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
             // The legacy foreign key cascades deletion to users. Match the UI's
             // protection server-side before deleting any referenced age grade.
             if ($agegrade->users()->exists()) {
                 return redirect()->route($profileData->access_level.'.agegrade')->with([
                     'message' => 'Age grade has associated users. Please reassign them first',
                     'alert-type' => 'error',
                 ]);
             }

             $agegrade->delete();

             return redirect()->route($profileData->access_level.'.agegrade')->with([
                 'message' => 'Age grade deleted successfully',
                 'alert-type' => 'success',
             ]);
         });
     }

}
