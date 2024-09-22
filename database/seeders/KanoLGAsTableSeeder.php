<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class KanoLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Kano state from the states table
        $kanoId = DB::table('states')->where('name', 'Kano')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Kano LGAs in alphabetical order
        $kanoLGAs = [
            ['name' => 'Ajingi', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Albasu', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bagwai', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bebeji', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bichi', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bunkure', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dala', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dambatta', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dawakin Kudu', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dawakin Tofa', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Doguwa', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Fagge', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gabasawa', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Garko', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Garun Mallam', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gaya', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gezawa', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gwale', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gwarzo', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kabo', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kano Municipal', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Karaye', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kibiya', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kiru', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kumbotso', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kunchi', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kura', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Madobi', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Makoda', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Minjibir', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nasarawa', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rano', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rimin Gado', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rogo', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Shanono', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sumaila', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Takai', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tarauni', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tofa', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tsanyawa', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tudun Wada', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ungogo', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Warawa', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Wudil', 'uuid' => (string) Str::uuid(), 'state_id' => $kanoId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($kanoLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
