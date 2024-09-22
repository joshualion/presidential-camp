<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BornoLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Borno state from the states table
        $bornoId = DB::table('states')->where('name', 'Borno')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Borno LGAs in alphabetical order
        $bornoLGAs = [
            ['name' => 'Abadam', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Askira/Uba', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bama', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bayo', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Biu', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Chibok', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Damboa', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dikwa', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gubio', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Guzamala', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gwoza', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Hawul', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jere', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kaga', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kala/Balge', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Konduga', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kukawa', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kwaya Kusar', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mafa', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Magumeri', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Malga_iduguri', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Marte', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mobbar', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Monguno', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ngala', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nganzai', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Shani', 'uuid' => (string) Str::uuid(), 'state_id' => $bornoId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($bornoLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
