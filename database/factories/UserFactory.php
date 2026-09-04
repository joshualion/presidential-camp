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
            'password' => bcrypt('password'),
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
            'country_id' => null,
            'region_id' => null,
            'state_id' => null,
            'senatorial_district_id' => null,
            'federal_constituency_id' => null,
            'lga_id' => null,
            'ward_id' => null,
            'polling_unit_id' => null,
            'religion_id' => null,
            'age_grade_id' => null,
            'requires_update' => false,
        ];
    }
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
