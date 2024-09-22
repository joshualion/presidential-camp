<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class NasarawaLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Nasarawa state from the states table
        $nasarawaId = DB::table('states')->where('name', 'Nasarawa')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Nasarawa LGAs in alphabetical order
        $nasarawaLGAs = [
            ['name' => 'Akwanga', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Awe', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Doma', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Karu', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Keana', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Keffi', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kokona', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lafia', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nasarawa', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nasarawa Egon', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obi', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Toto', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Wamba', 'uuid' => (string) Str::uuid(), 'state_id' => $nasarawaId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($nasarawaLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
