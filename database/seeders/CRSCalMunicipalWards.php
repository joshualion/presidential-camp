<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CRSCalMunicipalWards extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the ID of Calabar Municipal LGA from the LGAs table
        $calMunicipalId = DB::table('local_government_areas')->where('name', 'Calabar Municipal')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of CRSCalMunicipalWards in alphabetical order
        $crsCalMunicipalWards = [
            ['name' => 'Ward One', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Two', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Three', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Four', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Five', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Six', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Seven', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Eight', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Nine', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ward Ten', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],

        ];
        // Create CRSCalMunicipalWards
        foreach ($crsCalMunicipalWards as $crsCalMunicipalWard) {
            DB::table('wards')->insert([
                'uuid' => (string) Str::uuid(),
                'name' => $crsCalMunicipalWard['name'],
                'user_id' => null,
                'lga_id' => $calMunicipalId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
