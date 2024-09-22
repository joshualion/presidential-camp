<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EbonyiLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Ebonyi state from the states table
        $ebonyiId = DB::table('states')->where('name', 'Ebonyi')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Ebonyi LGAs in alphabetical order
        $ebonyiLGAs = [
            ['name' => 'Abakaliki' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Afikpo North' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Afikpo South' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ebonyi' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ezza North' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ezza South' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikwo' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ishielu' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ivo' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Izzi' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ohaozara' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ohaukwu' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Onicha' ,'uuid' => (string) Str::uuid(),  'state_id' => $ebonyiId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($ebonyiLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
