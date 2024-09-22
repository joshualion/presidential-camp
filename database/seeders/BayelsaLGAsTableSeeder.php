<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

use Illuminate\Support\Str;

class BayelsaLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Bayelsa state from the states table
        $bayelsaId = DB::table('states')->where('name', 'Bayelsa')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Bayelsa LGAs in alphabetical order
        $bayelsaLGAs = [
            ['name' => 'Brass', 'uuid' => (string) Str::uuid(), 'state_id' => $bayelsaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ekeremor', 'uuid' => (string) Str::uuid(), 'state_id' => $bayelsaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kolokuma/Opokuma', 'uuid' => (string) Str::uuid(), 'state_id' => $bayelsaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nembe', 'uuid' => (string) Str::uuid(), 'state_id' => $bayelsaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ogbia', 'uuid' => (string) Str::uuid(), 'state_id' => $bayelsaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sagbama', 'uuid' => (string) Str::uuid(), 'state_id' => $bayelsaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Southern Ijaw', 'uuid' => (string) Str::uuid(), 'state_id' => $bayelsaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yenagoa', 'uuid' => (string) Str::uuid(), 'state_id' => $bayelsaId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($bayelsaLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
