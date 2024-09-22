<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BauchiLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Bauchi state from the states table
        $bauchiId = DB::table('states')->where('name', 'Bauchi')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Bauchi LGAs in alphabetical order
        $bauchiLGAs = [
            ['name' => 'Alkaleri', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bauchi', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bogoro', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Damban', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Darazo', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dass', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gamawa', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ganjuwa', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Giade', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Itas/Gadau', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jama\'are', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Katagum', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kirfi', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Misau', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ningi', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Shira', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tafawa Balewa', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Toro', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Warji', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Zaki', 'uuid' => (string) Str::uuid(), 'state_id' => $bauchiId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($bauchiLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
