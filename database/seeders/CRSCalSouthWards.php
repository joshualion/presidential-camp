<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CRSCalSouthWards extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the ID of Calabar South LGA from the LGAs table
        $calSouthId = DB::table('local_government_areas')->where('name', 'Calabar South')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of CRSCalSouthWards in alphabetical order
        $crsCalSouthWards = [
            ['name' => 'Ward One (1)', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calSouthId , 'created_at' => $now, 'updated_at' => $now],
            // Add more CRSCalSouthWards from ward Two to Twelve
            ['name' => 'Ward Two(2)', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calSouthId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Three(3)', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calSouthId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Four(4)', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calSouthId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Five(5)', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calSouthId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Six(6)', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calSouthId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Seven (7)', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calSouthId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Eight(8)', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calSouthId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Nine(9)', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calSouthId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Ten(10)', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calSouthId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Eleven(11) ', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calSouthId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Twelve(12)', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calSouthId , 'created_at' => $now, 'updated_at' => $now],

        ];
        // Create CRSCalSouthWards
        foreach ($crsCalSouthWards as $crsCalSouthWard) {
            DB::table('wards')->insert([
                'uuid' => (string) Str::uuid(),
                'name' => $crsCalSouthWard['name'],
                'user_id' => null,
                'lga_id' => $calSouthId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
