<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AdamawaLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Adamawa state from the states table
        $adamawaId = DB::table('states')->where('name', 'Adamawa')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Adamawa LGAs in alphabetical order
        $adamawaLGAs = [
            ['name' => 'Demsa', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Fufure', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ganye', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gayuk', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gombi', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Grie', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Hong', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jada', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Larmurde', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Madagali', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Maiha', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mayo Belwa', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Michika', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mubi North', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mubi South', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Numan', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Shelleng', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Song', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Toungo', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yola North', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yola South', 'uuid' => (string) Str::uuid(), 'state_id' => $adamawaId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($adamawaLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
