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
                'description' => 'Includes states like Benue, Kogi, Kwara, Nasarawa, Niger, and Plateau.',
            ],
            [
                'name' => 'North East',
                'description' => 'Comprises states like Adamawa, Bauchi, Borno, Gombe, Taraba, and Yobe.',
            ],
            [
                'name' => 'North West',
                'description' => 'Encompasses states such as Kaduna, Kano, Katsina, Kebbi, Sokoto, and Zamfara.',
            ],
            [
                'name' => 'South East',
                'description' => 'Consists of states like Abia, Anambra, Ebonyi, Enugu, and Imo.',
            ],
            [
                'name' => 'South South',
                'description' => 'Includes states such as Akwa Ibom, Bayelsa, Cross River, Delta, Edo, and Rivers.',
            ],
            [
                'name' => 'South West',
                'description' => 'Comprises states like Ekiti, Lagos, Ogun, Ondo, Osun, and Oyo.',
            ],
            [
                'name' => 'No-region',
                'description' => 'For countries that have no region.',
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
