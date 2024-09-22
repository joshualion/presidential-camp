<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use illuminate\Support\Str;

class BenueLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Benue state from the states table
        $benueId = DB::table('states')->where('name', 'Benue')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Benue LGAs in alphabetical order
        $benueLGAs = [
            ['name' => 'Agatu', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Apa', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ado', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Buruku', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gboko', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Guma', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gwer East', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gwer West', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Katsina-Ala', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Konshisha', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kwande', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Logo', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Makurdi', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obi', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ogbadibo', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ohimini', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oju', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Okpokwu', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oturkpo', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tarka', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ukum', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ushongo', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Vandeikya', 'uuid' => (string) Str::uuid(), 'state_id' => $benueId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($benueLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
