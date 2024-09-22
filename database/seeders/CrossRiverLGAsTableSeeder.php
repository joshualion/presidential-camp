<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CrossRiverLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Cross River state from the states table
        $crossRiverId = DB::table('states')->where('name', 'Cross River')->value('id');
         // Current timestamp
         $now = Carbon::now();
        // Array of Cross River LGAs in alphabetical order
        $crossRiverLGAs = [
            ['name' => 'Abi', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Akpabuyo', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' =>$now],
            ['name' => 'Akamkpa', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bakassi', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bekwarra', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Biase', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Boki', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Calabar Municipal', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Calabar South', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Etung', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikom', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obanliku', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obubra', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obudu', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Odukpani', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ogoja', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yakurr', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yala', 'uuid' => (string) Str::uuid(), 'state_id' => $crossRiverId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($crossRiverLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
