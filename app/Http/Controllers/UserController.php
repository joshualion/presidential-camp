<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Country;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class UserController extends Controller
{


    public function __construct()
    {

        $pageTitle = 'Member Dashboard';
        View::share('pageTitle', $pageTitle);
    }
       // Function to get the profile data
       private function getProfileData()
       {
           $id = Auth::user()->id;
           return User::findOrFail($id);
       }

       
    //Function to get the system country
    private function getCountries()
    {
         // Retrieve the value of the default_country setting
         $defaultCountry = SystemSetting::find(1);

        // Retrieve the ID of the default country
        return Country::where('name', $defaultCountry->system_country)->first();
    }

    //Function to retrieves regions from set country
    public function getRegions()
    {
        $country_id = $this->getCountries()->id;
        $country = Country::where('id',$country_id)->first();

        if($country->name == 'Nigeria'){
            $region = Region::where('country_id', $country_id)->where('name', '!=', 'No-region')->get();
        }else{
        $region = Region::where('country_id', $country_id)->get();
        }


        return($region);
    }


       

    public function memberDashboard(){
         // Log out the user
         return redirect()->route('emergency.exit'); // Redirect to a logout route
    
        //$profileData = $this->getProfileData();
        
        //return view('backend.'.$profileData->access_level.'dashboard');
    }


    public function memberProfile(){

    }

    public function memberProfileStore(Request $request){

    }

    public function memberChangePassword(){

    }

    public function memberUpdatePassword(Request $request){

    }

    public function memberLogout($id){
        Auth::logout();
        return redirect()->route('login');
    }

}
