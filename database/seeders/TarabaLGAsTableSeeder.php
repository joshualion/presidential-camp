<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TarabaLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Taraba state from the states table
        $tarabaId = DB::table('states')->where('name', 'Taraba')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Taraba LGAs in alphabetical order
        $tarabaLGAs = [
            ['name' => 'Ardo Kola', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bali', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Donga', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gashaka', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gassol', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibi', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jalingo', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Karim Lamido', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kumi', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lau', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sardauna', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Takum', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ussa', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Wukari', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yorro', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Zing', 'uuid' => (string) Str::uuid(), 'state_id' => $tarabaId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($tarabaLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
