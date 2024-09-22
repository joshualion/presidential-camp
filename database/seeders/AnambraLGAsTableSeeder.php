<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AnambraLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Anambra state from the states table
        $anambraId = DB::table('states')->where('name', 'Anambra')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Anambra LGAs in alphabetical order
        $anambraLGAs = [
            ['name' => 'Aguata', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Anambra East', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Anambra West', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Anaocha', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Awka North', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Awka South', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ayamelum', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dunukofia', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ekwusigo', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Idemili North', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Idemili South', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ihiala', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Njikoka', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nnewi North', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nnewi South', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ogbaru', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Onitsha North', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Onitsha South', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Orumba North', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Orumba South', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oyi', 'uuid' => (string) Str::uuid(), 'state_id' => $anambraId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($anambraLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
