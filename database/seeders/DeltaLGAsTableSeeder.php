<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DeltaLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Delta state from the states table
        $deltaId = DB::table('states')->where('name', 'Delta')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Delta LGAs in alphabetical order
        $deltaLGAs = [
            ['name' => 'Aniocha North', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Aniocha South', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bomadi', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Burutu', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ethiope East', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ethiope West', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ika North East', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ika South', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Isoko North', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Isoko South', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ndokwa East', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ndokwa West', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Okpe', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oshimili North', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oshimili South', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Patani', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sapele', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Udu', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ughelli North', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ughelli South', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ukwuani', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Uvwie', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Warri North', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Warri South', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Warri South West', 'uuid' => (string) Str::uuid(), 'state_id' => $deltaId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($deltaLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
