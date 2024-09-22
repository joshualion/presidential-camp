<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class KatsinaLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Katsina state from the states table
        $katsinaId = DB::table('states')->where('name', 'Katsina')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Katsina LGAs in alphabetical order
        $katsinaLGAs = [
            ['name' => 'Bakori', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Batagarawa', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Batsari', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Baure', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bindawa', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Charanchi', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dan Musa', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dandume', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Danja', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Daura', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dutsi', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dutsin-Ma', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Faskari', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Funtua', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ingawa', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jibia', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kafur', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kaita', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kankara', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kankia', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Katsina', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kurfi', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kusada', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mai\'Adua', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Malumfashi', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mani', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mashi', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Matazu', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Musawa', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rimi', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sabuwa', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Safana', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sandamu', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Zango', 'uuid' => (string) Str::uuid(), 'state_id' => $katsinaId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($katsinaLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
