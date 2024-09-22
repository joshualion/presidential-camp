<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OyoLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Oyo state from the states table
        $oyoId = DB::table('states')->where('name', 'Oyo')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Oyo LGAs in alphabetical order
        $oyoLGAs = [
            ['name' => 'Afijio', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Akinyele', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Atiba', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Atisbo', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Egbeda', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibadan North', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibadan North East', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibadan North West', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibadan South East', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibadan South West', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibarapa Central', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibarapa East', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibarapa North', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ido', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Irepo', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Iseyin', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Itesiwaju', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Iwajowa', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kajola', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lagelu', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ogbomosho North', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ogbomosho South', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ogo Oluwa', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Olorunsogo', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oluyole', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ona Ara', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Orelope', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ori Ire', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oyo East', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oyo West', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Saki East', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Saki West', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Surulere', 'uuid' => (string) Str::uuid(), 'state_id' => $oyoId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($oyoLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
