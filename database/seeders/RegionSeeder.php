<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [


            [
                'name' => 'North Central',
                'description' => '',
            ],

            [
                'name' => 'North East',
                'description' => '',
            ],

            [
                'name' => 'North West',
                'description' => '',
            ],

            [
                'name' => 'South East',
                'description' => '',
            ],

            [
                'name' => 'South South',
                'description' => '',
            ],

            [
                'name' => 'South West',
                'description' => '',
            ],


        ];

        foreach ($regions as $region) {
            DB::table('regions')->insert([
                'uuid' => (string) Str::uuid(), // Generate UUID here
                'name' => $region['name'],
                'description' => $region['description'],
                'country_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
