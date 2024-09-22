<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EkitiLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Ekiti state from the states table
        $ekitiId = DB::table('states')->where('name', 'Ekiti')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Ekiti LGAs in alphabetical order
        $ekitiLGAs = [
            ['name' => 'Ado-Ekiti', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Efon', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ekiti East', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ekiti South-West', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ekiti West', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Emure', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gbonyin', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ido-Osi', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ijero', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikere', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikole', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ilejemeje', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Irepodun/Ifelodun', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ise/Orun', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Moba', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oye', 'uuid' => (string) Str::uuid(), 'state_id' => $ekitiId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($ekitiLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
