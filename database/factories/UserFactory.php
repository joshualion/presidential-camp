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
        return [
            'uuid' => Str::uuid(),
            'username' => $this->faker->unique()->userName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'), // Default password, replace as needed
            'firstname' => $this->faker->firstName,
            'lastname' => $this->faker->lastName,
            'phone' => $this->faker->unique()->phoneNumber,
            'vin' => $this->faker->regexify('[A-Z0-9]{17}'), // Example VIN pattern
            'photo' => '',
            'address' => $this->faker->address,
            'occupation' => $this->faker->jobTitle,
            'qualification' => $this->faker->randomElement(['High School', 'Bachelor\'s', 'Master\'s', 'PhD']),
            'validVoter' => $this->faker->randomElement(['yes', 'no']),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'status' => 'active',
            'access_level' => $this->faker->randomElement(['user', 'puadmin', 'wardadmin', 'lgaadmin']),
            'email_verified_at' => now(),
            'country_id' => Country::inRandomOrder()->first()->id,
            'region_id' => Region::inRandomOrder()->first()->id,
            'state_id' => State::inRandomOrder()->first()->id,
            'lga_id' => LocalGovernmentArea::inRandomOrder()->first()->id,
            'ward_id' => Ward::inRandomOrder()->first()->id,
            'polling_unit_id' => PollingUnit::inRandomOrder()->first()->id,
            'religion_id' => Religion::inRandomOrder()->first()->id,
            'age_grade_id' => AgeGrade::inRandomOrder()->first()->id,
            'remember_token' => Str::random(10),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            // Assign the "member" role to each user
            $user->assignRole('member');
        });
    }
}
