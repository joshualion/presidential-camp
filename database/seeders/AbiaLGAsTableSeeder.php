<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AbiaLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Abia state from the states table
        $abiaId = DB::table('states')->where('name', 'Abia')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Abia LGAs in alphabetical order
        $abiaLGAs = [
            ['name' => 'Aba North', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Aba South', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Arochukwu', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bende', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikwuano', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Isiala Ngwa North', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Isiala Ngwa South', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Isuikwuato', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obi Ngwa', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ohafia', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Osisioma', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ugwunagbo', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ukwa East', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ukwa West', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Umuahia North', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Umuahia South', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Umu Nneochi', 'uuid' => (string) Str::uuid(), 'state_id' => $abiaId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($abiaLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
