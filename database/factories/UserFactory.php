<?php
 namespace Database\Factories;
 
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'username' => $this->faker->unique()->userName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'), // or Hash::make('password')
            'firstname' => $this->faker->firstName,
            'lastname' => $this->faker->lastName,
            'phone' => $this->faker->unique()->phoneNumber,
            'vin' => strtoupper(Str::random(10)),
            'photo' => null,
            'cover_image' => null,
            'address' => $this->faker->address,
            'occupation' => $this->faker->jobTitle,
            'qualification' => $this->faker->randomElement(['BSc', 'ND', 'SSCE', 'NCE', 'HND']),
            'bank' => $this->faker->randomElement(['Access Bank', 'UBA', 'GTBank', 'Zenith']),
            'bank_account_number' => $this->faker->bankAccountNumber,
            'validVoter' => $this->faker->randomElement(['yes', 'no']),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'status' => 'active',
            'access_level' => 'user',
            'email_verified_at' => now(),
            'country_id' => 1,
            'region_id' => 1,
            'state_id' => 1,
            'lga_id' => null, // to be set dynamically
            'ward_id' => null, // to be set dynamically
            'polling_unit_id' => null, // to be set dynamically
            'religion_id' => $this->faker->numberBetween(1, 3),
            'age_grade_id' => $this->faker->numberBetween(1, 5),
            'requires_update' => false,
        ];
    }
}
