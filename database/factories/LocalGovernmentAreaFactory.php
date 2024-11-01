<?php

namespace Database\Factories;

use App\Models\LocalGovernmentArea;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LocalGovernmentAreaFactory extends Factory
{
    protected $model = LocalGovernmentArea::class;

    public function definition()
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->unique()->city,
            'user_id' => null, // Nullable user_id
            'state_id' => $this->faker->numberBetween(1, 37), // Random state_id from 1 to 36
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
