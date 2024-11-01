<?php

namespace Database\Factories;

use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WardFactory extends Factory
{
    protected $model = Ward::class;

    public function definition()
    {
            

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->unique()->streetName,
            'user_id' => null, // Nullable user_id
            'lga_id' => $this->faker->numberBetween(1, 774), // Random lga_id from 1 to 774
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
    }
}
