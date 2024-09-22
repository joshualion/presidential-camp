<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            // North Central
            ['name' => 'Benue', 'region_id' => 1],
            ['name' => 'Kogi', 'region_id' => 1],
            ['name' => 'Kwara', 'region_id' => 1],
            ['name' => 'Nasarawa', 'region_id' => 1],
            ['name' => 'Niger', 'region_id' => 1],
            ['name' => 'Plateau', 'region_id' => 1],
            ['name' => 'Federal Capital Territory (FCT)', 'region_id' => 1],

            // North East
            ['name' => 'Adamawa', 'region_id' => 2],
            ['name' => 'Bauchi', 'region_id' => 2],
            ['name' => 'Borno', 'region_id' => 2],
            ['name' => 'Gombe', 'region_id' => 2],
            ['name' => 'Taraba', 'region_id' => 2],
            ['name' => 'Yobe', 'region_id' => 2],

            // North West
            ['name' => 'Jigawa', 'region_id' => 3],
            ['name' => 'Kaduna', 'region_id' => 3],
            ['name' => 'Kano', 'region_id' => 3],
            ['name' => 'Katsina', 'region_id' => 3],
            ['name' => 'Kebbi', 'region_id' => 3],
            ['name' => 'Sokoto', 'region_id' => 3],
            ['name' => 'Zamfara', 'region_id' => 3],

            // South East
            ['name' => 'Abia', 'region_id' => 4],
            ['name' => 'Anambra', 'region_id' => 4],
            ['name' => 'Ebonyi', 'region_id' => 4],
            ['name' => 'Enugu', 'region_id' => 4],
            ['name' => 'Imo', 'region_id' => 4],

            // South South
            ['name' => 'Akwa Ibom', 'region_id' => 5],
            ['name' => 'Bayelsa', 'region_id' => 5],
            ['name' => 'Cross River', 'region_id' => 5],
            ['name' => 'Delta', 'region_id' => 5],
            ['name' => 'Edo', 'region_id' => 5],
            ['name' => 'Rivers', 'region_id' => 5],

            // South West
            ['name' => 'Ekiti', 'region_id' => 6],
            ['name' => 'Lagos', 'region_id' => 6],
            ['name' => 'Ogun', 'region_id' => 6],
            ['name' => 'Ondo', 'region_id' => 6],
            ['name' => 'Osun', 'region_id' => 6],
            ['name' => 'Oyo', 'region_id' => 6],
        ];

        foreach ($states as $state) {
            DB::table('states')->insert([
                'uuid' => (string) Str::uuid(), // Generate UUID here
                'name' => $state['name'],
                'region_id' => $state['region_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
