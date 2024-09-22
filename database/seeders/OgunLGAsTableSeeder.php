<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OgunLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Ogun state from the states table
        $ogunId = DB::table('states')->where('name', 'Ogun')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Ogun LGAs in alphabetical order
        $ogunLGAs = [
            ['name' => 'Abeokuta North', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Abeokuta South', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ado-Odo/Ota', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Egbado North', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Egbado South', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ewekoro', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ifo', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ijebu East', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ijebu North', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ijebu North East', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ijebu Ode', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikenne', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Imeko Afon', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ipokia', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obafemi Owode', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Odeda', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Odogbolu', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ogun Waterside', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Remo North', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Shagamu', 'uuid' => (string) Str::uuid(), 'state_id' => $ogunId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($ogunLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
