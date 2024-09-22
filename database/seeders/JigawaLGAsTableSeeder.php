<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class JigawaLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Jigawa state from the states table
        $jigawaId = DB::table('states')->where('name', 'Jigawa')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Jigawa LGAs in alphabetical order
        $jigawaLGAs = [
            ['name' => 'Auyo', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Babura', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Biriniwa', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Birnin Kudu', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Buji', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dutse', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gagarawa', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Garki', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gumel', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Guri', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gwaram', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gwiwa', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Hadejia', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jahun', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kafin Hausa', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kazaure', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kiri Kasama', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kiyawa', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Maigatari', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Malam Madori', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Miga', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ringim', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Roni', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sule Tankarkar', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Taura', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yankwashi', 'uuid' => (string) Str::uuid(), 'state_id' => $jigawaId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($jigawaLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
