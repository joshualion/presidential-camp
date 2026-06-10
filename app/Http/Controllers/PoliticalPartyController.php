<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\PoliticalParty;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PoliticalPartyController extends Controller
{

    public function __construct(){
        $pageTitle = 'Political Parties';
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
    public function allPoliticalParties()
    {
        $profileData = $this->getProfileData();
        $parties = PoliticalParty::all();
        return view('backend.'.$profileData->access_level.'.settings.politicalparty.parties', compact('parties', 'profileData'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function addPoliticalParty()
    {
        $profileData = $this->getProfileData();
        return view('backend.'.$profileData->access_level.'.settings.politicalparty.add', compact('profileData'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storePoliticalParty(Request $request)
    {
        //Validate Data
        $request->validate([
            'name' => 'required|unique:political_parties',
            'acronym' => 'required|string|unique:political_parties',
            'slogan' => 'string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $profileData = $this->getProfileData();

        $politicalParty = new PoliticalParty();
        $politicalParty->name = $request->name;
        $politicalParty->acronym = $request->acronym;
        $politicalParty->slogan = $request->slogan;
        if($request->hasFile('logo')){
            $file = $request->file('logo');
            $logofilename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/system_images/politicalparty/'), $logofilename);
            @unlink(public_path('uploads/system_images/politicalparty/') . $politicalParty->logo);
            $politicalParty->logo = $logofilename;

        }
        $politicalParty->save();

        $notification = array(
            'message' => 'Political party created successfully',
            'alert-type' => 'success'
        );
        return redirect()->route($profileData->access_level.'.election.politicalparty')->with($notification);


    }



    /**
     * Show the form for editing the specified resource.
     */
    public function editPoliticalParty(string $uuid)
    {
        $profileData = $this->getProfileData();
        $politicalparty = PoliticalParty::where('uuid', $uuid)->firstOrFail();
        return view('backend.'.$profileData->access_level.'.settings.politicalparty.edit',compact('politicalparty','profileData'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updatePoliticalParty(Request $request, string $uuid)
    {


        // validate data

        $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('political_parties', 'name')->ignore($uuid, 'uuid'),
            ],
            'acronym' => [
                'required',
                'string',
                Rule::unique('political_parties', 'acronym')->ignore($uuid, 'uuid'),
            ],
            'slogan' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $profileData = $this->getProfileData();
        $politicalParty = PoliticalParty::where('uuid', $uuid)->firstOrFail();

    // Handle logo removal
    if ($request->has('remove_logo') && $request->remove_logo) {
        @unlink(public_path('uploads/system_images/politicalparty/') . $politicalParty->logo);
        $politicalParty->logo = null;
    } elseif ($request->hasFile('logo')) {
        $file = $request->file('logo');
        $logofilename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('uploads/system_images/politicalparty/'), $logofilename);
        @unlink(public_path('uploads/system_images/politicalparty/') . $politicalParty->logo);
        $politicalParty->logo = $logofilename;
    }

    $politicalParty->name = $request->name;
    $politicalParty->acronym = $request->acronym;
    $politicalParty->slogan = $request->slogan;
    $politicalParty->save();

    $notification = [
        'message' => 'Political party updated successfully',
        'alert-type' => 'success'
    ];
    return redirect()->route($profileData->access_level.'.election.politicalparty')->with($notification);
}

    /**
     * Remove the specified resource from storage.
     */
    public function deletePoliticalParty(string $uuid)
    {
        $profileData = $this->getProfileData();
        $politicalParty = PoliticalParty::where('uuid', $uuid)->firstOrFail();
        $politicalParty->delete();
        $notification = array(
            'message' => 'Political party deleted successfully',
            'alert-type' => 'success'
        );
        return redirect()->route($profileData->access_level.'.election.politicalparty')->with($notification);
    }
}
