<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                // Specify the batch size
            $batchSize = 100; // You can adjust this value as needed
            $totalUsers = 4000; // Total number of users to create

            for ($i = 0; $i < $totalUsers; $i += $batchSize) {
                User::factory($batchSize)->create([
                    'access_level' => 'user', // Assign the default access level
                    // Other default values can go here if needed
                ]);
            }
    }
}
