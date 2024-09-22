<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Religion;

class ReligionTableSeeder extends Seeder
{
    public function run()
    {
        $religions = [
            [
                'name' => 'Christian',
                'description' => 'Followers of Christianity, a monotheistic religion based on the life and teachings of Jesus Christ.',
            ],
            [
                'name' => 'Muslim',
                'description' => 'Followers of Islam, a monotheistic religion that believes in the prophet Muhammad and the Quran.',
            ],
            [
                'name' => 'Indigenous',
                'description' => 'Followers of traditional, often animistic, religions indigenous to specific regions.',
            ],
            [
                'name' => 'Secular',
                'description' => 'A lifestyle or ideology that does not involve religious beliefs.',
            ],
            [
                'name' => 'No Religion',
                'description' => 'Individuals who do not identify with any religion or spiritual belief.',
            ],
            [
                'name' => 'Others',
                'description' => 'Other religions or belief systems not specifically listed.',
            ],
        ];

        foreach ($religions as $religion) {
            Religion::create([
                'uuid' => Str::uuid(),
                'name' => $religion['name'],
                'description' => $religion['description'],
            ]);
        }
    }
}
