<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GombeLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Gombe state from the states table
        $gombeId = DB::table('states')->where('name', 'Gombe')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Gombe LGAs in alphabetical order
        $gombeLGAs = [
            ['name' => 'Akko', 'uuid' => (string) Str::uuid(), 'state_id' => $gombeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Balanga', 'uuid' => (string) Str::uuid(), 'state_id' => $gombeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Billiri', 'uuid' => (string) Str::uuid(), 'state_id' => $gombeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dukku', 'uuid' => (string) Str::uuid(), 'state_id' => $gombeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Funakaye', 'uuid' => (string) Str::uuid(), 'state_id' => $gombeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gombe', 'uuid' => (string) Str::uuid(), 'state_id' => $gombeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kaltungo', 'uuid' => (string) Str::uuid(), 'state_id' => $gombeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kwami', 'uuid' => (string) Str::uuid(), 'state_id' => $gombeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nafada', 'uuid' => (string) Str::uuid(), 'state_id' => $gombeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Shongom', 'uuid' => (string) Str::uuid(), 'state_id' => $gombeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yamaltu/Deba', 'uuid' => (string) Str::uuid(), 'state_id' => $gombeId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($gombeLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
