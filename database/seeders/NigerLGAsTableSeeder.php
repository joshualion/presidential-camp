<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class NigerLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Niger state from the states table
        $nigerId = DB::table('states')->where('name', 'Niger')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Niger LGAs in alphabetical order
        $nigerLGAs = [
            ['name' => 'Agaie', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Agwara', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Blga_ida', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Borgu', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bosso', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Chanchaga', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Edati', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gbako', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gurara', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Katcha', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kontagora', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lapai', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lavun', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Magama', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mariga', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mashegu', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mokwa', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Moya', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Paikoro', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rafi', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rijau', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Shiroro', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Suleja', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tafa', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Wushishi', 'uuid' => (string) Str::uuid(), 'state_id' => $nigerId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($nigerLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
