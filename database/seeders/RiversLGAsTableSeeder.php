<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RiversLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Rivers state from the states table
        $riversId = DB::table('states')->where('name', 'Rivers')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Rivers LGAs in alphabetical order
        $riversLGAs = [
            ['name' => 'Abua/Odual', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ahoada East', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ahoada West', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Akuku-Toru', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Andoni', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Asari-Toru', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bonny', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Degema', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Eleme', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Emohua', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Etche', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gokana', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikwerre', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Khana', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obio/Akpor', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ogba/Egbema/Ndoni', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ogu/Bolo', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Okrika', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Omuma', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Opobo/Nkoro', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oyigbo', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Port Harcourt', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tai', 'uuid' => (string) Str::uuid(), 'state_id' => $riversId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($riversLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
