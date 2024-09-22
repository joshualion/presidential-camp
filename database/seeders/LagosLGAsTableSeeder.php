<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
class LagosLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Lagos state from the states table
        $lagosId = DB::table('states')->where('name', 'Lagos')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Lagos LGAs in alphabetical order
        $lagosLGAs = [
            ['name' => 'Agege', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ajeromi-Ifelodun', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Alimosho', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Amuwo-Odofin', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Apapa', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Badagry', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Epe', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Eti-Osa', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibeju-Lekki', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ifako-Ijaiye', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikeja', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikorodu', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kosofe', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lagos Island', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lagos Mainland', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mushin', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ojo', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oshodi-Isolo', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Somolu', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Surulere', 'uuid' => (string) Str::uuid(), 'state_id' => $lagosId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($lagosLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
