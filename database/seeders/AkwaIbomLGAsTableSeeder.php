<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AkwaIbomLGAsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the ID of Akwa Ibom state from the states table
        $akwaIbomId = DB::table('states')->where('name', 'Akwa Ibom')->value('id');
        // Current timestamp
        $now = Carbon::now();
        // Array of Akwa Ibom LGAs in alphabetical order
        $akwaIbomLGAs = [
            ['name' => 'Abak', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Eastern Obolo', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Eket', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Esit Eket', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Essien Udim', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Etim Ekpo', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Etinan', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibeno', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibesikpo Asutan', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ibiono-Ibom', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ika', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikono', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikot Abasi', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ikot Ekpene', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ini', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Itu', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mbo', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mkpat-Enin', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nsit-Atai', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nsit-Ibom', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nsit-Ubium', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obot Akara', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Okobo', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Onna', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oron', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oruk Anam', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Udung-Uko', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ukanafun', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Uruan', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Urue-Offong/Oruko', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Uyo', 'uuid' => (string) Str::uuid(), 'state_id' => $akwaIbomId, 'created_at' => $now, 'updated_at' => $now],
        ];

        // Loop through the LGAs array and insert records into the "lgas" table
        foreach ($akwaIbomLGAs as $lga) {
            DB::table('local_government_areas')->insert($lga);
        }
    }
}
