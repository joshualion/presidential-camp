<?php

namespace Database\Factories;

use App\Models\PollingUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PollingUnitFactory extends Factory
{
    protected $model = PollingUnit::class;

    public function definition()
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->unique()->streetName,
            'remarks' => $this->faker->randomElement(['New PU', 'Existing PU']),
            'user_id' => null, // Nullable user_id
            'ward_id' => $this->faker->numberBetween(1, 8809), // Random ward_id from 1 to 8809
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
