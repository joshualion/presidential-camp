<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FCTLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Federal Capital Territory from the states table
        $fctId = DB::table('states')->where('name', 'Federal Capital Territory')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of FCT Area Councils in alphabetical order
        $fctLGAs = [
            ['name' => 'Abaji', 'uuid' => (string) Str::uuid(), 'state_id' => $fctId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bwari', 'uuid' => (string) Str::uuid(), 'state_id' => $fctId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gwagwalada', 'uuid' => (string) Str::uuid(), 'state_id' => $fctId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kuje', 'uuid' => (string) Str::uuid(), 'state_id' => $fctId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kwali', 'uuid' => (string) Str::uuid(), 'state_id' => $fctId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Abuja Municipal', 'uuid' => (string) Str::uuid(), 'state_id' => $fctId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the Area Councils array and insert records into the "lgas" table
        foreach ($fctLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
