<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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

        $pageTitle = 'Members';
        View::share('pageTitle', $pageTitle);
    }
       // Function to get the profile data
       private function getProfileData()
       {
           $id = Auth::user()->id;
           return User::findOrFail($id);
       }





}
