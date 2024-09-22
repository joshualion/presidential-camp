<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ImoLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Imo state from the states table
        $imoId = DB::table('states')->where('name', 'Imo')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Imo LGAs in alphabetical order
        $imoLGAs = [
            ['name' => 'Aboh-Mbaise', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ahiazu-Mbaise', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ehime-Mbano', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ezinihitte', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ideato North', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ideato South', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ihitte/Uboma', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikeduru', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Isiala Mbano', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Isu', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mbaitoli', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ngor Okpala', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Njaba', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nkwerre', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nwangele', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obowo', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oguta', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ohaji/Egbema', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Okigwe', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Orlu', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Orsu', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oru East', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oru West', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Owerri Municipal', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Owerri North', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Owerri West', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Unuimo', 'uuid' => (string) Str::uuid(), 'state_id' => $imoId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($imoLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
