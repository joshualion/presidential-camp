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
            ['name' => 'BENUE', 'region_id' => 1],
            ['name' => 'KOGI', 'region_id' => 1],
            ['name' => 'KWARA', 'region_id' => 1],
            ['name' => 'NASARAWA', 'region_id' => 1],
            ['name' => 'NIGER', 'region_id' => 1],
            ['name' => 'PLATEAU', 'region_id' => 1],
            ['name' => 'FCT', 'region_id' => 1],

            // North East
            ['name' => 'ADAMAWA', 'region_id' => 2],
            ['name' => 'BAUCHI', 'region_id' => 2],
            ['name' => 'BORNO', 'region_id' => 2],
            ['name' => 'GOMBE', 'region_id' => 2],
            ['name' => 'TARABA', 'region_id' => 2],
            ['name' => 'YOBE', 'region_id' => 2],

            // North West
            ['name' => 'JIGAWA', 'region_id' => 3],
            ['name' => 'KADUNA', 'region_id' => 3],
            ['name' => 'KANO', 'region_id' => 3],
            ['name' => 'KATSINA', 'region_id' => 3],
            ['name' => 'KEBBI', 'region_id' => 3],
            ['name' => 'SOKOTO', 'region_id' => 3],
            ['name' => 'ZAMFARA', 'region_id' => 3],

            // South East
            ['name' => 'ABIA', 'region_id' => 4],
            ['name' => 'ANAMBRA', 'region_id' => 4],
            ['name' => 'EBONYI', 'region_id' => 4],
            ['name' => 'ENUGU', 'region_id' => 4],
            ['name' => 'IMO', 'region_id' => 4],

            // South South
            ['name' => 'AKWA IBOM', 'region_id' => 5],
            ['name' => 'BAYELSA', 'region_id' => 5],
            ['name' => 'CROSS RIVER', 'region_id' => 5],
            ['name' => 'DELTA', 'region_id' => 5],
            ['name' => 'EDO', 'region_id' => 5],
            ['name' => 'RIVERS', 'region_id' => 5],

            // South West
            ['name' => 'EKITI', 'region_id' => 6],
            ['name' => 'LAGOS', 'region_id' => 6],
            ['name' => 'OGUN', 'region_id' => 6],
            ['name' => 'ONDO', 'region_id' => 6],
            ['name' => 'OSUN', 'region_id' => 6],
            ['name' => 'OYO', 'region_id' => 6],
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
