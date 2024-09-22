<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EnuguLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Enugu state from the states table
        $enuguId = DB::table('states')->where('name', 'Enugu')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Enugu LGAs in alphabetical order
        $enuguLGAs = [
            ['name' => 'Aninri', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Awgu', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Enugu East', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Enugu North', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Enugu South', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ezeagu', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Igbo Etiti', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Igbo Eze North', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Igbo Eze South', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Isi Uzo', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nkanu East', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nkanu West', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nsukka', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oji River', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Udenu', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Udi', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Uzo-Uwani', 'uuid' => (string) Str::uuid(), 'state_id' => $enuguId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($enuguLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
