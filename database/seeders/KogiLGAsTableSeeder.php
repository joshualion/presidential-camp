<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use illuminate\Support\Str;

class KogiLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Kogi state from the states table
        $kogiId = DB::table('states')->where('name', 'Kogi')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Kogi LGAs in alphabetical order
        $kogiLGAs = [
            ['name' => 'Adavi', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ajaokuta', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ankpa', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bassa', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dekina', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibaji', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Idah', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Igalamela Odolu', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ijumu', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kabba/Bunu', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kogi', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lokoja', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mopa-Muro', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ofu', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ogori/Magongo', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Okehi', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Okene', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Olamaboro', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Omala', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yagba East', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yagba West', 'uuid' => (string) Str::uuid(), 'state_id' => $kogiId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($kogiLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
