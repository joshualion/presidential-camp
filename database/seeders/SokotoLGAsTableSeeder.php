<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SokotoLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Sokoto state from the states table
        $sokotoId = DB::table('states')->where('name', 'Sokoto')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Sokoto LGAs in alphabetical order
        $sokotoLGAs = [
            ['name' => 'Binji', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bodinga', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dange/Shuni', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gada', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Goronyo', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gudu', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gwadabawa', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Illela', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Isa', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kebbe', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kware', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rabah', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sabon Birni', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Shagari', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Silame', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sokoto North', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sokoto South', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tambuwal', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tangaza', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tureta', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Wamako', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Wurno', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yabo', 'uuid' => (string) Str::uuid(), 'state_id' => $sokotoId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($sokotoLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
