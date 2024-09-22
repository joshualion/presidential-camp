<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OndoLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Ondo state from the states table
        $ondoId = DB::table('states')->where('name', 'Ondo')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Ondo LGAs in alphabetical order
        $ondoLGAs = [
            ['name' => 'Akoko North East', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Akoko North West', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Akoko South Akure East', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Akoko South West', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Akure North', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Akure South', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ese Odo', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Idanre', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ifedore', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ilaje', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ile Oluji/Okeigbo', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Irele', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Odigbo', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Okitipupa', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ondo East', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ondo West', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ose', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Owo', 'uuid' => (string) Str::uuid(), 'state_id' => $ondoId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($ondoLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
