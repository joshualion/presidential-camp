<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EdoLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Edo state from the states table
        $edoId = DB::table('states')->where('name', 'Edo')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Edo LGAs in alphabetical order
        $edoLGAs = [
            ['name' => 'Akoko-Edo', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Egor', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Esan Central', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Esan North-East', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Esan South-East', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Esan West', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Etsako Central', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Etsako East', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Etsako West', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Igueben', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikpoba-Okha', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oredo', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Orhionmwon', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ovia North-East', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ovia South-West', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Owan East', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Owan West', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Uhunmwonde', 'uuid' => (string) Str::uuid(), 'state_id' => $edoId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($edoLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
