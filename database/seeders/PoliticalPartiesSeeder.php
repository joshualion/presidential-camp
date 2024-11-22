<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\PoliticalParty;

class PoliticalPartiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $politicalParties = [
            ['name' => 'Accord', 'acronym' => 'A'],
            ['name' => 'Action Alliance', 'acronym' => 'AA'],
            ['name' => 'Action Democratic Party', 'acronym' => 'ADP'],
            ['name' => 'Action Peoples Party', 'acronym' => 'APP'],
            ['name' => 'African Action Congress', 'acronym' => 'AAC'],
            ['name' => 'African Democratic Congress', 'acronym' => 'ADC'],
            ['name' => 'All Progressives Congress', 'acronym' => 'APC'],
            ['name' => 'All Progressives Grand Alliance', 'acronym' => 'APGA'],
            ['name' => 'Allied Peoples Movement', 'acronym' => 'APM'],
            ['name' => 'Boot Party', 'acronym' => 'BP'],
            ['name' => 'Labour Party', 'acronym' => 'LP'],
            ['name' => 'National Rescue Movement', 'acronym' => 'NRM'],
            ['name' => 'New Nigeria Peoples Party', 'acronym' => 'NNPP'],
            ['name' => 'Peoples Democratic Party', 'acronym' => 'PDP'],
            ['name' => 'Peoples Redemption Party', 'acronym' => 'PRP'],
            ['name' => 'Social Democratic Party', 'acronym' => 'SDP'],
            ['name' => 'Young Progressive Party', 'acronym' => 'YPP'],
            ['name' => 'Youth Party', 'acronym' => 'YP'],
            ['name' => 'Zenith Labour Party', 'acronym' => 'ZLP'],
        ];

        foreach ($politicalParties as $party) {
            PoliticalParty::create([
                'uuid' => Str::uuid(),
                'name' => $party['name'],
                'acronym' => $party['acronym'],
                'slogan' => null, // Defaulting to null
                'logo' => $party['acronym'].'.png',   // Defaulting to null
                'user_id' => null, // Assuming no user is assigned initially
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
