<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\AgeGrade;

class AgeGradeTableSeeder extends Seeder
{
    public function run()
    {
        $ageGrades = [
            '18-24',
            '25-30',
            '31-35',
            '36-40',
            '41-45',
            '46-50',
            '51-55',
            '56-60',
            '61-65',
            '66-70',
            '70+',
        ];

        foreach ($ageGrades as $ageGrade) {
            AgeGrade::create([
                'uuid' => Str::uuid(),
                'name' => $ageGrade,
                'description' => 'Age group ' . $ageGrade,
            ]);
        }
    }
}
