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


            // North Central States
            ['name' => 'Benue', 'region_id' => 1],


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
