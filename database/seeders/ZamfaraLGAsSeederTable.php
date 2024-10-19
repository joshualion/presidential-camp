<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ZamfaraLGAsSeederTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Zamfara state from the states table
        $zamfaraId = DB::table('states')->where('name', 'Zamfara')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Zamfara LGAs in alphabetical order
        $zamfaraLGAs = [
            ['name' => 'Anka', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bakura', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bukkuyum', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bungudu', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gummi', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gusau', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kaura Namoda', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Maradun', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Maru', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Shinkafi', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Talata Mafara', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tsafe', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Zurmi', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Birnin Magaji/Kiyaw', 'uuid' => (string) Str::uuid(), 'state_id' => $zamfaraId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "local_government_areas" table
        foreach ($zamfaraLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
