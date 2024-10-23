<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\Religion;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\Ward;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ChartsController extends Controller
{
    
    
    public function __construct()
    {

        $pageTitle = 'Admin Dashboard';
        View::share('pageTitle', $pageTitle);
    }

    // Function to get the current User profile data
    private function getProfileData()
    {
        $id = Auth::user()->id;
        return User::find($id);
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
    
    public function GenderDistributionByRegion($uuid)
    {
        $region = Region::where('uuid', $uuid)->firstOrFail();

        $maleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('region_id', $region->id)
                            ->where('gender', 'Male')->count();
        $femaleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('region_id', $region->id)
                            ->where('gender', 'Female')->count();

        $genderData = [
            'labels' => ['Male', 'Female'],
            'datasets' => [
                [
                    'data' => [$maleCount, $femaleCount],
                    'backgroundColor' => ['#28a745', '#e74c3c']
                ]
            ]
        ];

        return response()->json($genderData);
    }

    public function GenderDistributionByState($uuid)
    {
        $state = State::where('uuid', $uuid)->firstOrFail();

        $maleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('state_id', $state->id)
                            ->where('gender', 'Male')->count();
        $femaleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('state_id', $state->id)
                            ->where('gender', 'Female')->count();

        $genderData = [
            'labels' => ['Male', 'Female'],
            'datasets' => [
                [
                    'data' => [$maleCount, $femaleCount],
                    'backgroundColor' => ['#28a745', '#e74c3c']
                ]
            ]
        ];

        return response()->json($genderData);
    }



    // function member Distribution By Age
    public function AgeDistributionByRegion($uuid)
    {
        $region = Region::where('uuid', $uuid)->firstOrFail();
        $profileData  = $this->getProfileData();
        
        // Fetch all age grades
        $ageGrades = AgeGrade::all(); // Get both id and name

        // Initialize the counts array
        $ageCounts = [];

        // Loop through each age grade and count the users
        foreach ($ageGrades as $ageGrade) {
            // Count users by age_grade_id
            $count = User::where('access_level', '!=', 'superadmin')
                            ->where('region_id', $region->id)
                            ->where('age_grade_id', $ageGrade->id)->count();
            // Append the count to the ageCounts array
            $ageCounts[] = $count;
        }

        // Define an array of colors for the chart
        $colors = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9'
        ];

        // Use the array of colors for background colors
        $backgroundColors = array_slice($colors, 0, count($ageGrades));

        // Prepare the data structure for Chart.js
        $ageData = [
            'labels' => $ageGrades->pluck('name'), // Pluck age grade names
            'datasets' => [
                [
                    'label' => 'Age Distribution',
                    'data' => $ageCounts, // Data for the counts
                    'backgroundColor' => $backgroundColors
                ]
            ]
        ];

        // Return the data as JSON for Chart.js
        return response()->json($ageData);
    }

    public function AgeDistributionByState($uuid)
    {
        $state = State::where('uuid', $uuid)->firstOrFail();
        $profileData  = $this->getProfileData();
        
        // Fetch all age grades
        $ageGrades = AgeGrade::all(); // Get both id and name

        // Initialize the counts array
        $ageCounts = [];

        // Loop through each age grade and count the users
        foreach ($ageGrades as $ageGrade) {
            // Count users by age_grade_id
            $count = User::where('access_level', '!=', 'superadmin')
                            ->where('state_id', $state->id)
                            ->where('age_grade_id', $ageGrade->id)->count();
            // Append the count to the ageCounts array
            $ageCounts[] = $count;
        }

        // Define an array of colors for the chart
        $colors = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9'
        ];

        // Use the array of colors for background colors
        $backgroundColors = array_slice($colors, 0, count($ageGrades));

        // Prepare the data structure for Chart.js
        $ageData = [
            'labels' => $ageGrades->pluck('name'), // Pluck age grade names
            'datasets' => [
                [
                    'label' => 'Age Distribution',
                    'data' => $ageCounts, // Data for the counts
                    'backgroundColor' => $backgroundColors
                ]
            ]
        ];

        // Return the data as JSON for Chart.js
        return response()->json($ageData);
    }

       //function member Distribution By Eligible Voters
    public function VoterDistributionByRegion($uuid)
       {
           $region = Region::where('uuid', $uuid)->firstOrFail();
           $validVoterCounts = [
               'Yes' => User::where('access_level', '!=', 'superadmin')
                            ->where('region_id', $region->id)
                            ->where('validvoter', 'Yes')
                            ->where('access_level', '!=', 'superadmin')->count(),
               
                            'No' => User::where('access_level', '!=', 'superadmin')
                            ->where('region_id', $region->id)
                            ->where('validvoter', 'No')                            
                            ->where('access_level', '!=', 'superadmin')->count(),
           ];

           $validVoterData = [
               'labels' => ['Eligible Voters', 'Ineligible Voters'],
               'datasets' => [
                   [
                       'data' => array_values($validVoterCounts),
                       'backgroundColor' => ['#3498db', '#e74c3c']
                   ]
               ]
           ];

           return response()->json($validVoterData);
    }

    public function VoterDistributionByState($uuid)
    {
        $state = State::where('uuid', $uuid)->firstOrFail();
        $validVoterCounts = [
            'Yes' => User::where('access_level', '!=', 'superadmin')
                         ->where('region_id', $state->id)
                         ->where('validvoter', 'Yes')
                         ->where('access_level', '!=', 'superadmin')->count(),
            
                         'No' => User::where('access_level', '!=', 'superadmin')
                         ->where('region_id', $state->id)
                         ->where('validvoter', 'No')                            
                         ->where('access_level', '!=', 'superadmin')->count(),
        ];

        $validVoterData = [
            'labels' => ['Eligible Voters', 'Ineligible Voters'],
            'datasets' => [
                [
                    'data' => array_values($validVoterCounts),
                    'backgroundColor' => ['#3498db', '#e74c3c']
                ]
            ]
        ];

        return response()->json($validVoterData);
 }

      //function member Distribution By State
    public function StateDistributionByRegion($uuid)
      {
          $region = Region::where('uuid', $uuid)->firstOrFail();
          $states = State::where('region_id', $region->id)->get(); // Assuming you have a State model and table

          $stateCounts = [];
          // Define an array of colors
          $colors = [
              '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9',
              '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
              '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
              '#e74c3c', '#f39c12', '#1abc9c',
          ];

          // Use the array of colors for background colors
          $backgroundColors = array_slice($colors, 0, count($states));

          // Iterate over states to get the user count per state
          foreach ($states as $state) {
              $count = User::where('state_id', $state->id)
              ->where('region_id', $region->id)
              ->where('access_level', '!=', 'superadmin')->count();
              $stateCounts[] = $count;
          }

          // Get an array of state names
          $stateLabels = $states->pluck('name')->toArray();

          // Filter out states with zero user counts
          $filteredStateCounts = array_filter($stateCounts, function ($count) {
              return $count > 0;
          });

          // Filter corresponding state names and background colors
          $filteredLabels = array_values(array_intersect_key($stateLabels, $filteredStateCounts));
          $filteredBackgroundColors = array_slice($backgroundColors, 0, count($filteredLabels));

          // Prepare the state distribution data for the donut chart
          $stateData = [
              'labels' => $filteredLabels,
              'datasets' => [
                  [
                      'label' => 'State Distribution',
                      'data' => array_values($filteredStateCounts),
                      'backgroundColor' => $filteredBackgroundColors
                  ]
              ]
          ];

          return response()->json($stateData);
    } 

    public function lgaDistributionByState($uuid)
    {
        $state = State::where('uuid', $uuid)->firstOrFail();
        $lgas = LocalGovernmentArea::where('state_id', $state->id)->get(); // Assuming you have a LocalGovernmentArea model and table

        $lgaCounts = [];
        // Define an array of colors
        $colors = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c',
        ];

        // Use the array of colors for background colors
        $backgroundColors = array_slice($colors, 0, count($lgas));

        // Iterate over states to get the user count per state
            foreach ($lgas as $lga) {
                $count = User::where('lga_id', $lga->id)
                ->where('state_id', $state->id)
                ->where('access_level', '!=', 'superadmin')->count();
                $lgaCounts[] = $count;
            }

        // Get an array of state names
        $lgaLabels = $lgas->pluck('name')->toArray();

        // Filter out states with zero user counts
        $filteredlgaCounts = array_filter($lgaCounts, function ($count) {
            return $count > 0;
        });

        // Filter corresponding state names and background colors
        $filteredLabels = array_values(array_intersect_key($lgaLabels, $filteredlgaCounts));
        $filteredBackgroundColors = array_slice($backgroundColors, 0, count($filteredLabels));

        // Prepare the state distribution data for the donut chart
        $lgaData = [
            'labels' => $filteredLabels,
            'datasets' => [
                [
                    'label' => 'State Distribution',
                    'data' => array_values($filteredlgaCounts),
                    'backgroundColor' => $filteredBackgroundColors
                ]
            ]
        ];

        return response()->json($lgaData);
    } 

    
}
