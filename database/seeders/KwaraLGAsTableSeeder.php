<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class KwaraLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Kwara state from the states table
        $kwaraId = DB::table('states')->where('name', 'Kwara')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Kwara LGAs in alphabetical order
        $kwaraLGAs = [
            ['name' => 'Asa', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Baruten', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Edu', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ekiti', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ifelodun', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ilorin East', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ilorin South', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ilorin West', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Irepodun', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Isin', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kaiama', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Moro', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Offa', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oke Ero', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oyun', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pategi', 'uuid' => (string) Str::uuid(), 'state_id' => $kwaraId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($kwaraLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
