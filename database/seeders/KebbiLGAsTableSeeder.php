<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class KebbiLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Kebbi state from the states table
        $kebbiId = DB::table('states')->where('name', 'Kebbi')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Kebbi LGAs in alphabetical order
        $kebbiLGAs = [
            ['name' => 'Aleiro', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Arewa Dandi', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Argungu', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Augie', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bagudo', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Birnin Kebbi', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bunza', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dandi', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Fakai', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gwandu', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jega', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kalgo', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Koko/Besse', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Maiyama', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ngaski', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sakaba', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Shanga', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Suru', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Wasagu/Danko', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yauri', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Zuru', 'uuid' => (string) Str::uuid(), 'state_id' => $kebbiId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($kebbiLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
