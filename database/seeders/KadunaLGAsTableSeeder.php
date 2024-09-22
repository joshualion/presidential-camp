<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class KadunaLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Kaduna state from the states table
        $kadunaId = DB::table('states')->where('name', 'Kaduna')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Kaduna LGAs in alphabetical order
        $kadunaLGAs = [
            ['name' => 'Birnin Gwari', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Chikun', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Giwa', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Igabi', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikara', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jaba', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jema\'a', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kachia', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kaduna North', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kaduna South', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kagarko', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kajuru', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kaura', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kauru', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kubau', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kudan', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lere', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Makarfi', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sabon Gari', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sanga', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Soba', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Zangon Kataf', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Zaria', 'uuid' => (string) Str::uuid(), 'state_id' => $kadunaId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($kadunaLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
