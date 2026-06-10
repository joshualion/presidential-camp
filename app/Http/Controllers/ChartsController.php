<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\Election;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PoliticalParty;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\Religion;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\Vote;
use App\Models\Ward;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public function NationalGenderDistribution()
    {
        $maleCount = User::where('access_level', '!=', 'superadmin')->where('gender', 'Male')->count();
        $femaleCount = User::where('access_level', '!=', 'superadmin')->where('gender', 'Female')->count();

        return response()->json([
            'labels' => ['Male', 'Female'],
            'datasets' => [
                [
                    'data' => [$maleCount, $femaleCount],
                    'backgroundColor' => ['#28a745', '#e74c3c']
                ]
            ]
        ]);
    }

    public function NationalAgeDistribution()
    {
        $ageGrades = AgeGrade::all();
        $ageCounts = [];

        foreach ($ageGrades as $ageGrade) {
            $ageCounts[] = User::where('access_level', '!=', 'superadmin')->where('age_grade_id', $ageGrade->id)->count();
        }

        $colors = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9'
        ];

        return response()->json([
            'labels' => $ageGrades->pluck('name'),
            'datasets' => [
                [
                    'label' => 'Age Distribution',
                    'data' => $ageCounts,
                    'backgroundColor' => array_slice($colors, 0, count($ageGrades))
                ]
            ]
        ]);
    }

    public function NationalRegionDistribution()
    {
        $regions = Region::all();
        $regionData = [
            'labels' => [],
            'datasets' => [
                [
                    'data' => [],
                    'backgroundColor' => [
                        '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9'
                    ]
                ]
            ]
        ];

        foreach ($regions as $region) {
            $regionData['labels'][] = $region->name;
            $regionData['datasets'][0]['data'][] = User::where('access_level', '!=', 'superadmin')->where('region_id', $region->id)->count();
        }

        return response()->json($regionData);
    }

    public function NationalReligionDistribution()
    {
        $religions = Religion::pluck('name', 'id')->toArray();
        $religionCounts = [];
        $colors = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9'
        ];

        foreach ($religions as $religionId => $religionName) {
            $religionCounts[] = User::where('access_level', '!=', 'superadmin')->where('religion_id', $religionId)->count();
        }

        return response()->json([
            'labels' => array_values($religions),
            'datasets' => [
                [
                    'label' => 'Religion Distribution',
                    'data' => $religionCounts,
                    'backgroundColor' => array_slice($colors, 0, count($religions))
                ]
            ]
        ]);
    }

    public function NationalVoterDistribution()
    {
        return response()->json([
            'labels' => ['Eligible Voters', 'Ineligible Voters'],
            'datasets' => [
                [
                    'data' => [
                        User::where('access_level', '!=', 'superadmin')->where('validvoter', 'Yes')->count(),
                        User::where('access_level', '!=', 'superadmin')->where('validvoter', 'No')->count(),
                    ],
                    'backgroundColor' => ['#3498db', '#e74c3c']
                ]
            ]
        ]);
    }

    public function NationalStateDistribution()
    {
        $states = State::all();
        $stateCounts = [];
        $colors = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c',
        ];

        foreach ($states as $state) {
            $stateCounts[] = User::where('state_id', $state->id)->where('access_level', '!=', 'superadmin')->count();
        }

        $stateLabels = $states->pluck('name')->toArray();
        $filteredStateCounts = array_filter($stateCounts, fn ($count) => $count > 0);
        $filteredLabels = array_values(array_intersect_key($stateLabels, $filteredStateCounts));

        return response()->json([
            'labels' => $filteredLabels,
            'datasets' => [
                [
                    'label' => 'State Distribution',
                    'data' => array_values($filteredStateCounts),
                    'backgroundColor' => array_slice($colors, 0, count($filteredLabels))
                ]
            ]
        ]);
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

    public function GenderDistributionByLga($uuid)
    {
        $lga = LocalGovernmentArea::where('uuid', $uuid)->firstOrFail();

        $maleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('lga_id', $lga->id)
                            ->where('gender', 'Male')->count();
        $femaleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('lga_id', $lga->id)
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

    public function GenderDistributionBySenatorialDistrict($uuid)
    {
        $district = SenatorialDistrict::where('uuid', $uuid)->firstOrFail();

        $maleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('senatorial_district_id', $district->id)
                            ->where('gender', 'Male')->count();
        $femaleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('senatorial_district_id', $district->id)
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

    public function GenderDistributionByFederalConstituency($uuid)
    {
        $constituency = FederalConstituency::where('uuid', $uuid)->firstOrFail();

        $maleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('federal_constituency_id', $constituency->id)
                            ->where('gender', 'Male')->count();
        $femaleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('federal_constituency_id', $constituency->id)
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

    public function GenderDistributionByWard($uuid)
    {
        $ward = Ward::where('uuid', $uuid)->firstOrFail();

        $maleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('ward_id', $ward->id)
                            ->where('gender', 'Male')->count();
        $femaleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('ward_id', $ward->id)
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

    public function GenderDistributionByPu($uuid)
    {
        $pu = PollingUnit::where('uuid', $uuid)->firstOrFail();

        $maleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('polling_unit_id', $pu->id)
                            ->where('gender', 'Male')->count();
        $femaleCount = User::where('access_level', '!=', 'superadmin')
                            ->where('polling_unit_id', $pu->id)
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

    public function AgeDistributionByLga($uuid)
    {
        $lga = LocalGovernmentArea::where('uuid', $uuid)->firstOrFail();


        // Fetch all age grades
        $ageGrades = AgeGrade::all(); // Get both id and name

        // Initialize the counts array
        $ageCounts = [];

        // Loop through each age grade and count the users
        foreach ($ageGrades as $ageGrade) {
            // Count users by age_grade_id
            $count = User::where('access_level', '!=', 'superadmin')
                            ->where('lga_id', $lga->id)
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

    public function AgeDistributionBySenatorialDistrict($uuid)
    {
        $district = SenatorialDistrict::where('uuid', $uuid)->firstOrFail();
        $ageGrades = AgeGrade::all();
        $ageCounts = [];

        foreach ($ageGrades as $ageGrade) {
            $count = User::where('access_level', '!=', 'superadmin')
                            ->where('senatorial_district_id', $district->id)
                            ->where('age_grade_id', $ageGrade->id)->count();
            $ageCounts[] = $count;
        }

        $colors = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9'
        ];

        $ageData = [
            'labels' => $ageGrades->pluck('name'),
            'datasets' => [
                [
                    'label' => 'Age Distribution',
                    'data' => $ageCounts,
                    'backgroundColor' => array_slice($colors, 0, count($ageGrades))
                ]
            ]
        ];

        return response()->json($ageData);
    }

    public function AgeDistributionByFederalConstituency($uuid)
    {
        $constituency = FederalConstituency::where('uuid', $uuid)->firstOrFail();
        $ageGrades = AgeGrade::all();
        $ageCounts = [];

        foreach ($ageGrades as $ageGrade) {
            $count = User::where('access_level', '!=', 'superadmin')
                            ->where('federal_constituency_id', $constituency->id)
                            ->where('age_grade_id', $ageGrade->id)->count();
            $ageCounts[] = $count;
        }

        $colors = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9'
        ];

        $ageData = [
            'labels' => $ageGrades->pluck('name'),
            'datasets' => [
                [
                    'label' => 'Age Distribution',
                    'data' => $ageCounts,
                    'backgroundColor' => array_slice($colors, 0, count($ageGrades))
                ]
            ]
        ];

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
                         ->where('state_id', $state->id)
                         ->where('validvoter', 'Yes')->count(),

                         'No' => User::where('access_level', '!=', 'superadmin')
                         ->where('state_id', $state->id)
                         ->where('validvoter', 'No')->count(),
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

    public function VoterDistributionByLga($uuid)
    {
        $lga = LocalGovernmentArea::where('uuid', $uuid)->firstOrFail();
        $validVoterCounts = [
            'Yes' => User::where('access_level', '!=', 'superadmin')
                         ->where('lga_id', $lga->id)
                         ->where('validvoter', 'Yes')
                         ->where('access_level', '!=', 'superadmin')->count(),

                         'No' => User::where('access_level', '!=', 'superadmin')
                         ->where('lga_id', $lga->id)
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

    public function VoterDistributionBySenatorialDistrict($uuid)
    {
        $district = SenatorialDistrict::where('uuid', $uuid)->firstOrFail();
        $validVoterCounts = [
            'Yes' => User::where('access_level', '!=', 'superadmin')
                         ->where('senatorial_district_id', $district->id)
                         ->where('validvoter', 'Yes')->count(),

            'No' => User::where('access_level', '!=', 'superadmin')
                         ->where('senatorial_district_id', $district->id)
                         ->where('validvoter', 'No')->count(),
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

    public function VoterDistributionByFederalConstituency($uuid)
    {
        $constituency = FederalConstituency::where('uuid', $uuid)->firstOrFail();
        $validVoterCounts = [
            'Yes' => User::where('access_level', '!=', 'superadmin')
                         ->where('federal_constituency_id', $constituency->id)
                         ->where('validvoter', 'Yes')->count(),

            'No' => User::where('access_level', '!=', 'superadmin')
                         ->where('federal_constituency_id', $constituency->id)
                         ->where('validvoter', 'No')->count(),
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

    public function VoterDistributionByWard($uuid)
    {
        $ward = Ward::where('uuid', $uuid)->firstOrFail();
        $validVoterCounts = [
            'Yes' => User::where('access_level', '!=', 'superadmin')
                         ->where('ward_id', $ward->id)
                         ->where('validvoter', 'Yes')
                         ->where('access_level', '!=', 'superadmin')->count(),

                         'No' => User::where('access_level', '!=', 'superadmin')
                         ->where('ward_id', $ward->id)
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

    public function VoterDistributionByPu($uuid)
    {
        $pu = PollingUnit::where('uuid', $uuid)->firstOrFail();
        $validVoterCounts = [
            'Yes' => User::where('access_level', '!=', 'superadmin')
                         ->where('polling_unit_id', $pu->id)
                         ->where('validvoter', 'Yes')
                         ->where('access_level', '!=', 'superadmin')->count(),

                         'No' => User::where('access_level', '!=', 'superadmin')
                         ->where('polling_unit_id', $pu->id)
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
              ->where('access_level', '!=', 'superadmin')
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
        $lgas  = LocalGovernmentArea::where('state_id', $state->id)->get(); // Assuming you have a LocalGovernmentArea model and table

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
                ->where('access_level', '!=', 'superadmin')
                ->where('state_id', $state->id)->count();
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
                    'label' => 'LGA Distribution',
                    'data' => array_values($filteredlgaCounts),
                    'backgroundColor' => $filteredBackgroundColors
                ]
            ]
        ];

        return response()->json($lgaData);
    }

    public function lgaDistributionBySenatorialDistrict($uuid)
    {
        $district = SenatorialDistrict::where('uuid', $uuid)->firstOrFail();
        $lgas  = LocalGovernmentArea::where('senatorial_district_id', $district->id)->get();

        $lgaCounts = [];
        $colors = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c',
        ];

        foreach ($lgas as $lga) {
            $count = User::where('lga_id', $lga->id)
                ->where('senatorial_district_id', $district->id)
                ->where('access_level', '!=', 'superadmin')->count();
            $lgaCounts[] = $count;
        }

        $lgaLabels = $lgas->pluck('name')->toArray();
        $filteredlgaCounts = array_filter($lgaCounts, function ($count) {
            return $count > 0;
        });

        $lgaData = [
            'labels' => array_values(array_intersect_key($lgaLabels, $filteredlgaCounts)),
            'datasets' => [
                [
                    'label' => 'LGA Distribution',
                    'data' => array_values($filteredlgaCounts),
                    'backgroundColor' => array_slice($colors, 0, count($filteredlgaCounts))
                ]
            ]
        ];

        return response()->json($lgaData);
    }

    public function lgaDistributionByFederalConstituency($uuid)
    {
        $constituency = FederalConstituency::where('uuid', $uuid)->firstOrFail();
        $lgas  = LocalGovernmentArea::where('federal_constituency_id', $constituency->id)->get();

        $lgaCounts = [];
        $colors = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c',
        ];

        foreach ($lgas as $lga) {
            $count = User::where('lga_id', $lga->id)
                ->where('federal_constituency_id', $constituency->id)
                ->where('access_level', '!=', 'superadmin')->count();
            $lgaCounts[] = $count;
        }

        $lgaLabels = $lgas->pluck('name')->toArray();
        $filteredlgaCounts = array_filter($lgaCounts, function ($count) {
            return $count > 0;
        });

        $lgaData = [
            'labels' => array_values(array_intersect_key($lgaLabels, $filteredlgaCounts)),
            'datasets' => [
                [
                    'label' => 'LGA Distribution',
                    'data' => array_values($filteredlgaCounts),
                    'backgroundColor' => array_slice($colors, 0, count($filteredlgaCounts))
                ]
            ]
        ];

        return response()->json($lgaData);
    }

    public function wardDistributionByLga($uuid)
    {
        $lga = LocalGovernmentArea::where('uuid', $uuid)->firstOrFail();
        $wards  = Ward::where('lga_id', $lga->id)->get(); // Assuming you have a Ward model and table

        $wardCounts = [];
        // Define an array of colors
        $colors = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#27ae60', '#d35400', '#c0392b', '#34495e', '#3498db', '#2ecc71', '#2980b9',
            '#e74c3c', '#f39c12', '#1abc9c',
        ];

        // Use the array of colors for background colors
        $backgroundColors = array_slice($colors, 0, count($wards));

        // Iterate over wards to get the user count per ward
            foreach ($wards as $ward) {
                $count = User::where('ward_id', $ward->id)
                ->where('lga_id', $lga->id)
                ->where('access_level', '!=', 'superadmin')->count();
                $wardCounts[] = $count;
            }

        // Get an array of state names
        $wardLabels = $wards->pluck('name')->toArray();

        // Filter out states with zero user counts
        $filteredwardCounts = array_filter($wardCounts, function ($count) {
            return $count > 0;
        });

        // Filter corresponding state names and background colors
        $filteredLabels = array_values(array_intersect_key($wardLabels, $filteredwardCounts));
        $filteredBackgroundColors = array_slice($backgroundColors, 0, count($filteredLabels));

        // Prepare the state distribution data for the donut chart
        $wardData = [
            'labels' => $filteredLabels,
            'datasets' => [
                [
                    'label' => 'Ward Distribution',
                    'data' => array_values($filteredwardCounts),
                    'backgroundColor' => $filteredBackgroundColors
                ]
            ]
        ];

        return response()->json($wardData);
    }


    //Election Results Charts

    public function getElectionResultChartData($uuid)
    {
        // Fetch the election based on the UUID
        $election = Election::where('uuid', $uuid)->firstOrFail();
        $profileData = $this->getProfileData();

        // Query the votes grouped by party
        $voteQuery = Vote::query()
            ->select('party_id', DB::raw('SUM(quantity) as total_votes'))
            ->where('election_id', $election->id)
            ->groupBy('party_id');

        // Apply regional constraints based on user's access level
        switch ($profileData->access_level) {
            case 'regionaladmin':
                $voteQuery->where('region_id', $profileData->region_id);
                break;
            case 'stateadmin':
                $voteQuery->where('state_id', $profileData->state_id);
                break;
            case 'lgaadmin':
                $voteQuery->where('lga_id', $profileData->lga_id);
                break;
            case 'wardadmin':
                $voteQuery->where('ward_id', $profileData->ward_id);
                break;
            case 'pollingunitadmin':
                $voteQuery->where('polling_unit_id', $profileData->polling_unit_id);
                break;
            default:
                // Superadmin and Nationaladmin have no additional constraints
                break;
        }

        $results = $voteQuery->get();

        // Transform the data for Chart.js
        $chartData = $results->map(function ($result) {
            $party = PoliticalParty::find($result->party_id);

            return [
                'party' => $party ? "{$party->name} ({$party->acronym})" : 'Unknown Party',
                'total_votes' => $result->total_votes,
            ];
        });

        return response()->json($chartData);
    }
}
