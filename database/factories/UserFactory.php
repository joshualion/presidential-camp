<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Country;
use App\Models\Region;
use App\Models\State;
use App\Models\LocalGovernmentArea;
use App\Models\Ward;
use App\Models\PollingUnit;
use App\Models\Religion;
use App\Models\AgeGrade;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        $country = Country::where('name', 'Nigeria')->first();

        // Get a random region
        $region = Region::where('country_id', $country->id)->inRandomOrder()->first();

        if ($region) {
            $state = State::where('region_id', $region->id)->inRandomOrder()->first();

            if ($state) {
                $lga = LocalGovernmentArea::where('state_id', $state->id)->has('wards')->inRandomOrder()->first();

                if ($lga) {
                    $ward = Ward::where('lga_id', $lga->id)->has('pollingUnits')->inRandomOrder()->first();

                    if ($ward) {
                        $pollingUnit = PollingUnit::where('ward_id', $ward->id)->inRandomOrder()->first();

                        if ($pollingUnit) {
                            return [
                                'uuid' => Str::uuid(),
                                'username' => $this->faker->unique()->userName,
                                'email' => $this->faker->unique()->safeEmail,
                                'password' => Hash::make('password'), // Default password
                                'firstname' => $this->faker->firstName,
                                'lastname' => $this->faker->lastName,
                                'phone' => $this->faker->unique()->phoneNumber,
                                'vin' => $this->faker->regexify('[A-Z0-9]{17}'),
                                'photo' => '',
                                'address' => $this->faker->address,
                                'occupation' => $this->faker->jobTitle,
                                'qualification' => $this->faker->randomElement(['High School', 'Bachelor\'s', 'Master\'s', 'PhD']),
                                'validVoter' => $this->faker->randomElement(['yes', 'no']),
                                'gender' => $this->faker->randomElement(['male', 'female']),
                                'status' => 'active',
                                'access_level' => $this->faker->randomElement(['user', 'puadmin', 'wardadmin', 'lgaadmin']),
                                'email_verified_at' => now(),
                                'country_id' => $country->id,
                                'region_id' => $region->id,
                                'state_id' => $state->id,
                                'lga_id' => $lga->id,
                                'ward_id' => $ward->id,
                                'polling_unit_id' => $pollingUnit->id,
                                'religion_id' => Religion::inRandomOrder()->first()->id ?? null,
                                'age_grade_id' => AgeGrade::inRandomOrder()->first()->id ?? null,
                                'remember_token' => Str::random(10),
                            ];
                        }
                    }
                }
            }
        }

        // Return an empty array if any part of the hierarchy is missing
        return [];
    }



    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            // Assign the "member" role to each user
            $user->assignRole('member');
        });
    }
}
