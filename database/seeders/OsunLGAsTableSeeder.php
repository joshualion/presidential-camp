<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OsunLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Osun state from the states table
        $osunId = DB::table('states')->where('name', 'Osun')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Osun LGAs in alphabetical order
        $osunLGAs = [
            ['name' => 'Aiyedaade', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Aiyedire', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Atakumosa East', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Atakumosa West', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Boluwaduro', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Boripe', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ede North', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ede South', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Egbedore', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ejigbo', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ife Central', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ife East', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ife North', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ife South', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ifedayo', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ifelodun', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ila', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ilesa East', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ilesa West', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Irepodun', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Irewole', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Isokan', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Iwo', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obokun', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Odo Otin', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ola Oluwa', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Olorunda', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oriade', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Orolu', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Osogbo', 'uuid' => (string) Str::uuid(), 'state_id' => $osunId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($osunLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
