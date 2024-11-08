<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\SupportGroup;
use App\Models\User;

class VolunteerGroupController extends Controller
{
    public function __construct(){
        $pageTitle = 'Volunteer Groups';
        View::share('pageTitle', $pageTitle);
    }

     // Function to get the profile data
     private function getProfileData()
     {
         $id = Auth::user()->id;
         return User::find($id);
     }

    /**
     * Display a listing of the resource.
     */
    public function allVolunteerGroup()
    {
        $profileData = $this->getProfileData();
        $groups = SupportGroup::all();
        return view('backend.'.$profileData->access_level.'.settings.SupportGroup.groups', compact('groups','profileData'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function addVolunteerGroup()
    {
        $profileData = $this->getProfileData();
        return view('backend.'.$profileData->access_level.'.settings.SupportGroup.create',compact('profileData'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function storeVolunteerGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:support_groups',
            'description' => 'string',
        ]);

        $profileData = $this->getProfileData();

        //check for duplicate name
        if(SupportGroup::where('name', $request->name)->exists()){

            $notification = array(
                'message' => 'Name already exists',
                'alert-type' => 'error'
            );

            return redirect()->route($profileData->access_level.'.volunteer.group')->with($notification);
        }


        $group = new SupportGroup();
        $group->name = $request->name;
        $group->description = $request->description;
        $group->save();



        $notification = array(
            'message' => 'Volunteer Group Created Successfully',
            'alert-type' => 'success'
        );

        return redirect($profileData->access_level.'.volunteer.group')->with($notification);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function editVolunteerGroup($uuid)
    {
        $profileData = $this->getProfileData();
        $group = SupportGroup::where('uuid', $uuid)->firstOrFail();
        return view('backend.'.$profileData->access_level.'.settings.SupportGroup.edit',compact('group','profileData'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function updateVolunteerGroup(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'string',
        ]);

        $profileData = $this->getProfileData();
        $group = SupportGroup::where('uuid', $uuid)->firstOrFail();
        $group->name = $request->name;
        $group->description = $request->description;
        $group->save();

        $notification = array(
            'message' => 'Volunteer Group Updated Successfully',
            'alert-type' => 'success'
        );

        return redirect()->route($profileData->access_level.'.volunteer.group')->with($notification);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function deleteVolunteerGroup($uuid)
    {
        $profileData = $this->getProfileData();
        $group = SupportGroup::where('uuid', $uuid)->firstOrFail();
        //check if group has members
        if($group->users()->count() > 0){

            $notification = array(
                'message' => 'Group has members. Please remove them first',
                'alert-type' => 'error'
            );

            return redirect()->route($profileData->access_level.'.volunteer.group')->with($notification);
        }else{

                $group->delete();

                $notification = array(
                    'message' => 'Volunteer Group Deleted Successfully',
                    'alert-type' => 'success'
                );

                return redirect()->route($profileData->access_level.'.volunteer.group')->with($notification);
        }
    }


}
