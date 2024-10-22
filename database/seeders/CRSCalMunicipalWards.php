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
        $calMunicipalId = DB::table('local_government_areas')->where('name', 'Calabar Municipality')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of CRSCalMunicipalWards in alphabetical order
        $crsCalMunicipalWards = [
            ['name' => 'One', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Two', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Three', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Four', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Five', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Six', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Seven', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Eight', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nine', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ten', 'uuid' => (string) Str::uuid(), 'lga_id' =>  $calMunicipalId , 'created_at' => $now, 'updated_at' => $now],

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
