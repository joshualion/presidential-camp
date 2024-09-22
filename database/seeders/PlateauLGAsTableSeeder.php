<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PlateauLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Plateau state from the states table
        $plateauId = DB::table('states')->where('name', 'Plateau')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Plateau LGAs in alphabetical order
        $plateauLGAs = [
            ['name' => 'Barkin Ladi', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bassa', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bokkos', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jos East', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jos North', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jos South', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kanam', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kanke', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Langtang North', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Langtang South', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mangu', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mikang', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pankshin', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Qua\'an Pan', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Riyom', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Shendam', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Wase', 'uuid' => (string) Str::uuid(), 'state_id' => $plateauId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($plateauLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
