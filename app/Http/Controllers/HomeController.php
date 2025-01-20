<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class HomeController extends Controller
{
    public function __construct(){
        $pageTitle = 'Timeline';
        View::share('pageTitle', $pageTitle);
    }

     // Function to get the profile data
     private function getProfileData()
     {
         $id = Auth::user()->id;
         return User::find($id);
     }

     public function index(){
        $profileData = $this->getProfileData();
        $userSupportGroups = $profileData->supportGroups()->pluck('name')->toArray();
        // Get followers and following counts
        $followersCount = $profileData->followers()->count();
        $followingCount = $profileData->following()->count();
         // Get followers and following data
        $followers = $profileData->followers()->get(); // Users following this profile
        $following = $profileData->following()->get(); // Users this profile is following
     
        return view('frontend.index', compact(
            'profileData',
            'userSupportGroups',
            'followersCount',
            'followingCount',
            'followers',
            'following'
        ));

    }


    public function getFollowers()
    {
        $profileData = $this->getProfileData();
        // Paginate followers and following
        $followers = $profileData->followers()->paginate(10); // 10 followers per page

        return response()->json([
            'followers' => $followers
        ]);
    }

    public function getFollowing()
    {
        $profileData = $this->getProfileData();
        // Paginate  following
        $following = $profileData ->following()->paginate(10); // 10 following per page

        return response()->json([
            'following' => $following
        ]);
    }


}
