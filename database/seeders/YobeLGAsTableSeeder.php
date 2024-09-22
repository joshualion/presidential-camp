<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class YobeLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Yobe state from the states table
        $yobeId = DB::table('states')->where('name', 'Yobe')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Yobe LGAs in alphabetical order
        $yobeLGAs = [
            ['name' => 'Bade', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bursari', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Damaturu', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Fika', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Fune', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Geidam', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gujba', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gulani', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jakusko', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Karasuwa', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Machina', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nangere', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nguru', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Potiskum', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tarmuwa', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yunusari', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yusufari', 'uuid' => (string) Str::uuid(), 'state_id' => $yobeId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($yobeLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
