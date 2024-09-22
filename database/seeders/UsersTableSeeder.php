<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            'superadmin',
            'nationaladmin',
            'stateadmin',
            'lgaadmin',
            'wardadmin',
            'puadmin',
            'user',
        ];

        foreach ($roles as $role) {
            User::create([
                'uuid' => Str::uuid(),
                'username' => $role . '_user',
                'email' => $role . '@example.com',
                'password' => Hash::make('password'), // Using Laravel's password hashing
                'firstname' => ucfirst($role),
                'lastname' => 'User',
                'phone' => null,
                'vin' => null,
                'photo' => null,
                'address' => null,
                'occupation' => null,
                'qualification' => null,
                'validVoter' => 'no',
                'gender' => null,
                'status' => 'active',
                'role' => $role,
                'email_verified_at' => now(),
                'country_id' => 1,
                'region_id' => 5,
                'state_id' => 28,
                'lga_id' => 156,
                'ward_id' => 6,
                'polling_unit_id' => rand(1, 25),
                'support_group_id' => 1,
                'religion_id' => 1,
                'age_grade_id' => 1,
                'remember_token' => Str::random(10),
            ]);
        }
    }
}
